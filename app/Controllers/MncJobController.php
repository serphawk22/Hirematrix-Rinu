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

        // Discovery can take a while. Release PHP's session lock so the same
        // candidate can poll the cache concurrently while jobs are inserted.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // Reconnect to DB before starting the process
        try {
            \Config\Database::connect()->reconnect();
        } catch (\Throwable $e) {
            log_message('error', 'MncJobController: DB reconnect failed at start: ' . $e->getMessage());
        }

        $limit = (int) ($this->request->getGet('limit') ?? 10);
        $limit = max(1, min(100, $limit));
        $companyHints = [
            'website' => trim((string) ($this->request->getGet('website') ?? '')),
            'career_url' => trim((string) ($this->request->getGet('career_url') ?? '')),
        ];

        try {
            return $this->runDiscover($companyName, $limit, $companyHints);
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

    private function runDiscover(string $companyName, int $limit, array $companyHints = []): \CodeIgniter\HTTP\ResponseInterface
    {
        @set_time_limit(180);

        $model        = new MncJobModel();
        $ingestor     = new MncJobIngestor();
        $companyModel = model('CompanyModel');

        // 1. Get or Discover/Enrich Company Info
        $companyInfo = $companyModel->like('name', $companyName, 'both')->first();
        $hintWebsite = $this->normalizeApplyUrl((string) ($companyHints['website'] ?? ''));
        $hintCareerUrl = $this->normalizeApplyUrl((string) ($companyHints['career_url'] ?? ''));
        if (!$companyInfo) {
            $companyInfo = ['name' => $companyName];
        }
        if ($hintWebsite !== '' && empty($companyInfo['website'])) {
            $companyInfo['website'] = $hintWebsite;
        }
        if ($hintCareerUrl !== '' && empty($companyInfo['career_page'])) {
            $companyInfo['career_page'] = $hintCareerUrl;
        }
        
        // Company-profile enrichment is unrelated to loading job cards and can
        // add another remote AI call. Keep this endpoint focused on openings.

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
        if (!$mapping && ($hintCareerUrl !== '' || $hintWebsite !== '')) {
            $mapping = [
                'company_name' => $companyName,
                'career_url' => $hintCareerUrl,
                'website_url' => $hintWebsite,
                'platform' => 'Official Career Site',
            ];
        }

        // 1. Check if we have recently discovered jobs (Cache to save API costs)
        $jobs = $this->filterUsableJobs($this->getCachedJobsForCompanyAliases($model, $companyName, $companyInfo, $limit), $companyName, $limit);
        $jobs = $this->filterLiveJobs($jobs, $ingestor, $model);

        $emptyDiscoveryCacheKey = 'mnc_empty_discovery_' . sha1(strtolower(trim($companyName)));
        $runningDiscoveryCacheKey = 'mnc_running_discovery_' . sha1(strtolower(trim($companyName)));
        if (empty($jobs) && cache()->get($emptyDiscoveryCacheKey)) {
            return $this->response->setJSON([
                'success' => true,
                'company' => $companyName,
                'limit' => $limit,
                'count' => 0,
                'company_info' => $companyInfo,
                'jobs' => [],
                'source' => 'Recent discovery cache',
                'message' => 'No current openings were found in the latest check.',
            ]);
        }

        if (empty($jobs) && cache()->get($runningDiscoveryCacheKey)) {
            return $this->response->setJSON([
                'success' => true,
                'company' => $companyName,
                'limit' => $limit,
                'count' => 0,
                'company_info' => $companyInfo,
                'jobs' => [],
                'source' => 'Discovery in progress',
                'message' => 'Openings are already being checked for this company.',
            ]);
        }

        if (count($jobs) < $limit) {
            // 2. Perform AI discovery if cache is empty or old
            // This is the longest running part of the request
            log_message('info', "MncJobController: Cache miss for $companyName. Triggering AI discovery for up to $limit jobs.");
            cache()->save($runningDiscoveryCacheKey, true, 180);
            $discovered = $ingestor->discoverJobs($companyName, $limit, $mapping, $companyInfo);
            cache()->delete($runningDiscoveryCacheKey);
            
            // Critical: Reconnect after the deep search/AI parsing loop
            \Config\Database::connect()->reconnect();

            if (empty($discovered)) {
                log_message('notice', "MncJobController: AI discovery returned 0 jobs for $companyName.");
                cache()->save($emptyDiscoveryCacheKey, true, 600);
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

                    if ($ingestor->verifyJobIsLive($applyUrl) === false) {
                        $existingExpired = $model->where('company_name', $companyName)
                            ->where('apply_url', $applyUrl)
                            ->first();
                        if ($existingExpired) {
                            $model->update($existingExpired['id'], ['is_active' => 0]);
                        }
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

                $jobs = $this->filterUsableJobs($this->getCachedJobsForCompanyAliases($model, $companyName, $companyInfo, $limit), $companyName, $limit);
                $jobs = $this->filterLiveJobs($jobs, $ingestor, $model);
                if (!empty($jobs)) {
                    cache()->delete($emptyDiscoveryCacheKey);
                }
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
     * @return array<int, array<string, mixed>>
     */
    private function getCachedJobsForCompanyAliases(MncJobModel $model, string $companyName, ?array $companyInfo, int $limit): array
    {
        $aliases = [$companyName];
        if (!empty($companyInfo['name'])) {
            $aliases[] = (string) $companyInfo['name'];
        }

        if (preg_match('/tech$/i', $companyName) === 1) {
            $aliases[] = preg_replace('/tech$/i', ' Technologies', $companyName) ?? $companyName;
        }
        if (preg_match('/technologies$/i', $companyName) === 1) {
            $aliases[] = preg_replace('/\s*technologies$/i', 'Tech', $companyName) ?? $companyName;
        }

        $aliases = array_values(array_unique(array_filter(array_map(static function (string $alias): string {
            return trim($alias);
        }, $aliases))));

        $jobsByUrl = [];
        foreach ($aliases as $alias) {
            foreach ($model->getCachedJobs($alias, $limit) as $job) {
                $key = trim((string) ($job['apply_url'] ?? ''));
                if ($key === '') {
                    $key = (string) ($job['id'] ?? count($jobsByUrl));
                }
                $jobsByUrl[$key] = $job;
            }
        }

        if (count($jobsByUrl) < $limit && strlen($this->normalizeCompanyKey($companyName)) > 4) {
            try {
                $freshAfter = date('Y-m-d H:i:s', strtotime('-48 hours'));
                $likeJobs = (new MncJobModel())
                    ->where('is_active', 1)
                    ->where('last_sync_at >=', $freshAfter)
                    ->like('company_name', $companyName, 'both')
                    ->orderBy('last_sync_at', 'DESC')
                    ->limit($limit)
                    ->findAll();

                foreach ($likeJobs as $job) {
                    $key = trim((string) ($job['apply_url'] ?? ''));
                    if ($key !== '') {
                        $jobsByUrl[$key] = $job;
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'MncJobController alias cache lookup failed: ' . $e->getMessage());
            }
        }

        return array_slice(array_values($jobsByUrl), 0, $limit);
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
     * Remove confirmed expired vacancies and deactivate them in the cache.
     * An inconclusive check is retained because many career sites block bots.
     *
     * @param array<int, array<string, mixed>> $jobs
     * @return array<int, array<string, mixed>>
     */
    private function filterLiveJobs(array $jobs, MncJobIngestor $ingestor, MncJobModel $model): array
    {
        $liveJobs = [];

        foreach ($jobs as $job) {
            if ($ingestor->verifyJobIsLive((string) ($job['apply_url'] ?? '')) === false) {
                $jobId = (int) ($job['id'] ?? 0);
                if ($jobId > 0) {
                    $model->update($jobId, ['is_active' => 0]);
                }
                continue;
            }

            $liveJobs[] = $job;
        }

        return $liveJobs;
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

        if ($this->isStaleExternalJob($job)) {
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
        $discoveredEmployer = (string) ($job['discovered_employer'] ?? '');
        $discoveredEmployerKey = $this->normalizeCompanyKey($discoveredEmployer);

        if ($discoveredEmployerKey !== '') {
            return $this->companyNamesMatch($companyName, $discoveredEmployer);
        }

        if ($this->hostLooksOfficialForCompany($host, $companyName)) {
            return true;
        }

        if (str_contains($sourcePlatform, 'linkedin') || str_contains($host, 'linkedin.')) {
            $employerKey = $this->extractLinkedInEmployerKey($applyUrl);
            if ($employerKey !== '' && $this->companyKeysMatch($companyKey, $employerKey)) {
                return true;
            }
            return false;
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
                return $this->companyKeysMatch($companyKey, $hostKey) || $this->urlPathContainsCompanyKey($applyUrl, $companyKey);
            }
        }

        return $this->companyKeysMatch($companyKey, $hostKey);
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

    private function hostLooksOfficialForCompany(string $host, string $companyName): bool
    {
        $host = strtolower($host);
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $parts = explode('.', $host);

        foreach ($parts as $part) {
            foreach ($this->companyMatchKeys($companyName) as $companyKey) {
                if ($this->companyKeysMatch($companyKey, $this->normalizeCompanyKey($part))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function companyNamesMatch(string $expected, string $actual): bool
    {
        foreach ($this->companyMatchKeys($expected) as $expectedKey) {
            foreach ($this->companyMatchKeys($actual) as $actualKey) {
                if ($this->companyKeysMatch($expectedKey, $actualKey)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return array<int, string> */
    private function companyMatchKeys(string $companyName): array
    {
        $keys = [$this->normalizeCompanyKey($companyName)];
        $words = preg_split('/[^a-z0-9]+/i', strtolower($companyName)) ?: [];
        $ignored = ['and', 'of', 'the', 'for', 'in', 'at', 'limited', 'ltd', 'private', 'pvt'];
        $initials = '';

        foreach ($words as $word) {
            if ($word !== '' && !in_array($word, $ignored, true)) {
                $initials .= $word[0];
            }
        }

        if (strlen($initials) >= 2) {
            $keys[] = $initials;
        }

        return array_values(array_unique(array_filter($keys)));
    }

    private function companyKeysMatch(string $left, string $right): bool
    {
        if ($left === '' || $right === '') {
            return false;
        }

        if ($left === $right) {
            return true;
        }

        if (min(strlen($left), strlen($right)) <= 4 && (str_starts_with($left, $right) || str_starts_with($right, $left))) {
            return true;
        }

        if (min(strlen($left), strlen($right)) <= 4) {
            return false;
        }

        return str_contains($left, $right) || str_contains($right, $left);
    }

    private function urlPathContainsCompanyKey(string $url, string $companyKey): bool
    {
        if ($companyKey === '') {
            return false;
        }

        $path = strtolower(rawurldecode((string) (parse_url($url, PHP_URL_PATH) ?: '')));
        $segments = preg_split('/[^a-z0-9]+/', $path) ?: [];

        foreach ($segments as $segment) {
            if ($this->companyKeysMatch($companyKey, $this->normalizeCompanyKey($segment))) {
                return true;
            }
        }

        return false;
    }

    private function normalizeCompanyKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\b(limited|ltd|inc|llc|llp|plc|corp|corporation|company|co|technologies|technology|solutions|services|systems|group|holdings|private|pvt)\b/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        $key = str_replace(' ', '', trim($value));
        if (strlen($key) > 4 && str_ends_with($key, 'tech')) {
            $key = substr($key, 0, -4);
        }
        return $key;
    }

    private function isStaleExternalJob(array $job): bool
    {
        $postedAtRaw = trim((string) ($job['posted_at_raw'] ?? ''));
        if ($postedAtRaw === '') {
            return false;
        }

        $parsedDays = $this->parsePostedAtRawDays($postedAtRaw);
        if ($parsedDays !== null && $parsedDays > 30) {
            return true;
        }

        if (preg_match('/\b(month|year|yr|older than|more than|over)\b/i', $postedAtRaw) === 1
            && preg_match('/\b(less than|under|<|in\s+)\b/i', $postedAtRaw) !== 1
        ) {
            return true;
        }

        return false;
    }

    private function parsePostedAtRawDays(string $postedAtRaw): ?int
    {
        $text = strtolower(trim($postedAtRaw));
        if ($text === '' || $text === 'recently' || str_contains($text, 'just now') || str_contains($text, 'today') || str_contains($text, 'hour') || str_contains($text, 'minute') || str_contains($text, 'second')) {
            return 0;
        }

        if (str_contains($text, 'yesterday')) {
            return 1;
        }

        $relativePatterns = [
            '/(\d+)\s*d(ays?)?\b/i' => 1,
            '/(\d+)\s*day[s]?\b/i' => 1,
            '/(\d+)\s*w(eeks?)?\b/i' => 7,
            '/(\d+)\s*week[s]?\b/i' => 7,
            '/(\d+)\s*mo(nths?)?\b/i' => 30,
            '/(\d+)\s*month[s]?\b/i' => 30,
            '/(\d+)\s*y(ears?)?\b/i' => 365,
        ];

        foreach ($relativePatterns as $pattern => $multiplier) {
            if (preg_match($pattern, $text, $matches) === 1) {
                return (int) $matches[1] * $multiplier;
            }
        }

        if (preg_match('/(\d+)\s*day[s]?\s*ago/i', $text, $matches) === 1) {
            return (int) $matches[1];
        }
        if (preg_match('/(\d+)\s*week[s]?\s*ago/i', $text, $matches) === 1) {
            return (int) $matches[1] * 7;
        }
        if (preg_match('/(\d+)\s*month[s]?\s*ago/i', $text, $matches) === 1) {
            return (int) $matches[1] * 30;
        }
        if (preg_match('/(\d+)\s*year[s]?\s*ago/i', $text, $matches) === 1) {
            return (int) $matches[1] * 365;
        }

        if (preg_match('/\b(older than|more than|over)\s*(\d+)\s*(day|week|month|year)s?\b/i', $text, $matches) === 1) {
            $value = (int) $matches[2];
            $unit = strtolower($matches[3]);
            return match ($unit) {
                'day' => $value,
                'week' => $value * 7,
                'month' => $value * 30,
                'year' => $value * 365,
                default => null,
            };
        }

        $timestamp = strtotime($postedAtRaw);
        if ($timestamp !== false && $timestamp <= time()) {
            return (int) floor((time() - $timestamp) / 86400);
        }

        return null;
    }

    private function looksLikeDirectJobUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = strtolower((string) ($parts['path'] ?? ''));
        $query = strtolower((string) ($parts['query'] ?? ''));
        $haystack = $host . ' ' . $path . ' ' . $query;

        if ($host === '' || $path === '') {
            return false;
        }

        if (preg_match('#/(search|jobs/search|job-search|search-jobs|jobs-in)(/|$)#i', $path) === 1
            || preg_match('/(^|&)(keywords|keyword|q|query|search)=/i', $query) === 1
        ) {
            return false;
        }

        if (str_contains($host, 'linkedin.')) {
            return preg_match('#^/jobs/view/(?:[^/]*-)?\d+/?$#i', $path) === 1;
        }

        if (str_contains($host, 'indeed.')) {
            return str_contains($path, '/viewjob') && preg_match('/(^|&)jk=[a-z0-9]+/i', $query) === 1;
        }

        if (str_contains($host, 'glassdoor.')) {
            return str_contains($path, '/job-listing/');
        }

        $directPatterns = [
            'linkedin.com/jobs/view',
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
