<?php

namespace App\Controllers;

use App\Libraries\AiCandidateMatcher;
use App\Libraries\AtsScoreService;
use App\Libraries\ResumeTemplateRenderer;
use App\Models\UserModel;
use App\Models\ApplicationModel;
use App\Models\CandidateResumeVersionModel;
use App\Models\WorkExperienceModel;
use App\Models\EducationModel;
use App\Models\CertificationModel;
use App\Models\CandidateSkillsModel;
use App\Models\CandidateInterestsModel;
use App\Models\CandidateProjectModel;
use App\Models\GithubAnalysisModel;
use App\Models\RecruiterCandidateActionModel;
use App\Models\NotificationModel;
use App\Models\RecruiterCandidateMessageModel;
use App\Models\RecruiterCandidateNoteModel;
use App\Models\RecruiterJobInvitationModel;
use App\Models\JobModel;

class RecruiterCandidates extends BaseController
{
    private const ACTION_DEDUPE_HOURS = 24;

    public function index()
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $userModel = model('UserModel');
        $filters = [
            'keyword' => trim((string) $this->request->getGet('keyword')),
            'skills' => trim((string) $this->request->getGet('skills')),
            'location' => trim((string) $this->request->getGet('location')),
            'exp_min' => trim((string) $this->request->getGet('exp_min')),
            'exp_max' => trim((string) $this->request->getGet('exp_max')),
            'resume' => trim((string) $this->request->getGet('resume')),
            'job_id' => (int) ($this->request->getGet('job_id') ?? 0),
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
        $jobModel = model('JobModel');
        $recruiterId = (int) session()->get('user_id');
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

        $this->applyRecruiterVisibilityFilter($builder, $recruiterId);

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
            $builder->where(
                'candidate_profiles.location LIKE ' . $builder->db->escape('%' . $filters['location'] . '%'),
                null,
                false
            );
        }

        if ($expMinMonths !== null) {
            $builder->where('COALESCE(candidate_experience.total_experience_months, 0) >= ' . $expMinMonths, null, false);
        }

        if ($expMaxMonths !== null) {
            $builder->where('COALESCE(candidate_experience.total_experience_months, 0) <= ' . $expMaxMonths, null, false);
        }

        if ($filters['resume'] === 'yes') {
            $builder->where('candidate_profiles.resume_path IS NOT NULL', null, false)
                ->where('candidate_profiles.resume_path <>', '');
        } elseif ($filters['resume'] === 'no') {
            $builder->groupStart()
                ->where('candidate_profiles.resume_path IS NULL', null, false)
                ->orWhere('candidate_profiles.resume_path =', '')
                ->groupEnd();
        }
        $candidates = $builder->paginate(12);
        $pager = $userModel->pager;

        foreach ($candidates as &$candidate) {
            $candidate['experience_display'] = $this->formatExperienceDisplay((int) ($candidate['total_experience_months'] ?? 0));
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

            $this->applyRecruiterVisibilityFilter($suggestionBuilder, $recruiterId);
            $this->applySelectedJobAvailabilityFilter($suggestionBuilder, (int) $selectedJob['id']);

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
                $suggestionBuilder->where(
                    'candidate_profiles.location LIKE ' . $suggestionBuilder->db->escape('%' . $filters['location'] . '%'),
                    null,
                    false
                );
            }

            if ($expMinMonths !== null) {
                $suggestionBuilder->where('COALESCE(candidate_experience.total_experience_months, 0) >= ' . $expMinMonths, null, false);
            }

            if ($expMaxMonths !== null) {
                $suggestionBuilder->where('COALESCE(candidate_experience.total_experience_months, 0) <= ' . $expMaxMonths, null, false);
            }

            if ($filters['resume'] === 'yes') {
                $suggestionBuilder->where('candidate_profiles.resume_path IS NOT NULL', null, false)
                    ->where('candidate_profiles.resume_path <>', '');
            } elseif ($filters['resume'] === 'no') {
                $suggestionBuilder->groupStart()
                    ->where('candidate_profiles.resume_path IS NULL', null, false)
                    ->orWhere('candidate_profiles.resume_path =', '')
                    ->groupEnd();
            }
            $candidatePool = $suggestionBuilder->limit(120)->findAll();
            $atsScoreService = new AtsScoreService();
            foreach ($candidatePool as &$poolRow) {
                $poolRow['experience_display'] = $this->formatExperienceDisplay((int) ($poolRow['total_experience_months'] ?? 0));
                $atsAnalysis = $atsScoreService->analyzeCandidateJob((int) ($poolRow['id'] ?? 0), $selectedJob);
                $poolRow['match_score'] = (int) ($atsAnalysis['score'] ?? 0);
                $poolRow['match_reason'] = (string) ($atsAnalysis['match_reason'] ?? 'ATS alignment based on current resume and profile signals.');
            }
            unset($poolRow);

            usort($candidatePool, static fn (array $a, array $b): int => ((int) ($b['match_score'] ?? 0)) <=> ((int) ($a['match_score'] ?? 0)));
            $aiSuggestions = array_values(array_slice(array_filter($candidatePool, static function (array $candidate): bool {
                return (int) ($candidate['match_score'] ?? 0) > 0;
            }), 0, 20));
        }

        return view('recruiter/candidates/index', [
            'candidates' => $candidates,
            'pager' => $pager,
            'recruiterJobs' => $recruiterJobs,
            'selectedJob' => $selectedJob,
            'aiSuggestions' => $aiSuggestions,
            'filters' => [
                'keyword' => $filters['keyword'],
                'skills' => $filters['skills'],
                'location' => $filters['location'],
                'exp_min' => $expMinYears !== null ? (string) $expMinYears : '',
                'exp_max' => $expMaxYears !== null ? (string) $expMaxYears : '',
                'resume' => $filters['resume'],
                'job_id' => $filters['job_id'],
            ],
        ]);
    }

    public function viewProfile($candidateId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);
        
        if (!$candidate || $candidate['role'] !== 'candidate') {
            return redirect()->back()->with('error', 'Candidate not found');
        }

        if (!$this->canRecruiterAccessCandidate((int) $candidateId, (int) session()->get('user_id'))) {
            return redirect()->back()->with('error', 'This candidate profile is private unless they apply to your jobs.');
        }

        $applicationId = (int) ($this->request->getGet('application_id') ?? 0);
        $jobId = (int) ($this->request->getGet('job_id') ?? 0);
        $recruiterId = (int) session()->get('user_id');
        $emailActivityId = (int) ($this->request->getGet('email_activity_id') ?? 0);
        if ($emailActivityId > 0) {
            return redirect()->to(base_url('recruiter/messages/' . (int) $candidateId) . '?' . http_build_query([
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'email_activity_id' => $emailActivityId,
            ]));
        }

        $actionModel = new RecruiterCandidateActionModel();
        $applicationContext = null;
        $applicationId = $this->resolveApplicationIdForCandidateJob((int) $candidateId, $recruiterId, $applicationId, $jobId);

        if ($applicationId > 0) {
            $applicationContext = (new ApplicationModel())
                ->select('applications.*, jobs.title as job_title, jobs.recruiter_id as job_recruiter_id')
                ->join('jobs', 'jobs.id = applications.job_id', 'left')
                ->where('applications.id', $applicationId)
                ->where('applications.candidate_id', (int) $candidateId)
                ->first();

            if ($applicationContext && (int) ($applicationContext['job_recruiter_id'] ?? 0) !== $recruiterId) {
                $applicationContext = null;
            } elseif ($applicationContext) {
                $applicationContext['questionnaire_items'] = $this->decodeQuestionnaireResponses(
                    (string) ($applicationContext['questionnaire_responses'] ?? '')
                );
            }
        }

        $wasLogged = $actionModel->logAction(
            (int) $candidateId,
            $recruiterId,
            RecruiterCandidateActionModel::ACTION_PROFILE_VIEWED,
            $applicationId > 0 ? $applicationId : null,
            $jobId > 0 ? $jobId : null,
            self::ACTION_DEDUPE_HOURS
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
        
        $workExpModel = new WorkExperienceModel();
        $educationModel = new EducationModel();
        $certificationModel = new CertificationModel();
        $skillsModel = new CandidateSkillsModel();
        $interestsModel = new CandidateInterestsModel();
        $githubModel = new GithubAnalysisModel();
        $projectModel = new CandidateProjectModel();

        $workExperiences = $workExpModel->getByUser($candidateId);
        $education = $educationModel->getByUser($candidateId);
        $certifications = $certificationModel->getByUser($candidateId);
        // Calculate total experience in months
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
        $projects = \Config\Database::connect()->tableExists('candidate_projects')
            ? $projectModel->getByUser((int) $candidateId)
            : [];
        $messages = (new RecruiterCandidateMessageModel())->getThread(
            (int) $candidateId,
            (int) $recruiterId,
            $applicationId > 0 ? $applicationId : null
        );
        if (\Config\Database::connect()->tableExists('recruiter_mailbox_connections')) {
            try {
                (new \App\Libraries\RecruiterMailboxService())->syncRecruiterIfStale($recruiterId, 300);
            } catch (\Throwable $e) {
                log_message('error', 'Recruiter mailbox auto-sync failed on candidate profile: ' . $e->getMessage());
            }
        }
        $emailActivities = [];
        if (\Config\Database::connect()->tableExists('recruiter_email_activities')) {
            $emailBuilder = (new \App\Models\RecruiterEmailActivityModel())
                ->where('candidate_id', (int) $candidateId)
                ->where('recruiter_id', (int) $recruiterId);
            if ($applicationId > 0) {
                $emailBuilder->where('application_id', $applicationId);
            }
            $emailActivities = $emailBuilder->orderBy('occurred_at', 'DESC')->findAll(20);
        }
        $recruiterNote = (new RecruiterCandidateNoteModel())->getByCandidateAndRecruiter(
            (int) $candidateId,
            (int) $recruiterId
        );
        
        return view('recruiter/candidate_profile', [
            'candidate' => $candidate,
            'workExperiences' => $workExperiences,
            'education' => $education,
            'certifications' => $certifications,
            'skills' => $skills,
            'interests' => $interests,
            'github' => $github,
            'projects' => $projects,
            'messages' => $messages,
            'emailActivities' => $emailActivities,
            'totalExperienceMonths' => $totalExperienceMonths,
            'isFresherCandidate' => (int)($candidate['is_fresher_candidate'] ?? 0) === 1,
            'recruiterNote' => $recruiterNote,
            'recruiterJobs' => $this->getRecruiterOpenJobs($recruiterId),
            'jobInvitations' => $this->getCandidateInvitationStatusMap((int) $candidateId, $recruiterId),
            'applicationContext' => $applicationContext,
        ]);
    }

    public function communication($candidateId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $candidateId = (int) $candidateId;
        $recruiterId = (int) session()->get('user_id');
        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || ($candidate['role'] ?? '') !== 'candidate') {
            return redirect()->back()->with('error', 'Candidate not found');
        }

        if (!$this->canRecruiterAccessCandidate($candidateId, $recruiterId)) {
            return redirect()->back()->with('error', 'This candidate profile is private unless they apply to your jobs.');
        }

        $applicationId = (int) ($this->request->getGet('application_id') ?? 0);
        $jobId = (int) ($this->request->getGet('job_id') ?? 0);
        $applicationId = $this->resolveApplicationIdForCandidateJob($candidateId, $recruiterId, $applicationId, $jobId);

        $applicationContext = null;
        if ($applicationId > 0) {
            $applicationContext = (new ApplicationModel())
                ->select('applications.id, applications.job_id, jobs.title as job_title')
                ->join('jobs', 'jobs.id = applications.job_id', 'left')
                ->where('applications.id', $applicationId)
                ->where('applications.candidate_id', $candidateId)
                ->where('jobs.recruiter_id', $recruiterId)
                ->first();
            $jobId = (int) ($applicationContext['job_id'] ?? $jobId);
        }

        $messages = (new RecruiterCandidateMessageModel())->getThread(
            $candidateId,
            $recruiterId,
            $applicationId > 0 ? $applicationId : null
        );

        if (\Config\Database::connect()->tableExists('recruiter_mailbox_connections')) {
            try {
                (new \App\Libraries\RecruiterMailboxService())->syncRecruiterIfStale($recruiterId, 300);
            } catch (\Throwable $e) {
                log_message('error', 'Recruiter mailbox auto-sync failed on communication page: ' . $e->getMessage());
            }
        }

        $emailActivities = [];
        if (\Config\Database::connect()->tableExists('recruiter_email_activities')) {
            $emailBuilder = (new \App\Models\RecruiterEmailActivityModel())
                ->where('candidate_id', $candidateId)
                ->where('recruiter_id', $recruiterId);
            if ($applicationId > 0) {
                $emailBuilder->where('application_id', $applicationId);
            }
            $emailActivities = $emailBuilder->orderBy('occurred_at', 'ASC')->findAll(100);
        }

        $trimQuotedEmail = static function (string $body): string {
            $body = trim($body);
            foreach ([
                '/\n\s*On\s.+?wrote:\s*/is',
                '/\n\s*From:\s.+/is',
                '/\n\s*-{2,}\s*Original Message\s*-{2,}.*/is',
            ] as $pattern) {
                $body = preg_replace($pattern, '', $body) ?? $body;
            }
            return trim($body);
        };

        $communicationItems = [];
        foreach ($messages as $msg) {
            $isRecruiterMsg = ($msg['sender_role'] ?? '') === 'recruiter';
            $communicationItems[] = [
                'id' => 'message-' . (int) ($msg['id'] ?? 0),
                'source' => 'Portal Message',
                'direction' => $isRecruiterMsg ? 'outbound' : 'inbound',
                'sender' => $isRecruiterMsg ? 'You' : (string) ($candidate['name'] ?? 'Candidate'),
                'subject' => '',
                'body' => (string) ($msg['message'] ?? ''),
                'time' => (string) ($msg['created_at'] ?? date('Y-m-d H:i:s')),
            ];
        }
        foreach ($emailActivities as $emailActivity) {
            $isOutboundEmail = ($emailActivity['direction'] ?? '') === 'outbound';
            $communicationItems[] = [
                'id' => 'email-' . (int) ($emailActivity['id'] ?? 0),
                'source' => 'Email',
                'direction' => $isOutboundEmail ? 'outbound' : 'inbound',
                'sender' => $isOutboundEmail ? 'You' : (string) ($candidate['name'] ?? 'Candidate'),
                'subject' => (string) ($emailActivity['subject'] ?? ''),
                'body' => $trimQuotedEmail((string) ($emailActivity['body_text'] ?? '')),
                'time' => (string) ($emailActivity['occurred_at'] ?? date('Y-m-d H:i:s')),
            ];
        }
        usort($communicationItems, static fn (array $a, array $b): int => strtotime($a['time']) <=> strtotime($b['time']));

        return view('recruiter/candidate_communication', [
            'candidate' => $candidate,
            'applicationId' => $applicationId,
            'jobId' => $jobId,
            'applicationContext' => $applicationContext,
            'communicationItems' => $communicationItems,
            'emailActivityId' => (int) ($this->request->getGet('email_activity_id') ?? 0),
            'returnUrl' => current_url() . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''),
        ]);
    }

    public function redirectCommunication($candidateId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $query = (string) ($_SERVER['QUERY_STRING'] ?? '');
        $target = base_url('recruiter/messages/' . (int) $candidateId);
        if ($query !== '') {
            $target .= '?' . $query;
        }

        return redirect()->to($target);
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function decodeQuestionnaireResponses(string $rawResponses): array
    {
        if (trim($rawResponses) === '') {
            return [];
        }

        $decoded = json_decode($rawResponses, true);
        if (!is_array($decoded)) {
            return [];
        }

        $items = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $answer = trim((string) ($row['answer'] ?? ''));
            if ($label === '' || $answer === '') {
                continue;
            }

            $items[] = [
                'label' => $label,
                'answer' => $answer,
                'type' => trim((string) ($row['type'] ?? 'textarea')),
            ];
        }

        return $items;
    }

    public function viewContact($candidateId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || $candidate['role'] !== 'candidate') {
            return redirect()->back()->with('error', 'Candidate not found');
        }

        if (!$this->canRecruiterAccessCandidate((int) $candidateId, (int) session()->get('user_id'))) {
            return redirect()->back()->with('error', 'This candidate profile is private unless they apply to your jobs.');
        }

        $applicationId = (int) ($this->request->getGet('application_id') ?? 0);
        $jobId = (int) ($this->request->getGet('job_id') ?? 0);
        $applicationId = $this->resolveApplicationIdForCandidateJob((int) $candidateId, (int) session()->get('user_id'), $applicationId, $jobId);

        $wasLogged = (new RecruiterCandidateActionModel())->logAction(
            (int) $candidateId,
            (int) session()->get('user_id'),
            RecruiterCandidateActionModel::ACTION_CONTACT_VIEWED,
            $applicationId > 0 ? $applicationId : null,
            $jobId > 0 ? $jobId : null,
            self::ACTION_DEDUPE_HOURS
        );

        if ($wasLogged) {
            $this->notifyCandidateAction(
                (int) $candidateId,
                $applicationId > 0 ? $applicationId : null,
                'recruiter_contact_viewed',
                'Contact Viewed',
                'One recruiter viewed your contact details.'
            );
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'status' => 'success',
                'email' => (string) ($candidate['email'] ?? ''),
                'phone' => (string) ($candidate['phone'] ?? ''),
                'logged' => $wasLogged,
                'csrf_hash' => csrf_hash(),
            ]);
        }

        $redirectUrl = base_url('recruiter/candidate/' . $candidateId)
            . '?show_contact=1'
            . '&application_id=' . $applicationId
            . '&job_id=' . $jobId;

        return redirect()->to($redirectUrl);
    }

    public function downloadResume($candidateId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || $candidate['role'] !== 'candidate') {
            return redirect()->back()->with('error', 'Resume not found.');
        }

        if (!$this->canRecruiterAccessCandidate((int) $candidateId, (int) session()->get('user_id'))) {
            return redirect()->back()->with('error', 'This candidate profile is private unless they apply to your jobs.');
        }

        $applicationId = (int) ($this->request->getGet('application_id') ?? 0);
        $jobId = (int) ($this->request->getGet('job_id') ?? 0);
        $applicationId = $this->resolveApplicationIdForCandidateJob((int) $candidateId, (int) session()->get('user_id'), $applicationId, $jobId);
        $submittedResumeVersion = $this->getSubmittedResumeVersion((int) $candidateId, $applicationId);

        $wasLogged = (new RecruiterCandidateActionModel())->logAction(
            (int) $candidateId,
            (int) session()->get('user_id'),
            RecruiterCandidateActionModel::ACTION_RESUME_DOWNLOADED,
            $applicationId > 0 ? $applicationId : null,
            $jobId > 0 ? $jobId : null,
            self::ACTION_DEDUPE_HOURS
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

        if ($submittedResumeVersion) {
            $renderer = new ResumeTemplateRenderer();
            $pdfPath = $renderer->createPdfFile((string) ($submittedResumeVersion['content'] ?? ''), [
                'name' => (string) ($candidate['name'] ?? 'Candidate'),
                'target_role' => (string) ($submittedResumeVersion['target_role'] ?? ''),
                'summary' => (string) ($submittedResumeVersion['summary'] ?? ''),
                'highlight_skills' => array_values(array_filter(array_map('trim', explode(',', (string) ($submittedResumeVersion['highlight_skills'] ?? ''))))),
            ], (string) (($candidate['name'] ?? 'candidate') . '-' . ($submittedResumeVersion['target_role'] ?? 'resume')));

            return $this->response->download($pdfPath, null)->setFileName(basename($pdfPath));
        }

        if (empty($candidate['resume_path'])) {
            return redirect()->back()->with('error', 'Resume file not found.');
        }

        $filePath = WRITEPATH . $candidate['resume_path'];
        if (!is_file($filePath)) {
            return redirect()->back()->with('error', 'Resume file not found.');
        }

        return $this->response->download($filePath, null);
    }

    public function previewResume($candidateId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || ($candidate['role'] ?? '') !== 'candidate') {
            return $this->response->setStatusCode(404)->setBody('Resume not found.');
        }

        $recruiterId = (int) session()->get('user_id');
        if (!$this->canRecruiterAccessCandidate((int) $candidateId, $recruiterId)) {
            return $this->response->setStatusCode(403)->setBody('This candidate profile is private unless they apply to your jobs.');
        }

        $applicationId = (int) ($this->request->getGet('application_id') ?? 0);
        $jobId = (int) ($this->request->getGet('job_id') ?? 0);
        $applicationId = $this->resolveApplicationIdForCandidateJob((int) $candidateId, $recruiterId, $applicationId, $jobId);
        $submittedResumeVersion = $this->getSubmittedResumeVersion((int) $candidateId, $applicationId);

        if ($submittedResumeVersion) {
            $renderer = new ResumeTemplateRenderer();
            return $renderer->renderDocument((string) ($submittedResumeVersion['content'] ?? ''), [
                'name' => (string) ($candidate['name'] ?? 'Candidate'),
                'target_role' => (string) ($submittedResumeVersion['target_role'] ?? ''),
                'summary' => (string) ($submittedResumeVersion['summary'] ?? ''),
                'highlight_skills' => array_values(array_filter(array_map('trim', explode(',', (string) ($submittedResumeVersion['highlight_skills'] ?? ''))))),
            ]);
        }

        if (empty($candidate['resume_path'])) {
            return $this->response->setStatusCode(404)->setBody('Resume file not found.');
        }

        $filePath = WRITEPATH . $candidate['resume_path'];
        if (!is_file($filePath)) {
            return $this->response->setStatusCode(404)->setBody('Resume file not found.');
        }

        $mime = mime_content_type($filePath) ?: 'application/octet-stream';
        $disposition = strpos(strtolower((string) $mime), 'pdf') !== false ? 'inline' : 'attachment';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', $disposition . '; filename="' . basename($filePath) . '"')
            ->setBody(file_get_contents($filePath));
    }

    public function sendMessage($candidateId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || $candidate['role'] !== 'candidate') {
            return redirect()->back()->with('error', 'Candidate not found');
        }

        if (!$this->canRecruiterAccessCandidate((int) $candidateId, (int) session()->get('user_id'))) {
            return redirect()->back()->with('error', 'This candidate profile is private unless they apply to your jobs.');
        }

        $message = trim((string) $this->request->getPost('message'));
        $applicationId = (int) ($this->request->getPost('application_id') ?? 0);
        $jobId = (int) ($this->request->getPost('job_id') ?? 0);
        $job = $jobId > 0 ? (new JobModel())->find($jobId) : null;
        $message = $this->personalizeRecruiterMessageTemplate($message, $candidate, $job);
        $delivery = $this->prepareRecruiterMessageDelivery($message, $candidate, $job);
        $message = $delivery['body'];

        if ($message === '' || preg_match('/[[:alnum:]]/u', $message) !== 1) {
            return redirect()->back()->with('error', 'Message cannot be empty.');
        }

        if (mb_strlen($message) > 1000) {
            return redirect()->back()->with('error', 'Message is too long. Max 1000 characters.');
        }

        $recruiterName = (string) (session()->get('user_name') ?? 'Recruiter');
        $messageModel = new RecruiterCandidateMessageModel();

        $messageModel->insert([
            'candidate_id' => (int) $candidateId,
            'recruiter_id' => (int) session()->get('user_id'),
            'application_id' => $applicationId > 0 ? $applicationId : null,
            'job_id' => $jobId > 0 ? $jobId : null,
            'sender_id' => (int) session()->get('user_id'),
            'sender_role' => 'recruiter',
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if (\Config\Database::connect()->tableExists('recruiter_mailbox_connections')) {
            $mailbox = (new \App\Models\RecruiterMailboxConnectionModel())
                ->getConnectedForRecruiter((int) session()->get('user_id'));
            if ($mailbox && !empty($candidate['email'])) {
                $subject = $delivery['subject'];
                (new \App\Libraries\RecruiterMailboxService())->sendForRecruiter(
                    (int) session()->get('user_id'),
                    (string) $candidate['email'],
                    $subject,
                    '<p>' . nl2br(esc($message)) . '</p>',
                    ['candidate_id' => (int) $candidateId, 'application_id' => $applicationId ?: null, 'job_id' => $jobId ?: null]
                );
            }
        }

        $this->notifyCandidateAction(
            (int) $candidateId,
            $applicationId > 0 ? $applicationId : null,
            'recruiter_message',
            'Message from Recruiter',
            "{$recruiterName} sent you a message. Open conversation to read it.",
            base_url('candidate/messages/' . (int) session()->get('user_id') . ($applicationId > 0 ? '?application_id=' . $applicationId : ''))
        );

        $returnTo = trim((string) ($this->request->getPost('return_to') ?? ''));
        $baseUrl = rtrim(base_url(), '/');
        if ($returnTo !== '' && str_starts_with($returnTo, $baseUrl)) {
            $redirectUrl = $returnTo;
        } else {
            $redirectUrl = base_url('recruiter/candidate/' . $candidateId)
                . '?application_id=' . $applicationId
                . '&job_id=' . $jobId
                . '&show_contact=' . (int) ($this->request->getPost('show_contact') ?? 0);
        }

        return redirect()->to($redirectUrl)->with('success', 'Message sent to candidate.');
    }

    private function personalizeRecruiterMessageTemplate(string $message, array $candidate, ?array $job = null): string
    {
        $candidateName = trim((string) ($candidate['name'] ?? 'Candidate'));
        $recruiterName = trim((string) (session()->get('user_name') ?? 'Recruiter'));
        $jobTitle = trim((string) ($job['title'] ?? 'the role'));
        $companyName = trim((string) (($job['company'] ?? '') ?: (session()->get('company_name') ?? '')));

        $message = strtr($message, [
            '{candidate_name}' => $candidateName,
            '{{candidate_name}}' => $candidateName,
            '[candidate name]' => $candidateName,
            '[candidate_name]' => $candidateName,
            '[candidate\'s name]' => $candidateName,
            '[Candidate Name]' => $candidateName,
            '[Candidate\'s Name]' => $candidateName,
            '{recruiter_name}' => $recruiterName,
            '{{recruiter_name}}' => $recruiterName,
            '[recruiter name]' => $recruiterName,
            '[Recruiter Name]' => $recruiterName,
            '{job_title}' => $jobTitle,
            '{{job_title}}' => $jobTitle,
            '[job title]' => $jobTitle,
            '[Job Title]' => $jobTitle,
            '{company_name}' => $companyName,
            '{{company_name}}' => $companyName,
            '[company name]' => $companyName,
            '[Company Name]' => $companyName,
        ]);

        return trim(preg_replace('/\*\*(Subject|Message|Body):\*\*/i', '$1:', $message) ?? $message);
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function prepareRecruiterMessageDelivery(string $message, array $candidate, ?array $job = null): array
    {
        $body = trim($message);
        $subject = '';

        if (preg_match('/^\s*(?:\*\*)?Subject(?:\*\*)?\s*:\s*(.+?)(?:\r?\n|$)(.*)$/is', $body, $matches)) {
            $subject = trim((string) $matches[1]);
            $body = trim((string) $matches[2]);
        }

        $subject = trim(preg_replace('/\*\*/', '', $subject) ?? $subject);
        if ($subject === '') {
            $subject = $job
                ? 'Regarding your application for ' . (string) ($job['title'] ?? 'the role')
                : 'Message from ' . (string) (session()->get('user_name') ?? 'Recruiter');
        }

        return [
            'subject' => mb_substr($subject, 0, 160),
            'body' => $body,
        ];
    }

    private function buildRecruiterEmailHtml(string $body, string $recruiterName, string $companyName): string
    {
        return '
            <div style="font-family:Arial,sans-serif;max-width:620px;margin:0 auto;padding:20px;color:#0f172a;">
                <div style="background:#1FB7B5;color:#ffffff;padding:20px;border-radius:10px 10px 0 0;">
                    <h2 style="margin:0;font-size:22px;">Message from HireMatrix</h2>
                </div>
                <div style="padding:28px;background:#ffffff;border:1px solid #d9ece5;border-top:none;">
                    <p style="margin:0;color:#334155;line-height:1.8;">' . nl2br(esc($body)) . '</p>
                    <hr style="border:none;border-top:1px solid #e2e8f0;margin:28px 0;">
                    <p style="margin:0;color:#64748b;font-size:14px;line-height:1.7;">
                        <strong>Best regards,</strong><br>
                        ' . esc($recruiterName) . '<br>
                        ' . esc($companyName) . '
                    </p>
                </div>
                <div style="padding:14px;background:#f8fafc;text-align:center;color:#94a3b8;font-size:12px;border:1px solid #d9ece5;border-top:none;border-radius:0 0 10px 10px;">
                    This email was sent via HireMatrix Recruitment Portal.
                </div>
            </div>';
    }

    public function saveNotes($candidateId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || $candidate['role'] !== 'candidate') {
            return redirect()->back()->with('error', 'Candidate not found');
        }

        $recruiterId = (int) session()->get('user_id');
        if (!$this->canRecruiterAccessCandidate((int) $candidateId, $recruiterId)) {
            return redirect()->back()->with('error', 'This candidate profile is private unless they apply to your jobs.');
        }

        $rawTags = trim((string) $this->request->getPost('tags'));
        $notes = trim((string) $this->request->getPost('notes'));

        if (mb_strlen($rawTags) > 255) {
            return redirect()->back()->with('error', 'Tags are too long. Max 255 characters.');
        }

        if (mb_strlen($notes) > 5000) {
            return redirect()->back()->with('error', 'Notes are too long. Max 5000 characters.');
        }

        $tags = $this->normalizeTags($rawTags);
        $noteModel = new RecruiterCandidateNoteModel();
        $existing = $noteModel->getByCandidateAndRecruiter((int) $candidateId, $recruiterId);

        $data = [
            'candidate_id' => (int) $candidateId,
            'recruiter_id' => $recruiterId,
            'tags' => $tags,
            'notes' => $notes,
        ];

        if ($existing) {
            $noteModel->update((int) $existing['id'], $data);
        } else {
            $noteModel->insert($data);
        }

        $applicationId = (int) ($this->request->getPost('application_id') ?? 0);
        $jobId = (int) ($this->request->getPost('job_id') ?? 0);
        $showContact = (int) ($this->request->getPost('show_contact') ?? 0);

        $redirectUrl = base_url('recruiter/candidate/' . $candidateId)
            . '?application_id=' . $applicationId
            . '&job_id=' . $jobId
            . '&show_contact=' . $showContact;

        return redirect()->to($redirectUrl)->with('success', 'Recruiter notes saved.');
    }

    public function inviteToJob($candidateId)
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $result = $this->performJobInvitation(
            (int) session()->get('user_id'),
            (int) $candidateId,
            (int) ($this->request->getPost('job_id') ?? 0),
            trim((string) $this->request->getPost('message'))
        );
        $returnTo = trim((string) $this->request->getPost('return_to'));

        $redirectTarget = $returnTo !== '' ? $returnTo : base_url('recruiter/candidate/' . (int) $candidateId);
        $flashType = ($result['ok'] ?? false) ? 'success' : 'error';

        return redirect()->to($redirectTarget)->with($flashType, (string) ($result['message'] ?? 'Could not send invitation.'));
    }

    public function bulkInviteToJob()
    {
        if (session()->get('role') !== 'recruiter') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $candidateIds = $this->request->getPost('candidate_ids');
        $candidateIds = is_array($candidateIds) ? array_values(array_unique(array_map('intval', $candidateIds))) : [];
        $jobId = (int) ($this->request->getPost('job_id') ?? 0);
        $customMessage = trim((string) $this->request->getPost('message'));
        $returnTo = trim((string) $this->request->getPost('return_to'));

        if (empty($candidateIds)) {
            return redirect()->back()->with('error', 'Select at least one candidate to send invitations.');
        }

        $successCount = 0;
        $skippedCount = 0;
        $firstError = '';

        foreach ($candidateIds as $candidateId) {
            if ($candidateId <= 0) {
                continue;
            }

            $result = $this->performJobInvitation((int) session()->get('user_id'), $candidateId, $jobId, $customMessage);
            if ($result['ok'] ?? false) {
                $successCount++;
                continue;
            }

            $skippedCount++;
            if ($firstError === '') {
                $firstError = (string) ($result['message'] ?? 'Some invitations could not be sent.');
            }
        }

        $redirectTarget = $returnTo !== '' ? $returnTo : base_url('recruiter/resdex');
        if ($successCount > 0) {
            $message = $successCount . ' invitation' . ($successCount === 1 ? '' : 's') . ' sent successfully.';
            if ($skippedCount > 0) {
                $message .= ' ' . $skippedCount . ' skipped';
                if ($firstError !== '') {
                    $message .= ' because ' . strtolower(rtrim($firstError, '.')) . '.';
                } else {
                    $message .= '.';
                }
            }

            return redirect()->to($redirectTarget)->with('success', $message);
        }

        return redirect()->to($redirectTarget)->with('error', $firstError !== '' ? $firstError : 'No invitations were sent.');
    }

    public function sendBulkEmail()
    {
        if (session()->get('role') !== 'recruiter') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized'])->setStatusCode(403);
        }

        $candidateIds = $this->request->getPost('candidate_ids');
        $candidateIds = is_array($candidateIds) ? array_values(array_unique(array_map('intval', $candidateIds))) : [];
        $subject = trim((string) $this->request->getPost('subject'));
        $body = trim((string) $this->request->getPost('body'));

        if (empty($candidateIds)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Select at least one candidate.', 'csrf_hash' => csrf_hash()])->setStatusCode(400);
        }

        if ($subject === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Email subject is required.', 'csrf_hash' => csrf_hash()])->setStatusCode(400);
        }

        if ($body === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Email body is required.', 'csrf_hash' => csrf_hash()])->setStatusCode(400);
        }

        $recruiterId = (int) session()->get('user_id');
        $userModel = new UserModel();
        $candidates = [];

        foreach ($candidateIds as $candidateId) {
            if ($candidateId <= 0 || !$this->canRecruiterAccessCandidate($candidateId, $recruiterId)) {
                continue;
            }

            $candidate = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);
            if (!$candidate || ($candidate['role'] ?? '') !== 'candidate' || empty($candidate['email'])) {
                continue;
            }

            $candidates[$candidateId] = $candidate;
        }

        if (empty($candidates)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No valid candidate recipients found.', 'csrf_hash' => csrf_hash()])->setStatusCode(400);
        }

        $sentCount = 0;
        $failedCount = 0;
        $recruiterName = (string) (session()->get('user_name') ?? session()->get('name') ?? 'Recruiter');
        $companyName = (string) (session()->get('company_name') ?? 'Recruiting Team');
        try {
            $mailboxService = null;
            if (\Config\Database::connect()->tableExists('recruiter_mailbox_connections')) {
                $mailboxConnection = (new \App\Models\RecruiterMailboxConnectionModel())->getConnectedForRecruiter($recruiterId);
                if ($mailboxConnection) {
                    $mailboxService = new \App\Libraries\RecruiterMailboxService();
                }
            }

            if ($mailboxService !== null) {
                foreach ($candidates as $candidateId => $candidate) {
                    $personalSubject = $this->personalizeRecruiterMessageTemplate($subject, $candidate);
                    $personalBody = $this->personalizeRecruiterMessageTemplate($body, $candidate);
                    $htmlBody = $this->buildRecruiterEmailHtml($personalBody, $recruiterName, $companyName);
                    $sent = $mailboxService->sendForRecruiter($recruiterId, (string) $candidate['email'], $personalSubject, $htmlBody, [
                        'candidate_id' => (int) $candidateId,
                        'application_id' => null,
                        'job_id' => null,
                    ]);
                    $sent ? $sentCount++ : $failedCount++;
                }
            } else {
                $emailConfig = config('Email');
                $email = \Config\Services::email(null, false);
                foreach ($candidates as $candidate) {
                    $personalSubject = $this->personalizeRecruiterMessageTemplate($subject, $candidate);
                    $personalBody = $this->personalizeRecruiterMessageTemplate($body, $candidate);
                    $htmlBody = $this->buildRecruiterEmailHtml($personalBody, $recruiterName, $companyName);
                    $email->clear(true);
                    $email->setMailType('html');
                    if ($emailConfig->fromEmail !== '') {
                        $email->setFrom($emailConfig->fromEmail, $emailConfig->fromName ?: 'HireMatrix');
                    }
                    $email->setTo((string) $candidate['email']);
                    $email->setSubject($personalSubject);
                    $email->setMessage($htmlBody);
                    $email->send(false) ? $sentCount++ : $failedCount++;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'Candidate pool bulk email failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Failed to send email. Please try again.',
                'csrf_hash' => csrf_hash(),
            ])->setStatusCode(500);
        }

        return $this->response->setJSON([
            'status' => 'success',
            'message' => "Email sent to {$sentCount} candidate(s)." . ($failedCount > 0 ? " {$failedCount} failed." : ''),
            'csrf_hash' => csrf_hash(),
        ]);
    }

    private function notifyCandidateAction(
        int $candidateId,
        ?int $applicationId,
        string $type,
        string $title,
        string $message,
        ?string $actionLink = null
    ): void {
        $notificationModel = new NotificationModel();
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
            $userModel = new UserModel();
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
                        <h1 style="margin:0;font-size:26px;line-height:1.25;">' . esc($title) . '</h1>
                    </div>
                    <div style="padding:28px 30px;">
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">Hi ' . esc($candidateName) . ',</p>
                        <p style="margin:0 0 20px;font-size:15px;line-height:1.8;">' . esc($message) . '</p>
                        <a href="' . esc($actionLink) . '" style="display:inline-block;padding:13px 21px;border-radius:999px;background:#0b66ff;color:#ffffff;text-decoration:none;font-weight:700;">View Activity</a>
                        <p style="margin:18px 0 0;font-size:13px;line-height:1.7;color:#64748b;">You can manage email notifications from your candidate notification settings.</p>
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

    private function getRecruiterOpenJobs(int $recruiterId): array
    {
        return (new JobModel())
            ->select('id, title, company, status')
            ->where('recruiter_id', $recruiterId)
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }

    private function getCandidateInvitationStatusMap(int $candidateId, int $recruiterId): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('recruiter_job_invitations')) {
            return [];
        }

        $rows = (new RecruiterJobInvitationModel())
            ->where('candidate_id', $candidateId)
            ->where('recruiter_id', $recruiterId)
            ->orderBy('id', 'DESC')
            ->findAll();

        $map = [];
        foreach ($rows as $row) {
            $jobId = (int) ($row['job_id'] ?? 0);
            if ($jobId <= 0 || isset($map[$jobId])) {
                continue;
            }
            $map[$jobId] = $row;
        }

        return $map;
    }

    private function buildInvitationMessage(string $candidateName, string $jobTitle, string $companyName, string $recruiterName): string
    {
        return "Hi {$candidateName}, {$recruiterName} thinks your background could be a strong fit for {$jobTitle} at {$companyName}. Take a closer look and apply if the role feels right for your next move.";
    }

    private function performJobInvitation(int $recruiterId, int $candidateId, int $jobId, string $customMessage = ''): array
    {
        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || ($candidate['role'] ?? '') !== 'candidate') {
            return ['ok' => false, 'message' => 'Candidate not found.'];
        }

        if (!$this->canRecruiterAccessCandidate($candidateId, $recruiterId)) {
            return ['ok' => false, 'message' => 'This candidate profile is private unless they apply to your jobs.'];
        }

        $job = (new JobModel())
            ->where('id', $jobId)
            ->where('recruiter_id', $recruiterId)
            ->where('status', 'open')
            ->first();

        if (!$job) {
            return ['ok' => false, 'message' => 'Select a valid open job before sending an invitation.'];
        }

        $existingApplication = (new ApplicationModel())
            ->where('job_id', $jobId)
            ->where('candidate_id', $candidateId)
            ->where('status !=', 'withdrawn')
            ->first();

        if ($existingApplication) {
            return ['ok' => false, 'message' => 'This candidate has already applied for the selected job.'];
        }

        if (mb_strlen($customMessage) > 500) {
            return ['ok' => false, 'message' => 'Invitation note is too long. Max 500 characters.'];
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('recruiter_job_invitations')) {
            return ['ok' => false, 'message' => 'Invitation tracking is not ready yet. Run the latest migrations first.'];
        }

        $invitationModel = new RecruiterJobInvitationModel();
        if ($invitationModel->findActiveInvitation($recruiterId, $candidateId, $jobId)) {
            return ['ok' => false, 'message' => 'An active invitation for this candidate and job already exists.'];
        }

        $defaultMessage = $this->buildInvitationMessage(
            (string) ($candidate['name'] ?? 'there'),
            (string) ($job['title'] ?? 'this role'),
            (string) ($job['company'] ?? 'our team'),
            (string) (session()->get('user_name') ?? 'A recruiter')
        );
        $message = $customMessage !== '' ? $customMessage : $defaultMessage;

        $invitationId = $invitationModel->createInvitation($recruiterId, $candidateId, $jobId, $message);
        $jobLink = base_url('job/' . $jobId . '?invitation=' . $invitationId);

        $candidateProfile = $userModel->findCandidateWithProfile($candidateId) ?? $candidate;
        $allowInApp = (int) ($candidateProfile['job_alert_notify_in_app'] ?? 1) === 1;
        $allowEmail = (int) ($candidateProfile['job_alert_notify_email'] ?? 1) === 1;

        if ($allowInApp) {
            $this->notifyCandidateAction(
                $candidateId,
                null,
                'job_invitation',
                'Invitation to Apply',
                $message,
                $jobLink
            );
        }

        if ($allowEmail) {
            $this->sendInvitationEmail($candidateProfile, $job, $message, $jobLink);
        }

        return ['ok' => true, 'message' => 'Invitation sent to candidate successfully.'];
    }

    private function sendInvitationEmail(array $candidate, array $job, string $message, string $jobLink): void
    {
        $recipient = trim((string) ($candidate['email'] ?? ''));
        if ($recipient === '') {
            return;
        }

        $candidateName = trim((string) ($candidate['name'] ?? 'Candidate'));
        $jobTitle = trim((string) ($job['title'] ?? 'this role'));
        $companyName = trim((string) ($job['company'] ?? 'our company'));
        $recruiterName = trim((string) (session()->get('user_name') ?? 'A recruiter'));

        $subject = 'Invitation to apply: ' . $jobTitle . ' at ' . $companyName;

        $body = '
            <div style="margin:0;padding:24px;background:#eef4ff;font-family:Segoe UI,Arial,sans-serif;color:#0f172a;">
                <div style="max-width:680px;margin:0 auto;background:#ffffff;border-radius:24px;overflow:hidden;box-shadow:0 18px 40px rgba(15,23,42,0.10);">
                    <div style="padding:28px 32px;background:linear-gradient(135deg,#0b66ff 0%,#38bdf8 100%);color:#ffffff;">
                        <div style="font-size:12px;letter-spacing:0.12em;text-transform:uppercase;opacity:0.88;margin-bottom:10px;">HireMatrix recruiter invite</div>
                        <h1 style="margin:0;font-size:28px;line-height:1.2;">A recruiter wants you to consider a role</h1>
                        <p style="margin:12px 0 0;font-size:15px;line-height:1.7;opacity:0.95;">This is a direct invitation designed to feel personal, not mass-sent.</p>
                    </div>
                    <div style="padding:28px 32px;">
                        <p style="margin:0 0 16px;font-size:15px;line-height:1.7;">Hi ' . esc($candidateName) . ',</p>
                        <p style="margin:0 0 18px;font-size:15px;line-height:1.8;">' . esc($recruiterName) . ' invited you to explore <strong>' . esc($jobTitle) . '</strong> at <strong>' . esc($companyName) . '</strong>.</p>
                        <div style="padding:18px 20px;border-radius:18px;background:#f8fbff;border:1px solid #dbeafe;margin-bottom:20px;">
                            <div style="font-size:12px;text-transform:uppercase;letter-spacing:0.08em;color:#2563eb;font-weight:700;margin-bottom:8px;">Why you received this</div>
                            <div style="font-size:15px;line-height:1.8;color:#1e293b;">' . nl2br(esc($message)) . '</div>
                        </div>
                        <a href="' . esc($jobLink) . '" style="display:inline-block;padding:14px 22px;border-radius:999px;background:#0b66ff;color:#ffffff;text-decoration:none;font-weight:700;">Review Role and Apply</a>
                        <p style="margin:18px 0 0;font-size:13px;line-height:1.7;color:#64748b;">If the role fits your direction, you can apply right away from the job page. If not, you can simply ignore this invite.</p>
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
            log_message('error', 'Job invitation email failed: ' . $e->getMessage());
        }
    }

    private function applyRecruiterVisibilityFilter($builder, int $recruiterId): void
    {
        $builder->groupStart()
            ->where('COALESCE(candidate_profiles.allow_public_recruiter_visibility, 1) =', 1, false)
            ->orWhere('users.id IN (SELECT applications.candidate_id FROM applications INNER JOIN jobs ON jobs.id = applications.job_id WHERE jobs.recruiter_id = ' . $recruiterId . ')', null, false)
            ->groupEnd();
    }

    private function applySelectedJobAvailabilityFilter($builder, int $jobId): void
    {
        if ($jobId <= 0) {
            return;
        }

        $db = \Config\Database::connect();

        $builder->where(
            'users.id NOT IN (SELECT applications.candidate_id FROM applications WHERE applications.job_id = ' . (int) $jobId . " AND applications.status != 'withdrawn')",
            null,
            false
        );

        if ($db->tableExists('recruiter_job_invitations')) {
            $builder->where(
                'users.id NOT IN (SELECT recruiter_job_invitations.candidate_id FROM recruiter_job_invitations WHERE recruiter_job_invitations.job_id = ' . (int) $jobId . " AND recruiter_job_invitations.status IN ('sent', 'viewed', 'applied'))",
                null,
                false
            );
        }
    }

    private function resolveApplicationIdForCandidateJob(int $candidateId, int $recruiterId, int $applicationId, int $jobId): int
    {
        if ($applicationId > 0 || $jobId <= 0) {
            return $applicationId;
        }

        $application = (new ApplicationModel())
            ->select('applications.id')
            ->join('jobs', 'jobs.id = applications.job_id', 'inner')
            ->where('applications.candidate_id', $candidateId)
            ->where('applications.job_id', $jobId)
            ->where('jobs.recruiter_id', $recruiterId)
            ->where('applications.status !=', 'withdrawn')
            ->orderBy('applications.applied_at', 'DESC')
            ->first();

        return (int) ($application['id'] ?? 0);
    }

    private function canRecruiterAccessCandidate(int $candidateId, int $recruiterId): bool
    {
        $userModel = new UserModel();
        $candidate = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);
        if (!$candidate || ($candidate['role'] ?? '') !== 'candidate') {
            return false;
        }

        if ((int) ($candidate['allow_public_recruiter_visibility'] ?? 1) === 1) {
            return true;
        }

        $application = (new ApplicationModel())
            ->select('applications.id')
            ->join('jobs', 'jobs.id = applications.job_id')
            ->where('applications.candidate_id', $candidateId)
            ->where('jobs.recruiter_id', $recruiterId)
            ->first();

        return !empty($application);
    }

    private function normalizeTags(string $rawTags): string
    {
        if ($rawTags === '') {
            return '';
        }

        $parts = preg_split('/[,]+/', $rawTags) ?: [];
        $clean = [];
        foreach ($parts as $part) {
            $tag = trim($part);
            if ($tag === '') {
                continue;
            }
            if (mb_strlen($tag) > 40) {
                $tag = mb_substr($tag, 0, 40);
            }
            $clean[] = $tag;
        }

        $unique = [];
        $seen = [];
        foreach ($clean as $tag) {
            $key = mb_strtolower($tag);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $unique[] = $tag;
        }

        return implode(', ', $unique);
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

    private function getSubmittedResumeVersion(int $candidateId, int $applicationId): ?array
    {
        $db = \Config\Database::connect();
        if ($applicationId <= 0 || !$db->tableExists('candidate_resume_versions') || !$db->fieldExists('resume_version_id', 'applications')) {
            return null;
        }

        $application = (new ApplicationModel())
            ->select('applications.id, applications.resume_version_id, jobs.recruiter_id')
            ->join('jobs', 'jobs.id = applications.job_id', 'inner')
            ->where('applications.id', $applicationId)
            ->where('applications.candidate_id', $candidateId)
            ->where('jobs.recruiter_id', (int) session()->get('user_id'))
            ->first();

        if (!$application || empty($application['resume_version_id'])) {
            return null;
        }

        return (new CandidateResumeVersionModel())->find((int) $application['resume_version_id']);
    }
}
