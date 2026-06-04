<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ApplicationModel;
use App\Models\JobModel;
use App\Models\RecruiterCandidateActionModel;
use App\Models\UserModel;

class JobResponsesController extends BaseController
{
    /**
     * Helpers required for this controller and its views.
     * 'security' and 'form' are needed for CSRF tokens in the AJAX scripts.
     */
    protected $helpers = ['security', 'form', 'url'];

    /**
     * Displays the main "Jobs and Responses" page for recruiters.
     * This page will feature tabs for different application statuses and a scoreboard.
     */
    public function index()
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Access denied. Recruiter login required.');
        }

        $recruiterId = (int) session()->get('user_id');
        if ($recruiterId <= 0) {
            return redirect()->to(base_url('login'))->with('error', 'Recruiter ID not found in session.');
        }

        $statusFilter = $this->request->getGet('status') ?: 'active';
        $searchQuery = $this->request->getGet('q');

        $jobModel = model(JobModel::class);
        $builder = $jobModel->where('recruiter_id', $recruiterId);

        if ($statusFilter === 'active') {
            $builder->where('status', 'open');
        } elseif ($statusFilter === 'closed') {
            $builder->where('status', 'closed');
        }

        if ($searchQuery) {
            $builder->like('title', $searchQuery);
        }

        $jobs = $builder->orderBy('created_at', 'DESC')->paginate(10);
        $pager = $jobModel->pager;

        // applicant counts for list view
        $applicationModel = model(ApplicationModel::class);
        foreach ($jobs as &$job) {
            $job['applicant_count'] = $applicationModel->where('job_id', $job['id'])->countAllResults();
            $job['shortlisted_count'] = $applicationModel->where('job_id', $job['id'])->where('status', 'shortlisted')->countAllResults();
        }

        return view('recruiter/job_responses', [
            'jobs' => $jobs,
            'pager' => $pager,
            'filters' => ['status' => $statusFilter, 'q' => $searchQuery]
        ]);
    }

    public function viewJob(int $jobId)
    {
        if (session()->get('role') !== 'recruiter') return redirect()->to(base_url('login'));

        $recruiterId = (int) session()->get('user_id');
        $jobModel = model(JobModel::class);
        $job = $jobModel->where('id', $jobId)->where('recruiter_id', $recruiterId)->first();

        if (!$job) return redirect()->to(base_url('recruiter/jobs'))->with('error', 'Job not found.');

        $filters = [
            'skills'      => trim((string) $this->request->getGet('skills')),
            'experience'  => trim((string) $this->request->getGet('experience')),
            'location'    => trim((string) $this->request->getGet('location')),
            'ats_min'     => $this->request->getGet('ats_min'),
            'ats_max'     => $this->request->getGet('ats_max'),
            'sort'        => trim((string) $this->request->getGet('sort')),
            'last_active' => trim((string) $this->request->getGet('last_active')),
        ];

        $activeStage = $this->request->getGet('stage') ?: 'all';

        // Fetch all applications for counts and scoreboard (unpaginated)
        $allApplications = $this->getApplicationsForRecruiterJobs([$jobId], $recruiterId, false, 'all', $filters);
        $applicationsByStatus = $this->groupApplicationsByStatus($allApplications);
        $jobScoreboard = $this->calculateScoreboard($recruiterId, $allApplications);
        
        // Fetch paginated applications for the active stage
        $paginatedApplications = $this->getApplicationsForRecruiterJobs([$jobId], $recruiterId, true, $activeStage, $filters);
        $applicationModel = model(ApplicationModel::class);
        $pager = $applicationModel->pager;

        $leaderboard = $allApplications;
        usort($leaderboard, fn($a, $b) => $b['ats_score'] <=> $a['ats_score']);
        foreach ($leaderboard as $index => &$leaderboardCandidate) {
            $leaderboardCandidate['rank'] = $index + 1;
        }
        unset($leaderboardCandidate);
        $interviewBookings = $this->getInterviewBookingsForJob($jobId);
        $interviewSlots = $this->getInterviewSlotsForJob($jobId);
        $interviewStats = $this->getInterviewStatsForJob($jobId);

        $viewData = [
            'job' => $job,
            'applicationsByStatus' => $applicationsByStatus,
            'paginatedApplications' => $paginatedApplications,
            'scoreboard' => $jobScoreboard,
            'leaderboard' => $leaderboard,
            'interviewBookings' => $interviewBookings,
            'interviewSlots' => $interviewSlots,
            'interviewStats' => $interviewStats,
            'activeStage' => $activeStage,
            'pager' => $pager,
            'statuses' => [
                'applied' => 'Applied', 'shortlisted' => 'Shortlisted', 'interview_scheduled' => 'Interview Scheduled',
                'interviewed' => 'Interviewed', 'offered' => 'Offered', 'hired' => 'Hired',
                'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn', 'on_hold' => 'On Hold', 'filtered_out' => 'Filtered Out'
            ],
            'totalApplicationsCount' => count($allApplications),
            'advancedFilters' => $filters,
        ];

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => true,
                'activeStage' => $activeStage,
                'html' => view('recruiter/partials/job_pipeline_applications', $viewData),
            ]);
        }

        return view('recruiter/job_detail_responses', $viewData);
    }

    public function previewJob(int $jobId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'));
        }

        $recruiterId = (int) session()->get('user_id');
        $jobModel = model(JobModel::class);
        $job = $jobModel->where('id', $jobId)->where('recruiter_id', $recruiterId)->first();

        if (!$job) {
            return redirect()->to(base_url('recruiter/jobs'))->with('error', 'Job not found.');
        }

        $isExpired = false;
        if (!empty($job['application_deadline'])) {
            $isExpired = strtotime($job['application_deadline'] . ' 23:59:59') < time();
        }

        if (($job['posted_for'] ?? '') === 'client') {
            if (($job['client_disclosure'] ?? '') === 'visible' && !empty($job['client_company_name'])) {
                $job['company'] = $job['client_company_name'];
            } else {
                $job['company'] = ($job['company'] ?? 'Recruiter') . ' (Hiring for a Client)';
            }
        }

        $company = null;
        $companyModel = model('CompanyModel');
        $companyId = (int) ($job['company_id'] ?? 0);
        if ($companyId > 0) {
            $company = $companyModel->find($companyId);
        }
        if (!$company && !empty($job['company'])) {
            $company = $companyModel->where('name', $job['company'])->first();
        }

        return view('candidate/job_details', [
            'title' => 'Job Preview',
            'job' => $job,
            'company' => $company,
            'alreadyApplied' => false,
            'interviewId' => null,
            'isSaved' => false,
            'resumeCoach' => [],
            'invitation' => [],
            'applicationQuestionnaire' => $this->decodeApplicationQuestionnaire((string) ($job['application_questionnaire'] ?? '')),
            'application' => null,
            'isExpired' => $isExpired,
        ]);
    }

    private function decodeApplicationQuestionnaire(string $rawQuestionnaire): array
    {
        if (trim($rawQuestionnaire) === '') {
            return [];
        }

        $decoded = json_decode($rawQuestionnaire, true);
        return is_array($decoded) ? array_values($decoded) : [];
    }

    /**
     * Fetches applications for a given set of job IDs, including candidate and job details.
     *
     * @param array $recruiterJobIds
     * @param int $recruiterId
     * @param bool $paginate
     * @param string $stage
     * @return array
     */
    private function getApplicationsForRecruiterJobs(array $recruiterJobIds, int $recruiterId, bool $paginate = false, string $stage = 'all', array $filters = []): array
    {
        if (empty($recruiterJobIds)) {
            return [];
        }

        $applicationModel = model(ApplicationModel::class);
        $actionModel = model(RecruiterCandidateActionModel::class);
        $db = \Config\Database::connect();
        $hasInterviewSessions = $db->tableExists('interview_sessions');
        $hasLoginLogs = $db->tableExists('user_login_performance_logs');
        $hasRecruiterNotes = $db->tableExists('recruiter_candidate_notes');
        $ratingSelect = $hasInterviewSessions
            ? 'MAX(COALESCE(interview_sessions.overall_rating, 0)) as overall_rating,
                MAX(COALESCE(interview_sessions.technical_score, 0)) as technical_score,
                MAX(COALESCE(interview_sessions.communication_score, 0)) as communication_score'
            : '0 as overall_rating, 0 as technical_score, 0 as communication_score';
        $experienceSubQuery = '(SELECT user_id, SUM(TIMESTAMPDIFF(MONTH, start_date, COALESCE(NULLIF(end_date, \'\'), CURDATE()))) AS total_experience_months FROM work_experiences GROUP BY user_id) candidate_experience';
        $lastLoginSubQuery = '(SELECT user_id, MAX(login_at) as last_login FROM user_login_performance_logs GROUP BY user_id) last_login_table';
        $lastLoginSelect = $hasLoginLogs ? 'last_login_table.last_login' : 'NULL as last_login';
        $notesSelect = $hasRecruiterNotes ? 'recruiter_candidate_notes.tags as recruiter_tags, recruiter_candidate_notes.notes as recruiter_notes' : 'NULL as recruiter_tags, NULL as recruiter_notes';

        $applications = $applicationModel
            ->select('applications.*, jobs.title as job_title, jobs.company as job_company, jobs.location as job_location, jobs.experience_level, jobs.required_skills, users.name as candidate_name, users.email as candidate_email, users.phone as candidate_phone, candidate_profiles.resume_path, candidate_profiles.location as candidate_location, COALESCE(candidate_experience.total_experience_months, 0) as total_experience_months, ' . $lastLoginSelect . ', ' . $notesSelect . ', ' . $ratingSelect)
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->join('users', 'users.id = applications.candidate_id', 'left')
            ->join('candidate_profiles', 'candidate_profiles.user_id = applications.candidate_id', 'left')
            ->join('candidate_skills', 'candidate_skills.candidate_id = applications.candidate_id', 'left')
            ->join($experienceSubQuery, 'candidate_experience.user_id = applications.candidate_id', 'left', false)
            ->whereIn('applications.job_id', $recruiterJobIds);
        if ($hasInterviewSessions) {
            $applicationModel->join('interview_sessions', 'interview_sessions.application_id = applications.id', 'left');
        }
        if ($hasLoginLogs) {
            $applicationModel->join($lastLoginSubQuery, 'last_login_table.user_id = applications.candidate_id', 'left', false);
        }
        if ($hasRecruiterNotes) {
            $applicationModel->join(
                'recruiter_candidate_notes',
                'recruiter_candidate_notes.candidate_id = applications.candidate_id AND recruiter_candidate_notes.recruiter_id = ' . (int) $recruiterId,
                'left',
                false
            );
        }
        $applicationModel->groupBy('applications.id');

        if ($stage !== 'all') {
            $applicationModel->where('applications.status', $stage);
        }

        // Apply Advanced Filters
        if (!empty($filters['skills'])) {
            $applicationModel->like('candidate_skills.skill_name', $filters['skills']);
        }

        if (!empty($filters['experience'])) {
            preg_match('/\d+(\.\d+)?/', $filters['experience'], $matches);
            if (!empty($matches[0])) {
                $minMonths = (int) round(((float)$matches[0]) * 12);
                $applicationModel->where('COALESCE(candidate_experience.total_experience_months, 0) >= ' . $minMonths, null, false);
            }
        }

        if (!empty($filters['location'])) {
            $applicationModel->where(
                'candidate_profiles.location LIKE ' . $db->escape('%' . $filters['location'] . '%'),
                null,
                false
            );
        }

        if (!empty($filters['last_active'])) {
            $days = (int)$filters['last_active'];
            $applicationModel->where('last_login_table.last_login >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)', null, false);
        }

        $applicationModel->orderBy('applications.applied_at', 'DESC');
            
        $applications = $paginate ? $applicationModel->paginate(10) : $applicationModel->findAll();

        if (empty($applications)) {
            return [];
        }

        // Fetch recruiter actions for each application
        $applicationIds = array_column($applications, 'id');
        // Ensure we don't call the model if application IDs are empty
        $recruiterActions = !empty($applicationIds) ? $actionModel->getSummaryByApplicationIds(null, $applicationIds, $recruiterId) : [];

        // Status mapping to normalize database values for the recruiter UI
        $statusMap = [
            'interview_slot_booked' => 'interview_scheduled',
            'selected' => 'offered',
            'hold' => 'on_hold',
        ];

        foreach ($applications as &$app) {
            $app['recruiter_activity'] = $recruiterActions[$app['id']] ?? [
                'profile_viewed_count' => 0,
                'contact_viewed_count' => 0,
                'resume_downloaded_count' => 0,
                'last_recruiter_activity_at' => null,
            ];
            $app['candidate_skills'] = $this->getCandidateSkills((int) ($app['candidate_id'] ?? 0));
            $app['required_skills'] = $this->parseRequiredSkills($app['required_skills'] ?? '');
            $app['skill_match'] = $this->calculateSkillMatch($app['candidate_skills'], $app['required_skills']);
            $app['github_stack'] = $this->getGithubStack((int) ($app['candidate_id'] ?? 0));
            $app['ats_score'] = $this->calculateLeaderboardAtsScore($app);
            $app['experience_display'] = $this->formatExperienceDisplay((int) ($app['total_experience_months'] ?? 0));

            // Normalize status to prevent view crashes and ensure correct grouping/scoreboard counts
            $currentStatus = $app['status'] ?? 'applied';
            if (isset($statusMap[$currentStatus])) {
                $app['status'] = $statusMap[$currentStatus];
            }
            $app['can_manual_decision'] = $this->canTakeManualDecision((string) ($app['status'] ?? ''));
        }
        unset($app);

        // PHP Filtering for ATS scores (since calculated in PHP)
        if (!empty($filters['ats_min']) || !empty($filters['ats_max'])) {
            $atsMin = is_numeric($filters['ats_min']) ? (int)$filters['ats_min'] : null;
            $atsMax = is_numeric($filters['ats_max']) ? (int)$filters['ats_max'] : null;
            $applications = array_values(array_filter($applications, function ($app) use ($atsMin, $atsMax) {
                $score = (int)($app['ats_score'] ?? 0);
                if ($atsMin !== null && $score < $atsMin) return false;
                if ($atsMax !== null && $score > $atsMax) return false;
                return true;
            }));
        }

        // Sorting
        if (!empty($filters['sort'])) {
            if ($filters['sort'] === 'ats_desc') {
                usort($applications, fn($a, $b) => $b['ats_score'] <=> $a['ats_score']);
            } elseif ($filters['sort'] === 'ats_asc') {
                usort($applications, fn($a, $b) => $a['ats_score'] <=> $b['ats_score']);
            }
        }

        return $applications;
    }

    /**
     * Groups applications by their status.
     *
     * @param array $applications
     * @return array
     */
    private function groupApplicationsByStatus(array $applications): array
    {
        $grouped = [
            'applied' => [],
            'shortlisted' => [],
            'interview_scheduled' => [],
            'interviewed' => [],
            'offered' => [],
            'hired' => [],
            'rejected' => [],
            'withdrawn' => [],
            'on_hold' => [],
            'filtered_out' => [], // Keep filtered_out visible for recruiters
        ];

        foreach ($applications as $app) {
            $status = $app['status'] ?? 'applied'; // Default to 'applied' if status is missing
            if (array_key_exists($status, $grouped)) {
                $grouped[$status][] = $app;
            } else {
                // Handle any unexpected statuses by grouping them under 'applied' or a new 'other' category
                $grouped['applied'][] = $app;
            }
        }

        return $grouped;
    }

    /**
     * Calculates aggregated statistics for the scoreboard.
     *
     * @param int $recruiterId
     * @param array $applications
     * @return array
     */
    private function calculateScoreboard(int $recruiterId, array $applications): array
    {
        $totalApplications = count($applications);
        $statusCounts = array_count_values(array_column($applications, 'status'));

        $totalProfileViews = 0;
        $totalResumeDownloads = 0;
        $totalContactViews = 0;
        $totalAtsScore = 0;
        $atsScoredCount = 0;

        foreach ($applications as $app) {
            $totalProfileViews += (int) ($app['recruiter_activity']['profile_viewed_count'] ?? 0);
            $totalResumeDownloads += (int) ($app['recruiter_activity']['resume_downloaded_count'] ?? 0);
            $totalContactViews += (int) ($app['recruiter_activity']['contact_viewed_count'] ?? 0);
            if (isset($app['ats_score'])) {
                $totalAtsScore += (int) $app['ats_score'];
                $atsScoredCount++;
            }
        }

        $averageAtsScore = $atsScoredCount > 0 ? round($totalAtsScore / $atsScoredCount) : 0;

        return [
            'total_applications' => $totalApplications,
            'status_counts' => $statusCounts,
            'total_profile_views' => $totalProfileViews,
            'total_resume_downloads' => $totalResumeDownloads,
            'total_contact_views' => $totalContactViews,
            'average_ats_score' => $averageAtsScore,
            // Add more metrics as needed
        ];
    }

    /**
     * Initializes an empty scoreboard array.
     *
     * @return array
     */
    private function initializeScoreboard(): array
    {
        return [
            'total_applications' => 0,
            'status_counts' => [],
            'total_profile_views' => 0,
            'total_resume_downloads' => 0,
            'total_contact_views' => 0,
            'average_ats_score' => 0,
        ];
    }

    private function getCandidateSkills(int $candidateId): array
    {
        if ($candidateId <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('candidate_skills')) {
            return [];
        }

        $row = $db->table('candidate_skills')
            ->where('candidate_id', $candidateId)
            ->get()
            ->getRowArray();

        if (!$row || empty($row['skill_name'])) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $row['skill_name']))));
    }

    private function parseRequiredSkills(?string $requiredSkills): array
    {
        if (empty($requiredSkills)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $requiredSkills))));
    }

    private function calculateSkillMatch(array $candidateSkills, array $requiredSkills): int
    {
        if (empty($candidateSkills) || empty($requiredSkills)) {
            return 0;
        }

        $candidateSkillsLower = array_map('strtolower', $candidateSkills);
        $requiredSkillsLower = array_map('strtolower', $requiredSkills);

        return (int) round((count(array_intersect($candidateSkillsLower, $requiredSkillsLower)) / max(1, count($requiredSkillsLower))) * 100);
    }

    private function getGithubStack(int $candidateId): array
    {
        if ($candidateId <= 0) {
            return [];
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('candidate_github_stats')) {
            return [];
        }

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
                } elseif (is_string($value) && trim($value) !== '') {
                    $values[] = trim($value);
                }
            }

            return array_values(array_unique(array_filter($values)));
        }

        $parts = preg_split('/[,|\/]+/', (string) $row['languages_used']) ?: [];
        return array_values(array_unique(array_filter(array_map('trim', $parts))));
    }

    private function calculateLeaderboardAtsScore(array $candidate): int
    {
        $requiredSkills = $this->normalizeSkillTokens($candidate['required_skills'] ?? []);
        $candidateSkills = $this->normalizeSkillTokens($candidate['candidate_skills'] ?? []);
        $requiredMonths = $this->extractRequiredExperienceMonths((string) ($candidate['experience_level'] ?? ''));

        if (empty($requiredSkills) && ($requiredMonths === null || $requiredMonths <= 0)) {
            return 0;
        }

        if (empty($requiredSkills)) {
            $skillScore = 60;
        } else {
            $matched = count(array_intersect($candidateSkills, $requiredSkills));
            $skillScore = (int) round(($matched / max(1, count($requiredSkills))) * 60);
        }

        $candidateMonths = max(0, (int) ($candidate['total_experience_months'] ?? 0));
        $experienceScore = ($requiredMonths === null || $requiredMonths <= 0)
            ? 20
            : (int) round(min(1, $candidateMonths / $requiredMonths) * 20);
        $rating = (float) ($candidate['overall_rating'] ?? 0);
        $aiScore = $rating > 0 ? (int) round(min(10, max(0, $rating)) / 10 * 15) : 0;
        $profileScore = !empty($candidate['resume_path']) ? 5 : 0;

        return max(0, min(100, $skillScore + $experienceScore + $aiScore + $profileScore));
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

    private function canTakeManualDecision(string $applicationStatus): bool
    {
        if (in_array($applicationStatus, ['interview_slot_booked', 'interview_scheduled', 'selected', 'offered', 'hired'], true)) {
            return false;
        }

        return in_array($applicationStatus, ['applied', 'shortlisted', 'rejected', 'pending', 'hold', 'on_hold', 'filtered_out'], true);
    }

    private function normalizeSkillTokens($skills): array
    {
        $parts = is_array($skills) ? $skills : (preg_split('/[,|\/]+/', strtolower((string) $skills)) ?: []);
        $tokens = [];
        foreach ($parts as $part) {
            $value = strtolower(trim((string) $part));
            if ($value !== '') {
                $tokens[] = $value;
            }
        }

        return array_values(array_unique($tokens));
    }

    private function extractRequiredExperienceMonths(string $experienceLevel): ?int
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

    private function getInterviewBookingsForJob(int $jobId): array
    {
        $bookingModel = model('InterviewBookingModel');

        return $bookingModel
            ->select('interview_bookings.*, users.name as candidate_name, users.email, jobs.title as job_title, interview_slots.slot_date, interview_slots.slot_time, interview_booking_reviews.id as review_id, interview_booking_reviews.attendance_status as review_attendance_status, interview_booking_reviews.decision as review_decision, interview_booking_reviews.notes as review_notes, interview_booking_reviews.reviewed_at as review_reviewed_at')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->join('jobs', 'jobs.id = interview_bookings.job_id', 'left')
            ->join('interview_slots', 'interview_slots.id = interview_bookings.slot_id', 'left')
            ->join('interview_booking_reviews', 'interview_booking_reviews.booking_id = interview_bookings.id', 'left')
            ->where('interview_bookings.job_id', $jobId)
            ->orderBy('interview_bookings.slot_datetime', 'ASC')
            ->findAll();
    }

    private function getInterviewSlotsForJob(int $jobId): array
    {
        $slotModel = model('InterviewSlotModel');

        return $slotModel
            ->select('interview_slots.*, users.name as created_by_name')
            ->join('users', 'users.id = interview_slots.created_by', 'left')
            ->where('interview_slots.job_id', $jobId)
            ->orderBy('interview_slots.slot_datetime', 'ASC')
            ->findAll();
    }

    private function getInterviewStatsForJob(int $jobId): array
    {
        $bookingModel = model('InterviewBookingModel');
        $slotModel = model('InterviewSlotModel');

        return [
            'total_bookings' => $bookingModel->where('job_id', $jobId)->countAllResults(),
            'upcoming' => $bookingModel->where('job_id', $jobId)->where('slot_datetime >', date('Y-m-d H:i:s'))->countAllResults(),
            'completed' => $bookingModel->where('job_id', $jobId)->where('booking_status', 'completed')->countAllResults(),
            'rescheduled' => $bookingModel->where('job_id', $jobId)->where('booking_status', 'rescheduled')->countAllResults(),
            'booked_slots' => $slotModel->where('job_id', $jobId)->where('booked_count >', 0)->countAllResults(),
        ];
    }

    /**
     * Action to update an application's status.
     * This is a placeholder and would need proper validation and logic.
     */
    public function updateApplicationStatus(int $applicationId)
    {
        if (session()->get('role') !== 'recruiter') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $newStatus = $this->request->getPost('status');
        $recruiterId = (int) session()->get('user_id');

        // Basic validation
        if (empty($newStatus)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'New status is required.'])->setStatusCode(400);
        }

        $applicationModel = model(ApplicationModel::class);
        $application = $applicationModel->find($applicationId);

        if (!$application) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Application not found.'])->setStatusCode(404);
        }

        // Ensure the recruiter owns the job associated with this application
        $jobModel = model(JobModel::class);
        $job = $jobModel->find($application['job_id']);
        if (!$job || (int) $job['recruiter_id'] !== $recruiterId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'You do not have permission to update this application.'])->setStatusCode(403);
        }

        $applicationModel->update($applicationId, ['status' => $newStatus]);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Application status updated.']);
    }

    /**
     * Send bulk email to selected candidates.
     */
    public function sendBulkEmail(int $jobId)
    {
        if (session()->get('role') !== 'recruiter') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $recruiterId = (int) session()->get('user_id');
        $jobModel = model(JobModel::class);
        $job = $jobModel->where('id', $jobId)->where('recruiter_id', $recruiterId)->first();

        if (!$job) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Job not found.'])->setStatusCode(404);
        }

        $emails = $this->request->getPost('emails');
        $subject = trim((string) $this->request->getPost('subject'));
        $body = trim((string) $this->request->getPost('body'));

        if (empty($emails) || !is_array($emails)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No recipients specified.'])->setStatusCode(400);
        }

        if (empty($subject)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Email subject is required.'])->setStatusCode(400);
        }

        if (empty($body)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Email body is required.'])->setStatusCode(400);
        }

        // Validate and filter emails
        $validEmails = array_filter($emails, function($email) {
            return filter_var(trim((string) $email), FILTER_VALIDATE_EMAIL);
        });

        if (empty($validEmails)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No valid email addresses found.'])->setStatusCode(400);
        }

        $sentCount = 0;
        $failedCount = 0;
        $recruiterName = session()->get('name') ?? 'Recruiter';

        try {
            $emailConfig = config('Email');
            $email = \Config\Services::email(null, false);
            $email->clear(true);
            $email->setMailType('html');

            if ($emailConfig->fromEmail !== '') {
                $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName ?: 'HireMatrix');
            }

            $email->setSubject($subject);
            
            // Create HTML body with styling
            $htmlBody = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">';
            $htmlBody .= '<div style="background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">';
            $htmlBody .= '<h2 style="margin: 0;">Message from ' . esc($job['company'] ?? 'Our Company') . '</h2>';
            $htmlBody .= '</div>';
            $htmlBody .= '<div style="padding: 30px; background: #fff; border: 1px solid #e2e8f0; border-top: none;">';
            $htmlBody .= '<p style="color: #334155; line-height: 1.8;">' . nl2br(esc($body)) . '</p>';
            $htmlBody .= '<hr style="border: none; border-top: 1px solid #e2e8f0; margin: 30px 0;">';
            $htmlBody .= '<p style="color: #64748b; font-size: 14px;">';
            $htmlBody .= '<strong>Best regards,</strong><br>';
            $htmlBody .= esc($recruiterName) . '<br>';
            $htmlBody .= esc($job['company'] ?? 'Recruiting Team');
            $htmlBody .= '</p>';
            $htmlBody .= '</div>';
            $htmlBody .= '<div style="padding: 15px; background: #f8fafc; text-align: center; color: #94a3b8; font-size: 12px; border-radius: 0 0 8px 8px; border: 1px solid #e2e8f0; border-top: none;">';
            $htmlBody .= 'This email was sent via HireMatrix Recruitment Portal';
            $htmlBody .= '</div>';
            $htmlBody .= '</div>';

            $email->setMessage($htmlBody);

            foreach ($validEmails as $recipient) {
                $recipient = trim((string) $recipient);
                if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                    $email->setTo($recipient);
                    if ($email->send(false)) {
                        $sentCount++;
                    } else {
                        $failedCount++;
                    }
                    // Clear the recipient for next iteration
                    $email->clear(true);
                    $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName ?: 'HireMatrix');
                    $email->setSubject($subject);
                    $email->setMessage($htmlBody);
                }
            }

            return $this->response->setJSON([
                'status' => 'success',
                'message' => "Email sent to {$sentCount} candidate(s)." . ($failedCount > 0 ? " {$failedCount} failed." : '')
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'Bulk email send failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to send email. Please try again.'
            ])->setStatusCode(500);
        }
    }
}
