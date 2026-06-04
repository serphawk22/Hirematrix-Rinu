<?php

namespace App\Controllers;

use App\Models\MncJobModel;
use App\Libraries\MncJobIngestor;

class MncJobController extends BaseController
{
    /**
     * Renders the UI for AI-powered MNC Job Discovery.
     * GET /mnc
     */
    // public function index()
    // {
    //     return view('candidate/mnc_job_discovery_view');
    // }
    /**
     * Fetches live jobs for a candidate's targeted MNC using AI Discovery.
     * GET /mnc/discover?company=Google
     */
    public function discover()
    {
        $companyName = trim((string)$this->request->getGet('company'));
        if ($companyName === '') {
            return $this->response->setJSON(['error' => 'Company name required']);
        }

        // Reconnect to DB before starting the process
        try {
            \Config\Database::connect()->reconnect();
        } catch (\Throwable $e) {
            log_message('error', 'MncJobController: DB reconnect failed at start: ' . $e->getMessage());
        }

        $limit = (int) ($this->request->getGet('limit') ?? 10);
        $limit = max(1, min(100, $limit));

        try {
            return $this->runDiscover($companyName, $limit);
        } catch (\Throwable $e) {
            log_message('error', 'MncJobController::discover exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return $this->response->setJSON([
                'success'      => false,
                'company'      => $companyName,
                'jobs'         => [],
                'company_info' => null,
                'error'        => 'Discovery failed: ' . $e->getMessage(),
            ]);
        }
    }

    private function runDiscover(string $companyName, int $limit): \CodeIgniter\HTTP\ResponseInterface
    {
        $model        = new MncJobModel();
        $ingestor     = new MncJobIngestor();
        $companyModel = model('CompanyModel');

        // 1. Get or Discover/Enrich Company Info
        $companyInfo = $companyModel->like('name', $companyName, 'both')->first();
        
        // Enrichment logic: If company exists but lacks description or industry, trigger AI discovery
        $needsEnrichment = !$companyInfo || empty($companyInfo['short_description']) || empty($companyInfo['industry']);

        if ($needsEnrichment) {
            // AI Company Profile discovery is a slow network call
            $discoveredInfo = $ingestor->discoverCompanyProfile($companyName);
            
            // Reconnect after the AI call
            \Config\Database::connect()->reconnect();

            if (!empty($discoveredInfo)) {
                $companyData = [
                    'name'              => $discoveredInfo['name'] ?? $companyName,
                    'industry'          => $discoveredInfo['industry'] ?? ($companyInfo['industry'] ?? null),
                    'hq'                => $discoveredInfo['hq'] ?? ($companyInfo['hq'] ?? null),
                    'size'              => $discoveredInfo['size'] ?? ($companyInfo['size'] ?? null),
                    'website'           => $discoveredInfo['website'] ?? ($companyInfo['website'] ?? null),
                    'short_description' => $discoveredInfo['short_description'] ?? ($companyInfo['short_description'] ?? null),
                    'linkedin'          => $discoveredInfo['linkedin'] ?? ($companyInfo['linkedin'] ?? null),
                    'twitter'           => $discoveredInfo['twitter'] ?? ($companyInfo['twitter'] ?? null),
                    'facebook'          => $discoveredInfo['facebook'] ?? ($companyInfo['facebook'] ?? null),
                    'instagram'         => $discoveredInfo['instagram'] ?? ($companyInfo['instagram'] ?? null),
                    'youtube'           => $discoveredInfo['youtube'] ?? ($companyInfo['youtube'] ?? null),
                ];

                if ($companyInfo) {
                    // Enrich existing company info for this request session only
                    $companyInfo = array_merge($companyInfo, $companyData);
                } else {
                    // Use discovered data directly without persisting it to the database
                    $companyInfo = $companyData;
                }
            }
        }

        // Prepare profile for response
        if ($companyInfo) {
            // Handle Logo URL: fallback to Clearbit if no local logo exists
            if (empty($companyInfo['logo']) && !empty($companyInfo['website'])) {
                $domain = parse_url($companyInfo['website'], PHP_URL_HOST) ?? $companyInfo['website'];
                $domain = preg_replace('/^www\./i', '', (string) $domain) ?? (string) $domain;
                $companyInfo['logo_url'] = 'https://logo.clearbit.com/' . rawurlencode($domain) . '?size=96'; // Using Clearbit for potentially better quality logos
            } else {
                $companyInfo['logo_url'] = !empty($companyInfo['logo']) ? base_url($companyInfo['logo']) : null;
            }
        }

        // Find official ATS mapping to prioritize official website search
        $atsMappingModel = model('App\Models\CompanyAtsMappingModel');
        $mapping = $atsMappingModel->findMatchingMapping($companyName);

        // 1. Check if we have recently discovered jobs (Cache to save API costs)
        $jobs = $this->filterUsableJobs($model->getCachedJobs($companyName, $limit), $companyName, $limit);

        if (count($jobs) < $limit) {
            // 2. Perform AI discovery if cache is empty or old
            // This is the longest running part of the request
            log_message('info', "MncJobController: Cache miss for $companyName. Triggering AI discovery for up to $limit jobs.");
            $discovered = $ingestor->discoverJobs($companyName, $limit, $mapping, $companyInfo);
            
            // Critical: Reconnect after the deep search/AI parsing loop
            \Config\Database::connect()->reconnect();

            if (empty($discovered)) {
                log_message('notice', "MncJobController: AI discovery returned 0 jobs for $companyName.");
            }

            if (!empty($discovered)) {
                foreach ($discovered as $job) {
                    $applyUrl = $this->normalizeApplyUrl((string) ($job['apply_url'] ?? ''));
                    $title = trim((string) ($job['title'] ?? ''));
                    $location = trim((string) ($job['location'] ?? ''));
                    $postedAtRaw = trim((string) ($job['posted_at_raw'] ?? ''));
                    $sourcePlatform = trim((string) ($job['source_platform'] ?? '')) ?: ($mapping['platform'] ?? 'Official Career Site');
                    $discoveredEmployer = trim((string) ($job['employer'] ?? $job['company'] ?? ''));
                    $officialApplyUrl = $ingestor->resolveOfficialApplyUrl($companyName, $title, $applyUrl, $mapping, $companyInfo);
                    if ($officialApplyUrl !== '' && $officialApplyUrl !== $applyUrl) {
                        $applyUrl = $officialApplyUrl;
                        $sourcePlatform = parse_url($officialApplyUrl, PHP_URL_HOST) ?: 'Official Career Site';
                    }

                    // Ensure DB connection is alive before checking/inserting into the loop,
                    // as resolveOfficialApplyUrl can take a long time.
                    try {
                        \Config\Database::connect()->reconnect();
                    } catch (\Throwable $e) {
                        log_message('error', 'MncJobController: Loop DB reconnect failed: ' . $e->getMessage());
                    }

                    $jobData = [
                        'company_name' => $companyName,
                        'title'        => $title,
                        'location'     => $location !== '' ? $location : 'Remote/Multiple',
                        'apply_url'    => $applyUrl,
                        'source_platform' => $sourcePlatform,
                        'posted_at_raw'=> $postedAtRaw !== '' ? $postedAtRaw : 'Recently',
                        'last_sync_at' => date('Y-m-d H:i:s')
                    ];

                    $validationData = $jobData;
                    $validationData['discovered_employer'] = $discoveredEmployer;

                    if (!$this->isUsableJob($validationData, $companyName)) {
                        continue;
                    }
                    
                    // Check if this specific job link already exists for this company
                    $existing = $model->where('company_name', $companyName)
                                    ->where('apply_url', $applyUrl)
                                    ->first();
                    
                    if (!$existing) {
                        $model->insert($jobData);
                    } else {
                        // Update existing entry to keep it in the 48-hour cache window
                        $model->update($existing['id'], [
                            'title' => $jobData['title'],
                            'location' => $jobData['location'],
                            'source_platform' => $jobData['source_platform'],
                            'posted_at_raw' => $jobData['posted_at_raw'],
                            'last_sync_at' => $jobData['last_sync_at'],
                        ]);
                    }
                }
                
                // Final verification reconnect before the last cache fetch
                try {
                    \Config\Database::connect()->reconnect();
                } catch (\Throwable $e) {
                    log_message('error', 'MncJobController: Final cache fetch reconnect failed: ' . $e->getMessage());
                }

                $jobs = $this->filterUsableJobs($model->getCachedJobs($companyName, $limit), $companyName, $limit);
            }
        }

        \Config\Database::connect()->reconnect();
        $jobs = $this->markSavedExternalJobs($jobs);

        return $this->response->setJSON([
            'success' => true,
            'company' => $companyName,
            'limit'   => $limit,
            'count'   => count($jobs),
            'company_info' => $companyInfo,
            'jobs'    => $jobs,
            'source'  => 'AI Job Discovery Engine'
        ]);
    }

    public function save(int $jobId)
    {
        $candidateId = (int) session()->get('user_id');
        if ($candidateId <= 0) {
            return redirect()->to(base_url('login'));
        }

        $job = (new MncJobModel())
            ->select('id')
            ->where('id', $jobId)
            ->where('is_active', 1)
            ->first();

        if (!$job) {
            if ($this->request->isAJAX()) {
                return $this->response->setStatusCode(404)->setJSON([
                    'success' => false,
                    'message' => 'Job not found.',
                ]);
            }

            return redirect()->back()->with('error', 'Job not found.');
        }

        $savedJobModel = model('App\Models\SavedJobModel');
        $alreadySaved = $savedJobModel
            ->where('candidate_id', $candidateId)
            ->where('mnc_external_job_id', $jobId)
            ->first();

        if (!$alreadySaved) {
            $savedJobModel->insert([
                'candidate_id' => $candidateId,
                'job_id' => null,
                'mnc_external_job_id' => $jobId,
            ]);
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'saved' => true,
                'job_id' => $jobId,
                'message' => 'Job saved.',
            ]);
        }

        return redirect()->back();
    }

    public function unsave(int $jobId)
    {
        $candidateId = (int) session()->get('user_id');
        if ($candidateId <= 0) {
            return redirect()->to(base_url('login'));
        }

        model('App\Models\SavedJobModel')
            ->where('candidate_id', $candidateId)
            ->where('mnc_external_job_id', $jobId)
            ->delete();

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'saved' => false,
                'job_id' => $jobId,
                'message' => 'Job removed from saved list.',
            ]);
        }

        return redirect()->back();
    }

    /**
     * @param array<int, array<string, mixed>> $jobs
     * @return array<int, array<string, mixed>>
     */
    private function markSavedExternalJobs(array $jobs): array
    {
        $candidateId = (int) session()->get('user_id');
        if ($candidateId <= 0 || empty($jobs)) {
            foreach ($jobs as $index => $job) {
                $jobs[$index]['is_saved'] = false;
            }
            return $jobs;
        }

        $ids = [];
        foreach ($jobs as $job) {
            $id = (int) ($job['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }

        $savedIds = [];
        if (!empty($ids)) {
            try {
                $rows = model('App\Models\SavedJobModel')
                    ->select('mnc_external_job_id')
                    ->where('candidate_id', $candidateId)
                    ->whereIn('mnc_external_job_id', array_values(array_unique($ids)))
                    ->findAll();
                foreach ($rows as $row) {
                    $savedIds[(int) ($row['mnc_external_job_id'] ?? 0)] = true;
                }
            } catch (\Throwable $e) {
                $savedIds = [];
            }
        }

        foreach ($jobs as $index => $job) {
            $jobs[$index]['is_saved'] = isset($savedIds[(int) ($job['id'] ?? 0)]);
        }

        return $jobs;
    }

    private function normalizeApplyUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url === '#') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        $parts = parse_url($url);
        if (empty($parts['scheme']) || empty($parts['host'])) {
            return '';
        }

        if (empty($parts['query'])) {
            return $url;
        }

        parse_str($parts['query'], $query);
        $trackingPrefixes = ['utm_'];
        $trackingKeys = ['fbclid', 'gclid', 'msclkid', 'source', 'ref', 'trk'];
        foreach (array_keys($query) as $key) {
            $lowerKey = strtolower((string) $key);
            foreach ($trackingPrefixes as $prefix) {
                if (str_starts_with($lowerKey, $prefix)) {
                    unset($query[$key]);
                    continue 2;
                }
            }
            if (in_array($lowerKey, $trackingKeys, true)) {
                unset($query[$key]);
            }
        }

        $cleanUrl = $parts['scheme'] . '://' . $parts['host'] . ($parts['path'] ?? '');
        if (!empty($query)) {
            $cleanUrl .= '?' . http_build_query($query);
        }
        if (!empty($parts['fragment'])) {
            $cleanUrl .= '#' . $parts['fragment'];
        }

        return $cleanUrl;
    }

    /**
     * @param array<int, array<string, mixed>> $jobs
     * @return array<int, array<string, mixed>>
     */
    private function filterUsableJobs(array $jobs, string $companyName, int $limit): array
    {
        $usable = [];
        foreach ($jobs as $job) {
            if (!$this->isUsableJob($job, $companyName)) {
                continue;
            }

            $usable[] = $job;
            if (count($usable) >= $limit) {
                break;
            }
        }

        return $usable;
    }

    /**
     * Rejects generic search pages and incomplete AI guesses before they reach the UI.
     *
     * @param array<string, mixed> $job
     */
    private function isUsableJob(array $job, string $companyName): bool
    {
        $title = trim((string) ($job['title'] ?? ''));
        $location = trim((string) ($job['location'] ?? ''));
        $applyUrl = trim((string) ($job['apply_url'] ?? ''));

        if ($title === '' || $applyUrl === '' || !filter_var($applyUrl, FILTER_VALIDATE_URL)) {
            return false;
        }

        $titleLower = strtolower($title);
        $genericTitlePatterns = [
            '/^jobs?\s+in\s+/i',
            '/^careers?\s+(at|in|with)\s+/i',
            '/^job openings?\b/i',
            '/^open positions?\b/i',
            '/^search jobs?\b/i',
            '/^view all jobs?\b/i',
            '/^all jobs?\b/i',
            '/^workday careers?\b/i',
            '/^hiring\s+at\b/i',
            '/\bjobs?\s+in\s+(usa|india|uk|canada|australia|united states)\b/i',
        ];

        foreach ($genericTitlePatterns as $pattern) {
            if (preg_match($pattern, $title) === 1) {
                return false;
            }
        }

        $companyLower = strtolower($companyName);
        if ($titleLower === $companyLower || $titleLower === $companyLower . ' jobs' || $titleLower === 'jobs at ' . $companyLower) {
            return false;
        }

        $genericLocations = ['not specified', 'n/a', ''];
        if (in_array(strtolower($location), $genericLocations, true) && preg_match('/\b(engineer|developer|manager|analyst|consultant|architect|designer|specialist|lead|director|intern|administrator|associate)\b/i', $title) !== 1) {
            return false;
        }

        $discoveredEmployerKey = $this->normalizeCompanyKey((string) ($job['discovered_employer'] ?? ''));
        if ($discoveredEmployerKey !== '' && $this->companyKeysMatch($this->normalizeCompanyKey($companyName), $discoveredEmployerKey)) {
            return true;
        }
        // Fallback: if AI didn't explicitly extract employer, check URL host
        if ($discoveredEmployerKey === '') {
            $applyUrlHost = strtolower((string) (parse_url($applyUrl, PHP_URL_HOST) ?? ''));
            if ($this->hostLooksOfficialForCompany($applyUrlHost, $this->normalizeCompanyKey($companyName))) return true;
        }

        if (!$this->jobBelongsToCompany($job, $companyName)) {
            return false;
        }

        return $this->looksLikeDirectJobUrl($applyUrl);
    }

    /**
     * @param array<string, mixed> $job
     */
    private function jobBelongsToCompany(array $job, string $companyName): bool
    {
        $applyUrl = (string) ($job['apply_url'] ?? '');
        $sourcePlatform = strtolower((string) ($job['source_platform'] ?? ''));
        $companyKey = $this->normalizeCompanyKey($companyName);

        if ($companyKey === '') {
            return false;
        }

        $host = strtolower((string) (parse_url($applyUrl, PHP_URL_HOST) ?: ''));
        $hostKey = $this->normalizeCompanyKey($host);
        $discoveredEmployerKey = $this->normalizeCompanyKey((string) ($job['discovered_employer'] ?? ''));
        $applyUrlLower = strtolower($applyUrl);

        if ($discoveredEmployerKey !== '') {
            return $this->companyKeysMatch($companyKey, $discoveredEmployerKey);
        }

        if ($this->hostLooksOfficialForCompany($host, $companyKey)) {
            return true;
        }

        if (str_contains($sourcePlatform, 'linkedin') || str_contains($host, 'linkedin.')) {
            $employerKey = $this->extractLinkedInEmployerKey($applyUrl);
            if ($employerKey !== '' && $this->companyKeysMatch($companyKey, $employerKey)) {
                return true;
            }
            return str_contains(str_replace('-', '', $applyUrlLower), $companyKey);
        }

        $trustedAtsHosts = [
            'greenhouse.io',
            'lever.co',
            'myworkdayjobs.com',
            'smartrecruiters.com',
            'successfactors.',
            'icims.com',
            'ashbyhq.com',
        ];

        foreach ($trustedAtsHosts as $atsHost) {
            if (str_contains($host, $atsHost)) {
                return $this->companyKeysMatch($companyKey, $hostKey) || str_contains($hostKey, $companyKey) || str_contains(str_replace('-', '', $applyUrlLower), $companyKey);
            }
        }

        return $this->companyKeysMatch($companyKey, $hostKey) || str_contains($hostKey, $companyKey) || str_contains(str_replace('-', '', $applyUrlLower), $companyKey);
    }

    private function extractLinkedInEmployerKey(string $url): string
    {
        $pathLower = strtolower(rawurldecode((string) (parse_url($url, PHP_URL_PATH) ?: '')));
        
        // Try to match the common "-at-company-name" pattern
        if (preg_match('/-at-([a-z0-9-]+?)(?:-\d+)?$/i', $pathLower, $matches) === 1) {
            return $this->normalizeCompanyKey($matches[1]);
        }

        // Fallback: look for the company name slug after /jobs/view/ or /company/
        if (preg_match('#/(?:jobs/view|company)/([a-z0-9-]+)#i', $pathLower, $matches) === 1) {
            return $this->normalizeCompanyKey($matches[1]);
        }

        return '';
    }

    private function hostLooksOfficialForCompany(string $host, string $companyKey): bool
    {
        $host = strtolower($host);
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $parts = explode('.', $host);

        foreach ($parts as $part) {
            if ($this->companyKeysMatch($companyKey, $this->normalizeCompanyKey($part))) {
                return true;
            }
        }

        return false;
    }

    private function companyKeysMatch(string $left, string $right): bool
    {
        if ($left === '' || $right === '') {
            return false;
        }

        return $left === $right || str_contains($left, $right) || str_contains($right, $left);
    }

    private function normalizeCompanyKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\b(limited|ltd|inc|llc|llp|plc|corp|corporation|company|co|technologies|technology|solutions|services|systems|group|holdings|private|pvt)\b/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        return str_replace(' ', '', trim($value));
    }

    private function looksLikeDirectJobUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = strtolower((string) ($parts['path'] ?? ''));
        $query = strtolower((string) ($parts['query'] ?? ''));
        $haystack = $host . ' ' . $path . ' ' . $query;

        $directPatterns = [
            'linkedin.com/jobs/view',
            'linkedin.com/jobs/',
            'reqid',
            'jobid',
            'job_id',
            'jobdetail',
            'job-detail',
            '/job/',
            '/jobs/',
            '/company-job/description/',
            'gh_jid',
            'lever.co',
            'greenhouse.io',
            'myworkdayjobs.com',
            'smartrecruiters.com',
            'successfactors.',
            'icims.com',
            'ashbyhq.com',
        ];

        foreach ($directPatterns as $pattern) {
            if (str_contains($haystack, $pattern)) {
                return true;
            }
        }

        return false;
    }
}
