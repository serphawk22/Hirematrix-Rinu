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
        $company = $this->companyModel
            ->like('name', $companyName, 'both')
            ->first();

        $builder = $this->jobModel
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC');

        if ($company) {
            $builder->groupStart()
                ->where('company_id', (int) $company['id'])
                ->orLike('company', (string) ($company['name'] ?? $companyName), 'both')
                ->groupEnd();
        } else {
            $builder->like('company', $companyName, 'both');
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

            if (empty($jobs)) {
                $jobs = $model->like('company_name', $companyName, 'both')
                    ->where('is_active', 1)
                    ->where('last_sync_at >=', $freshAfter)
                    ->where('apply_url IS NOT NULL', null, false)
                    ->where('apply_url !=', '')
                    ->orderBy('last_sync_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
            }

            return array_values(array_filter(array_map(static function (array $job): array {
                $url = trim((string) ($job['apply_url'] ?? ''));
                if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
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
