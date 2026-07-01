<?php

namespace App\Controllers;

use App\Models\MncJobModel;

class CompanyJobsController extends BaseController
{
    private $jobModel;
    private $companyModel;

    public function __construct()
    {
        $this->jobModel = model('JobModel');
        $this->companyModel = model('CompanyModel');
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

        return $builder->findAll(50);
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
            $model = new MncJobModel();
            $jobs = $model->where('company_name', $companyName)
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

                $lastSyncAt = (string) ($job['last_sync_at'] ?? '');
                $job['is_stale'] = $lastSyncAt !== '' && strtotime($lastSyncAt) < strtotime('-7 days');
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

        if (strlen($companyKey) > 4) {
            return true;
        }

        foreach (explode('.', preg_replace('/^www\./', '', $host) ?? $host) as $part) {
            if ($this->companyKeysMatch($companyKey, $this->normalizeCompanyKey($part))) {
                return true;
            }
        }

        $thirdPartySources = ['linkedin', 'indeed', 'glassdoor', 'remotive', 'aggregator', 'search discovery'];
        foreach ($thirdPartySources as $thirdPartySource) {
            if (str_contains($source, $thirdPartySource) || str_contains($host, $thirdPartySource)) {
                return $this->textMentionsCompany((string) ($job['title'] ?? ''), $companyName)
                    || $this->urlPathMentionsCompany($applyUrl, $companyName);
            }
        }

        return $this->urlPathMentionsCompany($applyUrl, $companyName);
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

        if (min(strlen($left), strlen($right)) <= 4) {
            return false;
        }

        return str_contains($left, $right) || str_contains($right, $left);
    }

    private function normalizeCompanyKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\b(limited|ltd|inc|llc|llp|plc|corp|corporation|company|co|technologies|technology|solutions|services|systems|group|holdings|private|pvt)\b/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        return str_replace(' ', '', trim($value));
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
