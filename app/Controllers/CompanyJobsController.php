<?php

namespace App\Controllers;

use App\Models\JobModel;
use App\Models\MncJobModel;
use App\Models\CompanyAtsMappingModel;

class CompanyJobsController extends BaseController
{
    private $jobModel;
    private $companyModel;
    private $companyAtsMappingModel;

    public function __construct()
    {
        $this->jobModel = model('JobModel');
        $this->companyModel = model('CompanyModel');
        $this->companyAtsMappingModel = model(CompanyAtsMappingModel::class);
    }

    /**
     * Search portal-posted jobs by company.
     */
    public function searchByCompany(string $companyName = '')
    {
        if (empty($companyName)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Company name is required'
            ]);
        }

        $companyName = urldecode($companyName);
        $internalJobs = $this->getInternalJobsByCompany($companyName);
        $externalJobs = $this->getCachedDiscoveredJobsByCompany($companyName);

        return $this->response->setJSON([
            'status' => 'success',
            'company' => $companyName,
            'internal_count' => count($internalJobs),
            'external_count' => count($externalJobs),
            'total_count' => count($internalJobs) + count($externalJobs),
            'jobs' => $internalJobs,
            'discovered_jobs' => $externalJobs
        ]);
    }

    /** Lightweight endpoint used while background discovery fills the cache. */
    public function cachedJobs()
    {
        $companyName = trim((string) $this->request->getGet('company'));
        if ($companyName === '') {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'jobs' => [],
                'message' => 'Company name is required.',
            ]);
        }

        $jobs = $this->getCachedDiscoveredJobsByCompany($companyName);

        return $this->response->setJSON([
            'success' => true,
            'company' => $companyName,
            'count' => count($jobs),
            'jobs' => $jobs,
        ]);
    }

    /**
     * Get open jobs that belong to a company profile or match its name.
     */
    private function getInternalJobsByCompany(string $companyName): array
    {
        $company = $this->companyModel->where('name', $companyName)->first();
        if (!$company && strlen($this->normalizeCompanyKey($companyName)) > 4) {
            $company = $this->companyModel
                ->like('name', $companyName, 'both')
                ->first();
        }

        $builder = $this->jobModel
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC');
        JobModel::applyApplicationDeadlineFilter($builder);

        if ($company) {
            $builder->groupStart()->where('company_id', (int) $company['id']);
            if (strlen($this->normalizeCompanyKey($companyName)) > 4) {
                $builder->orLike('company', (string) ($company['name'] ?? $companyName), 'both');
            } else {
                $builder->orWhere('company', (string) ($company['name'] ?? $companyName));
            }
            $builder->groupEnd();
        } elseif (strlen($this->normalizeCompanyKey($companyName)) > 4) {
            $builder->like('company', $companyName, 'both');
        } else {
            $builder->where('company', $companyName);
        }

        $jobs = $builder->findAll(50);
        $freshExternalAfter = strtotime('-30 days');

        return array_values(array_filter($jobs, function (array $job) use ($freshExternalAfter): bool {
            if ((int) ($job['is_external'] ?? 0) !== 1) {
                return true;
            }

            $createdAt = strtotime((string) ($job['created_at'] ?? ''));
            if ($createdAt !== false && $createdAt >= $freshExternalAfter) {
                return true;
            }

            $jobId = (int) ($job['id'] ?? 0);
            if ($jobId > 0) {
                try {
                    $this->jobModel->update($jobId, ['status' => 'closed']);
                } catch (\Throwable $e) {
                    log_message('error', 'Could not close stale external job ' . $jobId . ': ' . $e->getMessage());
                }
            }

            return false;
        }));
    }

    /**
     * Get discovered jobs already stored by the MNC ingestor.
     *
     * These are not scraped during page load. A job is treated as live enough
     * for quick display only when it is active, has a valid apply URL, and was
     * checked recently.
     */
    private function getCachedDiscoveredJobsByCompany(string $companyName, int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));
        $freshAfter = date('Y-m-d H:i:s', strtotime('-30 days'));

        try {
            $aliases = [$companyName];
            if (preg_match('/tech$/i', $companyName) === 1) {
                $aliases[] = preg_replace('/tech$/i', ' Technologies', $companyName) ?? $companyName;
            }
            if (preg_match('/technologies$/i', $companyName) === 1) {
                $aliases[] = preg_replace('/\s*technologies$/i', 'Tech', $companyName) ?? $companyName;
            }
            $aliases = array_values(array_unique(array_filter(array_map('trim', $aliases))));

            $model = new MncJobModel();
            $model->groupStart();
            foreach ($aliases as $index => $alias) {
                if ($index === 0) {
                    $model->where('company_name', $alias);
                } else {
                    $model->orWhere('company_name', $alias);
                }
            }
            if (strlen($this->normalizeCompanyKey($companyName)) > 4) {
                $model->orLike('company_name', $companyName, 'both');
            }
            $model->groupEnd();

            $jobs = $model
                ->where('is_active', 1)
                ->where('last_sync_at >=', $freshAfter)
                ->where('apply_url IS NOT NULL', null, false)
                ->where('apply_url !=', '')
                ->orderBy('last_sync_at', 'DESC')
                ->limit($limit)
                ->findAll();

            return array_values(array_filter(array_map(function (array $job) use ($companyName): array {
                $url = trim((string) ($job['apply_url'] ?? ''));
                if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
                    return [];
                }

                if (!$this->cachedDiscoveredJobBelongsToCompany($job, $companyName)) {
                    return [];
                }

                if ($this->isStaleExternalJob($job)) {
                    return [];
                }

                $job['is_stale'] = false;
                return $job;
            }, $jobs), static fn (array $job): bool => !empty($job)));
        } catch (\Throwable $e) {
            log_message('error', 'CompanyJobsController cached discovered jobs failed: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Cached discovery rows may predate stricter matching, so validate again
     * before showing them on a company page.
     *
     * @param array<string, mixed> $job
     */
    private function cachedDiscoveredJobBelongsToCompany(array $job, string $companyName): bool
    {
        $companyKey = $this->normalizeCompanyKey($companyName);
        $applyUrl = (string) ($job['apply_url'] ?? '');
        $host = strtolower((string) (parse_url($applyUrl, PHP_URL_HOST) ?: ''));
        $source = strtolower((string) ($job['source_platform'] ?? ''));

        if ($companyKey === '') {
            return false;
        }

        foreach (explode('.', preg_replace('/^www\./', '', $host) ?? $host) as $part) {
            foreach ($this->companyMatchKeys($companyName) as $matchKey) {
                if ($this->companyKeysMatch($matchKey, $this->normalizeCompanyKey($part))) {
                    return true;
                }
            }
        }

        $thirdPartySources = ['linkedin', 'indeed', 'glassdoor', 'remotive', 'aggregator', 'search discovery'];
        foreach ($thirdPartySources as $thirdPartySource) {
            if (str_contains($source, $thirdPartySource) || str_contains($host, $thirdPartySource)) {
                if (!$this->looksLikeDirectExternalJobUrl($applyUrl)) {
                    return false;
                }

                return $this->textMentionsCompany((string) ($job['title'] ?? ''), $companyName)
                    || $this->urlPathMentionsCompany($applyUrl, $companyName);
            }
        }

        return $this->looksLikeDirectExternalJobUrl($applyUrl)
            && $this->urlPathMentionsCompany($applyUrl, $companyName);
    }

    private function looksLikeDirectExternalJobUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = strtolower((string) ($parts['path'] ?? ''));
        $query = strtolower((string) ($parts['query'] ?? ''));

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

        return preg_match('#/(job|jobs|career|careers|position|positions|vacancy|vacancies)/[^/]+#i', $path) === 1
            || preg_match('/(^|&)(jobid|job_id|reqid|gh_jid)=[^&]+/i', $query) === 1;
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
            && preg_match('/\b(less than|under|<)\b/i', $postedAtRaw) !== 1
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

    private function textMentionsCompany(string $text, string $companyName): bool
    {
        $pattern = $this->companyRegexPattern($companyName);
        return $pattern !== '' && preg_match('/' . $pattern . '/i', strtolower($text)) === 1;
    }

    private function urlPathMentionsCompany(string $url, string $companyName): bool
    {
        $path = strtolower(rawurldecode((string) (parse_url($url, PHP_URL_PATH) ?: '')));
        return $this->textMentionsCompany($path, $companyName);
    }

    private function companyRegexPattern(string $companyName): string
    {
        $companyName = strtolower(trim($companyName));
        $companyName = preg_replace('/[^a-z0-9]+/', ' ', $companyName) ?? '';
        $companyName = trim($companyName);

        if ($companyName === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $companyName) ?: [];
        $escaped = implode('[\s\-_\.]+', array_map(static fn (string $part): string => preg_quote($part, '/'), $parts));

        return '(?<![a-z0-9])' . $escaped . '(?![a-z0-9])';
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

    /**
     * View a company-specific open jobs page.
     */
    public function viewCompanyJobs(string $companyName = '')
    {
        if (empty($companyName)) {
            return redirect()->to('jobs');
        }

        $companyName = urldecode($companyName);
        $internalJobs = $this->getInternalJobsByCompany($companyName);
        $externalJobs = $this->getCachedDiscoveredJobsByCompany($companyName);

        $company = $this->companyModel
            ->like('name', $companyName, 'both')
            ->first();

        // ATS mappings are maintained independently and are the authoritative
        // source for career destinations. Company profile URLs can become stale
        // when an employer moves its careers site (for example, Deloitte).
        try {
            $mapping = $this->companyAtsMappingModel->findMatchingMapping($companyName);
            $mappedCareerUrl = trim((string) ($mapping['career_url'] ?? ''));
            if ($mappedCareerUrl !== '' && filter_var($mappedCareerUrl, FILTER_VALIDATE_URL)) {
                $company = is_array($company) ? $company : [];
                $company['career_page'] = $mappedCareerUrl;
            }
        } catch (\Throwable $e) {
            // Keep the company profile URL when mappings have not been migrated yet.
            log_message('warning', 'Could not resolve career URL mapping for ' . $companyName . ': ' . $e->getMessage());
        }

        return view('candidate/company_jobs', [
            'title' => "Jobs at {$companyName}",
            'company_name' => $companyName,
            'company' => $company,
            'internal_jobs' => $internalJobs,
            'external_jobs' => $externalJobs,
            'total_jobs' => count($internalJobs) + count($externalJobs)
        ]);
    }

    /**
     * Search portal-posted open jobs.
     */
    public function search()
    {
        $keyword = $this->request->getGet('q') ?? '';
        $company = $this->request->getGet('company') ?? '';
        $limit = (int) ($this->request->getGet('limit') ?? 25);

        if (empty($keyword) && empty($company)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Keyword or company is required'
            ]);
        }

        $builder = $this->jobModel->where('status', 'open');
        if ($keyword !== '') {
            $builder->groupStart()
                ->like('title', $keyword, 'both')
                ->orLike('required_skills', $keyword, 'both')
                ->orLike('category', $keyword, 'both')
                ->groupEnd();
        }
        if ($company !== '') {
            $builder->like('company', $company, 'both');
        }

        $jobs = $builder->orderBy('created_at', 'DESC')->findAll($limit);

        return $this->response->setJSON([
            'status' => 'success',
            'keyword' => $keyword,
            'company' => $company,
            'count' => count($jobs),
            'jobs' => $jobs
        ]);
    }

    /**
     * Legacy endpoint kept for old UI calls.
     */
    public function clearCache(string $companyName = '')
    {
        if (empty($companyName)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Company name is required'
            ]);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Company job list loads portal-posted and discovered jobs.',
            'cleared' => true
        ]);
    }

    /**
     * Legacy endpoint kept for old UI calls.
     */
    public function clearAllCache()
    {
        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'Company job list loads portal-posted and discovered jobs.'
        ]);
    }

    /**
     * Get company suggestions from the portal directory.
     */
    public function suggestions()
    {
        $query = trim((string) $this->request->getGet('q'));

        if (strlen($query) < 2) {
            return $this->response->setJSON(['status' => 'success', 'suggestions' => []]);
        }

        $dbCompanies = $this->companyModel
            ->select('name, industry, hq, logo, website')
            ->like('name', $query, 'both')
            ->orderBy('name', 'ASC')
            ->limit(10)
            ->findAll();

        $suggestions = [];
        foreach ($dbCompanies as $company) {
            $suggestions[] = [
                'name' => (string) ($company['name'] ?? ''),
                'domain' => parse_url((string) ($company['website'] ?? ''), PHP_URL_HOST) ?: '',
                'logo' => (string) ($company['logo'] ?? ''),
                'source' => 'directory',
            ];
        }

        return $this->response->setJSON(['status' => 'success', 'suggestions' => $suggestions]);
    }
}
