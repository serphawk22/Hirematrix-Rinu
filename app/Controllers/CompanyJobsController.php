<?php

namespace App\Controllers;

use App\Libraries\JobAggregator;

class CompanyJobsController extends BaseController
{
    private $jobAggregator;
    private $jobModel;
    private $companyModel;

    public function __construct()
    {
        $this->jobAggregator = new JobAggregator();
        $this->jobModel = model('JobModel');
        $this->companyModel = model('CompanyModel');
    }

    /**
     * Search jobs by company
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
        $page = (int) $this->request->getGet('page') ?? 1;
        $limit = (int) $this->request->getGet('limit') ?? 25;

        // Get internal jobs first
        $internalJobs = $this->getInternalJobsByCompany($companyName);

        // Get external jobs from Indeed
        $externalJobs = $this->jobAggregator->fetchJobsByCompany($companyName, $limit);

        // Merge and deduplicate
        $allJobs = array_merge($internalJobs, $externalJobs);

        return $this->response->setJSON([
            'status' => 'success',
            'company' => $companyName,
            'internal_count' => count($internalJobs),
            'external_count' => count($externalJobs),
            'total_count' => count($allJobs),
            'jobs' => $allJobs
        ]);
    }

    /**
     * Get internal jobs by company
     */
    private function getInternalJobsByCompany(string $companyName): array
    {
        // Find company
        $company = $this->companyModel
            ->where('LOWER(name)', 'LIKE', '%' . strtolower($companyName) . '%')
            ->first();

        if (!$company) {
            return [];
        }

        // Get jobs for this company
        return $this->jobModel
            ->where('company_id', $company['id'])
            ->where('status', 'active')
            ->orderBy('posted_at', 'DESC')
            ->findAll();
    }

    /**
     * View company jobs page
     */
    public function viewCompanyJobs(string $companyName = '')
    {
        if (empty($companyName)) {
            return redirect()->to('jobs');
        }

        $companyName = urldecode($companyName);

        // Get internal jobs
        $internalJobs = $this->getInternalJobsByCompany($companyName);

        // Get external jobs from Indeed
        $externalJobs = $this->jobAggregator->fetchJobsByCompany($companyName, 50);

        // Get company info
        $company = $this->companyModel
            ->where('LOWER(name)', 'LIKE', '%' . strtolower($companyName) . '%')
            ->first();

        $data = [
            'title' => "Jobs at {$companyName}",
            'company_name' => $companyName,
            'company' => $company,
            'internal_jobs' => $internalJobs,
            'external_jobs' => $externalJobs,
            'total_jobs' => count($internalJobs) + count($externalJobs)
        ];

        return view('candidate/company_jobs', $data);
    }

    /**
     * Search jobs by keyword and company
     */
    public function search()
    {
        $keyword = $this->request->getGet('q') ?? '';
        $company = $this->request->getGet('company') ?? '';
        $limit = (int) $this->request->getGet('limit') ?? 25;

        if (empty($keyword) && empty($company)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Keyword or company is required'
            ]);
        }

        $jobs = $this->jobAggregator->searchJobs($keyword, $company, $limit);

        return $this->response->setJSON([
            'status' => 'success',
            'keyword' => $keyword,
            'company' => $company,
            'count' => count($jobs),
            'jobs' => $jobs
        ]);
    }

    /**
     * Clear cache for a specific company
     * Usage: /candidate/company-jobs/clear-cache/McDonald's
     */
    public function clearCache(string $companyName = '')
    {
        if (empty($companyName)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Company name is required'
            ]);
        }

        $companyName = urldecode($companyName);
        $cleared = $this->jobAggregator->clearCompanyCache($companyName);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Cache cleared for {$companyName}",
            'cleared' => $cleared
        ]);
    }

    /**
     * Clear all job caches
     * Usage: /candidate/company-jobs/clear-all-cache
     */
    public function clearAllCache()
    {
        $cache = service('cache');
        $cache->clean();

        return $this->response->setJSON([
            'status' => 'success',
            'message' => 'All job caches cleared successfully'
        ]);
    }

    /**
     * Get company suggestions for autocomplete
     * Merges internal DB + Clearbit Autocomplete API (free, no key needed)
     * Usage: /candidate/company-jobs/suggestions?q=goo
     */
    public function suggestions()
    {
        $query = trim((string) $this->request->getGet('q'));

        if (strlen($query) < 2) {
            return $this->response->setJSON(['status' => 'success', 'suggestions' => []]);
        }

        $cacheKey = 'company_suggestions_' . md5(strtolower($query));
        $cache    = service('cache');

        if ($cached = $cache->get($cacheKey)) {
            return $this->response->setJSON(['status' => 'success', 'suggestions' => $cached]);
        }

        $seen        = [];
        $suggestions = [];

        // 1. Internal DB companies (highest priority)
        $dbCompanies = $this->companyModel
            ->select('name, industry, hq')
            ->like('name', $query, 'both')
            ->orderBy('name', 'ASC')
            ->limit(5)
            ->findAll();

        foreach ($dbCompanies as $c) {
            $key = strtolower($c['name']);
            if (!isset($seen[$key])) {
                $suggestions[] = [
                    'name'   => $c['name'],
                    'domain' => '',
                    'logo'   => '',
                    'source' => 'internal',
                ];
                $seen[$key] = true;
            }
        }

        // 2. Clearbit Autocomplete API — free, no key, returns real companies with logos
        try {
            $client   = new \GuzzleHttp\Client();
            $response = $client->get('https://autocomplete.clearbit.com/v1/companies/suggest', [
                'query'   => ['query' => $query],
                'headers' => ['Accept' => 'application/json'],
                'timeout'         => 3, // Reduced to ensure snappy UI responsiveness
                'connect_timeout' => 2, // Fail fast if DNS resolution hangs
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() === 200) {
                $results = json_decode((string) $response->getBody(), true) ?? [];

                foreach ($results as $item) {
                    $name = trim((string) ($item['name'] ?? ''));
                    $key  = strtolower($name);

                    if (empty($name) || isset($seen[$key])) {
                        continue;
                    }

                    $suggestions[] = [
                        'name'   => $name,
                        'domain' => $item['domain'] ?? '',
                        'logo'   => !empty($item['logo']) ? $item['logo'] : '',
                        'source' => 'clearbit',
                    ];
                    $seen[$key] = true;

                    if (count($suggestions) >= 10) {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            // Log as warning since external service dependencies can be flaky
            log_message('warning', 'Clearbit suggestions service error: ' . $e->getMessage());
        }

        $suggestions = array_slice($suggestions, 0, 10);

        // Cache for 24 hours — company names rarely change
        $cache->save($cacheKey, $suggestions, 86400);

        return $this->response->setJSON(['status' => 'success', 'suggestions' => $suggestions]);
    }
}
