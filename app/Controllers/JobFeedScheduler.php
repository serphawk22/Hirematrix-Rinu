<?php

namespace App\Controllers;

use App\Libraries\ExternalJobScraperService;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class JobFeedScheduler extends Controller
{
    private ExternalJobScraperService $scraper;

    public function __construct()
    {
        $this->scraper = new ExternalJobScraperService();
    }

    /**
     * Daily import endpoint - triggered by cron
     * POST /admin/jobs/import-external
     */
    public function importDaily(): ResponseInterface
    {
        if (!$this->validateCronRequest()) {
            log_message('warning', 'Unauthorized cron request from ' . $this->request->getIPAddress());
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Unauthorized',
            ]);
        }

        try {
            $limit = (int) ($this->request->getVar('limit') ?? 50);
            $limit = max(1, min(100, $limit));

            $sourcesParam = (string) ($this->request->getVar('sources') ?? 'remotive,remoteok,arbeitnow');
            $sources = array_filter(array_map('trim', explode(',', $sourcesParam)));

            $keyword = trim((string) ($this->request->getVar('keyword') ?? ''));
            $location = trim((string) ($this->request->getVar('location') ?? ''));

            log_message('info', "Starting external job import: limit=$limit, sources=" . implode(',', $sources));

            $result = $this->scraper->scrapeAndIngest($limit, $sources, $keyword, $location);

            log_message('info', 'External job import completed: ' . json_encode($result));

            return $this->response->setJSON([
                'status' => 'success',
                'timestamp' => date('Y-m-d H:i:s'),
                'imported' => $result['imported_count'],
                'skipped' => $result['skipped_count'],
                'fetched' => $result['fetched_count'],
                'source_stats' => $result['source_stats'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Job import failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Manual import endpoint - for admin dashboard
     * POST /admin/jobs/import-manual
     */
    public function importManual(): ResponseInterface
    {
        if (!$this->isAdminUser()) {
            return $this->response->setStatusCode(403)->setJSON([
                'status' => 'error',
                'message' => 'Admin access required',
            ]);
        }

        try {
            $limit = (int) ($this->request->getPost('limit') ?? 30);
            $limit = max(1, min(100, $limit));

            $sourcesParam = (string) ($this->request->getPost('sources') ?? 'remotive,remoteok,arbeitnow');
            $sources = array_filter(array_map('trim', explode(',', $sourcesParam)));

            $keyword = trim((string) ($this->request->getPost('keyword') ?? ''));
            $location = trim((string) ($this->request->getPost('location') ?? ''));

            log_message('info', "Admin manual import: limit=$limit, user=" . session()->get('user_id'));

            $result = $this->scraper->scrapeAndIngest($limit, $sources, $keyword, $location);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Import completed successfully',
                'imported' => $result['imported_count'],
                'skipped' => $result['skipped_count'],
                'fetched' => $result['fetched_count'],
                'source_stats' => $result['source_stats'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Manual import failed: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get import statistics
     * GET /admin/jobs/import-stats
     */
    public function getImportStats(): ResponseInterface
    {
        if (!$this->isAdminUser()) {
            return $this->response->setStatusCode(403);
        }

        try {
            $db = \Config\Database::connect();
            $days = (int) ($this->request->getVar('days') ?? 30);

            $stats = $db->table('jobs')
                ->selectSum('1', 'total_jobs')
                ->selectSum('is_external', 'external_jobs')
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime("-$days days")))
                ->get()
                ->getRowArray();

            $sourceBreakdown = $db->table('jobs')
                ->select('external_source, COUNT(*) as count')
                ->where('is_external', 1)
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime("-$days days")))
                ->groupBy('external_source')
                ->get()
                ->getResultArray();

            $dailyStats = $db->table('jobs')
                ->select('DATE(created_at) as date, COUNT(*) as count, SUM(is_external) as external_count')
                ->where('created_at >=', date('Y-m-d H:i:s', strtotime("-$days days")))
                ->groupBy('DATE(created_at)')
                ->orderBy('date', 'DESC')
                ->get()
                ->getResultArray();

            return $this->response->setJSON([
                'status' => 'success',
                'period_days' => $days,
                'summary' => $stats,
                'by_source' => $sourceBreakdown,
                'daily_breakdown' => $dailyStats,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to fetch import stats: ' . $e->getMessage());

            return $this->response->setStatusCode(500)->setJSON([
                'status' => 'error',
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Validate cron request
     */
    private function validateCronRequest(): bool
    {
        // Check for secret token
        $token = $this->request->getHeaderLine('X-Cron-Token');
        $expectedToken = getenv('CRON_SECRET_TOKEN');

        if ($expectedToken && !empty($token)) {
            return hash_equals($expectedToken, $token);
        }

        // Fallback: Check for localhost
        $clientIp = $this->request->getIPAddress();
        return in_array($clientIp, ['127.0.0.1', '::1', 'localhost'], true);
    }

    /**
     * Check if user is admin
     */
    private function isAdminUser(): bool
    {
        if (session()->get('admin_logged_in')) {
            return true;
        }

        $userId = session()->get('user_id');
        $userRole = session()->get('user_role');

        return !empty($userId) && $userRole === 'admin';
    }
}
