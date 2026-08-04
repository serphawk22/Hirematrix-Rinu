<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\JobModel;
use App\Libraries\RecruiterResponseDisciplineService;

class DashboardController extends BaseController
{
    /**
     * Admin Dashboard - Main Overview
     */
    public function index()
    {
        $applicationModel = model('ApplicationModel');
        $jobModel = model('JobModel');
        $slotModel = model('InterviewSlotModel');

        // Get current recruiter/admin ID
        $currentUserId = session()->get('user_id');
        $jobIds = [];
        // Get jobs posted by this recruiter
        $recruiterJobs = $jobModel->where('recruiter_id', $currentUserId)->findAll();
        $jobIds = array_column($recruiterJobs, 'id');

        // If no jobs posted, show empty dashboard
        if (empty($jobIds)) {
            return view('recruiter/dashboard/index', [
                'funnel' => [
                    'total_applications' => 0,
                    'ai_interview_started' => 0,
                    'ai_interview_completed' => 0,
                    'screening_completed' => 0,
                    'shortlisted' => 0,
                    'rejected' => 0,
                    'interview_slot_booked' => 0

                ],
                'pendingActions' => [
                    'pending_screening' => 0,
                    'hr_interviews_today' => 0,
                    'pending_offers' => 0,
                    'stale_jobs' => 0,
                    'unread_messages' => 0,
                    'awaiting_replies' => 0,
                ],
                'recentApplications' => [],
                'stageTimeAnalytics' => [],
                'jobStats' => [
                    'active_jobs' => 0,
                    'total_positions' => 0,
                    'available_slots' => 0,
                    'interview_bookings' => 0
                ],
                'topJobs' => [],
                'conversionMetrics' => [],
                'monthlyTrends' => [],
                'reminders' => [],
                'responseDiscipline' => ['items' => [], 'counts' => []],
                'unread_count' => model('NotificationModel')->getUnreadCount($currentUserId),
                'upcomingInterviews' => [],
                'interviewDates' => [],
                'todayInterviews' => [],
                'noJobs' => true
            ]);
        }
        // Build base query for applications
        $applicationBuilder = $applicationModel;
        if (!empty($jobIds)) {
            $applicationBuilder = $applicationBuilder->whereIn('job_id', $jobIds);
        }


        // Candidate Funnel Overview
        $funnel = [
            'total_applications' => $applicationModel->whereIn('job_id', $jobIds)->countAllResults(),
            'ai_interview_started' => 0,
            'ai_interview_completed' => $applicationModel->whereIn('job_id', $jobIds)->where('status', 'ai_interview_completed')->countAllResults(),
            'screening_completed' => $applicationModel
                ->whereIn('job_id', $jobIds)
                ->whereIn('status', [
                    'ai_interview_completed',
                    'shortlisted',
                    'interview_slot_booked',
                    'selected',
                    'hired',
                    'rejected',
                    'hold',
                    'filtered_out',
                ])
                ->countAllResults(),
            'shortlisted' => $applicationModel->whereIn('job_id', $jobIds)->where('status', 'shortlisted')->countAllResults(),
            'rejected' => $applicationModel->whereIn('job_id', $jobIds)->where('status', 'rejected')->countAllResults(),
            'interview_slot_booked' => $applicationModel->whereIn('job_id', $jobIds)->where('status', 'interview_slot_booked')->countAllResults()
        ];

        // Pending Actions Count
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        // Specifically track unread candidate messages for the dashboard alert
        $unreadMessagesCount = model('NotificationModel')
            ->where('user_id', $currentUserId)
            ->where('type', 'candidate_message_reply')
            ->where('is_read', 0)
            ->countAllResults();

        $pendingScreeningCount = $applicationModel->whereIn('status', ['pending', 'applied', 'ai_interview_completed'])
                ->whereIn('job_id', $jobIds ?: [0])
                ->countAllResults();

        $hrInterviewsTodayCount = model('InterviewBookingModel')
                ->where('slot_datetime >=', $todayStart)
                ->where('slot_datetime <=', $todayEnd)
                ->whereIn('booking_status', ['booked', 'rescheduled', 'confirmed'])
                ->whereIn('job_id', $jobIds ?: [0])
                ->countAllResults();

        $staleJobs = $this->getStaleJobsNeedingAttention($jobIds, 14);
        $awaitingReplyCount = model('NotificationModel')
            ->where('user_id', $currentUserId)
            ->whereIn('type', ['candidate_message_reply', 'candidate_email_reply'])
            ->where('is_read', 0)
            ->countAllResults();
        $pendingActions = [
            'pending_screening' => $pendingScreeningCount,
            'hr_interviews_today' => $hrInterviewsTodayCount,
            'stale_jobs' => count($staleJobs),
            'unread_messages' => $unreadMessagesCount,
            'awaiting_replies' => $awaitingReplyCount,
            // 'pending_offers' => $applicationModel->where('status', 'selected')
            //                                      ->where('offer_status', 'pending')
            // ->whereIn('job_id', $jobIds ?: [0])
            //                                      ->countAllResults()
        ];
        $responseDiscipline = (new RecruiterResponseDisciplineService())
            ->getDashboardReminders((int) $currentUserId);

        $reminders = [];
        if ((int) ($pendingActions['pending_screening'] ?? 0) > 0) {
            $count = (int) $pendingActions['pending_screening'];
            $reminders[] = [
                'label' => 'Review ' . $count . ' application' . ($count === 1 ? '' : 's'),
                'description' => 'Pending candidates are waiting for your screening decision.',
                'link' => base_url('recruiter/jobs'),
                'icon' => 'fas fa-file-signature',
                'tone' => 'warning',
            ];
        }

        if ((int) ($pendingActions['hr_interviews_today'] ?? 0) > 0) {
            $count = (int) $pendingActions['hr_interviews_today'];
            $reminders[] = [
                'label' => $count . ' interview' . ($count === 1 ? '' : 's') . ' today',
                'description' => 'Check today\'s booked interview schedule and update candidate status.',
                'link' => base_url('recruiter/slots/bookings'),
                'icon' => 'fas fa-calendar-check',
                'tone' => 'primary',
            ];
        }

        if ($unreadMessagesCount > 0) {
            $reminders[] = [
                'label' => 'Reply to ' . $unreadMessagesCount . ' candidate message' . ($unreadMessagesCount === 1 ? '' : 's'),
                'description' => 'Candidates have responded to your messages. Quick replies improve hiring momentum.',
                'link' => base_url('notifications'),
                'icon' => 'fas fa-comments',
                'tone' => 'info',
            ];
        }

        if (!empty($staleJobs)) {
            $count = count($staleJobs);
            $reminders[] = [
                'label' => $count . ' stale job' . ($count === 1 ? '' : 's') . ' with no shortlist',
                'description' => 'These open roles have applications but no shortlisted candidates yet.',
                'link' => base_url('recruiter/jobs'),
                'icon' => 'fas fa-exclamation-circle',
                'tone' => 'danger',
            ];
        }

        $unreadNotificationsCount = model('NotificationModel')->getUnreadCount($currentUserId);

        // Recent Activity
        $recentApplicationsBuilder = $applicationModel
            ->select('applications.*, users.name as candidate_name, jobs.title as job_title, jobs.required_skills as required_skills, candidate_profiles.location as candidate_location, candidate_profiles.headline as candidate_headline')
            ->join('users', 'users.id = applications.candidate_id', 'left')
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->join('candidate_profiles', 'candidate_profiles.user_id = applications.candidate_id', 'left');
        if (!empty($jobIds)) {
            $recentApplicationsBuilder->whereIn('applications.job_id', $jobIds);
        }
        $recentApplications = $recentApplicationsBuilder
            ->orderBy('applications.applied_at', 'DESC')
            ->limit(10)
            ->find();

        // Stage Time Analytics (Average days in each stage)
        $stageTimeAnalytics = $this->calculateStageTimeAnalytics($jobIds);

        // Job Statistics
        $jobStats = [
            'active_jobs' => $jobModel
                ->where('recruiter_id', $currentUserId)
                ->where('status', 'open')
                ->countAllResults(),
            'total_positions' => $jobModel
                ->selectSum('openings')
                ->where('recruiter_id', $currentUserId)
                ->where('status', 'open')
                ->get()
                ->getRow()
                ->openings ?? 0,
            'available_slots' => $slotModel->where('is_available', 1)
                ->where('slot_datetime >', date('Y-m-d H:i:s'))
                ->whereIn('job_id', $jobIds ?: [0])
                ->countAllResults(),
            'interview_bookings' => model('InterviewBookingModel')
                ->whereIn('job_id', $jobIds ?: [0])
                ->countAllResults()
        ];

        // Top Jobs by Applications - Only recruiter's jobs
        $topJobsBuilder = $applicationModel
            ->select('jobs.title, jobs.id, COUNT(applications.id) as application_count')
            ->join('jobs', 'jobs.id = applications.job_id', 'left');

        if (!empty($jobIds)) {
            $topJobsBuilder->whereIn('applications.job_id', $jobIds);
        }

        // Top Jobs by Applications
        $topJobs = $topJobsBuilder
            ->groupBy('applications.job_id')
            ->orderBy('application_count', 'DESC')
            ->limit(5)
            ->get()
            ->getResultArray();


        // Conversion Metrics
        $conversionMetrics = $this->calculateConversionMetrics($jobIds);

        // Monthly Trends (Last 6 months)
        $monthlyTrends = $this->getMonthlyTrends($jobIds);

        // ── Upcoming Interviews (next 30 days for the calendar) ──
        $upcomingInterviews = model('InterviewBookingModel')
            ->select("
                interview_bookings.id,
                interview_bookings.slot_datetime,
                interview_bookings.booking_status,
                interview_bookings.user_id AS candidate_id,
                s.slot_date,
                s.slot_time,
                u.name AS candidate_name,
                u.email AS candidate_email,
                j.title AS job_title,
                j.id AS job_id
            ")
            ->join('interview_slots s', 'interview_bookings.slot_id = s.id')
            ->join('users u', 'interview_bookings.user_id = u.id')
            ->join('jobs j', 'interview_bookings.job_id = j.id')
            ->where('s.created_by', $currentUserId)
            ->where('s.slot_date >=', date('Y-m-d'))
            ->where('s.slot_date <=', date('Y-m-d', strtotime('+30 days')))
            ->whereIn('interview_bookings.booking_status', ['booked', 'confirmed', 'rescheduled'])
            ->orderBy('s.slot_date', 'ASC')
            ->orderBy('s.slot_time', 'ASC')
            ->findAll();

        // Group interview dates for calendar dots
        $interviewDates = [];
        foreach ($upcomingInterviews as $iv) {
            $d = $iv['slot_date'];
            if (!isset($interviewDates[$d])) {
                $interviewDates[$d] = 0;
            }
            $interviewDates[$d]++;
        }

        // Today's interviews
        $todayInterviews = array_values(array_filter($upcomingInterviews, function ($iv) {
            return $iv['slot_date'] === date('Y-m-d');
        }));

        return view('recruiter/dashboard/index', [
            'funnel' => $funnel,
            'pendingActions' => $pendingActions,
            'recentApplications' => $recentApplications,
            'stageTimeAnalytics' => $stageTimeAnalytics,
            'jobStats' => $jobStats,
            'topJobs' => $topJobs,
            'conversionMetrics' => $conversionMetrics,
            'monthlyTrends' => $monthlyTrends,
            'reminders' => $reminders,
            'responseDiscipline' => $responseDiscipline,
            'staleJobs' => $staleJobs,
            'unread_count' => $unreadNotificationsCount,
            'upcomingInterviews' => $upcomingInterviews,
            'interviewDates' => $interviewDates,
            'todayInterviews' => $todayInterviews,
        ]);
    }

    /**
     * Legacy leaderboard page.
     */
    public function leaderboard($jobIdFromRoute = null)
    {
        $jobModel = model('JobModel');
        $currentUserId = session()->get('user_id');
        $jobId = $jobIdFromRoute !== null ? (int) $jobIdFromRoute : (int) ($this->request->getGet('job_id') ?? 0);

        if ($jobId > 0) {
            $selectedJob = $jobModel
                ->where('id', $jobId)
                ->where('recruiter_id', $currentUserId)
                ->first();
            if ($selectedJob) {
                return redirect()->to(base_url('recruiter/jobs/view/' . $jobId . '#leaderboard'));
            }
        }

        return redirect()->to(base_url('recruiter/jobs'))->with('info', 'Leaderboards now live inside each job pipeline.');
    }

    /**
     * Export Dashboard Data to Excel
     */
    public function exportExcel()
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('candidate/dashboard'))->with('error', 'Access denied.');
        }
        
        $type = $this->request->getGet('type') ?? 'overview';
        // Get current recruiter/admin ID and role
        $currentUserId = session()->get('user_id');
        // Get job IDs for recruiter filtering
        $jobIds = [];
        $jobModel = model('JobModel');
        $recruiterJobs = $jobModel->where('recruiter_id', $currentUserId)->findAll();
        $jobIds = array_column($recruiterJobs, 'id');

        // If no jobs, return error
        if (empty($jobIds)) {
            return redirect()->back()->with('error', 'You have no jobs to export data from.');
        }

        $requestedJobId = (int) ($this->request->getGet('job_id') ?? 0);
        $requestedJob = null;
        if ($requestedJobId > 0) {
            foreach ($recruiterJobs as $recruiterJob) {
                if ((int) ($recruiterJob['id'] ?? 0) === $requestedJobId) {
                    $requestedJob = $recruiterJob;
                    break;
                }
            }

            if ($requestedJob === null) {
                return redirect()->back()->with('error', 'Job not found or you do not have permission to export it.');
            }

            $jobIds = [$requestedJobId];
        }


        // try {


        switch ($type) {
            case 'overview':
                $data = $this->getOverviewExportData($jobIds);
                $filename = 'recruitment_overview_' . date('Y-m-d');
                break;

            case 'leaderboard':
                $data = $this->getLeaderboardExportData($jobIds);
                $filename = 'candidate_leaderboard_' . date('Y-m-d');
                break;

            case 'funnel':
                $data = $this->getFunnelExportData($jobIds);
                $filename = 'recruitment_funnel_' . date('Y-m-d');
                break;

            case 'detailed':
                $data = $this->getDetailedExportData($jobIds);
                if ($requestedJob !== null) {
                    $safeJobTitle = preg_replace('/[^a-z0-9]+/i', '_', (string) ($requestedJob['title'] ?? 'job'));
                    $safeJobTitle = trim((string) $safeJobTitle, '_') ?: 'job_' . $requestedJobId;
                    $filename = 'job_applicants_' . $safeJobTitle . '_' . date('Y-m-d');
                } else {
                    $filename = 'recruitment_detailed_' . date('Y-m-d');
                }
                break;

            default:
                return redirect()->back()->with('error', 'Invalid export type');
        }

        // Check if PhpSpreadsheet is available
        // if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        //     log_message('warning', 'PhpSpreadsheet not found, falling back to CSV export');
        //     return $this->exportAsCSV($data, $filename);
        // }

        // Generate Excel file
        $excelPath = $this->generateExcelReport($data, $filename);

        return $this->response->download($excelPath, null)->setFileName($filename . '.xlsx');

        // } catch (\Exception $e) {
        //     log_message('error', 'Export failed: ' . $e->getMessage());

        //     // Try CSV fallback on any error
        //     try {
        //         return $this->exportAsCSV($data, $filename);
        //     } catch (\Exception $csvError) {
        //         return redirect()->back()->with('error', 'Export failed. Please contact administrator.');
        //     }
        // }

    }

    /**
     * Calculate Stage Time Analytics
     */
    private function calculateStageTimeAnalytics($jobIds = [])
    {
        $db = \Config\Database::connect();
        $whereClause = '';
        if (!empty($jobIds)) {
            $jobIdsStr = implode(',', $jobIds);
            $whereClause = "AND a.job_id IN ($jobIdsStr)";
        }

        $query = "
            SELECT 
                sh.stage_name,
                AVG(TIMESTAMPDIFF(HOUR, sh.start_time, COALESCE(sh.end_time, NOW()))) as avg_hours,
                COUNT(DISTINCT sh.application_id) as candidate_count
            FROM stage_history sh
            JOIN applications a ON a.id = sh.application_id
            WHERE 1=1 $whereClause
            GROUP BY sh.stage_name
            ORDER BY avg_hours DESC
        ";

        $results = $db->query($query)->getResultArray();

        $analytics = [];
        foreach ($results as $row) {
            $hours = round($row['avg_hours'], 1);
            $analytics[] = [
                'stage' => $row['stage_name'],
                'hours' => $hours,
                'days' => round($hours / 24, 1),
                'count' => $row['candidate_count']
            ];
        }

        return $analytics;
    }

    /**
     * Calculate Conversion Metrics
     */
    private function calculateConversionMetrics($jobIds = [])
    {
        $applicationModel = model('ApplicationModel');
        // Apply job filter if provided
        $builder = $applicationModel;
        if (!empty($jobIds)) {
            $builder = $builder->whereIn('job_id', $jobIds);
        }

        $total = $builder->countAllResults(false);
        if ($total == 0)
            return [];


        // Reset and reapply filter for each query
        $screened = $applicationModel->whereIn('status', ['ai_interview_completed', 'shortlisted', 'interview_slot_booked', 'selected', 'hired', 'rejected', 'hold', 'filtered_out']);
        if (!empty($jobIds))
            $screened->whereIn('job_id', $jobIds);
        $screenedCount = $screened->countAllResults();

        $shortlisted = $applicationModel->whereIn('status', ['shortlisted', 'interview_slot_booked', 'selected', 'hired']);
        if (!empty($jobIds))
            $shortlisted->whereIn('job_id', $jobIds);
        $shortlistedCount = $shortlisted->countAllResults();

        $hrScheduled = $applicationModel->whereIn('status', ['interview_slot_booked', 'selected', 'hired']);
        if (!empty($jobIds))
            $hrScheduled->whereIn('job_id', $jobIds);
        $hrScheduledCount = $hrScheduled->countAllResults();

        $selected = $applicationModel->whereIn('status', ['selected', 'hired']);
        if (!empty($jobIds))
            $selected->whereIn('job_id', $jobIds);
        $selectedCount = $selected->countAllResults();

        $safeRate = static function (int $numerator, int $denominator): ?float {
            if ($denominator <= 0) {
                return null;
            }

            return round(($numerator / $denominator) * 100, 1);
        };

        return [
            'application_to_screening' => $safeRate($screenedCount ?? 0, $total),
            'screening_to_shortlist' => $safeRate($shortlistedCount, $screenedCount ?? 0),
            'shortlist_to_hr_interview' => $safeRate($hrScheduledCount, $shortlistedCount),
            'hr_interview_to_selection' => $safeRate($selectedCount, $hrScheduledCount),
            'overall_conversion' => $safeRate($selectedCount, $total) ?? 0.0
        ];

    }

    public function getStaleJobsNeedingAttention(array $jobIds, int $daysWithoutShortlist = 14): array
    {
        $jobIds = array_values(array_filter(array_map('intval', $jobIds), static fn (int $id): bool => $id > 0));
        if (empty($jobIds)) {
            return [];
        }

        $db = \Config\Database::connect();
        $jobIdsSql = implode(',', $jobIds);
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . max(1, $daysWithoutShortlist) . ' days'));

        return $db->query("
            SELECT
                jobs.id,
                jobs.title,
                COUNT(applications.id) AS application_count,
                MAX(applications.applied_at) AS latest_application_at
            FROM jobs
            JOIN applications ON applications.job_id = jobs.id
            WHERE jobs.id IN ($jobIdsSql)
              AND jobs.status = 'open'
              AND applications.applied_at <= ?
            GROUP BY jobs.id, jobs.title
            HAVING SUM(CASE WHEN applications.status IN ('shortlisted', 'interview_slot_booked', 'selected', 'hired') THEN 1 ELSE 0 END) = 0
            ORDER BY application_count DESC, latest_application_at ASC
        ", [$cutoff])->getResultArray();
    }

    public function getCandidateRepliesAwaitingResponse(int $recruiterId, array $jobIds, int $daysWaiting = 3): array
    {
        $jobIds = array_values(array_filter(array_map('intval', $jobIds), static fn (int $id): bool => $id > 0));
        if ($recruiterId <= 0 || empty($jobIds)) {
            return [];
        }

        $db = \Config\Database::connect();
        $jobIdsSql = implode(',', $jobIds);
        $cutoff = date('Y-m-d H:i:s', strtotime('-' . max(1, $daysWaiting) . ' days'));

        return $db->query("
            SELECT
                thread.candidate_id,
                thread.application_id,
                thread.job_id,
                users.name AS candidate_name,
                jobs.title AS job_title,
                thread.last_candidate_message_at
            FROM (
                SELECT
                    candidate_id,
                    application_id,
                    job_id,
                    MAX(CASE WHEN sender_role = 'candidate' THEN created_at ELSE NULL END) AS last_candidate_message_at,
                    MAX(CASE WHEN sender_role = 'recruiter' THEN created_at ELSE NULL END) AS last_recruiter_message_at
                FROM recruiter_candidate_messages
                WHERE recruiter_id = ?
                  AND job_id IN ($jobIdsSql)
                GROUP BY candidate_id, application_id, job_id
            ) thread
            LEFT JOIN users ON users.id = thread.candidate_id
            LEFT JOIN jobs ON jobs.id = thread.job_id
            WHERE thread.last_candidate_message_at IS NOT NULL
              AND thread.last_candidate_message_at <= ?
              AND (
                  thread.last_recruiter_message_at IS NULL
                  OR thread.last_recruiter_message_at < thread.last_candidate_message_at
              )
            ORDER BY thread.last_candidate_message_at ASC
        ", [$recruiterId, $cutoff])->getResultArray();
    }

    /**
     * Get Monthly Trends
     */
    private function getMonthlyTrends($jobIds = [])
    {
        $db = \Config\Database::connect();
        $whereClause = '';
        if (!empty($jobIds)) {
            $jobIdsStr = implode(',', $jobIds);
            $whereClause = "AND job_id IN ($jobIdsStr)";
        }

        $query = "
            SELECT 
                DATE_FORMAT(applied_at, '%Y-%m') as month,
                COUNT(*) as total_applications,
                SUM(CASE WHEN status = 'ai_interview_completed' THEN 1 ELSE 0 END) as ai_interview_completed,
                SUM(CASE WHEN status = 'shortlisted' THEN 1 ELSE 0 END) as shortlisted,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM applications
            WHERE applied_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            $whereClause
            GROUP BY DATE_FORMAT(applied_at, '%Y-%m')
            ORDER BY month ASC
        ";

        return $db->query($query)->getResultArray();
    }

    /**
     * Get Overview Export Data
     */
    public function getOverviewExportData($jobIds = [])
    {

        return [
            'Summary' => $this->getSummarySheet($jobIds),
            'Applications' => $this->getApplicationsSheet($jobIds),
            'Job Statistics' => $this->getJobStatsSheet($jobIds)
        ];
    }

    /**
     * Get Leaderboard Export Data
     */
    private function getLeaderboardExportData($jobIds = [])
    {
        $applicationModel = model('ApplicationModel');
        $db = \Config\Database::connect();
        $hasInterviewSessions = $db->tableExists('interview_sessions');

        $scoreSelect = $hasInterviewSessions
            ? 'interview_sessions.technical_score,
                    interview_sessions.communication_score,
                    interview_sessions.overall_rating'
            : '0 as technical_score, 0 as communication_score, 0 as overall_rating';

        $builder = $applicationModel
            ->select('applications.*, users.name, users.email, jobs.title as job_title, jobs.required_skills as required_skills,
                    ' . $scoreSelect)
            ->join('users', 'users.id = applications.candidate_id', 'left')
            ->join('jobs', 'jobs.id = applications.job_id', 'left');
        if ($hasInterviewSessions) {
            $builder->join('interview_sessions', 'interview_sessions.application_id = applications.id', 'left')
                ->where('interview_sessions.overall_rating IS NOT NULL');
        }
        // Filter by job IDs if provided (for recruiters)
        if (!empty($jobIds)) {
            $builder->whereIn('applications.job_id', $jobIds);
        }

        $candidates = $hasInterviewSessions
            ? $builder->orderBy('interview_sessions.overall_rating', 'DESC')->findAll()
            : $builder->orderBy('applications.applied_at', 'DESC')->findAll();


        $data = [
            ['Rank', 'Name', 'Email', 'Job', 'Required Skills', 'Technical Score', 'Communication Score', 'Overall Rating', 'Status']
        ];

        $rank = 1;
        foreach ($candidates as $candidate) {
            $data[] = [
                $rank++,
                $candidate['name'],
                $candidate['email'],
                $candidate['job_title'],
                $candidate['required_skills'],
                $candidate['technical_score'] ?? 0,
                $candidate['communication_score'] ?? 0,
                $candidate['overall_rating'] ?? 0,
                $candidate['status']

            ];
        }


        return ['Leaderboard' => $data];
    }

    /**
     * Get Funnel Export Data
     */
    private function getFunnelExportData($jobIds = [])
    {
        $applicationModel = model('ApplicationModel');

        $stages = [
            'Total Applications',
            'AI Interview Completed',
            'Shortlisted',
            'Rejected',
            'Interview Slot Booked',
        ];

        $data = [
            ['Stage', 'Count', 'Percentage']
        ];

        // Get total with job filter
        if (!empty($jobIds)) {
            $total = $applicationModel->whereIn('job_id', $jobIds)->countAllResults();
        } else {
            $total = $applicationModel->countAll();
        }


        foreach ($stages as $stage) {
            $count = $this->getStageCount($stage, $jobIds);
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            $data[] = [$stage, $count, $percentage . '%'];
        }

        return ['Funnel Analysis' => $data];
    }

    /**
     * Get Detailed Export Data
     */
    public function getDetailedExportData($jobIds = [])
    {
        $applicationModel = model('ApplicationModel');
        $db = \Config\Database::connect();
        $hasInterviewSessions = $db->tableExists('interview_sessions');

        $detailedScoreSelect = $hasInterviewSessions
            ? 'interview_sessions.technical_score, interview_sessions.communication_score, interview_sessions.overall_rating'
            : '0 as technical_score, 0 as communication_score, 0 as overall_rating';

        $builder = $applicationModel
            ->select('applications.*, users.name, users.email, jobs.title as job_title,
            ' . $detailedScoreSelect)
            ->join('users', 'users.id = applications.candidate_id', 'left')
            ->join('jobs', 'jobs.id = applications.job_id', 'left');
        if ($hasInterviewSessions) {
            $builder->join('interview_sessions', 'interview_sessions.application_id = applications.id', 'left');
        }
        // Filter by job IDs if provided (for recruiters)
        if (!empty($jobIds)) {
            $builder->whereIn('applications.job_id', $jobIds);
        }

        $applications = $builder
            ->orderBy('applications.applied_at', 'DESC')
            ->findAll();

        $data = [
            [
                'ID',
                'Name',
                'Email',
                'Job',
                'Status',
                'Technical Score',
                'Communication Score',
                'Overall Rating',
                'Applied Date'

            ]
        ];

        foreach ($applications as $app) {
            $data[] = [
                $app['id'],
                $app['name'],
                $app['email'],
                $app['job_title'],
                $app['status'],
                $app['technical_score'] ?? 0,
                $app['communication_score'] ?? 0,
                $app['overall_rating'] ?? 0,
                date('Y-m-d', strtotime($app['applied_at']))

            ];
        }

        return ['Detailed Report' => $data];
    }

    /**
     * Generate Excel Report
     */
    public function generateExcelReport($data, $filename)
    {
        require_once ROOTPATH . 'vendor/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach ($data as $sheetName => $sheetData) {
            $sheet = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($spreadsheet, $sheetName);
            $spreadsheet->addSheet($sheet);

            // Add data
            $sheet->fromArray($sheetData, null, 'A1');

            // Style header row
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER]
            ];

            $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);

            // Auto-size columns
            foreach (range('A', $sheet->getHighestColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // Save file
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filepath = WRITEPATH . 'uploads/' . $filename . '.xlsx';
        $writer->save($filepath);

        return $filepath;
    }

    /**
     * CSV Export Fallback
     */
    private function exportAsCSV($data, $filename)
    {
        // Flatten multi-sheet data into single CSV
        $csvData = [];

        foreach ($data as $sheetName => $sheetData) {
            $csvData[] = ['=== ' . $sheetName . ' ==='];
            $csvData = array_merge($csvData, $sheetData);
            $csvData[] = [''];  // Empty row between sheets
        }

        // Ensure directory exists
        $uploadDir = WRITEPATH . 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate CSV
        $filepath = $uploadDir . $filename . '.csv';

        $fp = fopen($filepath, 'w');

        foreach ($csvData as $row) {
            fputcsv($fp, $row);
        }

        fclose($fp);

        return $this->response->download($filepath, null)->setFileName($filename . '.csv');
    }


    /**
     * Helper Methods
     */
    public function getSummarySheet($jobIds = [])
    {
        $applicationModel = model('ApplicationModel');

        $builder = $applicationModel;
        if (!empty($jobIds)) {
            $builder = $builder->whereIn('job_id', $jobIds);
        }

        $total = $builder->countAllResults(false);

        $activeBuilder = clone $builder;
        $active = $activeBuilder->whereNotIn('status', ['rejected', 'withdrawn', 'filtered_out', 'hired'])->countAllResults();

        $completedBuilder = clone $builder;
        $completed = $completedBuilder->whereIn('status', ['ai_interview_completed', 'shortlisted', 'interview_slot_booked', 'selected', 'hired'])->countAllResults();

        $selectedBuilder = clone $builder;
        $selected = $selectedBuilder->whereIn('status', ['shortlisted', 'interview_slot_booked', 'selected', 'hired'])->countAllResults();

        $rejectedBuilder = clone $builder;
        $rejected = $rejectedBuilder->where('status', 'rejected')->countAllResults();

        return [
            ['Metric', 'Value'],
            ['Total Applications', $total],
            ['Active Applications', $active],
            ['Completed Interviews', $completed],
            ['Shortlisted Candidates', $selected],
            ['Rejected Candidates', $rejected],
            ['Generated On', date('Y-m-d H:i:s')]
        ];

    }

    public function getApplicationsSheet($jobIds = [])
    {
        $applicationModel = model('ApplicationModel');

        $builder = $applicationModel
            ->select('applications.id, users.name, jobs.title as job, applications.status, applications.applied_at')
            ->join('users', 'users.id = applications.candidate_id', 'left')
            ->join('jobs', 'jobs.id = applications.job_id', 'left');
        if (!empty($jobIds)) {
            $builder->whereIn('applications.job_id', $jobIds);
        }

        $apps = $builder
            ->orderBy('applications.applied_at', 'DESC')
            ->limit(1000)
            ->findAll();

        $data = [['ID', 'Candidate', 'Job', 'Status', 'Applied Date']];

        foreach ($apps as $app) {
            $data[] = [
                $app['id'],
                $app['name'],
                $app['job'],
                $app['status'],
                date('Y-m-d', strtotime($app['applied_at']))
            ];
        }

        return $data;
    }

    public function getJobStatsSheet($jobIds = [])
    {
        $db = \Config\Database::connect();
        $whereClause = '';
        if (!empty($jobIds)) {
            $jobIdsStr = implode(',', $jobIds);
            $whereClause = "WHERE jobs.id IN ($jobIdsStr)";
        }

        $query = "
              SELECT 
                  jobs.title,
                  COUNT(applications.id) as total_applications,
                SUM(CASE WHEN applications.status IN ('selected', 'hired') THEN 1 ELSE 0 END) as selected,
                  SUM(CASE WHEN applications.status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM jobs
            LEFT JOIN applications ON applications.job_id = jobs.id
            $whereClause
            GROUP BY jobs.id
            ORDER BY total_applications DESC
        ";

        $results = $db->query($query)->getResultArray();

        $data = [['Job Title', 'Total Applications', 'Selected', 'Rejected']];

        foreach ($results as $row) {
            $data[] = [
                $row['title'],
                $row['total_applications'],
                $row['selected'],
                $row['rejected']
            ];
        }

        return $data;
    }

    private function getStageCount($stage, $jobIds = [])
    {
        $applicationModel = model('ApplicationModel');

        $statusMap = [
            'Total Applications' => null,
            'AI Interview Completed' => 'ai_interview_completed',
            'Shortlisted' => 'shortlisted',
            'Rejected' => 'rejected',
            'Interview Slot Booked' => 'interview_slot_booked'

        ];
        $builder = $applicationModel;

        if (!empty($jobIds)) {
            $builder = $builder->whereIn('job_id', $jobIds);
        }

        if ($stage === 'Total Applications') {
            return $builder->countAllResults();
        }

        $status = $statusMap[$stage] ?? null;
        if ($status) {
            return $builder->where('status', $status)->countAllResults();
        }

        return 0;
    }
}
