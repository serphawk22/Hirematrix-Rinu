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

        // Calculate Conversion Rate (Hired / Total Apps)
        $conversionRate = $totalApps > 0 ? round(($pipeline['Hired'] / $totalApps) * 100) : 0;

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
                if (isset($pipeline[$status])) $pipeline[$status] = (int)$row['count'];
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
                'applications_count' => array_sum($pipeline),
                'created_at'=> $job['created_at'],
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
        $jobId = (int) $this->request->getVar('job_id');
        $query = $this->request->getVar('q');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $appModel = new ApplicationModel();
        $appsBuilder = $appModel->select('
                applications.*,
                users.name as candidate_name,
                users.email as candidate_email,
                jobs.title as job_title,
                jobs.recruiter_id,
                jobs.company_id,
                jobs.required_skills,
                jobs.location as job_location,
                candidate_profiles.key_skills,
                candidate_profiles.resume_path,
                candidate_profiles.location as candidate_location,
                candidate_profiles.is_fresher_candidate
            ')
            ->join('users', 'users.id = applications.candidate_id')
            ->join('jobs', 'jobs.id = applications.job_id')
            ->join('candidate_profiles', 'candidate_profiles.user_id = applications.candidate_id', 'left')
            ->where('jobs.recruiter_id', $recruiterId);

        if ($jobId > 0) {
            $appsBuilder->where('applications.job_id', $jobId);
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
                'match_score'    => (string)$this->calculateMobileMatchScore($skills, (string) ($app['required_skills'] ?? '')),
                'experience'     => $app['is_fresher_candidate'] ? 'Fresher' : 'Experienced',
                'skills'         => $skills,
                'resume_link'    => $app['resume_path'] ?? '',
                'resume_url'     => $this->toPublicUrl((string) ($app['resume_path'] ?? '')),
                'location'       => $app['candidate_location'] ?: ($app['job_location'] ?? ''),
                'applied_at'     => $app['applied_at'],
            ];
        }

        return $this->respond([
            'success'      => true,
            'applications' => $formattedApps
        ]);
    }

    public function getInterviews()
    {
        $recruiterId = $this->request->getVar('recruiter_id');
        if (!$recruiterId) return $this->fail('Recruiter ID required');

        $userModel = new UserModel();
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $slotModel = new \App\Models\InterviewSlotModel();
        $slots = $slotModel->select('
                interview_slots.*, 
                jobs.title as job_title, 
                interview_bookings.id as booking_id, 
                interview_bookings.booking_status, 
                users.name as candidate_name, 
                interview_bookings.calendar_html_link, 
                interview_bookings.calendar_add_link
            ')
            ->join('jobs', 'jobs.id = interview_slots.job_id')
            ->join('interview_bookings', 'interview_bookings.slot_id = interview_slots.id', 'left')
            ->join('users', 'users.id = interview_bookings.user_id', 'left')
            ->where('jobs.recruiter_id', $recruiterId)
            ->where('interview_slots.slot_datetime >=', date('Y-m-d 00:00:00'))
            ->orderBy('interview_slots.slot_datetime', 'ASC')
            ->findAll();

        $formattedInterviews = [];
        foreach ($slots as $slot) {
            $meetingLink = $slot['calendar_html_link'] ?: $slot['calendar_add_link'] ?: '';
            $status = !empty($slot['booking_status']) ? ucwords(str_replace('_', ' ', $slot['booking_status'])) : 'Available';
            
            $formattedInterviews[] = [
                'id' => (string)($slot['booking_id'] ?: $slot['id']),
                'candidate_name' => $slot['candidate_name'] ?: 'Available Slot (Unbooked)',
                'job_title' => $slot['job_title'],
                'interview_date' => $slot['slot_datetime'],
                'interview_type' => $status,
                'interview_mode' => !empty($meetingLink) ? 'Online' : 'Offline',
                'meeting_link' => $meetingLink,
                'is_booked' => !empty($slot['booking_id']),
            ];
        }

        return $this->respond([
            'success'    => true,
            'interviews' => $formattedInterviews
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
        $recruiter = $userModel->find((int)$recruiterId);
        if (!$recruiter) return $this->failNotFound('Recruiter not found');

        $jobModel = new JobModel();
        $data = [
            'title' => $this->request->getVar('job_title'),
            'recruiter_id' => $recruiterId,
            'company_id' => $recruiter['company_id'],
            'location' => $this->request->getVar('job_location'),
            'description' => $this->request->getVar('job_description'),
            'employment_type' => $this->request->getVar('job_type'),
            'experience_level' => $this->request->getVar('experience_required'),
            'salary_range' => $this->request->getVar('salary_range'),
            'required_skills' => $this->request->getVar('skills_required'),
            'status' => 'open',
        ];

        if ($jobModel->insert($data)) {
            return $this->respondCreated([
                'success' => true,
                'message' => 'Job posted successfully',
                'job_id' => (string)$jobModel->getInsertID()
            ]);
        }

        return $this->fail('Failed to post job');
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
        $data = [
            'name'                      => $this->request->getVar('company_name'),
            'website'                   => $this->request->getVar('website'),
            'careers_page_url'          => $this->request->getVar('careers_page_url'),
            'industry'                  => $this->request->getVar('industry'),
            'company_size'              => $this->request->getVar('company_size'),
            'hq'                        => $this->request->getVar('hq_location'),
            'branch_locations'          => $this->request->getVar('branch_locations'),
            'short_description'         => $this->request->getVar('short_description'),
            'about'                     => $this->request->getVar('about_company'),
            'linkedin_url'              => $this->request->getVar('linkedin_url'),
            'twitter_url'               => $this->request->getVar('twitter_url'),
            'facebook_url'              => $this->request->getVar('facebook_url'),
            'instagram_url'             => $this->request->getVar('instagram_url'),
            'youtube_url'               => $this->request->getVar('youtube_url'),
            'mission'                   => $this->request->getVar('mission_values'),
            'culture'                   => $this->request->getVar('culture_environment'),
            'benefits'                  => $this->request->getVar('employee_benefits'),
            'office_tour_title'         => $this->request->getVar('office_tour_title'),
            'office_tour_url'           => $this->request->getVar('office_tour_url'),
            'office_tour_summary'       => $this->request->getVar('office_tour_summary'),
            'hr_email'                  => $this->request->getVar('hr_support_email'),
            'phone'                     => $this->request->getVar('recruiter_phone'),
            'public_contact_visibility' => $this->request->getVar('public_contact_visibility'),
        ];

        // Filtering out null values to prevent overwriting with null
        $data = array_filter($data, fn($v) => !is_null($v));

        if ($companyModel->update($companyId, $data)) {
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
}
