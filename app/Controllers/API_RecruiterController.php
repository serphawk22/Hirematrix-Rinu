<?php

namespace App\Controllers;

use App\Models\JobModel;
use App\Models\UserModel;
use App\Models\ApplicationModel;
use App\Models\NotificationModel;
use App\Models\InterviewBookingModel;
use App\Models\CompanyModel;
use App\Models\StageHistoryModel;
use CodeIgniter\RESTful\ResourceController;

class API_RecruiterController extends ResourceController
{
    protected $format = 'json';

    public function exportExcel()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $type = $this->request->getVar('type') ?? 'overview';
        $requestedJobId = (int) ($this->request->getVar('job_id') ?? 0);

        if (!$recruiterId) {
            return $this->fail('Recruiter ID required');
        }

        // Get job IDs for recruiter filtering
        $jobIds = [];
        $jobModel = model('JobModel');
        $recruiterJobs = $jobModel->where('recruiter_id', $recruiterId)->findAll();
        $jobIds = array_column($recruiterJobs, 'id');

        // If no jobs, return error
        if (empty($jobIds)) {
            return $this->fail('You have no jobs to export data from.');
        }

        $requestedJob = null;
        if ($requestedJobId > 0) {
            foreach ($recruiterJobs as $recruiterJob) {
                if ((int) ($recruiterJob['id'] ?? 0) === $requestedJobId) {
                    $requestedJob = $recruiterJob;
                    break;
                }
            }
            if ($requestedJob === null) {
                return $this->fail('Job not found or you do not have permission to export it.');
            }
            $jobIds = [$requestedJobId];
        }

        $dashboardController = new \App\Controllers\DashboardController();
        $dashboardController->initController($this->request, $this->response, \Config\Services::logger());

        if ($type === 'detailed') {
            $data = $dashboardController->getDetailedExportData($jobIds);
            if ($requestedJob !== null) {
                $safeJobTitle = preg_replace('/[^a-z0-9]+/i', '_', (string) ($requestedJob['title'] ?? 'job'));
                $safeJobTitle = trim((string) $safeJobTitle, '_') ?: 'job_' . $requestedJobId;
                $filename = 'job_applicants_' . $safeJobTitle . '_' . date('Y-m-d');
            } else {
                $filename = 'recruitment_detailed_' . date('Y-m-d');
            }
        } else {
            $data = $dashboardController->getOverviewExportData($jobIds);
            $filename = 'recruitment_overview_' . date('Y-m-d');
        }

        try {
            $excelPath = $dashboardController->generateExcelReport($data, $filename);
            return $this->response->download($excelPath, null)->setFileName($filename . '.xlsx');
        } catch (\Exception $e) {
            log_message('error', 'Mobile Export failed: ' . $e->getMessage());
            return $this->fail('Export failed: ' . $e->getMessage());
        }
    }

    public function getDashboard()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $jobModel  = new JobModel();
        $appModel  = new ApplicationModel();
        $bookingModel = new \App\Models\InterviewBookingModel();

        $recruiter = $userModel->findRecruiterWithProfile((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $companyId = $recruiter['company_id'];
        $companyLogo = '';
        if (!empty($companyId)) {
            $companyModel = new CompanyModel();
            $company = $companyModel->find((int)$companyId);
            if (!empty($company['logo'])) {
                $companyLogo = preg_match('/^https?:\/\//i', $company['logo'])
                    ? $company['logo']
                    : base_url(ltrim($company['logo'], '/'));
            }
        }

        // Recruiter-Specific Workspace Stats
        $recruiterJobs = $jobModel->where('recruiter_id', $recruiterId)->findAll();
        $jobIds = array_column($recruiterJobs, 'id');

        $openJobs = 0;
        foreach ($recruiterJobs as $j) {
            if (in_array(strtolower($j['status'] ?? ''), ['open', 'active'], true)) {
                $openJobs++;
            }
        }

        $totalApps = empty($jobIds) ? 0 : $appModel->whereIn('job_id', $jobIds)->countAllResults();
        $interviewBookings = empty($jobIds) ? 0 : $bookingModel->whereIn('job_id', $jobIds)->countAllResults();

        $pipeline = $this->emptyPipelineStats();

        if (!empty($jobIds)) {
            $apps = $appModel->select('status, COUNT(*) as count')
                ->whereIn('job_id', $jobIds)
                ->groupBy('status')
                ->get()->getResultArray();

            foreach ($apps as $row) {
                $status = $this->formatApplicationStatus((string) $row['status']);
                if (isset($pipeline[$status])) {
                    $pipeline[$status] += (int)$row['count'];
                }
            }
        }

        // Calculate Conversion Rate (Selected/Hired / Total Apps)
        $selectedHiredCount = 0;
        if (!empty($jobIds)) {
            $selectedHiredCount = $appModel->whereIn('job_id', $jobIds)
                ->whereIn('status', ['selected', 'hired'])
                ->countAllResults();
        }
        $conversionRate = $totalApps > 0 ? round(($selectedHiredCount / $totalApps) * 100, 1) : 0.0;

        // Calculate Time to Hire (Average days from Applied to Hired)
        $db = \Config\Database::connect();
        $timeToHire = 'N/A';
        if (!empty($jobIds)) {
            $avgDays = $db->table('applications')
                ->select('AVG(DATEDIFF(stage_history.start_time, applications.applied_at)) as avg_days')
                ->join('stage_history', 'stage_history.application_id = applications.id')
                ->whereIn('applications.job_id', $jobIds)
                ->where('stage_history.stage_name', 'Hired')
                ->get()->getRow()->avg_days;

            if ($avgDays) {
                $timeToHire = round($avgDays) . 'd';
            }
        }

        // Application Trends (last 7 days)
        $trends = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $count = empty($jobIds) ? 0 : $appModel->whereIn('job_id', $jobIds)
                ->where('DATE(applied_at)', $date)
                ->countAllResults();
            $trends[] = ['date' => $date, 'count' => $count];
        }

        return $this->respond([
            'success' => true,
            'recruiter' => [
                'id' => (string)$recruiter['id'],
            'full_name' => $recruiter['recruiter_full_name'] ?? $recruiter['name'],
            'company_name' => $recruiter['company_name'],
            'email' => $recruiter['email'],
            'phone' => $recruiter['recruiter_phone'] ?? $recruiter['phone'] ?? '',
            'designation' => $recruiter['recruiter_designation'] ?? '',
            'company_id' => (string)$recruiter['company_id'],
            'company_logo' => $companyLogo,
            'is_verified' => !empty($recruiter['email_verified_at']),
        ],
            'stats' => [
                'open_jobs' => $openJobs,
                'total_applications' => $totalApps,
                'interview_bookings' => $interviewBookings,
                'conversion_rate' => $conversionRate . '%',
                'time_to_hire' => $timeToHire,
                'need_review' => $pipeline['Applied'] + $pipeline['Screening'],
                'application_trends' => $trends
            ],
            'pipeline_stats' => $pipeline
        ]);
    }

    public function getJobs()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $query       = $this->request->getVar('q');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $jobModel = new JobModel();
        $appModel = new ApplicationModel();

        $builder = $jobModel->where('recruiter_id', $recruiterId);
        if (!empty($query)) {
            $builder->like('title', $query);
        }
        $jobs = $builder->orderBy('created_at', 'DESC')->findAll();

        $formattedJobs = [];
        foreach ($jobs as $job) {
            // Fetch Pipeline Stats for each job
            $pipeline = $this->emptyPipelineStats();

            $apps = $appModel->select('status, COUNT(*) as count')
                ->where('job_id', $job['id'])
                ->groupBy('status')
                ->get()->getResultArray();

            foreach ($apps as $row) {
                $status = $this->formatApplicationStatus((string) $row['status']);
                if (isset($pipeline[$status])) $pipeline[$status] += (int)$row['count'];
            }

            $formattedJobs[] = [
                'job_id'    => (string)$job['id'],
                'recruiter_id' => (string)$job['recruiter_id'],
                'company_id'   => (string)$job['company_id'],
                'job_title' => $job['title'],
                'location'  => $job['location'],
                'job_type'  => $job['employment_type'],
                'work_mode' => 'Onsite', // Default if missing in DB
                'experience_required' => $job['experience_level'],
                'salary_range' => $job['salary_range'],
                'job_description' => $job['description'],
                'job_status'    => $job['status'],
                'pipeline'  => $pipeline,
                'applications_count' => (int)$appModel->where('job_id', $job['id'])->countAllResults(),
                'shortlisted_count' => (int)$appModel->where('job_id', $job['id'])->where('status', 'shortlisted')->countAllResults(),
                'created_at'=> $job['created_at'],
                'category' => $job['category'],
                'required_skills' => $job['required_skills'],
                'posted_for' => $job['posted_for'] ?? 'own_company',
                'client_company_name' => $job['client_company_name'],
                'client_disclosure' => $job['client_disclosure'] ?? 'visible',
                'payroll_type' => $job['payroll_type'] ?? '',
                'application_deadline' => $job['application_deadline'],
                'openings' => (int)($job['openings'] ?? 1),
                'ai_interview_policy' => $job['ai_interview_policy'] ?? 'REQUIRED_HARD',
                'min_ai_cutoff_score' => (int)($job['min_ai_cutoff_score'] ?? 0),
                'application_questionnaire' => $job['application_questionnaire'],
            ];
        }

        return $this->respond([
            'success' => true,
            'jobs'    => $formattedJobs
        ]);
    }

    public function getApplications()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $jobId       = (int)$this->request->getVar('job_id');
        $stage       = $this->request->getVar('stage');
        $query       = $this->request->getVar('q');

        $skillsFilter = $this->request->getVar('skills');
        $locationFilter = $this->request->getVar('location');
        $experienceFilter = $this->request->getVar('experience');
        $lastActiveFilter = $this->request->getVar('last_active');
        $atsMin = $this->request->getVar('ats_min');
        $atsMax = $this->request->getVar('ats_max');
        $sortFilter = $this->request->getVar('sort');

        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $appModel = new ApplicationModel();
        $db = \Config\Database::connect();
        $hasInterviewSessions = $db->tableExists('interview_sessions');
        $ratingSelect = $hasInterviewSessions
            ? 'MAX(COALESCE(interview_sessions.overall_rating, 0)) as overall_rating'
            : '0 as overall_rating';

        $experienceSubQuery = '(SELECT user_id, SUM(TIMESTAMPDIFF(MONTH, start_date, COALESCE(NULLIF(end_date, \'\'), CURDATE()))) AS total_experience_months FROM work_experiences GROUP BY user_id) candidate_experience';

        $appsBuilder = $appModel->select('
                applications.*,
                users.name as candidate_name,
                users.email as candidate_email,
                jobs.title as job_title,
                jobs.recruiter_id,
                jobs.company_id,
                jobs.required_skills,
                jobs.experience_level,
                jobs.location as job_location,
                candidate_profiles.key_skills,
                candidate_profiles.resume_path,
                candidate_profiles.location as candidate_location,
                candidate_profiles.is_fresher_candidate,
                COALESCE(candidate_experience.total_experience_months, 0) as total_experience_months,
                ' . $ratingSelect . '
            ')
            ->join('users', 'users.id = applications.candidate_id')
            ->join('jobs', 'jobs.id = applications.job_id')
            ->join('candidate_profiles', 'candidate_profiles.user_id = applications.candidate_id', 'left')
            ->join($experienceSubQuery, 'candidate_experience.user_id = applications.candidate_id', 'left', false);

        if ($hasInterviewSessions) {
            $appsBuilder->join('interview_sessions', 'interview_sessions.application_id = applications.id', 'left');
        }

        if (!empty($skillsFilter)) {
            $appsBuilder->join('candidate_skills', 'candidate_skills.candidate_id = applications.candidate_id', 'left')
                        ->like('candidate_skills.skill_name', $skillsFilter);
        }

        if (!empty($locationFilter)) {
            $appsBuilder->like('candidate_profiles.location', $locationFilter);
        }

        if (!empty($experienceFilter)) {
            preg_match('/\d+(\.\d+)?/', $experienceFilter, $matches);
            if (!empty($matches[0])) {
                $minMonths = (int) round(((float)$matches[0]) * 12);
                $appsBuilder->where('COALESCE(candidate_experience.total_experience_months, 0) >=', $minMonths);
            }
        }

        if (!empty($lastActiveFilter) && $db->tableExists('user_login_performance_logs')) {
            $days = (int)$lastActiveFilter;
            $lastLoginSubQuery = '(SELECT user_id, MAX(login_at) as last_login FROM user_login_performance_logs GROUP BY user_id) last_login_table';
            $appsBuilder->join($lastLoginSubQuery, 'last_login_table.user_id = applications.candidate_id', 'left', false);
            $appsBuilder->where('last_login_table.last_login >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)', null, false);
        }

        $appsBuilder->where('jobs.recruiter_id', $recruiterId)
            ->groupBy('applications.id');

        if ($jobId > 0) {
            $appsBuilder->where('applications.job_id', $jobId);
        }

        if (!empty($stage) && strtolower($stage) !== 'all') {
            $stageLower = strtolower($stage);
            if ($stageLower === 'interview') {
                $appsBuilder->whereIn('applications.status', ['interview', 'interview_scheduled', 'interview_slot_booked']);
            } elseif ($stageLower === 'offer') {
                $appsBuilder->whereIn('applications.status', ['offer', 'offered', 'selected']);
            } elseif ($stageLower === 'on hold') {
                $appsBuilder->whereIn('applications.status', ['hold', 'on_hold']);
            } elseif ($stageLower === 'screening') {
                $appsBuilder->whereIn('applications.status', ['ai_interview_started', 'ai_interview_completed', 'ai_evaluated']);
            } else {
                $appsBuilder->groupStart()
                    ->where('applications.status', $stage)
                    ->orWhere('applications.status', $stageLower)
                    ->groupEnd();
            }
        }

        if (!empty($query)) {
            $appsBuilder->groupStart()
                ->like('users.name', $query)
                ->orLike('users.email', $query)
                ->orLike('candidate_profiles.key_skills', $query)
                ->groupEnd();
        }

        $apps = $appsBuilder->orderBy('applications.applied_at', 'DESC')->findAll();

        $formattedApps = [];
        foreach ($apps as $app) {
            $skills = $this->splitSkills((string) ($app['key_skills'] ?? ''));

            $candForAts = $app;
            $candForAts['candidate_skills'] = $this->getLeaderboardCandidateSkills($app['candidate_id']);
            $candForAts['required_skills'] = $app['required_skills'];
            $candForAts['experience_level'] = $app['experience_level'];
            $atsScore = $this->calculateLeaderboardAtsScore($candForAts);

            $formattedApps[] = [
                'application_id' => (string)$app['id'],
                'candidate_id'   => (string)$app['candidate_id'],
                'candidate_name' => $app['candidate_name'],
                'candidate_email'=> $app['candidate_email'],
                'job_id'         => (string)$app['job_id'],
                'recruiter_id'   => (string)$app['recruiter_id'],
                'company_id'     => (string)$app['company_id'],
                'job_title'      => $app['job_title'],
                'status'         => $this->formatApplicationStatus((string) $app['status']),
                'status_key'     => $this->normalizeApplicationStatus((string) $app['status']),
                'match_score'    => (string)$atsScore,
                'experience'     => $app['is_fresher_candidate'] ? 'Fresher' : 'Experienced',
                'skills'         => $skills,
                'resume_link'    => $app['resume_path'] ?? '',
                'resume_url'     => $this->toPublicUrl((string) ($app['resume_path'] ?? '')),
                'location'       => $app['candidate_location'] ?: ($app['job_location'] ?? ''),
                'applied_at'     => $app['applied_at'],
            ];
        }

        // Filter by ATS Score in PHP
        if ($atsMin !== null && $atsMin !== '' || $atsMax !== null && $atsMax !== '') {
            $formattedApps = array_values(array_filter($formattedApps, function ($app) use ($atsMin, $atsMax) {
                $score = (int)($app['match_score'] ?? 0);
                if ($atsMin !== null && $atsMin !== '' && $score < (int)$atsMin) return false;
                if ($atsMax !== null && $atsMax !== '' && $score > (int)$atsMax) return false;
                return true;
            }));
        }

        // Sort in PHP
        if (!empty($sortFilter)) {
            if ($sortFilter === 'ats_desc') {
                usort($formattedApps, fn($a, $b) => (int)$b['match_score'] <=> (int)$a['match_score']);
            } elseif ($sortFilter === 'ats_asc') {
                usort($formattedApps, fn($a, $b) => (int)$a['match_score'] <=> (int)$b['match_score']);
            } elseif ($sortFilter === 'applied_desc') {
                usort($formattedApps, fn($a, $b) => strcmp($b['applied_at'], $a['applied_at']));
            }
        }

        // Calculate pipeline stats
        $pipelineStats = $this->emptyPipelineStats();
        $statsBuilder = $appModel->select('applications.status, COUNT(*) as count')
            ->join('jobs', 'jobs.id = applications.job_id')
            ->where('jobs.recruiter_id', $recruiterId);
        if ($jobId > 0) {
            $statsBuilder->where('applications.job_id', $jobId);
        }
        $statsData = $statsBuilder->groupBy('applications.status')->get()->getResultArray();
        foreach ($statsData as $row) {
            $formattedStatus = $this->formatApplicationStatus((string) $row['status']);
            if (isset($pipelineStats[$formattedStatus])) {
                $pipelineStats[$formattedStatus] += (int)$row['count'];
            }
        }

        return $this->respond([
            'success'        => true,
            'applications'   => $formattedApps,
            'pipeline_stats' => $pipelineStats
        ]);
    }

    public function getInterviews()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $jobId = (int) $this->request->getVar('job_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        // Fetch Booked Interviews
        $bookingModel = new \App\Models\InterviewBookingModel();
        $bookingsQuery = $bookingModel->select('
                interview_bookings.*, 
                users.name as candidate_name, 
                users.email as candidate_email,
                jobs.title as job_title, 
                interview_slots.slot_date, 
                interview_slots.slot_time,
                interview_bookings.calendar_html_link, 
                interview_bookings.calendar_add_link
            ')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->join('interview_slots', 'interview_slots.id = interview_bookings.slot_id', 'left')
            ->where('jobs.recruiter_id', $recruiterId);

        if ($jobId > 0) {
            $bookingsQuery->where('interview_bookings.job_id', $jobId);
        } else {
            $bookingsQuery->where('interview_bookings.slot_datetime >=', date('Y-m-d 00:00:00'));
        }

        $bookings = $bookingsQuery->orderBy('interview_bookings.slot_datetime', 'ASC')->findAll();

        $formattedInterviews = [];
        foreach ($bookings as $b) {
            $meetingLink = $b['calendar_html_link'] ?: $b['calendar_add_link'] ?: '';
            $status = !empty($b['booking_status']) ? ucwords(str_replace('_', ' ', $b['booking_status'])) : 'Booked';
            
            $formattedInterviews[] = [
                'id' => (string)$b['id'],
                'candidate_id' => (string)$b['user_id'],
                'candidate_name' => $b['candidate_name'] ?: 'Unknown Candidate',
                'candidate_email' => $b['candidate_email'] ?: '',
                'job_title' => $b['job_title'],
                // Dashboard keys
                'interview_date' => $b['slot_datetime'],
                'interview_type' => $status,
                'interview_mode' => !empty($meetingLink) ? 'Online' : 'Offline',
                'meeting_link' => $meetingLink,
                'is_booked' => true,
                // Detail screen keys
                'slot_date' => $b['slot_date'],
                'slot_time' => $b['slot_time'],
                'slot_datetime' => $b['slot_datetime'],
                'booking_status' => $b['booking_status'] ?: 'booked',
                'calendar_html_link' => $b['calendar_html_link'] ?: '',
                'calendar_add_link' => $b['calendar_add_link'] ?: '',
                'created_at' => $b['booked_at'] ?? '',
            ];
        }

        // Fetch Slot Capacity/Interview Slots
        $formattedSlots = [];
        if ($jobId > 0) {
            $slotModel = new \App\Models\InterviewSlotModel();
            $slots = $slotModel->select('interview_slots.*, users.name as created_by_name')
                ->join('users', 'users.id = interview_slots.created_by', 'left')
                ->where('interview_slots.job_id', $jobId)
                ->orderBy('interview_slots.slot_datetime', 'ASC')
                ->findAll();

            foreach ($slots as $s) {
                $formattedSlots[] = [
                    'id' => (string)$s['id'],
                    'slot_date' => $s['slot_date'],
                    'slot_time' => $s['slot_time'],
                    'slot_datetime' => $s['slot_datetime'],
                    'capacity' => (int)$s['capacity'],
                    'booked_count' => (int)$s['booked_count'],
                    'status' => ($s['is_available'] ?? 1) ? 'Available' : 'Unavailable',
                    'created_by_name' => $s['created_by_name'] ?: 'System',
                ];
            }
        }

        return $this->respond([
            'success'    => true,
            'interviews' => $formattedInterviews,
            'slots'      => $formattedSlots,
        ]);
    }

    public function getNotifications()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $notifyModel = new NotificationModel();
        $notifications = $notifyModel->where('user_id', $recruiterId)->orderBy('created_at', 'DESC')->findAll();

        return $this->respond([
            'success'       => true,
            'notifications' => $notifications
        ]);
    }

    public function markNotificationRead()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $notificationId = $this->request->getVar('notification_id');

        if (!$recruiterId || !$notificationId) return $this->fail('ID required');

        $notifyModel = new NotificationModel();

        if ($notificationId === 'all') {
            $notifyModel->where('user_id', $recruiterId)->update(null, ['is_read' => 1]);
        } else {
            $notifyModel->where('id', $notificationId)->where('user_id', $recruiterId)->update(null, ['is_read' => 1]);
        }

        return $this->respond([
            'success' => true,
            'message' => 'Notification(s) marked as read'
        ]);
    }

    public function deleteNotification()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $notificationId = $this->request->getVar('notification_id');

        if (!$recruiterId || !$notificationId) return $this->fail('ID required');

        $notifyModel = new NotificationModel();

        if ($notificationId === 'all') {
            $notifyModel->where('user_id', $recruiterId)->delete();
        } else {
            $notifyModel->where('id', $notificationId)->where('user_id', $recruiterId)->delete();
        }

        return $this->respond([
            'success' => true,
            'message' => 'Notification(s) deleted successfully'
        ]);
    }

    public function getCompany()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $company = null;
        if (!empty($recruiter['company_id'])) {
            $companyModel = new CompanyModel();
            $company = $companyModel->find((int)$recruiter['company_id']);
        }

        if ($company) {
            $companyLogo = trim((string) ($company['logo'] ?? ''));
            if ($companyLogo !== '' && !preg_match('/^https?:\/\//i', $companyLogo)) {
                $companyLogo = base_url(ltrim($companyLogo, '/'));
            }
            $company['company_logo'] = $companyLogo;

            $workplacePhotos = [];
            $photosRaw = $company['workplace_photos'] ?? '';
            if (is_string($photosRaw) && trim($photosRaw) !== '') {
                $decoded = json_decode($photosRaw, true);
                if (is_array($decoded)) {
                    foreach ($decoded as $p) {
                        $p = trim((string) $p);
                        if ($p === '') continue;
                        if (!preg_match('/^https?:\/\//i', $p)) {
                            $p = base_url(ltrim($p, '/'));
                        }
                        $workplacePhotos[] = $p;
                    }
                }
            }
            $company['workplace_photos_urls'] = $workplacePhotos;

            // Map database fields to keys expected by the mobile client:
            $company['company_name'] = $company['name'] ?? '';
            $company['careers_page_url'] = $company['career_page'] ?? '';
            $company['company_size'] = $company['size'] ?? '';
            $company['hq_location'] = $company['hq'] ?? '';
            $company['branch_locations'] = $company['branches'] ?? '';
            $company['about_company'] = $company['what_we_do'] ?? '';
            $company['linkedin_url'] = $company['linkedin'] ?? '';
            $company['twitter_url'] = $company['twitter'] ?? '';
            $company['facebook_url'] = $company['facebook'] ?? '';
            $company['instagram_url'] = $company['instagram'] ?? '';
            $company['youtube_url'] = $company['youtube'] ?? '';
            $company['culture_environment'] = $company['culture_summary'] ?? '';
            $company['hr_support_email'] = $company['contact_email'] ?? '';
            $company['recruiter_phone'] = $company['contact_phone'] ?? '';
            $company['public_contact_visibility'] = $company['contact_public'] ?? 0;
            $company['company_id'] = $company['id'];
        }

        return $this->respond([
            'success' => true,
            'company' => $company
        ]);
    }

    public function getProfile()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->findRecruiterWithProfile((int)$recruiterId);

        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        return $this->respond([
            'success' => true,
            'recruiter' => [
                'id' => (string)$recruiter['id'],
                'full_name' => $recruiter['recruiter_full_name'] ?? $recruiter['name'],
                'company_name' => $recruiter['company_name'],
                'email' => $recruiter['email'],
                'phone' => $recruiter['recruiter_phone'] ?? $recruiter['phone'] ?? '',
                'designation' => $recruiter['recruiter_designation'] ?? '',
                'company_id' => (string)$recruiter['company_id'],
                'company_location' => $recruiter['location'] ?? '',
                'website' => $recruiter['website'] ?? '',
            ]
        ]);
    }

    public function updateProfile()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $data = [
            'name'  => $this->request->getVar('full_name'),
            'phone' => $this->request->getVar('phone'),
        ];

        if ($userModel->update($recruiterId, $data)) {
            // Also update recruiter profile
            $userModel->upsertRecruiterProfile((int)$recruiterId, [
                'full_name'   => $this->request->getVar('full_name'),
                'phone'       => $this->request->getVar('phone'),
                'designation' => $this->request->getVar('designation'),
            ]);

            return $this->respond([
                'success' => true,
                'message' => 'Profile updated successfully',
                'recruiter' => $userModel->findRecruiterWithProfile((int)$recruiterId)
            ]);
        }

        return $this->fail('Failed to update profile');
    }

    public function addJob()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->findRecruiterWithProfile((int)$recruiterId) ?? $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $companyModel = new CompanyModel();
        $jobModel = new JobModel();

        $title = trim((string) $this->request->getVar('title'));
        $category = trim((string) $this->request->getVar('category'));
        $description = trim((string) $this->request->getVar('description'));
        $location = trim((string) $this->request->getVar('location'));
        $requiredSkills = trim((string) $this->request->getVar('required_skills'));
        $experienceLevel = trim((string) $this->request->getVar('experience_level'));
        $employmentType = trim((string) $this->request->getVar('employment_type'));
        $salaryRange = trim((string) $this->request->getVar('salary_range'));
        $applicationDeadlineRaw = trim((string) $this->request->getVar('application_deadline'));
        $postedFor = trim((string) $this->request->getVar('posted_for'));
        $clientCompanyName = trim((string) $this->request->getVar('client_company_name'));
        $clientDisclosure = trim((string) $this->request->getVar('client_disclosure'));
        $payrollType = trim((string) $this->request->getVar('payroll_type'));
        $aiInterviewPolicy = JobModel::normalizeAiPolicy($this->request->getVar('ai_interview_policy'));
        $minAiCutoffRaw = trim((string) $this->request->getVar('min_ai_cutoff_score'));
        $minAiCutoff = $minAiCutoffRaw === '' ? null : (int) $minAiCutoffRaw;
        $openings = (int) $this->request->getVar('openings');
        
        $questionnaireRaw = $this->request->getVar('questionnaire');
        $questionnaireArray = is_string($questionnaireRaw) ? json_decode($questionnaireRaw, true) : $questionnaireRaw;
        if (!is_array($questionnaireArray)) $questionnaireArray = [];

        $companyId = (int) ($recruiter['company_id'] ?? 0);
        $companyRow = $companyId > 0 ? $companyModel->find($companyId) : null;
        $company = trim((string) ($companyRow['name'] ?? ($recruiter['company_name'] ?? '')));

        if ($title === '' || $category === '' || $description === '' || $location === '') {
            return $this->fail('Title, category, description and location are required.');
        }

        if ($company === '') {
            return $this->fail('Please set your company name in Company Profile before posting jobs.');
        }

        if ($openings <= 0) {
            return $this->fail('Openings must be greater than 0.');
        }

        if ($postedFor === 'client' && $clientCompanyName === '') {
            return $this->fail('Client company name is required when posting for a client.');
        }

        if ($aiInterviewPolicy !== JobModel::AI_POLICY_OFF) {
            if ($minAiCutoff === null) {
                return $this->fail('Minimum AI cutoff score is required when AI interview is enabled.');
            }
            if ($minAiCutoff < 0 || $minAiCutoff > 100) {
                return $this->fail('Minimum AI cutoff score must be between 0 and 100.');
            }
        } else {
            $minAiCutoff = 0;
        }

        $applicationDeadline = null;
        if ($applicationDeadlineRaw !== '') {
            $parsedDate = \DateTime::createFromFormat('Y-m-d', $applicationDeadlineRaw);
            if ($parsedDate) {
                $applicationDeadline = $parsedDate->format('Y-m-d');
            }
        }

        $data = [
            'title' => $title,
            'category' => $category,
            'company_id' => $companyId > 0 ? $companyId : null,
            'company' => $company,
            'description' => $description,
            'location' => $location,
            'required_skills' => $requiredSkills,
            'experience_level' => $experienceLevel,
            'employment_type' => $employmentType !== '' ? $employmentType : null,
            'salary_range' => $salaryRange !== '' ? $salaryRange : null,
            'application_deadline' => $applicationDeadline,
            'posted_for' => in_array($postedFor, ['own_company', 'client']) ? $postedFor : 'own_company',
            'client_company_name' => $postedFor === 'client' ? $clientCompanyName : null,
            'client_disclosure' => in_array($clientDisclosure, ['visible', 'confidential']) ? $clientDisclosure : 'visible',
            'payroll_type' => in_array($payrollType, ['company_payroll', 'client_payroll', 'consultancy_payroll', 'third_party_contract']) ? $payrollType : null,
            'ai_interview_policy' => $aiInterviewPolicy,
            'min_ai_cutoff_score' => $minAiCutoff,
            'openings' => $openings,
            'application_questionnaire' => !empty($questionnaireArray) ? json_encode($questionnaireArray) : null,
            'candidate_fee_allowed' => 0,
            'status' => 'open',
            'recruiter_id' => $recruiterId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        if ($jobModel->insert($data)) {
            $jobId = (int) $jobModel->getInsertID();
            $job = $jobModel->find($jobId);
            if (!empty($job)) {
                (new \App\Libraries\JobAlertService())->processNewJob($job);
            }
            return $this->respondCreated([
                'success' => true,
                'message' => 'Job posted successfully',
                'job_id' => (string)$jobId
            ]);
        }

        return $this->fail('Failed to post job');
    }

    public function updateJob()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $jobId = $this->request->getVar('job_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');
        if (!$jobId) return $this->fail('Job ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->findRecruiterWithProfile((int)$recruiterId) ?? $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $jobModel = new JobModel();
        $job = $jobModel->where('id', $jobId)->where('recruiter_id', $recruiterId)->first();
        if (!$job) {
            return $this->failNotFound('Job not found or access denied');
        }

        $title = trim((string) $this->request->getVar('title'));
        $category = trim((string) $this->request->getVar('category'));
        $description = trim((string) $this->request->getVar('description'));
        $location = trim((string) $this->request->getVar('location'));
        $requiredSkills = trim((string) $this->request->getVar('required_skills'));
        $experienceLevel = trim((string) $this->request->getVar('experience_level'));
        $employmentType = trim((string) $this->request->getVar('employment_type'));
        $salaryRange = trim((string) $this->request->getVar('salary_range'));
        $applicationDeadlineRaw = trim((string) $this->request->getVar('application_deadline'));
        $postedFor = trim((string) $this->request->getVar('posted_for'));
        $clientCompanyName = trim((string) $this->request->getVar('client_company_name'));
        $clientDisclosure = trim((string) $this->request->getVar('client_disclosure'));
        $payrollType = trim((string) $this->request->getVar('payroll_type'));
        $aiInterviewPolicy = JobModel::normalizeAiPolicy($this->request->getVar('ai_interview_policy'));
        $minAiCutoffRaw = trim((string) $this->request->getVar('min_ai_cutoff_score'));
        $minAiCutoff = $minAiCutoffRaw === '' ? null : (int) $minAiCutoffRaw;
        $openings = (int) $this->request->getVar('openings');
        
        $questionnaireRaw = $this->request->getVar('questionnaire');
        $questionnaireArray = is_string($questionnaireRaw) ? json_decode($questionnaireRaw, true) : $questionnaireRaw;
        if (!is_array($questionnaireArray)) $questionnaireArray = [];

        if ($title === '' || $category === '' || $description === '' || $location === '') {
            return $this->fail('Title, category, description and location are required.');
        }

        if ($openings <= 0) {
            return $this->fail('Openings must be greater than 0.');
        }

        if ($postedFor === 'client' && $clientCompanyName === '') {
            return $this->fail('Client company name is required when posting for a client.');
        }

        if ($aiInterviewPolicy !== JobModel::AI_POLICY_OFF) {
            if ($minAiCutoff === null) {
                return $this->fail('Minimum AI cutoff score is required when AI interview is enabled.');
            }
            if ($minAiCutoff < 0 || $minAiCutoff > 100) {
                return $this->fail('Minimum AI cutoff score must be between 0 and 100.');
            }
        } else {
            $minAiCutoff = 0;
        }

        $applicationDeadline = null;
        if ($applicationDeadlineRaw !== '') {
            $parsedDate = \DateTime::createFromFormat('Y-m-d', $applicationDeadlineRaw);
            if ($parsedDate) {
                $applicationDeadline = $parsedDate->format('Y-m-d');
            }
        }

        $data = [
            'title' => $title,
            'category' => $category,
            'description' => $description,
            'location' => $location,
            'required_skills' => $requiredSkills,
            'experience_level' => $experienceLevel,
            'employment_type' => $employmentType !== '' ? $employmentType : null,
            'salary_range' => $salaryRange !== '' ? $salaryRange : null,
            'application_deadline' => $applicationDeadline,
            'posted_for' => in_array($postedFor, ['own_company', 'client']) ? $postedFor : 'own_company',
            'client_company_name' => $postedFor === 'client' ? $clientCompanyName : null,
            'client_disclosure' => in_array($clientDisclosure, ['visible', 'confidential']) ? $clientDisclosure : 'visible',
            'payroll_type' => in_array($payrollType, ['company_payroll', 'client_payroll', 'consultancy_payroll', 'third_party_contract']) ? $payrollType : null,
            'ai_interview_policy' => $aiInterviewPolicy,
            'min_ai_cutoff_score' => $minAiCutoff,
            'openings' => $openings,
            'application_questionnaire' => !empty($questionnaireArray) ? json_encode($questionnaireArray) : null,
        ];

        if ($jobModel->update($jobId, $data)) {
            return $this->respond([
                'success' => true,
                'message' => 'Job updated successfully'
            ]);
        }

        return $this->fail('Failed to update job');
    }

    public function getActivity()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $db = \Config\Database::connect();
        $builder = $db->table('recruiter_candidate_actions');
        $activities = $builder->select('recruiter_candidate_actions.*, users.name as candidate_name, jobs.title as job_title')
            ->join('users', 'users.id = recruiter_candidate_actions.candidate_id')
            ->join('jobs', 'jobs.id = recruiter_candidate_actions.job_id', 'left')
            ->where('recruiter_candidate_actions.recruiter_id', $recruiterId)
            ->orderBy('recruiter_candidate_actions.created_at', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        $formatted = [];
        foreach ($activities as $act) {
            $formatted[] = [
                'action' => ucwords(str_replace('_', ' ', $act['action_type'])),
                'details' => "Candidate: " . $act['candidate_name'] . ($act['job_title'] ? " for " . $act['job_title'] : ""),
                'created_at' => $act['created_at']
            ];
        }

        return $this->respond([
            'success' => true,
            'activity' => $formatted
        ]);
    }

    public function getTeam()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $me = $userModel->find($recruiterId);
        if (!$me) return $this->failNotFound('User not found');

        $team = $userModel->where('company_id', $me['company_id'])
            ->where('role', 'recruiter')
            ->findAll();

        return $this->respond([
            'success' => true,
            'members' => $team,
            'invites' => [] // TODO: Implement invite tracking if needed
        ]);
    }

    public function inviteMember()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $email = $this->request->getVar('email');
        $role = $this->request->getVar('role');

        if (!$email || !$role) return $this->fail('Email and Role required');

        // Logic for inviting members (e.g. email or record in DB)
        // For now, we'll return a success message
        return $this->respond([
            'success' => true,
            'message' => 'Invitation sent to ' . $email
        ]);
    }

    public function updateApplicationStatus()
    {
        $appId = $this->request->getVar('application_id');
        $status = $this->normalizeApplicationStatus((string) $this->request->getVar('status'));
        $recruiterId = $this->request->getVar('recruiter_id');

        if (!$appId || !$status || !$recruiterId) return $this->fail('Application ID, Status and Recruiter ID required');

        $validStatuses = ['applied', 'screening', 'shortlisted', 'interview_slot_booked', 'selected', 'hired', 'hold', 'rejected', 'withdrawn'];
        if (!in_array($status, $validStatuses, true)) {
            return $this->fail('Invalid application status', 422);
        }

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $appModel = new ApplicationModel();
        $app = $appModel
            ->select('applications.*, jobs.company_id, jobs.recruiter_id')
            ->join('jobs', 'jobs.id = applications.job_id')
            ->where('applications.id', (int)$appId)
            ->first();

        if (!$app || (int)$app['recruiter_id'] !== (int)$recruiterId) {
            return $this->failNotFound('Application not found for this recruiter');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $appModel->update((int)$appId, ['status' => $status]);

        $stageModel = new StageHistoryModel();
        $stageModel->moveToStage((int)$appId, $this->formatApplicationStatus($status));

        $updatedApplication = $appModel->find((int) $appId);
        if ($updatedApplication) {
            (new NotificationModel())->triggerApplicationNotifications((int) $app['candidate_id'], $updatedApplication);
        }

        $db->table('recruiter_candidate_actions')->insert([
            'candidate_id' => $app['candidate_id'],
            'recruiter_id' => $recruiterId,
            'application_id' => $appId,
            'job_id' => $app['job_id'],
            'action_type' => 'status_update_' . $status,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->fail('Update failed');
        }

        return $this->respond([
            'success' => true,
            'message' => 'Application status updated to ' . $this->formatApplicationStatus($status),
            'application' => [
                'application_id' => (string)$appId,
                'status' => $this->formatApplicationStatus($status),
                'status_key' => $status,
            ],
        ]);
    }

    private function normalizeApplicationStatus(string $status): string
    {
        $normalized = strtolower(trim(str_replace([' ', '-'], '_', $status)));
        if ($normalized === '') {
            return 'applied';
        }

        return match ($normalized) {
            'interview', 'interview_scheduled', 'interview_slot_booked' => 'interview_slot_booked',
            'offer', 'offered', 'selected' => 'selected',
            'on_hold' => 'hold',
            default => $normalized,
        };
    }

    private function formatApplicationStatus(string $status): string
    {
        $normalized = $this->normalizeApplicationStatus($status);

        return match ($normalized) {
            'interview_slot_booked' => 'Interview',
            'ai_interview_started', 'ai_interview_completed', 'ai_evaluated' => 'Screening',
            'hold' => 'On Hold',
            'selected' => 'Offer',
            default => ucwords(str_replace('_', ' ', $normalized)),
        };
    }

    private function emptyPipelineStats(): array
    {
        return [
            'Applied' => 0,
            'Screening' => 0,
            'Shortlisted' => 0,
            'Interview' => 0,
            'Offer' => 0,
            'Hired' => 0,
            'Rejected' => 0,
            'Withdrawn' => 0,
        ];
    }

    private function toPublicUrl(string $path): string
    {
        $path = trim($path);
        if ($path === '') return '';
        if (preg_match('/^https?:\/\//i', $path)) return $path;
        return base_url(ltrim($path, '/'));
    }

    private function splitSkills(string $skills): array
    {
        $parts = preg_split('/[,|\/]+/', $skills) ?: [];
        return array_values(array_filter(array_map('trim', $parts), static fn($skill) => $skill !== ''));
    }

    private function calculateMobileMatchScore(array $candidateSkills, string $requiredSkills): int
    {
        $candidate = array_map('strtolower', $candidateSkills);
        $required = array_map('strtolower', $this->splitSkills($requiredSkills));
        if (empty($required)) return 0;

        $matched = 0;
        foreach ($required as $skill) {
            if (in_array($skill, $candidate, true)) {
                $matched++;
            }
        }

        return (int) round(($matched / count($required)) * 100);
    }

    public function updateCompanyProfile()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $companyId   = $this->request->getVar('company_id');

        if (!$recruiterId || !$companyId) return $this->fail('Recruiter and Company ID required');

        $companyModel = new CompanyModel();

        // Support both old keys and new keys (exact DB columns)
        $name = $this->request->getVar('name') ?? $this->request->getVar('company_name');
        $website = $this->request->getVar('website');
        $careerPage = $this->request->getVar('career_page') ?? $this->request->getVar('careers_page_url');
        $industry = $this->request->getVar('industry');
        $size = $this->request->getVar('size') ?? $this->request->getVar('company_size');
        $hq = $this->request->getVar('hq') ?? $this->request->getVar('hq_location');
        $branches = $this->request->getVar('branches') ?? $this->request->getVar('branch_locations');
        $shortDescription = $this->request->getVar('short_description');
        $whatWeDo = $this->request->getVar('what_we_do') ?? $this->request->getVar('about_company');
        $linkedin = $this->request->getVar('linkedin') ?? $this->request->getVar('linkedin_url');
        $twitter = $this->request->getVar('twitter') ?? $this->request->getVar('twitter_url');
        $facebook = $this->request->getVar('facebook') ?? $this->request->getVar('facebook_url');
        $instagram = $this->request->getVar('instagram') ?? $this->request->getVar('instagram_url');
        $youtube = $this->request->getVar('youtube') ?? $this->request->getVar('youtube_url');
        $missionValues = $this->request->getVar('mission_values') ?? $this->request->getVar('mission');
        $cultureSummary = $this->request->getVar('culture_summary') ?? $this->request->getVar('culture_environment') ?? $this->request->getVar('culture');
        $employeeBenefits = $this->request->getVar('employee_benefits') ?? $this->request->getVar('benefits');
        $officeTourTitle = $this->request->getVar('office_tour_title');
        $officeTourUrl = $this->request->getVar('office_tour_url');
        $officeTourSummary = $this->request->getVar('office_tour_summary');
        $contactEmail = $this->request->getVar('contact_email') ?? $this->request->getVar('hr_support_email') ?? $this->request->getVar('hr_email');
        $contactPhone = $this->request->getVar('contact_phone') ?? $this->request->getVar('recruiter_phone') ?? $this->request->getVar('phone');
        $contactPublic = $this->request->getVar('contact_public') ?? $this->request->getVar('public_contact_visibility');

        $data = [
            'name'                => $name,
            'website'             => $website,
            'career_page'         => $careerPage,
            'industry'            => $industry,
            'size'                => $size,
            'hq'                  => $hq,
            'branches'            => $branches,
            'short_description'   => $shortDescription,
            'what_we_do'          => $whatWeDo,
            'linkedin'            => $linkedin,
            'twitter'             => $twitter,
            'facebook'            => $facebook,
            'instagram'           => $instagram,
            'youtube'             => $youtube,
            'mission_values'      => $missionValues,
            'culture_summary'     => $cultureSummary,
            'employee_benefits'   => $employeeBenefits,
            'office_tour_title'   => $officeTourTitle,
            'office_tour_url'     => $officeTourUrl,
            'office_tour_summary' => $officeTourSummary,
            'contact_email'       => $contactEmail,
            'contact_phone'       => $contactPhone,
            'contact_public'      => $contactPublic !== null ? ($contactPublic ? 1 : 0) : null,
        ];

        // Filtering out null values to prevent overwriting with null
        $data = array_filter($data, fn($v) => !is_null($v));

        if ($companyModel->update($companyId, $data)) {
            // Sync company name to recruiter profile and jobs if name was updated
            if (!empty($data['name'])) {
                $userModel = new UserModel();
                $userModel->upsertRecruiterProfile((int)$recruiterId, [
                    'company_name' => $data['name'],
                ]);
                model('JobModel')
                    ->where('recruiter_id', $recruiterId)
                    ->set(['company' => $data['name']])
                    ->update();
            }

            return $this->respond([
                'success' => true,
                'message' => 'Company profile updated successfully'
            ]);
        }

        return $this->fail('Update failed');
    }

    public function getSettings()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $profile = $userModel->findRecruiterWithProfile((int)$recruiterId);

        if (!$profile) return $this->failNotFound('Profile not found');

        $prefs = json_decode($profile['notification_preferences'] ?? '{}', true);

        return $this->respond([
            'success' => true,
            'settings' => [
                'workspace_status' => (int)($profile['workspace_status'] ?? 1),
                'application_alerts' => (int)($prefs['application_alerts'] ?? 1),
                'interview_reminders' => (int)($prefs['interview_reminders'] ?? 1),
                'hiring_summary' => (int)($prefs['hiring_summary'] ?? 1),
                'two_factor_auth' => (int)($prefs['two_factor_auth'] ?? 0),
                'language' => $prefs['language'] ?? 'English',
            ]
        ]);
    }

    public function updateSettings()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $db = \Config\Database::connect();
        $userModel = new UserModel();
        $profile = $userModel->findRecruiterWithProfile((int)$recruiterId);

        $data = [];
        if ($this->request->getVar('workspace_status') !== null) {
            $data['workspace_status'] = (int)$this->request->getVar('workspace_status');
        }

        $prefs = json_decode($profile['notification_preferences'] ?? '{}', true);
        if ($this->request->getVar('application_alerts') !== null) $prefs['application_alerts'] = (int)$this->request->getVar('application_alerts');
        if ($this->request->getVar('interview_reminders') !== null) $prefs['interview_reminders'] = (int)$this->request->getVar('interview_reminders');
        if ($this->request->getVar('hiring_summary') !== null) $prefs['hiring_summary'] = (int)$this->request->getVar('hiring_summary');
        if ($this->request->getVar('two_factor_auth') !== null) $prefs['two_factor_auth'] = (int)$this->request->getVar('two_factor_auth');
        if ($this->request->getVar('language') !== null) $prefs['language'] = $this->request->getVar('language');
 
        $data['notification_preferences'] = json_encode($prefs);

        $exists = $db->table('recruiter_profiles')->where('user_id', $recruiterId)->countAllResults() > 0;
        
        $success = false;
        if ($exists) {
            $success = (bool) $db->table('recruiter_profiles')->where('user_id', $recruiterId)->update($data);
        } else {
            $data['user_id'] = $recruiterId;
            $data['full_name'] = $profile['name'] ?? 'Recruiter';
            $success = (bool) $db->table('recruiter_profiles')->insert($data);
        }

        if ($success) {
            return $this->respond([
                'success' => true,
                'message' => 'Settings updated successfully'
            ]);
        }

        return $this->fail('Update failed');
    }

    public function uploadCompanyImage()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $type = $this->request->getVar('type') ?? 'logo';

        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $companyModel = new CompanyModel();
        $company = $companyModel->find($recruiter['company_id']);
        if (!$company) return $this->failNotFound('Company not found');

        $file = $this->request->getFile('photo');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->fail('Invalid or missing image file');
        }

        $allowedTypes = ['image/png', 'image/jpeg', 'image/webp', 'image/gif', 'image/jpg'];
        $allowedExts = ['png', 'jpg', 'jpeg', 'webp', 'gif'];
        $mime = $file->getClientMimeType();
        $ext = strtolower(pathinfo($file->getClientName() ?: '', PATHINFO_EXTENSION) ?: $file->guessExtension() ?: $file->getExtension() ?: '');
        
        $isAllowedMime = in_array($mime, $allowedTypes, true) || $mime === 'application/octet-stream';
        $isAllowedExt = in_array($ext, $allowedExts, true);
        
        if (!$isAllowedMime || !$isAllowedExt) {
            return $this->fail('Only PNG, JPG, WEBP, GIF files allowed');
        }

        if ($type === 'logo') {
            $uploadPath = FCPATH . 'uploads/company_logos/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $newName = $file->getRandomName();
            $file->move($uploadPath, $newName);
            $logoPath = 'uploads/company_logos/' . $newName;
            $companyModel->update($company['id'], ['logo' => $logoPath]);

            return $this->respond([
                'success' => true,
                'message' => 'Company logo uploaded successfully',
                'path' => $logoPath
            ]);
        }

        // Workplace photo upload
        $uploadPath = FCPATH . 'uploads/company_branding/';
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
        $newName = $file->getRandomName();
        $file->move($uploadPath, $newName);
        $photoPath = 'uploads/company_branding/' . $newName;

        $existingPhotos = $this->parseWorkplacePhotos($company['workplace_photos'] ?? null);
        $existingPhotos[] = $photoPath;
        $existingPhotos = array_values(array_unique($existingPhotos));
        $existingPhotos = array_slice($existingPhotos, 0, 6);

        $companyModel->update($company['id'], [
            'workplace_photos' => empty($existingPhotos) ? null : json_encode($existingPhotos)
        ]);

        $allowEmail = (int) ($candidate['job_alert_notify_email'] ?? 1) === 1;
        if ($allowEmail) {
            $this->sendCandidateActionEmail(
                $candidate,
                'Invitation to Apply',
                $message,
                $jobLink
            );
        }

        return $this->respond([
            'success' => true,
            'message' => 'Company workplace photo uploaded successfully',
            'path' => $photoPath
        ]);
    }

    public function deleteCompanyImage()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $photoUrl = $this->request->getVar('photo_url');

        if (!$recruiterId || !$photoUrl) return $this->fail('Recruiter ID and photo URL required');

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $companyModel = new CompanyModel();
        $company = $companyModel->find($recruiter['company_id']);
        if (!$company) return $this->failNotFound('Company not found');

        $relativePath = $this->extractPhotoPath($photoUrl);
        $currentPhotos = $this->parseWorkplacePhotos($company['workplace_photos'] ?? null);
        $updatedPhotos = array_values(array_diff($currentPhotos, [$relativePath]));

        if (count($currentPhotos) === count($updatedPhotos)) {
            return $this->failNotFound('Photo not found on company profile');
        }

        if (!empty($relativePath)) {
            $absoluteFile = FCPATH . $relativePath;
            if (is_file($absoluteFile)) {
                @unlink($absoluteFile);
            }
        }

        $companyModel->update($company['id'], [
            'workplace_photos' => empty($updatedPhotos) ? null : json_encode($updatedPhotos)
        ]);

        $allowEmail = (int) ($candidate['job_alert_notify_email'] ?? 1) === 1;
        if ($allowEmail) {
            $this->sendCandidateActionEmail(
                $candidate,
                'Invitation to Apply',
                $message,
                $jobLink
            );
        }

        return $this->respond([
            'success' => true,
            'message' => 'Company workplace photo deleted successfully'
        ]);
    }

    private function parseWorkplacePhotos($raw): array
    {
        if (is_array($raw)) {
            return $this->normalizePhotoPaths($raw);
        }

        $raw = trim((string) $raw);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $this->normalizePhotoPaths($decoded);
        }

        return $this->normalizePhotoPaths(explode(',', $raw));
    }

    private function normalizePhotoPaths($paths): array
    {
        if (!is_array($paths)) {
            return [];
        }

        $normalized = [];
        foreach ($paths as $path) {
            $trimmed = trim((string) $path);
            if ($trimmed !== '') {
                $normalized[] = $trimmed;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function extractPhotoPath(string $url): string
    {
        $url = trim($url);
        $uploadsPos = stripos($url, 'uploads/');
        if ($uploadsPos !== false) {
            return substr($url, $uploadsPos);
        }

        return ltrim($url, '/');
    }

    public function updateFcmToken()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $fcmToken = $this->request->getVar('fcm_token');

        if (!$recruiterId) {
            return $this->fail('Recruiter ID required');
        }

        $userModel = new UserModel();
        if ($userModel->update($recruiterId, ['fcm_token' => $fcmToken])) {
            return $this->respond([
                'success' => true,
                'message' => 'FCM token updated successfully'
            ]);
        }

        return $this->fail('Failed to update FCM token');
    }

    public function getLeaderboard()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) {
            return $this->fail('Recruiter ID required');
        }

        $jobModel = new JobModel();
        $applicationModel = new ApplicationModel();
        $db = \Config\Database::connect();
        $hasInterviewSessions = $db->tableExists('interview_sessions');

        // Get jobs posted by this recruiter
        $recruiterJobs = $jobModel->where('recruiter_id', $recruiterId)->orderBy('created_at', 'DESC')->findAll();
        $jobIds = array_column($recruiterJobs, 'id');

        if (empty($jobIds)) {
            return $this->respond([
                'success' => true,
                'candidates' => [],
                'skills' => [],
                'jobs' => [],
                'filters' => [
                    'skill' => null,
                    'sort_by' => 'technical_score',
                    'job_id' => null
                ],
                'metrics' => [
                    'avg_technical_score' => 0,
                    'avg_communication_score' => 0,
                    'avg_overall_rating' => 0,
                    'avg_ats_score' => 0
                ]
            ]);
        }

        // Get filters
        $skill = $this->request->getVar('skill');
        $sortBy = $this->request->getVar('sort_by') ?? 'technical_score';
        $jobId = $this->request->getVar('job_id') ? (int) $this->request->getVar('job_id') : null;

        // Build query
        $experienceSubQuery = '(SELECT user_id, SUM(TIMESTAMPDIFF(MONTH, start_date, COALESCE(NULLIF(end_date, \'\'), CURDATE()))) AS total_experience_months FROM work_experiences GROUP BY user_id) candidate_experience';

        $ratingSelect = $hasInterviewSessions
            ? 'MAX(COALESCE(interview_sessions.overall_rating, 0)) as overall_rating,
               MAX(COALESCE(interview_sessions.technical_score, 0)) as technical_score,
               MAX(COALESCE(interview_sessions.communication_score, 0)) as communication_score'
            : '0 as overall_rating, 0 as technical_score, 0 as communication_score';

        $builder = $applicationModel
            ->select('applications.*, users.name, users.email, candidate_profiles.resume_path as resume_path, jobs.title as job_title, jobs.required_skills, jobs.experience_level,
                    COALESCE(candidate_experience.total_experience_months, 0) as total_experience_months,
                    ' . $ratingSelect)
            ->join('users', 'users.id = applications.candidate_id', 'left')
            ->join('candidate_profiles', 'candidate_profiles.user_id = applications.candidate_id', 'left')
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->join($experienceSubQuery, 'candidate_experience.user_id = applications.candidate_id', 'left', false)
            ->groupBy('applications.id');

        if ($hasInterviewSessions) {
            $builder->join('interview_sessions', 'interview_sessions.application_id = applications.id', 'left');
        }

        // Filter by recruiter's jobs only
        $builder->whereIn('applications.job_id', $jobIds);

        // Apply job filter
        if ($jobId) {
            $builder->where('applications.job_id', $jobId);
        }

        // Apply skill filter
        if ($skill) {
            $builder->join('candidate_skills', 'candidate_skills.candidate_id = applications.candidate_id', 'left');
            $builder->where("FIND_IN_SET(" . $db->escape($skill) . ", candidate_skills.skill_name) >", 0);
            $builder->groupBy('applications.id');
        }

        // Apply sorting
        if ($sortBy === 'technical_score') {
            $builder->orderBy('technical_score', 'DESC');
        } elseif ($sortBy === 'overall_rating') {
            $builder->orderBy('overall_rating', 'DESC');
        } elseif ($sortBy === 'communication_score') {
            $builder->orderBy('communication_score', 'DESC');
        }
        $builder->orderBy('applications.applied_at', 'DESC');

        // Fetch all matching candidates (no pagination needed or return top 100 for simplicity)
        $candidates = $builder->limit(100)->get()->getResultArray();

        // Calculate rankings and extra attributes
        foreach ($candidates as &$candidate) {
            $candidate['candidate_skills'] = $this->getLeaderboardCandidateSkills($candidate['candidate_id']);
            $candidate['required_skills'] = $this->parseLeaderboardRequiredSkills($candidate['required_skills']);
            $candidate['skill_match'] = $this->calculateLeaderboardSkillMatch(
                $candidate['candidate_skills'],
                $candidate['required_skills']
            );
            $candidate['github_stack'] = $this->getLeaderboardGithubStack((int)$candidate['candidate_id']);
            $candidate['ats_score'] = $this->calculateLeaderboardAtsScore($candidate);
        }

        // Calculate ranks
        $candidates = $this->assignLeaderboardRanks($candidates, $sortBy);

        // Metrics calculations
        $totalCandidates = count($candidates);
        $avgTech = 0;
        $avgComm = 0;
        $avgOverall = 0;
        $atsScores = [];

        if ($totalCandidates > 0) {
            $avgTech = array_sum(array_column($candidates, 'technical_score')) / $totalCandidates;
            $avgComm = array_sum(array_column($candidates, 'communication_score')) / $totalCandidates;
            $avgOverall = array_sum(array_column($candidates, 'overall_rating')) / $totalCandidates;
            $atsScores = array_filter(array_column($candidates, 'ats_score'), function ($score) {
                return $score !== null;
            });
        }
        $avgAts = count($atsScores) > 0 ? array_sum($atsScores) / count($atsScores) : 0;

        // Get unique skills for filtering
        $allSkills = $this->extractLeaderboardAllSkills();

        // Format jobs filter options
        $jobsFilterList = [];
        foreach ($recruiterJobs as $j) {
            $jobsFilterList[] = [
                'id' => (string)$j['id'],
                'title' => $j['title']
            ];
        }

        // Format candidates list response
        $formattedCandidates = [];
        foreach ($candidates as $c) {
            $formattedCandidates[] = [
                'rank' => (int)$c['rank'],
                'application_id' => (string)$c['id'],
                'candidate_id' => (string)$c['candidate_id'],
                'candidate_name' => $c['name'],
                'candidate_email' => $c['email'],
                'job_id' => (string)$c['job_id'],
                'job_title' => $c['job_title'],
                'status' => $c['status'],
                'technical_score' => (float)$c['technical_score'],
                'communication_score' => (float)$c['communication_score'],
                'overall_rating' => (float)$c['overall_rating'],
                'ats_score' => (int)$c['ats_score'],
                'skill_match' => (int)$c['skill_match'],
                'candidate_skills' => $c['candidate_skills'],
                'required_skills' => $c['required_skills'],
                'github_stack' => $c['github_stack'],
                'resume_path' => $c['resume_path'] ?? '',
                'resume_url' => $this->toPublicUrl((string) ($c['resume_path'] ?? ''))
            ];
        }

        return $this->respond([
            'success' => true,
            'candidates' => $formattedCandidates,
            'skills' => $allSkills,
            'jobs' => $jobsFilterList,
            'filters' => [
                'skill' => $skill,
                'sort_by' => $sortBy,
                'job_id' => $jobId ? (string)$jobId : null
            ],
            'metrics' => [
                'avg_technical_score' => round($avgTech, 1),
                'avg_communication_score' => round($avgComm, 1),
                'avg_overall_rating' => round($avgOverall, 1),
                'avg_ats_score' => round($avgAts, 1)
            ]
        ]);
    }

    private function assignLeaderboardRanks($candidates, $sortBy)
    {
        $rank = 1;
        foreach ($candidates as &$candidate) {
            $candidate['rank'] = $rank++;
        }
        return $candidates;
    }

    private function getLeaderboardCandidateSkills($candidateId)
    {
        $db = \Config\Database::connect();
        $row = $db->table('candidate_skills')
            ->where('candidate_id', $candidateId)
            ->get()
            ->getRowArray();
        if (!$row) return [];
        return array_map('trim', explode(',', $row['skill_name']));
    }

    private function parseLeaderboardRequiredSkills($requiredSkillsJson)
    {
        if (empty($requiredSkillsJson)) {
            return [];
        }
        return array_map('trim', explode(',', $requiredSkillsJson));
    }

    private function calculateLeaderboardSkillMatch($candidateSkills, $requiredSkills)
    {
        if (empty($candidateSkills) || empty($requiredSkills)) {
            return 0;
        }
        $candidateSkillsLower = array_map('strtolower', $candidateSkills);
        $requiredSkillsLower = array_map('strtolower', $requiredSkills);
        $matched = array_intersect($candidateSkillsLower, $requiredSkillsLower);
        return round((count($matched) / count($requiredSkills)) * 100);
    }

    private function getLeaderboardGithubStack(int $candidateId): array
    {
        $db = \Config\Database::connect();
        $row = $db->table('candidate_github_stats')
            ->select('languages_used')
            ->where('candidate_id', $candidateId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getRowArray();

        if (!$row || empty($row['languages_used'])) {
            return [];
        }

        $languages = json_decode($row['languages_used'], true);
        if (is_array($languages)) {
            $values = [];
            foreach ($languages as $key => $value) {
                if (is_string($key) && $key !== '') {
                    $values[] = trim($key);
                    continue;
                }
                if (is_string($value) && trim($value) !== '') {
                    $values[] = trim($value);
                }
            }
            return array_values(array_unique(array_filter($values)));
        }

        $parts = preg_split('/[,|\\/]+/', (string) $row['languages_used']) ?: [];
        $languages = [];
        foreach ($parts as $part) {
            $value = trim($part);
            if ($value !== '') {
                $languages[] = $value;
            }
        }
        return array_values(array_unique($languages));
    }

    private function calculateLeaderboardAtsScore(array $candidate): int
    {
        $requiredSkills = $this->normalizeLeaderboardSkillTokens($candidate['required_skills'] ?? '');
        $requiredMonths = $this->extractLeaderboardRequiredExperienceMonths((string) ($candidate['experience_level'] ?? ''));
        $hasMeaningfulInputs = !empty($requiredSkills) || ($requiredMonths !== null && $requiredMonths > 0);

        if (!$hasMeaningfulInputs) {
            return 0;
        }

        $candidateSkills = $this->normalizeLeaderboardSkillTokens($candidate['candidate_skills'] ?? []);

        if (empty($requiredSkills)) {
            $skillScore = 60;
        } else {
            $matched = 0;
            foreach ($requiredSkills as $requiredSkill) {
                if (in_array($requiredSkill, $candidateSkills, true)) {
                    $matched++;
                }
            }
            $skillScore = (int) round(($matched / max(1, count($requiredSkills))) * 60);
        }

        $candidateMonths = max(0, (int) ($candidate['total_experience_months'] ?? 0));
        if ($requiredMonths === null || $requiredMonths <= 0) {
            $experienceScore = 20;
        } else {
            $experienceScore = (int) round(min(1, $candidateMonths / $requiredMonths) * 20);
        }

        $rating = array_key_exists('overall_rating', $candidate) ? (float) $candidate['overall_rating'] : null;
        if ($rating <= 0) {
            $aiScore = 0;
        } else {
            $aiScore = (int) round(min(10, max(0, $rating)) / 10 * 15);
        }

        $profileScore = !empty($candidate['resume_path']) ? 5 : 0;

        return max(0, min(100, $skillScore + $experienceScore + $aiScore + $profileScore));
    }

    private function normalizeLeaderboardSkillTokens($skills): array
    {
        if (is_array($skills)) {
            $tokens = [];
            foreach ($skills as $skill) {
                $value = strtolower(trim((string) $skill));
                if ($value !== '') {
                    $tokens[] = $value;
                }
            }
            return array_values(array_unique($tokens));
        }

        $parts = preg_split('/[,|\\/]+/', strtolower($skills)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $value = trim($part);
            if ($value !== '') {
                $tokens[] = $value;
            }
        }
        return array_values(array_unique($tokens));
    }

    private function extractLeaderboardRequiredExperienceMonths(string $experienceLevel): ?int
    {
        $value = strtolower(trim($experienceLevel));
        if ($value === '') {
            return null;
        }
        if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (int) round(((float) $matches[1]) * 12);
        }
        if (preg_match('/(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (int) round(((float) $matches[1]) * 12);
        }
        return null;
    }

    private function extractLeaderboardAllSkills()
    {
        $skillModel = model('SkillsModel');
        if (!$skillModel) return [];
        $rows = $skillModel->select('skill_name')->orderBy('skill_name', 'ASC')->findAll();
        $skills = [];
        foreach ($rows as $row) {
            if (!empty($row['skill_name'])) {
                $skills[] = trim($row['skill_name']);
            }
        }
        return $skills;
    }

    public function getCandidateDatabase()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = model('UserModel');
        $jobModel = model('JobModel');

        $filters = [
            'keyword' => trim((string) ($this->request->getVar('keyword') ?? '')),
            'skills' => trim((string) ($this->request->getVar('skills') ?? '')),
            'location' => trim((string) ($this->request->getVar('location') ?? '')),
            'exp_min' => trim((string) ($this->request->getVar('exp_min') ?? '')),
            'exp_max' => trim((string) ($this->request->getVar('exp_max') ?? '')),
            'resume' => trim((string) ($this->request->getVar('resume') ?? '')),
            'job_id' => (int) ($this->request->getVar('job_id') ?? 0),
        ];

        $expMinYears = is_numeric($filters['exp_min']) ? max(0, (float) $filters['exp_min']) : null;
        $expMaxYears = is_numeric($filters['exp_max']) ? max(0, (float) $filters['exp_max']) : null;
        if ($expMinYears !== null && $expMaxYears !== null && $expMinYears > $expMaxYears) {
            [$expMinYears, $expMaxYears] = [$expMaxYears, $expMinYears];
        }
        $expMinMonths = $expMinYears !== null ? (int) round($expMinYears * 12) : null;
        $expMaxMonths = $expMaxYears !== null ? (int) round($expMaxYears * 12) : null;

        if (!in_array($filters['resume'], ['', 'yes', 'no'], true)) {
            $filters['resume'] = '';
        }

        $recruiterJobs = $jobModel
            ->select('id, title, company, category, location, description, required_skills, experience_level, employment_type, status, created_at')
            ->where('recruiter_id', $recruiterId)
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        $selectedJob = null;
        if ($filters['job_id'] > 0) {
            foreach ($recruiterJobs as $job) {
                if ((int) ($job['id'] ?? 0) === $filters['job_id']) {
                    $selectedJob = $job;
                    break;
                }
            }
            if ($selectedJob === null) {
                $filters['job_id'] = 0;
            }
        }

        $experienceSubQuery = '(SELECT user_id, SUM(TIMESTAMPDIFF(MONTH, start_date, COALESCE(NULLIF(end_date, \'\'), CURDATE()))) AS total_experience_months FROM work_experiences GROUP BY user_id) candidate_experience';

        $builder = $userModel
            ->select('users.id, users.name, users.email, candidate_profiles.location as location, candidate_profiles.resume_path as resume_path, candidate_profiles.profile_photo as profile_photo, candidate_profiles.allow_public_recruiter_visibility as allow_public_recruiter_visibility, users.created_at, MAX(candidate_skills.skill_name) as skill_name, COALESCE(candidate_experience.total_experience_months, 0) as total_experience_months')
            ->join('candidate_skills', 'candidate_skills.candidate_id = users.id', 'left')
            ->join('candidate_profiles', 'candidate_profiles.user_id = users.id', 'left')
            ->join($experienceSubQuery, 'candidate_experience.user_id = users.id', 'left', false)
            ->where('users.role', 'candidate')
            ->groupBy('users.id')
            ->orderBy('users.created_at', 'DESC');

        // Apply visibility filter
        $builder->groupStart()
            ->where('candidate_profiles.allow_public_recruiter_visibility', 1)
            ->orWhereIn('users.id', function($subQuery) use ($recruiterId) {
                return $subQuery->select('candidate_id')
                    ->from('applications')
                    ->join('jobs', 'jobs.id = applications.job_id')
                    ->where('jobs.recruiter_id', $recruiterId);
            })
            ->groupEnd();

        if ($filters['keyword'] !== '') {
            $builder->groupStart()
                ->like('users.name', $filters['keyword'])
                ->orLike('users.email', $filters['keyword'])
                ->orLike('candidate_skills.skill_name', $filters['keyword'])
                ->groupEnd();
        }

        if ($filters['skills'] !== '') {
            $builder->like('candidate_skills.skill_name', $filters['skills']);
        }

        if ($filters['location'] !== '') {
            $builder->like('candidate_profiles.location', $filters['location']);
        }

        if ($expMinMonths !== null) {
            $builder->where('COALESCE(candidate_experience.total_experience_months, 0) >= ' . $expMinMonths);
        }

        if ($expMaxMonths !== null) {
            $builder->where('COALESCE(candidate_experience.total_experience_months, 0) <= ' . $expMaxMonths);
        }

        if ($filters['resume'] === 'yes') {
            $builder->where('candidate_profiles.resume_path IS NOT NULL')
                ->where('candidate_profiles.resume_path <>', '');
        } elseif ($filters['resume'] === 'no') {
            $builder->groupStart()
                ->where('candidate_profiles.resume_path IS NULL')
                ->orWhere('candidate_profiles.resume_path =', '')
                ->groupEnd();
        }

        $candidates = $builder->findAll();
        foreach ($candidates as &$candidate) {
            $candidate['experience_display'] = $this->formatExperienceDisplay((int) ($candidate['total_experience_months'] ?? 0));
            if (!empty($candidate['profile_photo']) && !preg_match('/^https?:\/\//i', $candidate['profile_photo'])) {
                $candidate['profile_photo_url'] = base_url(ltrim($candidate['profile_photo'], '/'));
            } else {
                $candidate['profile_photo_url'] = $candidate['profile_photo'] ?? '';
            }
        }
        unset($candidate);

        $aiSuggestions = [];
        if ($selectedJob) {
            $suggestionBuilder = $userModel
                ->select('users.id, users.name, users.email, candidate_profiles.location as location, candidate_profiles.resume_path as resume_path, candidate_profiles.profile_photo as profile_photo, candidate_profiles.allow_public_recruiter_visibility as allow_public_recruiter_visibility, users.created_at, MAX(candidate_skills.skill_name) as skill_name, COALESCE(candidate_experience.total_experience_months, 0) as total_experience_months')
                ->join('candidate_skills', 'candidate_skills.candidate_id = users.id', 'left')
                ->join('candidate_profiles', 'candidate_profiles.user_id = users.id', 'left')
                ->join($experienceSubQuery, 'candidate_experience.user_id = users.id', 'left', false)
                ->where('users.role', 'candidate')
                ->groupBy('users.id')
                ->orderBy('users.created_at', 'DESC');

            $suggestionBuilder->groupStart()
                ->where('candidate_profiles.allow_public_recruiter_visibility', 1)
                ->orWhereIn('users.id', function($subQuery) use ($recruiterId) {
                    return $subQuery->select('candidate_id')
                        ->from('applications')
                        ->join('jobs', 'jobs.id = applications.job_id')
                        ->where('jobs.recruiter_id', $recruiterId);
                })
                ->groupEnd();

            $suggestionBuilder->whereNotIn('users.id', function($subQuery) use ($selectedJob) {
                return $subQuery->select('candidate_id')
                    ->from('applications')
                    ->where('job_id', (int) $selectedJob['id'])
                    ->where('status !=', 'withdrawn');
            });

            if ($filters['keyword'] !== '') {
                $suggestionBuilder->groupStart()
                    ->like('users.name', $filters['keyword'])
                    ->orLike('users.email', $filters['keyword'])
                    ->orLike('candidate_skills.skill_name', $filters['keyword'])
                    ->groupEnd();
            }

            if ($filters['skills'] !== '') {
                $suggestionBuilder->like('candidate_skills.skill_name', $filters['skills']);
            }

            if ($filters['location'] !== '') {
                $suggestionBuilder->like('candidate_profiles.location', $filters['location']);
            }

            if ($expMinMonths !== null) {
                $suggestionBuilder->where('COALESCE(candidate_experience.total_experience_months, 0) >= ' . $expMinMonths);
            }

            if ($expMaxMonths !== null) {
                $suggestionBuilder->where('COALESCE(candidate_experience.total_experience_months, 0) <= ' . $expMaxMonths);
            }

            if ($filters['resume'] === 'yes') {
                $suggestionBuilder->where('candidate_profiles.resume_path IS NOT NULL')
                    ->where('candidate_profiles.resume_path <>', '');
            } elseif ($filters['resume'] === 'no') {
                $suggestionBuilder->groupStart()
                    ->where('candidate_profiles.resume_path IS NULL')
                    ->orWhere('candidate_profiles.resume_path =', '')
                    ->groupEnd();
            }

            $candidatePool = $suggestionBuilder->limit(120)->findAll();
            $atsScoreService = new \App\Libraries\AtsScoreService();
            foreach ($candidatePool as &$poolRow) {
                $poolRow['experience_display'] = $this->formatExperienceDisplay((int) ($poolRow['total_experience_months'] ?? 0));
                $atsAnalysis = $atsScoreService->analyzeCandidateJob((int) ($poolRow['id'] ?? 0), $selectedJob);
                $poolRow['match_score'] = (int) ($atsAnalysis['score'] ?? 0);
                $poolRow['match_reason'] = (string) ($atsAnalysis['match_reason'] ?? 'ATS alignment based on current resume and profile signals.');
                
                if (!empty($poolRow['profile_photo']) && !preg_match('/^https?:\/\//i', $poolRow['profile_photo'])) {
                    $poolRow['profile_photo_url'] = base_url(ltrim($poolRow['profile_photo'], '/'));
                } else {
                    $poolRow['profile_photo_url'] = $poolRow['profile_photo'] ?? '';
                }
            }
            unset($poolRow);

            usort($candidatePool, static fn (array $a, array $b): int => ((int) ($b['match_score'] ?? 0)) <=> ((int) ($a['match_score'] ?? 0)));
            $aiSuggestions = array_values(array_slice(array_filter($candidatePool, static function (array $candidate): bool {
                return (int) ($candidate['match_score'] ?? 0) > 0;
            }), 0, 20));
        }

        return $this->respond([
            'success' => true,
            'candidates' => $candidates,
            'ai_suggestions' => $aiSuggestions,
            'recruiter_jobs' => $recruiterJobs
        ]);
    }

    private function formatExperienceDisplay(int $months): string
    {
        if ($months <= 0) {
            return '-';
        }

        $years = intdiv($months, 12);
        $remainingMonths = $months % 12;

        if ($years > 0 && $remainingMonths > 0) {
            return $years . 'y ' . $remainingMonths . 'm';
        }

        if ($years > 0) {
            return $years . 'y';
        }

        return $remainingMonths . 'm';
    }

    public function inviteCandidate()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $candidateId = $this->request->getVar('candidate_id');
        $jobId = $this->request->getVar('job_id');
        $customMessage = trim((string) ($this->request->getVar('message') ?? ''));

        if (!$recruiterId) return $this->fail('Recruiter ID required');
        if (!$candidateId) return $this->fail('Candidate ID required');
        if (!$jobId) return $this->fail('Job ID required');

        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || ($candidate['role'] ?? '') !== 'candidate') {
            return $this->failNotFound('Candidate not found.');
        }

        // Check recruiter accessibility
        $allowPublic = (int) ($candidate['allow_public_recruiter_visibility'] ?? 1) === 1;
        $hasApplied = false;
        if (!$allowPublic) {
            $application = (new ApplicationModel())
                ->select('applications.id')
                ->join('jobs', 'jobs.id = applications.job_id')
                ->where('applications.candidate_id', $candidateId)
                ->where('jobs.recruiter_id', $recruiterId)
                ->first();
            $hasApplied = !empty($application);
        }

        if (!$allowPublic && !$hasApplied) {
            return $this->failForbidden('This candidate profile is private unless they apply to your jobs.');
        }

        $job = (new JobModel())
            ->where('id', $jobId)
            ->where('recruiter_id', $recruiterId)
            ->where('status', 'open')
            ->first();

        if (!$job) {
            return $this->fail('Select a valid open job before sending an invitation.');
        }

        $existingApplication = (new ApplicationModel())
            ->where('job_id', $jobId)
            ->where('candidate_id', $candidateId)
            ->where('status !=', 'withdrawn')
            ->first();

        if ($existingApplication) {
            return $this->fail('This candidate has already applied for the selected job.');
        }

        if (mb_strlen($customMessage) > 500) {
            return $this->fail('Invitation note is too long. Max 500 characters.');
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('recruiter_job_invitations')) {
            return $this->fail('Invitation tracking is not ready yet. Run the latest migrations first.');
        }

        $invitationModel = new \App\Models\RecruiterJobInvitationModel();
        if ($invitationModel->findActiveInvitation($recruiterId, $candidateId, $jobId)) {
            return $this->fail('An active invitation for this candidate and job already exists.');
        }

        $defaultMessage = "Hi " . ($candidate['name'] ?? 'there') . ", we would love for you to apply to the " . ($job['title'] ?? 'this role') . " position at " . ($job['company'] ?? 'our company') . "!";
        $message = $customMessage !== '' ? $customMessage : $defaultMessage;

        $invitationId = $invitationModel->createInvitation($recruiterId, $candidateId, $jobId, $message);
        $jobLink = base_url('job/' . $jobId . '?invitation=' . $invitationId);

        $notificationModel = new \App\Models\NotificationModel();
        $notificationModel->insert([
            'user_id' => $candidateId,
            'application_id' => null,
            'type' => 'job_invitation',
            'title' => 'Invitation to Apply',
            'message' => $message,
            'action_link' => $jobLink,
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $allowEmail = (int) ($candidate['job_alert_notify_email'] ?? 1) === 1;
        if ($allowEmail) {
            $this->sendCandidateActionEmail(
                $candidate,
                'Invitation to Apply',
                $message,
                $jobLink
            );
        }

        return $this->respond([
            'success' => true,
            'message' => 'Invitation sent successfully.'
        ]);
    }

    public function bulkInviteCandidate()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $candidateIdsRaw = $this->request->getVar('candidate_ids');
        $jobId = $this->request->getVar('job_id');
        $customMessage = trim((string) ($this->request->getVar('message') ?? ''));

        if (!$recruiterId) return $this->fail('Recruiter ID required');
        if (empty($candidateIdsRaw)) return $this->fail('Candidate IDs required');
        if (!$jobId) return $this->fail('Job ID required');

        $candidateIds = is_array($candidateIdsRaw) ? $candidateIdsRaw : explode(',', $candidateIdsRaw);
        $candidateIds = array_map('intval', array_filter($candidateIds));

        if (empty($candidateIds)) {
            return $this->fail('Invalid candidate IDs format');
        }

        $job = (new JobModel())
            ->where('id', $jobId)
            ->where('recruiter_id', $recruiterId)
            ->where('status', 'open')
            ->first();

        if (!$job) {
            return $this->fail('Select a valid open job before sending invitations.');
        }

        if (mb_strlen($customMessage) > 500) {
            return $this->fail('Invitation note is too long. Max 500 characters.');
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('recruiter_job_invitations')) {
            return $this->fail('Invitation tracking is not ready yet. Run the latest migrations first.');
        }

        $userModel = new UserModel();
        $invitationModel = new \App\Models\RecruiterJobInvitationModel();
        $notificationModel = new \App\Models\NotificationModel();
        $applicationModel = new ApplicationModel();

        $successCount = 0;
        $failedCount = 0;
        $firstError = '';

        foreach ($candidateIds as $candidateId) {
            $candidate = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);
            if (!$candidate || ($candidate['role'] ?? '') !== 'candidate') {
                $failedCount++;
                if (!$firstError) $firstError = "Candidate $candidateId not found.";
                continue;
            }

            // Check recruiter accessibility
            $allowPublic = (int) ($candidate['allow_public_recruiter_visibility'] ?? 1) === 1;
            $hasApplied = false;
            if (!$allowPublic) {
                $application = clone $applicationModel;
                $application = $application
                    ->select('applications.id')
                    ->join('jobs', 'jobs.id = applications.job_id')
                    ->where('applications.candidate_id', $candidateId)
                    ->where('jobs.recruiter_id', $recruiterId)
                    ->first();
                $hasApplied = !empty($application);
            }

            $candidateName = $candidate['name'] ?? "Candidate #$candidateId";

            if (!$allowPublic && !$hasApplied) {
                $failedCount++;
                if (!$firstError) $firstError = "$candidateName's profile is private.";
                continue;
            }

            $existingApplication = clone $applicationModel;
            $existingApplication = $existingApplication
                ->where('job_id', $jobId)
                ->where('candidate_id', $candidateId)
                ->where('status !=', 'withdrawn')
                ->first();

            if ($existingApplication) {
                $failedCount++;
                if (!$firstError) $firstError = "$candidateName already applied for this job.";
                continue;
            }

            if ($invitationModel->findActiveInvitation($recruiterId, $candidateId, $jobId)) {
                $failedCount++;
                if (!$firstError) $firstError = "Active invitation for $candidateName already exists.";
                continue;
            }

            $defaultMessage = "Hi " . ($candidate['name'] ?? 'there') . ", we would love for you to apply to the " . ($job['title'] ?? 'this role') . " position at " . ($job['company'] ?? 'our company') . "!";
            $message = $customMessage !== '' ? $customMessage : $defaultMessage;

            $invitationId = $invitationModel->createInvitation($recruiterId, $candidateId, $jobId, $message);
            $jobLink = base_url('job/' . $jobId . '?invitation=' . $invitationId);

            $notificationModel->insert([
                'user_id' => $candidateId,
                'application_id' => null,
                'type' => 'job_invitation',
                'title' => 'Invitation to Apply',
                'message' => $message,
                'action_link' => $jobLink,
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $allowEmail = (int) ($candidate['job_alert_notify_email'] ?? 1) === 1;
            if ($allowEmail) {
                $this->sendCandidateActionEmail(
                    $candidate,
                    'Invitation to Apply',
                    $message,
                    $jobLink
                );
            }

            $successCount++;
        }

        if ($successCount > 0) {
            return $this->respond([
                'success' => true,
                'message' => "Successfully sent $successCount invitation(s)." . ($failedCount > 0 ? " ($failedCount failed: $firstError)" : "")
            ]);
        } else {
            return $this->fail("Failed to send invitations: $firstError");
        }
    }

    public function getCandidateProfile($candidateId)
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new \App\Models\UserModel();
        $candidate = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);

        if (!$candidate || ($candidate['role'] ?? '') !== 'candidate') {
            return $this->failNotFound('Candidate not found');
        }

        // Access check
        $allowPublic = (int) ($candidate['allow_public_recruiter_visibility'] ?? 1) === 1;
        $hasApplied = false;
        if (!$allowPublic) {
            $application = (new \App\Models\ApplicationModel())
                ->select('applications.id')
                ->join('jobs', 'jobs.id = applications.job_id')
                ->where('applications.candidate_id', $candidateId)
                ->where('jobs.recruiter_id', $recruiterId)
                ->first();
            $hasApplied = !empty($application);
        }

        if (!$allowPublic && !$hasApplied) {
            return $this->failForbidden('This candidate profile is private unless they apply to your jobs.');
        }

        $applicationId = (int) ($this->request->getVar('application_id') ?? 0);
        $jobId = (int) ($this->request->getVar('job_id') ?? 0);

        // Resolve application id if job_id is provided but no application_id
        if ($applicationId <= 0 && $jobId > 0) {
            $application = (new \App\Models\ApplicationModel())
                ->select('applications.id')
                ->join('jobs', 'jobs.id = applications.job_id', 'inner')
                ->where('applications.candidate_id', $candidateId)
                ->where('applications.job_id', $jobId)
                ->where('jobs.recruiter_id', $recruiterId)
                ->where('applications.status !=', 'withdrawn')
                ->orderBy('applications.applied_at', 'DESC')
                ->first();
            if ($application) {
                $applicationId = (int) $application['id'];
            }
        }

        // Log action & Notify candidate
        $actionModel = new \App\Models\RecruiterCandidateActionModel();
        $wasLogged = $actionModel->logAction(
            (int) $candidateId,
            (int) $recruiterId,
            \App\Models\RecruiterCandidateActionModel::ACTION_PROFILE_VIEWED,
            $applicationId > 0 ? $applicationId : null,
            $jobId > 0 ? $jobId : null,
            24
        );

        if ($wasLogged) {
            $this->notifyCandidateAction(
                (int) $candidateId,
                $applicationId > 0 ? $applicationId : null,
                'recruiter_profile_viewed',
                'Profile Viewed',
                'One recruiter viewed your profile.'
            );
        }

        // Fetch sub-entities
        $workExpModel = new \App\Models\WorkExperienceModel();
        $educationModel = new \App\Models\EducationModel();
        $certificationModel = new \App\Models\CertificationModel();
        $skillsModel = new \App\Models\CandidateSkillsModel();
        $interestsModel = new \App\Models\CandidateInterestsModel();
        $githubModel = new \App\Models\GithubAnalysisModel();
        $projectModel = new \App\Models\CandidateProjectModel();

        $workExperiences = $workExpModel->getByUser($candidateId);
        $education = $educationModel->getByUser($candidateId);
        $certifications = $certificationModel->getByUser($candidateId);

        $totalExperienceMonths = 0;
        foreach ($workExperiences as $exp) {
            $startDate = new \DateTime($exp['start_date']);
            $endDate = !empty($exp['is_current']) ? new \DateTime() : new \DateTime($exp['end_date']);
            $interval = $startDate->diff($endDate);
            $totalExperienceMonths += ($interval->y * 12) + $interval->m;
        }

        $skills = $skillsModel->where('candidate_id', $candidateId)->first();
        $interestRow = $interestsModel->where('candidate_id', $candidateId)->first();
        $interests = [];
        if ($interestRow && !empty($interestRow['interest'])) {
            $interests = array_values(array_filter(array_map('trim', explode(',', (string) $interestRow['interest']))));
        }
        $github = $githubModel->where('candidate_id', $candidateId)->first();
        
        $db = \Config\Database::connect();
        $projects = $db->tableExists('candidate_projects') ? $projectModel->getByUser((int) $candidateId) : [];

        $messageModel = new \App\Models\RecruiterCandidateMessageModel();
        $messages = $messageModel->getThread((int) $candidateId, (int) $recruiterId, $applicationId > 0 ? $applicationId : null);

        $noteModel = new \App\Models\RecruiterCandidateNoteModel();
        $recruiterNote = $noteModel->getByCandidateAndRecruiter((int) $candidateId, (int) $recruiterId);

        // Format photo/video urls
        if (!empty($candidate['profile_photo']) && !preg_match('/^https?:\/\//i', $candidate['profile_photo'])) {
            $candidate['profile_photo_url'] = base_url(ltrim($candidate['profile_photo'], '/'));
        } else {
            $candidate['profile_photo_url'] = $candidate['profile_photo'] ?? '';
        }

        if (!empty($candidate['intro_video_path']) && !preg_match('/^https?:\/\//i', $candidate['intro_video_path'])) {
            $candidate['intro_video_url'] = base_url(ltrim($candidate['intro_video_path'], '/'));
        } else {
            $candidate['intro_video_url'] = $candidate['intro_video_path'] ?? '';
        }

        // Expose open jobs for recruiter invite option
        $jobModel = new \App\Models\JobModel();
        $recruiterJobs = $jobModel
            ->select('id, title, company, status, created_at')
            ->where('recruiter_id', $recruiterId)
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC')
            ->findAll();

        // Check if there are active invitations
        $jobInvitations = [];
        if ($db->tableExists('recruiter_job_invitations')) {
            $invitationRows = $db->table('recruiter_job_invitations')
                ->where('recruiter_id', $recruiterId)
                ->where('candidate_id', $candidateId)
                ->get()->getResultArray();
            foreach ($invitationRows as $row) {
                $jobInvitations[(int) $row['job_id']] = [
                    'id' => (int) $row['id'],
                    'status' => (string) ($row['status'] ?? 'sent'),
                    'message' => (string) ($row['message'] ?? ''),
                ];
            }
        }

        return $this->respond([
            'success' => true,
            'candidate' => $candidate,
            'work_experiences' => $workExperiences,
            'education' => $education,
            'certifications' => $certifications,
            'skills' => $skills,
            'interests' => $interests,
            'github' => $github,
            'projects' => $projects,
            'messages' => $messages,
            'recruiter_note' => $recruiterNote,
            'total_experience_months' => $totalExperienceMonths,
            'experience_display' => $this->formatExperienceDisplay($totalExperienceMonths),
            'recruiter_jobs' => $recruiterJobs,
            'job_invitations' => $jobInvitations,
            'application_id' => $applicationId,
            'job_id' => $jobId
        ]);
    }

    public function logCandidateAction($candidateId)
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $action = $this->request->getVar('action'); // 'resume' or 'contact'
        $applicationId = (int) ($this->request->getVar('application_id') ?? 0);
        $jobId = (int) ($this->request->getVar('job_id') ?? 0);

        if (!$recruiterId) return $this->fail('Recruiter ID required');
        if (!$action) return $this->fail('Action type required');

        $actionType = null;
        $notifyType = null;
        $title = null;
        $msg = null;

        if ($action === 'resume') {
            $actionType = \App\Models\RecruiterCandidateActionModel::ACTION_RESUME_DOWNLOADED;
            $notifyType = 'recruiter_resume_downloaded';
            $title = 'Resume Downloaded';
            $msg = 'One recruiter downloaded your resume.';
        } elseif ($action === 'contact') {
            $actionType = \App\Models\RecruiterCandidateActionModel::ACTION_CONTACT_VIEWED;
            $notifyType = 'recruiter_contact_viewed';
            $title = 'Contact Viewed';
            $msg = 'One recruiter viewed your contact details.';
        } else {
            return $this->fail('Invalid action type');
        }

        $actionModel = new \App\Models\RecruiterCandidateActionModel();
        $wasLogged = $actionModel->logAction(
            (int) $candidateId,
            (int) $recruiterId,
            $actionType,
            $applicationId > 0 ? $applicationId : null,
            $jobId > 0 ? $jobId : null,
            24
        );

        if ($wasLogged) {
            $this->notifyCandidateAction(
                (int) $candidateId,
                $applicationId > 0 ? $applicationId : null,
                $notifyType,
                $title,
                $msg
            );
        }

        return $this->respond([
            'success' => true,
            'logged' => $wasLogged,
            'message' => 'Action tracked successfully.'
        ]);
    }

    public function sendCandidateMessage($candidateId)
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $message = trim((string) $this->request->getVar('message'));
        $applicationId = (int) ($this->request->getVar('application_id') ?? 0);
        $jobId = (int) ($this->request->getVar('job_id') ?? 0);

        if (!$recruiterId) return $this->fail('Recruiter ID required');
        if ($message === '') return $this->fail('Message cannot be empty');

        $userModel = new \App\Models\UserModel();
        $recruiter = $userModel->findRecruiterWithProfile((int) $recruiterId);
        $recruiterName = !empty($recruiter['name']) ? $recruiter['name'] : 'Recruiter';

        $messageModel = new \App\Models\RecruiterCandidateMessageModel();
        $messageModel->insert([
            'candidate_id' => (int) $candidateId,
            'recruiter_id' => (int) $recruiterId,
            'application_id' => $applicationId > 0 ? $applicationId : null,
            'job_id' => $jobId > 0 ? $jobId : null,
            'sender_id' => (int) $recruiterId,
            'sender_role' => 'recruiter',
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->notifyCandidateAction(
            (int) $candidateId,
            $applicationId > 0 ? $applicationId : null,
            'recruiter_message',
            'Message from Recruiter',
            "{$recruiterName} sent you a message. Open conversation to read it.",
            base_url('candidate/messages/' . (int) $recruiterId . ($applicationId > 0 ? '?application_id=' . $applicationId : ''))
        );

        return $this->respond([
            'success' => true,
            'message' => 'Message sent successfully.'
        ]);
    }

    public function saveCandidateNotes($candidateId)
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $tags = trim((string) $this->request->getVar('tags'));
        $notes = trim((string) $this->request->getVar('notes'));

        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $noteModel = new \App\Models\RecruiterCandidateNoteModel();
        $existing = $noteModel->where('candidate_id', $candidateId)->where('recruiter_id', $recruiterId)->first();

        $data = [
            'candidate_id' => (int) $candidateId,
            'recruiter_id' => (int) $recruiterId,
            'tags' => $this->normalizeTags($tags),
            'notes' => $notes,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $noteModel->update($existing['id'], $data);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $noteModel->insert($data);
        }

        return $this->respond([
            'success' => true,
            'message' => 'Notes saved successfully.'
        ]);
    }

    
    private function normalizeTags(string $rawTags): string
    {
        if ($rawTags === '') return '';
        $parts = preg_split('/[,]+/', $rawTags) ?: [];
        $clean = [];
        foreach ($parts as $part) {
            $tag = trim($part);
            if ($tag === '') continue;
            if (mb_strlen($tag) > 40) $tag = mb_substr($tag, 0, 40);
            $clean[] = $tag;
        }
        return implode(', ', array_unique($clean));
    }

    public function downloadCandidateResume($candidateId)
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new \App\Models\UserModel();
        $candidate = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || ($candidate['role'] ?? '') !== 'candidate') {
            return $this->failNotFound('Candidate not found');
        }

        // Access check
        $allowPublic = (int) ($candidate['allow_public_recruiter_visibility'] ?? 1) === 1;
        $hasApplied = false;
        if (!$allowPublic) {
            $application = (new \App\Models\ApplicationModel())
                ->select('applications.id')
                ->join('jobs', 'jobs.id = applications.job_id')
                ->where('applications.candidate_id', $candidateId)
                ->where('jobs.recruiter_id', $recruiterId)
                ->first();
            $hasApplied = !empty($application);
        }

        if (!$allowPublic && !$hasApplied) {
            return $this->failForbidden('This candidate profile is private unless they apply to your jobs.');
        }

        $applicationId = (int) ($this->request->getVar('application_id') ?? 0);
        $jobId = (int) ($this->request->getVar('job_id') ?? 0);

        $actionModel = new \App\Models\RecruiterCandidateActionModel();
        $wasLogged = $actionModel->logAction(
            (int) $candidateId,
            (int) $recruiterId,
            \App\Models\RecruiterCandidateActionModel::ACTION_RESUME_DOWNLOADED,
            $applicationId > 0 ? $applicationId : null,
            $jobId > 0 ? $jobId : null,
            24
        );

        if ($wasLogged) {
            $this->notifyCandidateAction(
                (int) $candidateId,
                $applicationId > 0 ? $applicationId : null,
                'recruiter_resume_downloaded',
                'Resume Downloaded',
                'One recruiter downloaded your resume.'
            );
        }

        // Check if there is an ATS submitted resume version
        $db = \Config\Database::connect();
        $submittedResumeVersion = null;
        if ($applicationId > 0 && $db->tableExists('candidate_resume_versions') && $db->fieldExists('resume_version_id', 'applications')) {
            $application = (new \App\Models\ApplicationModel())
                ->select('applications.id, applications.resume_version_id')
                ->where('applications.id', $applicationId)
                ->first();
            if ($application && !empty($application['resume_version_id'])) {
                $submittedResumeVersion = (new \App\Models\CandidateResumeVersionModel())->find((int) $application['resume_version_id']);
            }
        }

        if ($submittedResumeVersion) {
            $renderer = new \App\Libraries\ResumeTemplateRenderer();
            $pdfPath = $renderer->createPdfFile((string) ($submittedResumeVersion['content'] ?? ''), [
                'name' => (string) ($candidate['name'] ?? 'Candidate'),
                'target_role' => (string) ($submittedResumeVersion['target_role'] ?? ''),
                'summary' => (string) ($submittedResumeVersion['summary'] ?? ''),
                'highlight_skills' => array_values(array_filter(array_map('trim', explode(',', (string) ($submittedResumeVersion['highlight_skills'] ?? ''))))),
            ], (string) (($candidate['name'] ?? 'candidate') . '-' . ($submittedResumeVersion['target_role'] ?? 'resume')));

            return $this->response->download($pdfPath, null)->setFileName(basename($pdfPath));
        }

        if (empty($candidate['resume_path'])) {
            return $this->fail('Resume file not found.');
        }

        $filePath = WRITEPATH . $candidate['resume_path'];
        if (!is_file($filePath)) {
            $filePath = FCPATH . $candidate['resume_path'];
        }

        if (!is_file($filePath)) {
            return $this->fail('Resume file not found on server.');
        }

        return $this->response->download($filePath, null);
    }

    private function notifyCandidateAction(
        int $candidateId,
        ?int $applicationId,
        string $type,
        string $title,
        string $message,
        ?string $actionLink = null
    ): void {
        $notificationModel = new \App\Models\NotificationModel();
        $notificationModel->insert([
            'user_id' => $candidateId,
            'application_id' => $applicationId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_link' => $actionLink ?? base_url('candidate/applications'),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $emailEligibleTypes = [
            'recruiter_profile_viewed',
            'recruiter_contact_viewed',
            'recruiter_resume_downloaded',
        ];

        if (in_array($type, $emailEligibleTypes, true)) {
            $userModel = new \App\Models\UserModel();
            $candidate = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId) ?? [];
            $allowEmail = (int) ($candidate['job_alert_notify_email'] ?? 1) === 1;
            if ($allowEmail) {
                $this->sendCandidateActionEmail($candidate, $title, $message, $actionLink ?? base_url('candidate/applications'));
            }
        }
    }

    private function sendCandidateActionEmail(array $candidate, string $title, string $message, string $actionLink): void
    {
        $recipient = trim((string) ($candidate['email'] ?? ''));
        if ($recipient === '') {
            return;
        }

        $candidateName = trim((string) ($candidate['name'] ?? 'Candidate'));
        $subject = $title . ' on HireMatrix';

        $body = '
            <div style="margin:0;padding:24px;background:#eef4ff;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
                <div style="max-width:640px;margin:0 auto;background:#ffffff;border-radius:22px;overflow:hidden;box-shadow:0 18px 40px rgba(15,23,42,0.10);">
                    <div style="padding:26px 30px;background:linear-gradient(135deg,#0b66ff 0%,#38bdf8 100%);color:#ffffff;">
                        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;opacity:0.88;margin-bottom:10px;">HireMatrix recruiter activity</div>
                        <h1 style="margin:0;font-size:24px;line-height:1.2;">Activity on your profile</h1>
                    </div>
                    <div style="padding:28px 30px;">
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">Hi ' . esc($candidateName) . ',</p>
                        <p style="margin:0 0 20px;font-size:15px;line-height:1.8;color:#334155;">' . esc($message) . '</p>
                        <a href="' . esc($actionLink) . '" style="display:inline-block;padding:12px 20px;border-radius:999px;background:#0b66ff;color:#ffffff;text-decoration:none;font-weight:700;font-size:14px;">View Details</a>
                    </div>
                </div>
            </div>';

        try {
            $email = \Config\Services::email(null, false);
            $config = config('Email');
            $email->clear(true);
            $email->setMailType('html');

            if ($config->fromEmail !== '') {
                $email->setFrom($config->fromEmail, $config->fromName ?: 'HireMatrix');
            }

            $email->setTo($recipient);
            $email->setSubject($subject);
            $email->setMessage($body);
            $email->send(false);
        } catch (\Throwable $e) {
            log_message('error', 'Candidate action email failed: ' . $e->getMessage());
        }
    }

    public function bulkUpdateApplicationStatus()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $applicationIdsRaw = $this->request->getVar('application_ids');
        $status = $this->normalizeApplicationStatus((string) $this->request->getVar('status'));

        if (!$recruiterId || empty($applicationIdsRaw) || !$status) {
            return $this->fail('Recruiter ID, Application IDs, and Status are required');
        }

        $applicationIds = is_array($applicationIdsRaw) ? $applicationIdsRaw : explode(',', $applicationIdsRaw);
        $applicationIds = array_map('intval', array_filter($applicationIds));

        if (empty($applicationIds)) {
            return $this->fail('No valid Application IDs provided');
        }

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $appModel = new ApplicationModel();
        $stageModel = new StageHistoryModel();
        $notificationModel = new NotificationModel();
        $db = \Config\Database::connect();

        $successCount = 0;
        foreach ($applicationIds as $appId) {
            $app = $appModel
                ->select('applications.*, jobs.recruiter_id')
                ->join('jobs', 'jobs.id = applications.job_id')
                ->where('applications.id', $appId)
                ->first();

            if (!$app || (int)$app['recruiter_id'] !== (int)$recruiterId) {
                continue;
            }

            $db->transStart();
            $appModel->update($appId, ['status' => $status]);
            $stageModel->moveToStage($appId, $this->formatApplicationStatus($status));

            $updatedApplication = $appModel->find($appId);
            if ($updatedApplication) {
                $notificationModel->triggerApplicationNotifications((int) $app['candidate_id'], $updatedApplication);
            }

            $db->table('recruiter_candidate_actions')->insert([
                'candidate_id' => $app['candidate_id'],
                'recruiter_id' => $recruiterId,
                'application_id' => $appId,
                'job_id' => $app['job_id'],
                'action_type' => 'status_update_' . $status,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            $db->transComplete();

            if ($db->transStatus() !== false) {
                $successCount++;
            }
        }

        return $this->respond([
            'success' => true,
            'message' => "Successfully updated $successCount applications to " . $this->formatApplicationStatus($status)
        ]);
    }

    public function bulkSendEmail()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $candidateIdsRaw = $this->request->getVar('candidate_ids');
        $subject = $this->request->getVar('subject');
        $body = $this->request->getVar('body');

        if (!$recruiterId || empty($candidateIdsRaw) || !$subject || !$body) {
            return $this->fail('Recruiter ID, Candidate IDs, Subject, and Body are required');
        }

        $candidateIds = is_array($candidateIdsRaw) ? $candidateIdsRaw : explode(',', $candidateIdsRaw);
        $candidateIds = array_map('intval', array_filter($candidateIds));

        if (empty($candidateIds)) {
            return $this->fail('No valid Candidate IDs provided');
        }

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $emailSentCount = 0;
        foreach ($candidateIds as $candidateId) {
            $candidate = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);
            if (!$candidate) continue;

            $recipient = trim((string) ($candidate['email'] ?? ''));
            if ($recipient === '') continue;

            $actionModel = new \App\Models\RecruiterCandidateActionModel();
            $actionModel->logAction(
                (int) $candidateId,
                (int) $recruiterId,
                'email_sent',
                null,
                null,
                24
            );

            try {
                $email = \Config\Services::email(null, false);
                $config = config('Email');
                $email->clear(true);
                $email->setMailType('html');
                if ($config->fromEmail !== '') {
                    $email->setFrom($config->fromEmail, $config->fromName ?: 'HireMatrix');
                }
                $email->setTo($recipient);
                $email->setSubject($subject);
                $email->setMessage($body);
                if ($email->send(false)) {
                    $emailSentCount++;
                }
            } catch (\Throwable $e) {
                log_message('error', 'Bulk email failed: ' . $e->getMessage());
            }
        }

        return $this->respond([
            'success' => true,
            'message' => "Successfully sent email to $emailSentCount candidates."
        ]);
    }

    public function bulkSendMessage()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $candidateIdsRaw = $this->request->getVar('candidate_ids');
        $message = trim((string) $this->request->getVar('message'));
        $applicationId = (int) ($this->request->getVar('application_id') ?? 0);
        $jobId = (int) ($this->request->getVar('job_id') ?? 0);

        if (!$recruiterId) return $this->fail('Recruiter ID required');
        if (empty($candidateIdsRaw)) return $this->fail('Candidate IDs required');
        if ($message === '') return $this->fail('Message cannot be empty');

        $candidateIds = is_array($candidateIdsRaw) ? $candidateIdsRaw : explode(',', $candidateIdsRaw);
        $candidateIds = array_map('intval', array_filter($candidateIds));

        if (empty($candidateIds)) return $this->fail('No valid Candidate IDs provided');

        $userModel = new \App\Models\UserModel();
        $recruiter = $userModel->findRecruiterWithProfile((int) $recruiterId);
        $recruiterName = !empty($recruiter['name']) ? $recruiter['name'] : 'Recruiter';

        $messageModel = new \App\Models\RecruiterCandidateMessageModel();

        $successCount = 0;
        foreach ($candidateIds as $candidateId) {
            $messageModel->insert([
                'candidate_id' => $candidateId,
                'recruiter_id' => (int) $recruiterId,
                'application_id' => $applicationId > 0 ? $applicationId : null,
                'job_id' => $jobId > 0 ? $jobId : null,
                'sender_id' => (int) $recruiterId,
                'sender_role' => 'recruiter',
                'message' => $message,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $this->notifyCandidateAction(
                (int) $candidateId,
                $applicationId > 0 ? $applicationId : null,
                'recruiter_message',
                'Message from Recruiter',
                "{$recruiterName} sent you a message. Open conversation to read it.",
                base_url('candidate/messages/' . (int) $recruiterId . ($applicationId > 0 ? '?application_id=' . $applicationId : ''))
            );

            $successCount++;
        }

        return $this->respond([
            'success' => true,
            'message' => "Successfully sent message to $successCount candidates."
        ]);
    }

    public function addInterviewSlot()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $jobId = $this->request->getVar('job_id');
        $startDate = $this->request->getVar('start_date');
        $endDate = $this->request->getVar('end_date');
        $timesRaw = $this->request->getVar('times');
        $times = [];
        if (is_string($timesRaw)) {
            if (strpos($timesRaw, '[') === 0 && substr($timesRaw, -1) === ']') {
                $timesRaw = trim($timesRaw, '[]');
                $times = array_filter(array_map('trim', explode(',', $timesRaw)));
            } else {
                $decoded = json_decode($timesRaw, true);
                $times = is_array($decoded) ? $decoded : [$timesRaw];
            }
        } elseif (is_array($timesRaw)) {
            $times = $timesRaw;
        }
        $capacity = (int) ($this->request->getVar('capacity') ?? 1);
        $excludeWeekends = $this->request->getVar('exclude_weekends') == '1';

        if (!$recruiterId || !$jobId || !$startDate || empty($times)) {
            return $this->fail('Missing required fields');
        }

        $slotModel = new \App\Models\InterviewSlotModel();
        
        $dates = [];
        $current = strtotime($startDate);
        $end = $endDate ? strtotime($endDate) : $current;

        while ($current <= $end) {
            $dayOfWeek = date('N', $current);
            if ($excludeWeekends && ($dayOfWeek == 6 || $dayOfWeek == 7)) {
                $current = strtotime('+1 day', $current);
                continue;
            }
            $dates[] = date('Y-m-d', $current);
            $current = strtotime('+1 day', $current);
        }

        $totalCreated = 0;
        foreach ($dates as $date) {
            $created = $slotModel->createBulkSlots(
                (int) $jobId,
                $date,
                $times,
                (int) $recruiterId,
                $capacity
            );
            $totalCreated += $created;
        }

        return $this->respond([
            'success' => true,
            'message' => "Successfully created {$totalCreated} interview slots"
        ]);
    }

    public function updateInterviewSlot()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $slotId = $this->request->getVar('slot_id');
        $date = $this->request->getVar('slot_date');
        $time = $this->request->getVar('slot_time');
        $capacity = (int) ($this->request->getVar('capacity') ?? 1);

        if (!$recruiterId || !$slotId || !$date || !$time) {
            return $this->fail('Missing required fields');
        }

        $slotModel = new \App\Models\InterviewSlotModel();
        $slot = $slotModel->find($slotId);

        if (!$slot) {
            return $this->failNotFound('Slot not found');
        }

        if ($slot['booked_count'] > 0 && $capacity < $slot['booked_count']) {
            return $this->fail('Cannot reduce capacity below current bookings');
        }

        $dbTime = substr($slot['slot_time'], 0, 5);
        $inputTime = substr($time, 0, 5);

        if ($slot['booked_count'] > 0 && ($date !== $slot['slot_date'] || $inputTime !== $dbTime)) {
            return $this->fail('Cannot edit time/date for slot with existing bookings');
        }

        $slotModel->update($slotId, [
            'slot_date' => $date,
            'slot_time' => $time,
            'slot_datetime' => $date . ' ' . $time,
            'capacity' => $capacity
        ]);

        $allowEmail = (int) ($candidate['job_alert_notify_email'] ?? 1) === 1;
        if ($allowEmail) {
            $this->sendCandidateActionEmail(
                $candidate,
                'Invitation to Apply',
                $message,
                $jobLink
            );
        }

        return $this->respond([
            'success' => true,
            'message' => 'Slot updated successfully'
        ]);
    }

    public function deleteInterviewSlot()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $slotId = $this->request->getVar('slot_id');

        if (!$recruiterId || !$slotId) {
            return $this->fail('Missing required fields');
        }

        $slotModel = new \App\Models\InterviewSlotModel();
        $slot = $slotModel->find($slotId);

        if (!$slot) {
            return $this->failNotFound('Slot not found');
        }

        if ($slot['booked_count'] > 0) {
            return $this->fail('Cannot delete slot with existing bookings');
        }

        $slotModel->delete($slotId);

        return $this->respond([
            'success' => true,
            'message' => 'Slot deleted successfully'
        ]);
    }

    public function changePassword()
    {
        $json = $this->request->getJSON();
        
        $recruiterId = $json->recruiter_id ?? $this->request->getVar('recruiter_id');
        $currentPassword = $json->current_password ?? $this->request->getVar('current_password');
        $newPassword = $json->new_password ?? $this->request->getVar('new_password');

        if (!$recruiterId || !$currentPassword || !$newPassword) {
            return $this->fail('Missing required fields');
        }

        $userModel = new UserModel();
        $user = $userModel->find($recruiterId);

        if (!$user || $user['role'] !== 'recruiter') {
            return $this->failNotFound('Recruiter not found');
        }

        if (empty($user['password']) || !password_verify((string)$currentPassword, (string)$user['password'])) {
            return $this->fail('Current password is incorrect', 400);
        }

        if (password_verify((string)$newPassword, (string)$user['password'])) {
            return $this->fail('New password must be different from the current password', 400);
        }

        $userModel->update($recruiterId, [
            'password' => password_hash((string)$newPassword, PASSWORD_DEFAULT),
        ]);

        $allowEmail = (int) ($candidate['job_alert_notify_email'] ?? 1) === 1;
        if ($allowEmail) {
            $this->sendCandidateActionEmail(
                $candidate,
                'Invitation to Apply',
                $message,
                $jobLink
            );
        }

        return $this->respond([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    // ── Interview Slots ──────────────────────────────────────────────────────

    /**
     * GET api/mobile/interview_slots
     * Returns all interview slots for the recruiter with metrics and optional filters.
     * Query params: recruiter_id, job_id (optional), status (optional), date (optional)
     */
    public function getInterviewSlots()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) {
            return $this->fail('Recruiter ID required');
        }

        $jobIdFilter  = $this->request->getVar('job_id');
        $statusFilter = $this->request->getVar('status');
        $dateFilter   = $this->request->getVar('date');

        $db        = \Config\Database::connect();
        $slotModel = new \App\Models\InterviewSlotModel();
        $jobModel  = new \App\Models\JobModel();

        // Get all job IDs that belong to this recruiter
        $recruiterJobs = $jobModel
            ->select('id')
            ->where('recruiter_id', $recruiterId)
            ->findAll();
        $jobIds = array_column($recruiterJobs, 'id');

        if (empty($jobIds)) {
            return $this->respond([
                'success' => true,
                'slots'   => [],
                'metrics' => [
                    'total'          => '0',
                    'available'      => '0',
                    'booked'         => '0',
                    'total_bookings' => '0',
                ],
            ]);
        }

        // Build query
        $builder = $slotModel
            ->select('interview_slots.*, jobs.title as job_title, users.name as created_by_name')
            ->join('jobs', 'jobs.id = interview_slots.job_id', 'left')
            ->join('users', 'users.id = interview_slots.created_by', 'left')
            ->whereIn('interview_slots.job_id', $jobIds)
            ->orderBy('interview_slots.slot_datetime', 'ASC');

        // Job filter
        if (!empty($jobIdFilter)) {
            $builder->where('interview_slots.job_id', (int)$jobIdFilter);
        }

        // Date filter
        if (!empty($dateFilter)) {
            $builder->where('interview_slots.slot_date', $dateFilter);
        }

        // Status filter — match web's SlotManagementController exactly
        $now = date('Y-m-d H:i:s');
        if ($statusFilter === 'available') {
            $builder->where('interview_slots.is_available', 1)
                    ->where('interview_slots.slot_datetime >=', $now)
                    ->where('interview_slots.booked_count < interview_slots.capacity');
        } elseif ($statusFilter === 'full') {
            // Web uses is_available=0, same here
            $builder->where('interview_slots.is_available', 0);
        } elseif ($statusFilter === 'past') {
            $builder->where('interview_slots.slot_datetime <', $now);
        }

        $slots = $builder->findAll();

        // Compute per-slot status — mirroring web's PHP logic exactly:
        // Past:      slot_datetime < now
        // Full:      is_available = 0 (regardless of capacity — admin may mark unavailable)
        // Available: is_available = 1 AND not past AND booked_count < capacity
        // Unavailable: is_available = 0 (same as full in web logic)
        $formattedSlots = [];
        foreach ($slots as $s) {
            $isPast      = strtotime($s['slot_datetime']) < time();
            $isAvailFlag = (int)$s['is_available'] === 1;
            $isFull      = !$isAvailFlag; // web: fully_booked = is_available=0

            if ($isPast) {
                $slotStatus = 'past';
            } elseif ($isFull) {
                $slotStatus = 'full';
            } elseif ($isAvailFlag) {
                $slotStatus = 'available';
            } else {
                $slotStatus = 'unavailable';
            }

            $formattedSlots[] = [
                'slot_id'         => (string)$s['id'],
                'id'              => (string)$s['id'],
                'job_id'          => (string)$s['job_id'],
                'job_title'       => $s['job_title'] ?? '',
                'slot_date'       => $s['slot_date'],
                'slot_time'       => $s['slot_time'],
                'slot_datetime'   => $s['slot_datetime'],
                'capacity'        => (string)$s['capacity'],
                'booked_count'    => (string)$s['booked_count'],
                'is_available'    => (int)$s['is_available'],
                'status'          => $slotStatus,
                'created_by_name' => $s['created_by_name'] ?? 'System',
            ];
        }

        // Compute aggregate metrics — match web's SlotManagementController exactly:
        // total_slots    = COUNT(*)
        // available_slots= is_available=1 AND slot_datetime > now
        // fully_booked   = is_available=0
        // total_bookings = COUNT(interview_bookings)
        $slotModel2 = new \App\Models\InterviewSlotModel();

        $totalSlots = $slotModel2->whereIn('job_id', $jobIds)->countAllResults();

        $availableSlots = $slotModel2
            ->whereIn('job_id', $jobIds)
            ->where('is_available', 1)
            ->where('slot_datetime >', date('Y-m-d H:i:s'))
            ->countAllResults();

        $fullyBooked = $slotModel2
            ->whereIn('job_id', $jobIds)
            ->where('is_available', 0)
            ->countAllResults();

        $totalBookings = (int)$db->table('interview_bookings')
            ->whereIn('job_id', $jobIds)
            ->countAllResults();

        return $this->respond([
            'success' => true,
            'slots'   => $formattedSlots,
            'metrics' => [
                'total'          => (string)$totalSlots,
                'available'      => (string)$availableSlots,
                'booked'         => (string)$fullyBooked,
                'total_bookings' => (string)$totalBookings,
            ],
        ]);
    }

    // ── Interview Bookings ───────────────────────────────────────────────────

    /**
     * GET api/mobile/interview_bookings
     * Returns all candidate bookings for the recruiter's jobs with candidate + review info.
     * Query params: recruiter_id, job_id (optional), status (optional)
     */
    public function getInterviewBookings()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) {
            return $this->fail('Recruiter ID required');
        }

        $jobIdFilter  = $this->request->getVar('job_id');
        $statusFilter = $this->request->getVar('status');

        $jobModel = new \App\Models\JobModel();

        // Get all job IDs for this recruiter
        $recruiterJobs = $jobModel
            ->select('id')
            ->where('recruiter_id', $recruiterId)
            ->findAll();
        $jobIds = array_column($recruiterJobs, 'id');

        if (empty($jobIds)) {
            return $this->respond([
                'success'  => true,
                'bookings' => [],
            ]);
        }

        $bookingModel = new \App\Models\InterviewBookingModel();

        $builder = $bookingModel
            ->select('
                interview_bookings.*,
                users.name as candidate_name,
                users.email,
                jobs.title as job_title,
                interview_slots.slot_date,
                interview_slots.slot_time,
                interview_booking_reviews.id as review_id,
                interview_booking_reviews.decision as review_decision,
                interview_booking_reviews.notes as review_notes,
                interview_booking_reviews.attendance_status as review_attendance_status,
                interview_booking_reviews.reviewed_at as review_reviewed_at
            ')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->join('interview_slots', 'interview_slots.id = interview_bookings.slot_id', 'left')
            ->join('interview_booking_reviews', 'interview_booking_reviews.booking_id = interview_bookings.id', 'left')
            ->whereIn('interview_bookings.job_id', $jobIds)
            ->orderBy('interview_bookings.slot_datetime', 'DESC');

        if (!empty($jobIdFilter)) {
            $builder->where('interview_bookings.job_id', (int)$jobIdFilter);
        }

        if (!empty($statusFilter)) {
            $builder->where('interview_bookings.booking_status', $statusFilter);
        }

        $bookings = $builder->findAll();

        $formattedBookings = [];
        foreach ($bookings as $b) {
            $rescheduleCount = 0;
            if (!empty($b['reschedule_history'])) {
                $history = json_decode($b['reschedule_history'], true);
                $rescheduleCount = is_array($history) ? count($history) : 0;
            }

            $formattedBookings[] = [
                'id'                     => (string)$b['id'],
                'candidate_id'           => (string)$b['user_id'],
                'candidate_name'         => $b['candidate_name'] ?? 'Unknown',
                'email'                  => $b['email'] ?? '',
                'job_id'                 => (string)$b['job_id'],
                'job_title'              => $b['job_title'] ?? '',
                'slot_id'                => (string)$b['slot_id'],
                'slot_date'              => $b['slot_date'] ?? '',
                'slot_time'              => $b['slot_time'] ?? '',
                'slot_datetime'          => $b['slot_datetime'] ?? '',
                'booking_status'         => $b['booking_status'] ?? 'booked',
                'booked_at'              => $b['booked_at'] ?? $b['created_at'] ?? '',
                'application_id'         => (string)($b['application_id'] ?? ''),
                'reschedule_count'       => (string)$rescheduleCount,
                // Review data
                'review_id'              => $b['review_id'] ?? null,
                'review_decision'        => $b['review_decision'] ?? null,
                'review_notes'           => $b['review_notes'] ?? null,
                'review_attendance_status' => $b['review_attendance_status'] ?? null,
                'review_reviewed_at'     => $b['review_reviewed_at'] ?? null,
            ];
        }

        // Calculate Metrics
        $now = date('Y-m-d H:i:s');
        $metrics = ['total' => 0, 'upcoming' => 0, 'completed' => 0, 'rescheduled' => 0];
        
        $metricsBuilder = $bookingModel->select('booking_status, slot_datetime')
            ->whereIn('job_id', $jobIds);
            
        $allBookingsForMetrics = $metricsBuilder->findAll();
        foreach ($allBookingsForMetrics as $b) {
            $status = strtolower($b['booking_status'] ?? 'booked');
            $metrics['total']++;
            
            if ($status === 'completed') {
                $metrics['completed']++;
            } elseif ($status === 'rescheduled') {
                $metrics['rescheduled']++;
                if (!empty($b['slot_datetime']) && $b['slot_datetime'] >= $now) {
                    $metrics['upcoming']++;
                }
            } elseif ($status === 'booked' || $status === 'confirmed') {
                if (!empty($b['slot_datetime']) && $b['slot_datetime'] >= $now) {
                    $metrics['upcoming']++;
                }
            }
        }

        return $this->respond([
            'success'  => true,
            'bookings' => $formattedBookings,
            'metrics'  => $metrics,
        ]);
    }

    // ── Reschedule Interview Booking ─────────────────────────────────────────

    /**
     * POST api/mobile/interviews/reschedule
     * Reschedules an interview booking to a new date/time (creates or uses a slot).
     * Body params: interview_id, recruiter_id, interview_date (Y-m-d H:i:s)
     */
    public function rescheduleInterviewBooking()
    {
        $recruiterId    = $this->request->getVar('recruiter_id');
        $bookingId      = $this->request->getVar('interview_id');
        $newDatetime    = $this->request->getVar('interview_date');

        if (!$recruiterId || !$bookingId || !$newDatetime) {
            return $this->fail('Missing required fields: recruiter_id, interview_id, interview_date');
        }

        $bookingModel = new \App\Models\InterviewBookingModel();
        $slotModel    = new \App\Models\InterviewSlotModel();

        $booking = $bookingModel->find($bookingId);
        if (!$booking) {
            return $this->failNotFound('Booking not found');
        }

        // Verify the recruiter owns this job
        $db = \Config\Database::connect();
        $job = $db->table('jobs')
            ->where('id', $booking['job_id'])
            ->where('recruiter_id', $recruiterId)
            ->get()->getRowArray();

        if (!$job) {
            return $this->fail('Unauthorized: booking does not belong to your jobs');
        }

        // Parse the new date/time
        $newDt   = strtotime($newDatetime);
        $newDate = date('Y-m-d', $newDt);
        $newTime = date('H:i:s', $newDt);

        // Decrement the old slot's booked count
        if (!empty($booking['slot_id'])) {
            $slotModel->decrementBookedCount((int)$booking['slot_id']);
        }

        // Create a new slot for the rescheduled time or find an existing one
        $existingSlot = $slotModel
            ->where('job_id', $booking['job_id'])
            ->where('slot_date', $newDate)
            ->where("TIME(slot_time) = TIME('" . $newTime . "')")
            ->first();

        if ($existingSlot) {
            $newSlotId = $existingSlot['id'];
            $slotModel->incrementBookedCount($newSlotId);
        } else {
            // Create a one-off slot for this reschedule
            $newSlotId = $slotModel->insert([
                'job_id'        => $booking['job_id'],
                'slot_date'     => $newDate,
                'slot_time'     => $newTime,
                'slot_datetime' => $newDatetime,
                'capacity'      => 1,
                'booked_count'  => 1,
                'is_available'  => 0,
                'created_by'    => $recruiterId,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }

        // Update the booking record
        $bookingModel->update($bookingId, [
            'slot_id'        => $newSlotId,
            'slot_datetime'  => $newDatetime,
            'booking_status' => 'rescheduled',
        ]);

        $allowEmail = (int) ($candidate['job_alert_notify_email'] ?? 1) === 1;
        if ($allowEmail) {
            $this->sendCandidateActionEmail(
                $candidate,
                'Invitation to Apply',
                $message,
                $jobLink
            );
        }

        return $this->respond([
            'success' => true,
            'message' => 'Interview rescheduled successfully',
        ]);
    }

    public function getRescheduleData()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $bookingId   = $this->request->getVar('booking_id');

        if (!$recruiterId || !$bookingId) {
            return $this->fail('Missing recruiter_id or booking_id');
        }

        $bookingModel = model('InterviewBookingModel');
        $slotModel = model('InterviewSlotModel');

        $booking = $bookingModel->select('interview_bookings.*, users.name as candidate_name, users.email, jobs.title as job_title')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->find($bookingId);

        if (!$booking) {
            return $this->fail('Booking not found');
        }

        $availableSlots = $slotModel->getAvailableSlotsGrouped($booking['job_id']);

        return $this->respond([
            'success' => true,
            'booking' => $booking,
            'available_slots' => $availableSlots
        ]);
    }

    public function processRescheduleData()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        $bookingId   = $this->request->getVar('booking_id');
        $newSlotId   = $this->request->getVar('slot_id');
        $reason      = $this->request->getVar('reason');

        if (!$recruiterId || !$bookingId || !$newSlotId) {
            return $this->fail('Missing required fields');
        }

        $bookingModel = model('InterviewBookingModel');
        $slotModel = model('InterviewSlotModel');
        $rescheduleHistoryModel = model('RescheduleHistoryModel');
        $notificationModel = model('NotificationModel');

        $booking = $bookingModel->find($bookingId);

        if (!$booking) {
            return $this->fail('Booking not found');
        }

        if (!$slotModel->isSlotAvailable($newSlotId)) {
            return $this->fail('Selected slot is not available');
        }

        $newSlot = $slotModel->find($newSlotId);

        $db = \Config\Database::connect();
        $db->transStart();

        $slotModel->decrementBookedCount($booking['slot_id']);
        $slotModel->incrementBookedCount($newSlotId);

        $bookingModel->update($bookingId, [
            'slot_id' => $newSlotId,
            'slot_datetime' => $newSlot['slot_datetime'],
            'booking_status' => 'rescheduled',
            'last_rescheduled_at' => date('Y-m-d H:i:s')
        ]);

        $rescheduleHistoryModel->insert([
            'booking_id' => $bookingId,
            'old_slot_id' => $booking['slot_id'],
            'new_slot_id' => $newSlotId,
            'old_slot_datetime' => $booking['slot_datetime'],
            'new_slot_datetime' => $newSlot['slot_datetime'],
            'reason' => $reason,
            'rescheduled_by' => 'admin',
            'rescheduled_at' => date('Y-m-d H:i:s')
        ]);

        model('ApplicationModel')->update($booking['application_id'], [
            'interview_slot' => $newSlot['slot_datetime'],
            'status' => 'reschedule_required'
        ]);

        $notificationModel->createNotification(
            $booking['user_id'],
            $booking['application_id'],
            'interview_rescheduled',
            'Your interview has been rescheduled by the admin.',
            base_url('candidate/my-bookings'),
            true
        );

        $db->transComplete();

        if ($db->transStatus()) {
            return $this->respond(['success' => true, 'message' => 'Booking rescheduled successfully']);
        } else {
            return $this->fail('Failed to reschedule booking');
        }
    }

    // ── Submit Interview Review ──────────────────────────────────────────────

    /**
     * POST api/mobile/interviews/review
     * Submits a review for an interview booking.
     * Body params: booking_id, recruiter_id, attendance_status, decision, notes, strengths, concerns
     */
    public function submitInterviewReview()
    {
        $recruiterId      = $this->request->getVar('recruiter_id');
        $bookingId        = $this->request->getVar('booking_id');
        
        if (!$recruiterId || !$bookingId) {
            return $this->fail('Missing required fields: recruiter_id, booking_id');
        }

        $bookingModel = new \App\Models\InterviewBookingModel();
        $bookingReviewModel = new \App\Models\InterviewBookingReviewModel();
        $applicationModel = new \App\Models\ApplicationModel();
        $stageModel = new \App\Models\StageHistoryModel();

        $booking = $bookingModel
            ->select('interview_bookings.*, jobs.recruiter_id, jobs.title as job_title, users.name as candidate_name, users.email')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->where('interview_bookings.id', (int) $bookingId)
            ->first();

        if (!$booking) {
            return $this->failNotFound('Booking not found.');
        }

        if ((int) ($booking['recruiter_id'] ?? 0) !== (int) $recruiterId) {
            return $this->fail('Unauthorized access.', 403);
        }

        $attendanceStatus = (string) $this->request->getVar('attendance_status');
        $decision = trim((string) $this->request->getVar('decision'));
        $notes = trim((string) $this->request->getVar('notes'));
        $strengths = trim((string) $this->request->getVar('strengths'));
        $concerns = trim((string) $this->request->getVar('concerns'));

        $allowedAttendance = ['attended', 'late', 'no_show'];
        $allowedDecision = ['shortlisted', 'hold', 'selected', 'rejected'];

        if (!in_array($attendanceStatus, $allowedAttendance, true)) {
            return $this->fail('Please choose a valid attendance status.', 400);
        }

        if ($attendanceStatus !== 'no_show' && !in_array($decision, $allowedDecision, true)) {
            return $this->fail('Please choose a valid recruiter decision.', 400);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        $reviewData = [
            'booking_id' => (int) $bookingId,
            'application_id' => (int) ($booking['application_id'] ?? 0),
            'candidate_id' => (int) ($booking['user_id'] ?? 0),
            'job_id' => (int) ($booking['job_id'] ?? 0),
            'recruiter_id' => (int) $recruiterId,
            'attendance_status' => $attendanceStatus,
            'decision' => $attendanceStatus === 'no_show' ? 'rejected' : $decision,
            'strengths' => $strengths !== '' ? $strengths : null,
            'concerns' => $concerns !== '' ? $concerns : null,
            'notes' => $notes !== '' ? $notes : null,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];

        $existingReview = $bookingReviewModel->getByBookingId((int) $bookingId);
        if ($existingReview) {
            $bookingReviewModel->update((int) $existingReview['id'], $reviewData);
        } else {
            $bookingReviewModel->insert($reviewData);
        }

        $bookingStatus = $attendanceStatus === 'no_show' ? 'no_show' : 'completed';
        $bookingModel->update((int) $bookingId, [
            'booking_status' => $bookingStatus,
        ]);

        if (!empty($booking['application_id'])) {
            $applicationStatus = $attendanceStatus === 'no_show'
                ? 'rejected'
                : $decision;

            $applicationModel->update((int) $booking['application_id'], [
                'status' => $applicationStatus,
            ]);

            $stageLabel = $attendanceStatus === 'no_show'
                ? 'HR Interview No Show'
                : ('HR Interview Reviewed - ' . ucwords(str_replace('_', ' ', $decision)));
            $stageModel->moveToStage((int) $booking['application_id'], $stageLabel);

            $notificationModel = new \App\Models\NotificationModel();
            $candidateId = (int) ($booking['user_id'] ?? 0);
            $applicationId = (int) $booking['application_id'];

            $notificationModel->createNotification(
                $candidateId,
                $applicationId,
                'interview_reviewed',
                $attendanceStatus === 'no_show'
                    ? 'Your interview was marked as no show by the recruiter.'
                    : 'Your interview review is complete. Final status: ' . ucwords(str_replace('_', ' ', $applicationStatus)) . '.',
                base_url('candidate/applications'),
                true
            );
        }

        $db->transComplete();

        if ($db->transStatus() === false) {
            return $this->fail('Failed to submit review.');
        }

        return $this->respond([
            'success' => true,
            'message' => 'Review submitted successfully',
        ]);
    }
}
