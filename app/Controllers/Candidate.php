<?php

namespace App\Controllers;

use App\Libraries\AiResumeBuilder;
use App\Libraries\ResumeTemplateRenderer;
use App\Models\UserModel;
use App\Libraries\ResumeParser;
use App\Models\ApplicationModel;
use App\Models\CareerTransitionModel;
use App\Models\CandidateResumeVersionModel;
use App\Models\CandidateProjectModel;
use App\Models\CandidateSkillsModel;
use App\Models\CandidateInterestsModel;
use App\Models\JobModel;
use App\Models\GithubAnalysisModel;
use App\Libraries\GithubAnalyzer;
use App\Models\WorkExperienceModel;
use App\Models\EducationModel;
use App\Models\CertificationModel;


class Candidate extends BaseController
{
    public function settings()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }

        $userId = (int) session()->get('user_id');
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile($userId) ?? $userModel->find($userId) ?? [];

        return view('candidate/settings', [
            'user' => $user,
        ]);
    }

    public function profile()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $userId = session()->get('user_id');
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile((int) $userId) ?? $userModel->find($userId);
        $githubModel = model('GithubAnalysisModel');
        $github = $githubModel->where('candidate_id', $userId)->first();
        $skillsModel = model('CandidateSkillsModel');
        $skills = $skillsModel->where('candidate_id', $userId)->first();
        $interestsModel = new CandidateInterestsModel();
        $interestRow    = $interestsModel->where('candidate_id', $userId)->first();
        // Convert comma-separated string → flat array for the view
        $interests = [];
        if ($interestRow && !empty($interestRow['interest'])) {
            $interests = array_values(array_filter(array_map('trim', explode(',', $interestRow['interest']))));
        }
        
        $workExpModel = new WorkExperienceModel();
        $educationModel = new EducationModel();
        $certificationModel = new CertificationModel();
        $projectModel = new CandidateProjectModel();
        $db = \Config\Database::connect();
        
        $workExperiences = $workExpModel->getByUser($userId);
        $education = $educationModel->getByUser($userId);
        $certifications = $certificationModel->getByUser($userId);
        $projects = $db->tableExists('candidate_projects')
            ? $projectModel->getByUser((int) $userId)
            : [];

        // Calculate total experience in months
        $totalExperienceMonths = 0;
        foreach ($workExperiences as $exp) {
            $startDate = new \DateTime($exp['start_date']);
            $endDate = $exp['is_current'] ? new \DateTime() : new \DateTime($exp['end_date']);
            $interval = $startDate->diff($endDate);
            $totalExperienceMonths += ($interval->y * 12) + $interval->m;
        }

        // Get application stats
        $applicationModel = model('ApplicationModel');
        $bookingModel = model('InterviewBookingModel');
        
        $totalApplications = $applicationModel->where('candidate_id', $userId)->countAllResults();
        $totalInterviews = $bookingModel->where('user_id', $userId)->countAllResults();
        $totalOffers = $applicationModel->where('candidate_id', $userId)
                                      ->whereIn('status', ['selected', 'hired'])
                                      ->countAllResults();

        $profileReadiness = $this->getProfileReadinessForResume($userId);

        // Calculate profile completion percentage
        $completionFields = [
            'name' => !empty($user['name']),
            'email' => !empty($user['email']),
            'phone' => !empty($user['phone']),
            'profile_photo' => !empty($user['profile_photo']),
            'resume' => !empty($user['resume_path']),
            'intro_video' => !empty($user['intro_video_path']),
            'github' => !empty($github['github_username']),
            'skills' => !empty($skills['skill_name']),
            'location' => !empty($user['location']),
            'bio' => !empty($user['bio'])
        ];
        
        $completedFields = array_sum($completionFields); // This is for the overall profile completion, not specific missing details
        $totalFields = count($completionFields);
        $completionPercentage = round(($completedFields / $totalFields) * 100);

        return view('candidate/profile', [
            'user'            => $user,
            'github'          => $github,
            'skills'          => $skills,
            'interests'       => $interests,
            'workExperiences' => $workExperiences,
            'education'       => $education,
            'certifications'  => $certifications,
            'projects'        => $projects,
            'stats' => [
                'applications' => $totalApplications,
                'interviews'   => $totalInterviews,
                'offers'       => $totalOffers
            ],
            'completion' => [
                'percentage' => $completionPercentage,
                'fields'     => $completionFields
            ],
            'totalExperienceMonths' => $totalExperienceMonths,
            'isFresherCandidate' => (int)($user['is_fresher_candidate'] ?? 0) === 1,
            'profileReadiness' => $profileReadiness,
        ]);
    }

    public function resumeStudio()
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('recruiter/dashboard'))->with('error', 'Access denied.');
        }
        
        $userId = (int) session()->get('user_id');
        helper('premium');
        requirePremiumForFeature($userId, 'resume studio');
        $userModel = new UserModel();
        $user = $userModel->findCandidateWithProfile($userId) ?? $userModel->find($userId) ?? [];

        $studioData = $this->buildResumeStudioData($userId, $user);

        return view('candidate/resume_studio', array_merge([
            'user' => $user,
        ], $studioData));
    }

    public function resumeUpload()
    {
        $session = session();

        if (!$session->get('logged_in')) {
            return redirect()->to(base_url('login'));
        }

        $candidateId = $session->get('user_id');
        $file = $this->request->getFile('resume');

        if (!$file || !$file->isValid()) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'No file uploaded or invalid file'
            ]);
        }

        $allowedTypes = ['pdf', 'docx', 'doc'];

        if (!in_array(strtolower($file->getExtension()), $allowedTypes)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Only PDF, DOCX or DOC files allowed'
            ]);
        }

        $uploadPath = WRITEPATH . 'uploads/resumes/';

        if (!$file->move($uploadPath)) {
            return $this->response->setJSON([
                'success' => false,
                'error' => 'File upload failed'
            ]);
        }

        $filePath = $uploadPath . $file->getName();

        $candidateModel = new UserModel();
        $resumeData = [
            'resume_path' => 'uploads/resumes/' . $file->getName()
        ];
        $candidateModel->upsertCandidateProfile((int) $candidateId, $resumeData);

        $parser = new ResumeParser();
        $result = $parser->parse($filePath);
        $skillModel = new CandidateSkillsModel();

        $skillModel->where('candidate_id', $candidateId)->delete();

        $skillNames = [];
        foreach ($result['skills'] as $skill) {
            $skillName = trim($skill['name']);
            $skillNames[] = $skillName;
        }

        if (!empty($skillNames)) {
            $skillModel->insert([
                'candidate_id' => $candidateId,
                'skill_name' => implode(', ', $skillNames)
            ]);
        }

        model('JobAlertModel')->syncFromCandidateProfile((int) $candidateId);

        return redirect()->back()->with('upload_success', 'Resume Uploaded Successfully');
    }

    public function generateAiResume()
    {
        $candidateId = (int) session()->get('user_id');
        if (!\Config\Database::connect()->tableExists('candidate_resume_versions')) {
            return redirect()->back()->with('error', 'Resume version storage is not ready yet. Run the latest migrations first.');
        }

        $generationMode = trim((string) $this->request->getPost('generation_mode'));
        $targetRole = trim((string) $this->request->getPost('target_role'));
        $jobId = (int) ($this->request->getPost('job_id') ?? 0);
        $makePrimary = (int) ($this->request->getPost('make_primary') ?? 0) === 1;
        $templateKey = trim((string) $this->request->getPost('template_key'));

        // Enforce Readiness Check
        $readiness = $this->getProfileReadinessForResume($candidateId);
        if (!$readiness['is_ready']) {
            return redirect()->back()->with('error', 'Profile incomplete: ' . implode(', ', $readiness['missing_details']));
        }

        if (!in_array($generationMode, ['role', 'job'], true)) {
            return redirect()->back()->with('error', 'Choose either Generate By Role or Generate For Specific Job.');
        }

        $job = null;
        if ($generationMode === 'job' && $jobId > 0) {
            $job = (new JobModel())->find($jobId);
            $targetRole = trim((string) ($job['title'] ?? ''));
        }

        if ($generationMode === 'role') {
            if ($targetRole === '') {
                return redirect()->back()->with('error', 'Target role is required for Generate By Role.');
            }
            if ($jobId > 0) {
                return redirect()->back()->with('error', 'Choose only one source. Role-based generation cannot use a specific job at the same time.');
            }
        }

        if ($generationMode === 'job') {
            if ($jobId <= 0 || !$job) {
                return redirect()->back()->with('error', 'Select a specific job for Generate For Specific Job.');
            }
        }

        $profile = $this->buildResumeProfileSnapshot($candidateId);
        $blockedTemplates = $this->getBlockedResumeTemplates($profile);
        if ($templateKey !== '' && isset($blockedTemplates[$templateKey])) {
            return redirect()->back()->with('error', $blockedTemplates[$templateKey]);
        }
        helper('premium');
        requirePremiumForFeature($candidateId, 'AI resume generation');
        $currentRole = $this->detectCurrentRole($profile);
        $resume = (new AiResumeBuilder())->buildResume($profile, $targetRole, [
            'current_role' => $currentRole,
            'job_title' => (string) ($job['title'] ?? ''),
            'job_description' => (string) ($job['description'] ?? ''),
            'template_key' => $templateKey,
        ]);

        $resumeVersionModel = new CandidateResumeVersionModel();
        $payload = [
            'candidate_id' => $candidateId,
            'job_id' => $jobId > 0 ? $jobId : null,
            'title' => (string) ($resume['title'] ?? ($targetRole . ' Resume')),
            'target_role' => $targetRole,
            'source_role' => $currentRole,
            'generation_source' => $jobId > 0 ? 'job_version' : 'role_based',
            'base_resume_path' => (string) ($profile['resume_path'] ?? ''),
            'summary' => (string) ($resume['summary'] ?? ''),
            'highlight_skills' => implode(', ', (array) ($resume['highlight_skills'] ?? [])),
            'content' => (string) ($resume['content'] ?? ''),
            'is_primary' => 0,
            'last_synced_at' => date('Y-m-d H:i:s'),
        ];

        $existing = $jobId > 0
            ? $resumeVersionModel->findJobVersion($candidateId, $jobId)
            : $resumeVersionModel->findRoleBasedVersion($candidateId, $targetRole);

        if ($existing) {
            $resumeVersionModel->update((int) $existing['id'], $payload);
            $versionId = (int) $existing['id'];
        } else {
            $versionId = (int) $resumeVersionModel->insert($payload, true);
        }

        if ($makePrimary || !$resumeVersionModel->where('candidate_id', $candidateId)->where('is_primary', 1)->first()) {
            $resumeVersionModel->setPrimaryVersion($candidateId, (int) $versionId);
        }

        return redirect()->to(base_url('candidate/resume-studio'))->with(
            'success',
            $jobId > 0
                ? 'AI resume version generated for the selected job.'
                : 'Role-based AI resume saved for this target role.'
        );
    }

    public function syncResumeFromTransition()
    {
        $candidateId = (int) session()->get('user_id');
        if (!\Config\Database::connect()->tableExists('candidate_resume_versions')) {
            return redirect()->back()->with('error', 'Resume version storage is not ready yet. Run the latest migrations first.');
        }

        $transitionModel = new CareerTransitionModel();
        $activeTransition = $transitionModel->getActiveTransition($candidateId);

        if (!$activeTransition) {
            return redirect()->back()->with('error', 'No active career transition found.');
        }

        $profile = $this->buildResumeProfileSnapshot($candidateId);
        $targetRole = trim((string) ($activeTransition['target_role'] ?? ''));
        $currentRole = trim((string) ($activeTransition['current_role'] ?? $this->detectCurrentRole($profile)));
        $skillGaps = json_decode((string) ($activeTransition['skill_gaps'] ?? '[]'), true);
        $transitionSummary = 'Career transition in progress from ' . $currentRole . ' to ' . $targetRole . '.'
            . (!empty($skillGaps) ? ' Current focus areas: ' . implode(', ', array_slice((array) $skillGaps, 0, 6)) . '.' : '');

        helper('premium');
        requirePremiumForFeature($candidateId, 'AI resume sync');
        $resume = (new AiResumeBuilder())->buildResume($profile, $targetRole, [
            'current_role' => $currentRole,
            'transition_summary' => $transitionSummary,
            'template_key' => (int) ($profile['is_fresher_candidate'] ?? 0) === 1 ? 'tech_compact' : 'executive_sidebar',
        ]);

        $resumeVersionModel = new CandidateResumeVersionModel();
        $existing = $resumeVersionModel
            ->where('candidate_id', $candidateId)
            ->where('career_transition_id', (int) $activeTransition['id'])
            ->where('generation_source', 'career_transition')
            ->first();

        $payload = [
            'candidate_id' => $candidateId,
            'career_transition_id' => (int) $activeTransition['id'],
            'title' => (string) ($resume['title'] ?? ($targetRole . ' Career Transition Resume')),
            'target_role' => $targetRole,
            'source_role' => $currentRole,
            'generation_source' => 'career_transition',
            'base_resume_path' => (string) ($profile['resume_path'] ?? ''),
            'summary' => (string) ($resume['summary'] ?? ''),
            'highlight_skills' => implode(', ', (array) ($resume['highlight_skills'] ?? [])),
            'content' => (string) ($resume['content'] ?? ''),
            'last_synced_at' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $resumeVersionModel->update((int) $existing['id'], $payload);
            $versionId = (int) $existing['id'];
        } else {
            $payload['is_primary'] = 0;
            $versionId = (int) $resumeVersionModel->insert($payload, true);
        }

        $resumeVersionModel->setPrimaryVersion($candidateId, $versionId);

        return redirect()->to(base_url('candidate/resume-studio'))->with(
            'success',
            'Career-transition resume refreshed and set as your primary AI resume version.'
        );
    }

    public function setPrimaryResumeVersion($versionId)
    {
        $candidateId = (int) session()->get('user_id');
        if (!\Config\Database::connect()->tableExists('candidate_resume_versions')) {
            return redirect()->back()->with('error', 'Resume version storage is not ready yet. Run the latest migrations first.');
        }

        $resumeVersionModel = new CandidateResumeVersionModel();
        $version = $resumeVersionModel->find((int) $versionId);

        if (!$version || (int) ($version['candidate_id'] ?? 0) !== $candidateId) {
            return redirect()->back()->with('error', 'Resume version not found.');
        }

        $resumeVersionModel->setPrimaryVersion($candidateId, (int) $versionId);

        return redirect()->to(base_url('candidate/resume-studio'))->with('success', 'Primary AI resume version updated.');
    }

    public function downloadResumeVersion($versionId)
    {
        $candidateId = (int) session()->get('user_id');
        if (!\Config\Database::connect()->tableExists('candidate_resume_versions')) {
            return redirect()->back()->with('error', 'Resume version storage is not ready yet. Run the latest migrations first.');
        }

        $resumeVersion = (new CandidateResumeVersionModel())->find((int) $versionId);
        if (!$resumeVersion || (int) ($resumeVersion['candidate_id'] ?? 0) !== $candidateId) {
            return redirect()->back()->with('error', 'Resume version not found.');
        }

        $user = (new UserModel())->find($candidateId) ?? [];
        $renderer = new ResumeTemplateRenderer();
        $pdfPath = $renderer->createPdfFile((string) ($resumeVersion['content'] ?? ''), [
            'name' => (string) ($user['name'] ?? 'Candidate'),
            'target_role' => (string) ($resumeVersion['target_role'] ?? ''),
            'summary' => (string) ($resumeVersion['summary'] ?? ''),
            'highlight_skills' => $this->splitCsvList((string) ($resumeVersion['highlight_skills'] ?? '')),
        ], (string) (($user['name'] ?? 'candidate') . '-' . ($resumeVersion['target_role'] ?? 'resume')));

        return $this->response->download($pdfPath, null)->setFileName(basename($pdfPath));
    }

    public function previewResumeVersion($versionId)
    {
        $candidateId = (int) session()->get('user_id');
        if (!\Config\Database::connect()->tableExists('candidate_resume_versions')) {
            return 'Resume version storage is not ready yet.';
        }

        $resumeVersion = (new CandidateResumeVersionModel())->find((int) $versionId);
        if (!$resumeVersion || (int) ($resumeVersion['candidate_id'] ?? 0) !== $candidateId) {
            return 'Resume version not found.';
        }

        $user = (new UserModel())->find($candidateId) ?? [];
        $renderer = new ResumeTemplateRenderer();
        
        return $renderer->renderDocument((string) ($resumeVersion['content'] ?? ''), [
            'name' => (string) ($user['name'] ?? 'Candidate'),
            'target_role' => (string) ($resumeVersion['target_role'] ?? ''),
            'summary' => (string) ($resumeVersion['summary'] ?? ''),
            'highlight_skills' => $this->splitCsvList((string) ($resumeVersion['highlight_skills'] ?? '')),
        ]);
    }

    public function deleteResumeVersion($versionId)
    {
        $candidateId = (int) session()->get('user_id');
        if (!\Config\Database::connect()->tableExists('candidate_resume_versions')) {
            return redirect()->back()->with('error', 'Resume version storage is not ready yet. Run the latest migrations first.');
        }

        $resumeVersionModel = new CandidateResumeVersionModel();
        $resumeVersion = $resumeVersionModel->find((int) $versionId);
        if (!$resumeVersion || (int) ($resumeVersion['candidate_id'] ?? 0) !== $candidateId) {
            return redirect()->back()->with('error', 'Resume version not found.');
        }

        $wasPrimary = (int) ($resumeVersion['is_primary'] ?? 0) === 1;
        $resumeVersionModel->delete((int) $versionId);

        if ($wasPrimary) {
            $replacement = $resumeVersionModel
                ->where('candidate_id', $candidateId)
                ->orderBy('updated_at', 'DESC')
                ->first();

            if ($replacement) {
                $resumeVersionModel->setPrimaryVersion($candidateId, (int) $replacement['id']);
            }
        }

        return redirect()->to(base_url('candidate/resume-studio'))->with('success', 'Resume version deleted.');
    }

    public function analyzeGithubSkills()
    {
        $session = session();
        $candidateId = $session->get('user_id');
        $username = $this->request->getPost('github_username');

        if (!$username) {
            return redirect()->back()->with('error', 'GitHub username required');
        }

        $github = new GithubAnalyzer();
        $data = $github->analyze($username);

        if (empty($data['languages'])) {
            return redirect()->back()->with('error', 'GitHub profile not found or API blocked');
        }

        $githubModel = new GithubAnalysisModel();
        $githubModel->where('candidate_id', $candidateId)->delete();

        $githubModel->insert([
            'candidate_id' => $session->get('user_id'),
            'github_username' => $username,
            'repo_count' => $data['repo_count'],
            'commit_count' => $data['commit_count'],
            'languages_used' => implode(',', $data['languages']),
            'github_score' => min(10, round($data['commit_count'] / 20))
        ]);

        return redirect()->back()->with('success', 'GitHub profile analyzed successfully');
    }

    public function downloadResume()
    {
        $userId = session()->get('user_id');
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile((int) $userId) ?? $userModel->find($userId);
        
        if (!$user || empty($user['resume_path'])) {
            return redirect()->back()->with('error', 'No resume found');
        }
        
        $filePath = WRITEPATH . $user['resume_path'];
        
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'Resume file not found');
        }
        
        return $this->response->download($filePath, null);
    }

    public function previewResume()
    {
        $userId = session()->get('user_id');
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile((int) $userId) ?? $userModel->find($userId);
        
        if (!$user || empty($user['resume_path'])) {
            return $this->response->setJSON(['error' => 'No resume found']);
        }
        
        $filePath = WRITEPATH . $user['resume_path'];
        
        if (!file_exists($filePath)) {
            return $this->response->setJSON(['error' => 'Resume file not found']);
        }
        
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);
        
        // For DOCX/DOC, trigger download instead
        if (in_array(strtolower($ext), ['docx', 'doc'])) {
            return $this->response->setJSON([
                'error' => 'Preview not available for Word documents. Click Download to view the file.'
            ]);
        }
        
        // For PDF, serve directly
        $fileUrl = base_url('candidate/serve-resume');
        return $this->response->setJSON(['url' => $fileUrl]);
    }

    public function serveResume()
    {
        $userId = session()->get('user_id');
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile((int) $userId) ?? $userModel->find($userId);
        
        if (!$user || empty($user['resume_path'])) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Resume not found');
        }
        
        $filePath = WRITEPATH . $user['resume_path'];
        
        if (!file_exists($filePath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Resume file not found');
        }
        
        $mimeType = mime_content_type($filePath);
        
        return $this->response
            ->setHeader('Content-Type', $mimeType)
            ->setHeader('Content-Disposition', 'inline')
            ->setBody(file_get_contents($filePath));
    }

    public function updatePersonal()
    {
        $userId = session()->get('user_id');
        $userModel = model('UserModel');
        
        $baseData = [
            'name' => $this->request->getPost('name'),
            'email' => $this->request->getPost('email'),
            'phone' => $this->request->getPost('phone'),
            'location' => $this->request->getPost('location'),
            'bio' => $this->request->getPost('bio'),
            'gender' => $this->request->getPost('gender'),
            'date_of_birth' => $this->request->getPost('date_of_birth')
        ];

        $userModel->update($userId, [
            'name' => $baseData['name'],
            'email' => $baseData['email'],
            'phone' => $baseData['phone'],
        ]);
        $userModel->upsertCandidateProfile((int) $userId, [
            'location' => $baseData['location'],
            'bio' => $baseData['bio'],
            'gender' => $baseData['gender'],
            'date_of_birth' => $baseData['date_of_birth'],
        ]);
        session()->set('user_name', (string) $baseData['name']);
        
        return redirect()->back()->with('personal_success', 'Personal information updated successfully');
    }

    public function updateCareerDetails()
    {
        $userId = session()->get('user_id');
        $userModel = model('UserModel');
        
        $data = [
            'resume_headline' => $this->request->getPost('resume_headline'),
            'current_salary' => $this->normalizeSalaryToLpa($this->request->getPost('current_salary')),
            'notice_period' => $this->request->getPost('notice_period'),
            'is_fresher_candidate' => $this->request->getPost('is_fresher_candidate') === '1' ? 1 : 0
        ];
        $userModel->upsertCandidateProfile((int) $userId, $data);
        
        return redirect()->back()->with('career_success', 'Career details updated successfully');
    }

    public function updateIntroVideo()
    {
        $userId = (int) session()->get('user_id');
        $userModel = model('UserModel');

        $data = [
            'intro_video_target_role' => trim((string) $this->request->getPost('intro_video_target_role')),
            'intro_video_pitch' => trim((string) $this->request->getPost('intro_video_pitch')),
        ];

        $userModel->upsertCandidateProfile($userId, $data);

        return redirect()->back()->with('video_success', 'Video introduction details updated successfully');
    }

    public function updatePreferences()
    {
        $userId = session()->get('user_id');
        $userModel = model('UserModel');

        $data = [
            'preferred_job_titles' => trim((string) $this->request->getPost('preferred_job_titles')),
            'preferred_locations' => trim((string) $this->request->getPost('preferred_locations')),
            'preferred_employment_type' => trim((string) $this->request->getPost('preferred_employment_type')),
            'expected_salary' => $this->normalizeSalaryToLpa($this->request->getPost('expected_salary')),
        ];
        $userModel->upsertCandidateProfile((int) $userId, $data);
        model('JobAlertModel')->syncFromCandidateProfile((int) $userId);

        return redirect()->back()->with('preferences_success', 'Preferences updated successfully');
    }

    public function updateSettings()
    {
        $userId = (int) session()->get('user_id');
        $userModel = model('UserModel');

        $data = [
            'allow_public_recruiter_visibility' => $this->request->getPost('allow_public_recruiter_visibility') === '1' ? 1 : 0,
        ];

        $userModel->upsertCandidateProfile($userId, $data);

        return redirect()->to(base_url('candidate/settings'))->with('settings_success', 'Settings updated successfully');
    }

    public function updateNotificationSettings()
    {
        $userId = (int) session()->get('user_id');
        $userModel = model('UserModel');

        $data = [
            'job_alerts_enabled' => $this->request->getPost('job_alerts_enabled') === '1' ? 1 : 0,
            'job_alert_notify_in_app' => $this->request->getPost('job_alert_notify_in_app') === '1' ? 1 : 0,
            'job_alert_notify_email' => $this->request->getPost('job_alert_notify_email') === '1' ? 1 : 0,
        ];

        $userModel->upsertCandidateProfile($userId, $data);
        model('JobAlertModel')->syncFromCandidateProfile($userId);

        return redirect()->to(base_url('candidate/settings?tab=notifications'))->with('settings_success', 'Notification settings updated successfully');
    }

    public function uploadPhoto()
    {
        $userId = session()->get('user_id');
        $file = $this->request->getFile('profile_photo');
        
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'No file uploaded or invalid file');
        }
        
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array(strtolower($file->getExtension()), $allowedTypes)) {
            return redirect()->back()->with('error', 'Only JPG, PNG, GIF files allowed');
        }
        
        $uploadPath = FCPATH . 'uploads/profiles/';
        
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        $newName = $userId . '_' . time() . '.' . $file->getExtension();
        
        if (!$file->move($uploadPath, $newName)) {
            return redirect()->back()->with('error', 'File upload failed');
        }
        
        $userModel = model('UserModel');
        $photoPath = 'uploads/profiles/' . $newName;
        $userModel->upsertCandidateProfile((int) $userId, ['profile_photo' => $photoPath]);
        session()->set('profile_photo', $photoPath);
        
        return redirect()->back()->with('success', 'Profile photo updated successfully');
    }

    public function removePhoto()
    {
        $userId = (int) session()->get('user_id');
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile($userId) ?? $userModel->find($userId);

        if (!$user) {
            return redirect()->back()->with('error', 'User not found');
        }

        $photoPath = trim((string) ($user['profile_photo'] ?? ''));
        if ($photoPath === '') {
            return redirect()->back()->with('error', 'No profile photo to remove');
        }

        // Delete physical file only for expected uploads path.
        if (str_starts_with($photoPath, 'uploads/profiles/')) {
            $absolutePath = FCPATH . $photoPath;
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        $userModel->upsertCandidateProfile((int) $userId, ['profile_photo' => '']);
        session()->remove('profile_photo');

        return redirect()->back()->with('success', 'Profile photo removed successfully');
    }

    public function uploadIntroVideo()
    {
        $userId = (int) session()->get('user_id');
        $file = $this->request->getFile('intro_video');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'No video uploaded or invalid video file');
        }

        $allowedTypes = ['mp4', 'webm', 'mov'];
        if (!in_array(strtolower($file->getExtension()), $allowedTypes, true)) {
            return redirect()->back()->with('error', 'Only MP4, WEBM, or MOV videos are allowed');
        }

        if ($file->getSizeByUnit('mb') > 30) {
            return redirect()->back()->with('error', 'Video size should be 30MB or less');
        }

        $uploadPath = FCPATH . 'uploads/intro-videos/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile($userId) ?? $userModel->find($userId);
        $currentVideoPath = trim((string) ($user['intro_video_path'] ?? ''));
        if ($currentVideoPath !== '' && str_starts_with($currentVideoPath, 'uploads/intro-videos/')) {
            $absolutePath = FCPATH . $currentVideoPath;
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        $newName = $userId . '_intro_' . time() . '.' . strtolower($file->getExtension());
        if (!$file->move($uploadPath, $newName)) {
            return redirect()->back()->with('error', 'Video upload failed');
        }

        $videoPath = 'uploads/intro-videos/' . $newName;
        $userModel->upsertCandidateProfile($userId, ['intro_video_path' => $videoPath]);

        return redirect()->back()->with('video_success', 'Profile introduction video uploaded successfully');
    }

    public function removeIntroVideo()
    {
        $userId = (int) session()->get('user_id');
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile($userId) ?? $userModel->find($userId);

        if (!$user) {
            return redirect()->back()->with('error', 'User not found');
        }

        $videoPath = trim((string) ($user['intro_video_path'] ?? ''));
        if ($videoPath === '') {
            return redirect()->back()->with('error', 'No introduction video to remove');
        }

        if (str_starts_with($videoPath, 'uploads/intro-videos/')) {
            $absolutePath = FCPATH . $videoPath;
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }

        $userModel->upsertCandidateProfile($userId, ['intro_video_path' => '']);

        return redirect()->back()->with('video_success', 'Profile introduction video removed successfully');
    }

    public function addSkill()
    {
        $userId = session()->get('user_id');
        $skillName = $this->request->getPost('skill_name');
        
        if (empty($skillName)) {
            return redirect()->back()->with('error', 'Skill name is required');
        }
        
        $skillsModel = model('CandidateSkillsModel');
        $existingSkills = $skillsModel->where('candidate_id', $userId)->first();
        
        if ($existingSkills) {
            $currentSkills = $existingSkills['skill_name'];
            $updatedSkills = $currentSkills . ', ' . trim($skillName);
            
            $skillsModel->update($existingSkills['id'], [
                'skill_name' => $updatedSkills
            ]);
        } else {
            $skillsModel->insert([
                'candidate_id' => $userId,
                'skill_name' => trim($skillName)
            ]);
        }

        model('JobAlertModel')->syncFromCandidateProfile((int) $userId);
        
        return redirect()->back()->with('success', 'Skill added successfully');
    }

    public function addWorkExperience()
    {
        $userId = session()->get('user_id');
        $workExpModel = new WorkExperienceModel();
        
        $data = [
            'user_id' => $userId,
            'job_title' => $this->request->getPost('job_title'),
            'company_name' => $this->request->getPost('company_name'),
            'employment_type' => $this->request->getPost('employment_type') ?: 'Full-time',
            'location' => $this->request->getPost('location'),
            'start_date' => $this->request->getPost('start_date'),
            'end_date' => $this->request->getPost('end_date'),
            'is_current' => $this->request->getPost('is_current') ? 1 : 0,
            'description' => $this->request->getPost('description')
        ];
        
        try {
            $id = $this->request->getPost('id');
            if ($id) {
                $workExpModel->update($id, $data);
            } else {
                $workExpModel->insert($data);
            }
            return redirect()->back()->with('success', 'Work experience saved successfully');
        } catch (\Exception $e) {
            log_message('error', 'Work experience save error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to save work experience: ' . $e->getMessage());
        }
    }

    public function deleteWorkExperience($id)
    {
        $userId = session()->get('user_id');
        $workExpModel = new WorkExperienceModel();
        
        $exp = $workExpModel->find($id);
        if ($exp && $exp['user_id'] == $userId) {
            $workExpModel->delete($id);
        }
        
        return redirect()->back()->with('success', 'Work experience deleted');
    }

    public function addEducation()
    {
        $userId = session()->get('user_id');
        $educationModel = new EducationModel();
        
        $data = [
            'user_id' => $userId,
            'degree' => $this->request->getPost('degree'),
            'field_of_study' => $this->request->getPost('field_of_study'),
            'institution' => $this->request->getPost('institution'),
            'start_year' => $this->request->getPost('start_year'),
            'end_year' => $this->request->getPost('end_year'),
            'grade' => $this->request->getPost('grade')
        ];
        
        try {
            $id = $this->request->getPost('id');
            if ($id) {
                $educationModel->update($id, $data);
            } else {
                $educationModel->insert($data);
            }
            return redirect()->back()->with('success', 'Education saved successfully');
        } catch (\Exception $e) {
            log_message('error', 'Education save error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to save education: ' . $e->getMessage());
        }
    }

    public function deleteEducation($id)
    {
        $userId = session()->get('user_id');
        $educationModel = new EducationModel();
        
        $edu = $educationModel->find($id);
        if ($edu && $edu['user_id'] == $userId) {
            $educationModel->delete($id);
        }
        
        return redirect()->back()->with('success', 'Education deleted');
    }

    public function addCertification()
    {
        $userId = session()->get('user_id');
        $certificationModel = new CertificationModel();
        
        $data = [
            'user_id' => $userId,
            'certification_name' => $this->request->getPost('certification_name'),
            'issuing_organization' => $this->request->getPost('issuing_organization'),
            'issue_date' => $this->request->getPost('issue_date'),
            'expiry_date' => $this->request->getPost('expiry_date'),
            'credential_id' => $this->request->getPost('credential_id'),
            'credential_url' => $this->request->getPost('credential_url')
        ];
        
        try {
            $id = $this->request->getPost('id');
            if ($id) {
                $certificationModel->update($id, $data);
            } else {
                $certificationModel->insert($data);
            }
            return redirect()->back()->with('success', 'Certification saved successfully');
        } catch (\Exception $e) {
            log_message('error', 'Certification save error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to save certification: ' . $e->getMessage());
        }
    }

    public function addProject()
    {
        $userId = (int) session()->get('user_id');
        $db = \Config\Database::connect();
        if (!$db->tableExists('candidate_projects')) {
            return redirect()->back()->with('error', 'Project storage is not ready yet. Run the latest migrations first.');
        }

        $projectModel = new CandidateProjectModel();
        $data = [
            'user_id' => $userId,
            'project_name' => trim((string) $this->request->getPost('project_name')),
            'role_name' => trim((string) $this->request->getPost('role_name')),
            'tech_stack' => trim((string) $this->request->getPost('tech_stack')),
            'project_url' => trim((string) $this->request->getPost('project_url')),
            'project_summary' => trim((string) $this->request->getPost('project_summary')),
            'impact_metrics' => trim((string) $this->request->getPost('impact_metrics')),
            'start_date' => $this->nullIfEmpty((string) $this->request->getPost('start_date')),
            'end_date' => $this->nullIfEmpty((string) $this->request->getPost('end_date')),
        ];

        if ($data['project_name'] === '') {
            return redirect()->back()->with('error', 'Project name is required.');
        }

        if ($data['project_url'] !== '' && !filter_var($data['project_url'], FILTER_VALIDATE_URL)) {
            return redirect()->back()->with('error', 'Project URL must be a valid URL.');
        }

        $id = (int) ($this->request->getPost('id') ?? 0);
        if ($id > 0) {
            $existing = $projectModel->find($id);
            if (!$existing || (int) ($existing['user_id'] ?? 0) !== $userId) {
                return redirect()->back()->with('error', 'Invalid project selected.');
            }
            $projectModel->update($id, $data);
        } else {
            $projectModel->insert($data);
        }

        return redirect()->back()->with('success', 'Project saved successfully.');
    }

    public function deleteProject($id)
    {
        $userId = (int) session()->get('user_id');
        $projectModel = new CandidateProjectModel();
        $project = $projectModel->find((int) $id);

        if ($project && (int) ($project['user_id'] ?? 0) === $userId) {
            $projectModel->delete((int) $id);
        }

        return redirect()->back()->with('success', 'Project deleted.');
    }

    public function deleteCertification($id)
    {
        $userId = session()->get('user_id');
        $certificationModel = new CertificationModel();
        
        $cert = $certificationModel->find($id);
        if ($cert && $cert['user_id'] == $userId) {
            $certificationModel->delete($id);
        }
        
        return redirect()->back()->with('success', 'Certification deleted');
    }

    // ── Interests (stored as comma-separated string in one row per candidate) ──

    public function addInterest()
    {
        $userId   = session()->get('user_id');
        $newItem  = trim($this->request->getPost('interest'));

        if (empty($newItem)) {
            return redirect()->back()->with('error', 'Interest cannot be empty');
        }

        $interestsModel = new CandidateInterestsModel();
        $existingRow    = $interestsModel->where('candidate_id', $userId)->first();

        if ($existingRow) {
            // Parse current list and check for duplicate (case-insensitive)
            $current = array_filter(array_map('trim', explode(',', $existingRow['interest'])));
            $lower   = array_map('strtolower', $current);

            if (!in_array(strtolower($newItem), $lower)) {
                $current[] = $newItem;
                $interestsModel->update($existingRow['id'], [
                    'interest' => implode(', ', $current),
                ]);
            }
        } else {
            $interestsModel->insert([
                'candidate_id' => $userId,
                'interest'     => $newItem,
            ]);
        }

        return redirect()->back()->with('success', 'Interest added successfully');
    }

    public function deleteInterest($interest)
    {
        // $interest is the URL-encoded interest name (not an ID)
        $userId      = session()->get('user_id');
        $toRemove    = urldecode($interest);

        $interestsModel = new CandidateInterestsModel();
        $existingRow    = $interestsModel->where('candidate_id', $userId)->first();

        if ($existingRow) {
            $current  = array_filter(array_map('trim', explode(',', $existingRow['interest'])));
            $filtered = array_values(array_filter($current, function ($item) use ($toRemove) {
                return strtolower(trim($item)) !== strtolower($toRemove);
            }));

            if (empty($filtered)) {
                // No interests left — delete the row entirely
                $interestsModel->delete($existingRow['id']);
            } else {
                $interestsModel->update($existingRow['id'], [
                    'interest' => implode(', ', $filtered),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Interest removed');
    }

    private function buildResumeProfileSnapshot(int $candidateId): array
    {
        $userModel = new UserModel();
        $user = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId) ?? [];
        $skillsRow = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->first();
        $interestsRow = (new CandidateInterestsModel())->where('candidate_id', $candidateId)->first();
        $githubRow = (new GithubAnalysisModel())->where('candidate_id', $candidateId)->first();
        $workExperiences = (new WorkExperienceModel())->getByUser($candidateId);
        $education = (new EducationModel())->getByUser($candidateId);
        $certifications = (new CertificationModel())->getByUser($candidateId);
        $projects = \Config\Database::connect()->tableExists('candidate_projects')
            ? (new CandidateProjectModel())->getByUser($candidateId)
            : [];

        return [
            'name' => (string) ($user['name'] ?? ''),
            'bio' => (string) ($user['bio'] ?? ''),
            'location' => (string) ($user['location'] ?? ''),
            'resume_path' => (string) ($user['resume_path'] ?? ''),
            'skills' => $this->splitCsvList((string) ($skillsRow['skill_name'] ?? '')),
            'github_languages' => $this->splitCsvList((string) ($githubRow['languages_used'] ?? '')),
            'interests' => $this->splitCsvList((string) ($interestsRow['interest'] ?? '')),
            'work_experiences' => $workExperiences,
            'education' => $education,
            'certifications' => $certifications,
            'projects' => $projects,
        ];
    }

    /**
     * Checks if the profile has the minimum data needed to generate an AI resume.
     */
    private function getProfileReadinessForResume(int $candidateId): array
    {
        $userModel = new UserModel();
        $user = $userModel->findCandidateWithProfile($candidateId);
        $isFresher = (int)($user['is_fresher_candidate'] ?? 0) === 1;
        
        $missing = [];
        
        if ($isFresher) {
            $edu = (new EducationModel())->where('user_id', $candidateId)->first();
            if (!$edu || empty($edu['degree']) || empty($edu['institution'])) {
                $missing[] = 'At least one education entry with Degree and Institution';
            }
        } else {
            $exp = (new WorkExperienceModel())->where('user_id', $candidateId)->first();
            if (!$exp || empty($exp['job_title']) || empty($exp['company_name'])) {
                $missing[] = 'At least one work experience entry with Job Title and Company';
            }
        }

        return [
            'is_ready' => empty($missing),
            'missing_details' => $missing
        ];
    }

    private function detectCurrentRole(array $profile): string
    {
        $workExperiences = (array) ($profile['work_experiences'] ?? []);
        foreach ($workExperiences as $experience) {
            if ((int) ($experience['is_current'] ?? 0) === 1 && !empty($experience['job_title'])) {
                return trim((string) $experience['job_title']);
            }
        }

        if (!empty($workExperiences[0]['job_title'])) {
            return trim((string) $workExperiences[0]['job_title']);
        }

        return 'Candidate';
    }

    private function splitCsvList(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    private function nullIfEmpty(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function normalizeSalaryToLpa($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $salary = (float) $value;
        if ($salary <= 0) {
            return null;
        }

        if ($salary > 1000) {
            $salary = $salary / 100000;
        }

        return round($salary, 2);
    }

    private function buildResumeStudioData(int $userId, array $user): array
    {
        $db = \Config\Database::connect();
        $transitionModel = new CareerTransitionModel();
        $jobModel = new JobModel();
        $templateRenderer = new ResumeTemplateRenderer();
        $profileSnapshot = $this->buildResumeProfileSnapshot($userId);
        $blockedResumeTemplates = $this->getBlockedResumeTemplates($profileSnapshot);
        $readiness = $this->getProfileReadinessForResume($userId);

        $resumeVersions = $db->tableExists('candidate_resume_versions')
            ? (new CandidateResumeVersionModel())->getForCandidate($userId)
            : [];

        foreach ($resumeVersions as &$resumeVersion) {
            $decoded = $templateRenderer->decodeStoredContent((string) ($resumeVersion['content'] ?? ''), [
                'name' => (string) ($user['name'] ?? ''),
                'target_role' => (string) ($resumeVersion['target_role'] ?? ''),
                'summary' => (string) ($resumeVersion['summary'] ?? ''),
                'highlight_skills' => $this->splitCsvList((string) ($resumeVersion['highlight_skills'] ?? '')),
            ]);

            $resumeVersion['template_label'] = $templateRenderer->getTemplateLabel($decoded['template_key'] ?? 'modern_professional');
            $resumeVersion['rendered_preview'] = $templateRenderer->renderPreview((string) ($resumeVersion['content'] ?? ''), [
                'name' => (string) ($user['name'] ?? ''),
                'target_role' => (string) ($resumeVersion['target_role'] ?? ''),
                'summary' => (string) ($resumeVersion['summary'] ?? ''),
                'highlight_skills' => $this->splitCsvList((string) ($resumeVersion['highlight_skills'] ?? '')),
            ]);

            // Calculate a pseudo-strength score (can be improved with AI later)
            $contentLength = strlen((string)($resumeVersion['content'] ?? ''));
            $skillCount = count($this->splitCsvList((string)($resumeVersion['highlight_skills'] ?? '')));
            
            $score = 40; // Base score
            $score += min(30, ($contentLength / 100)); // Up to 30 points for detail
            $score += min(30, ($skillCount * 3)); // Up to 30 points for skills
            
            $resumeVersion['strength_score'] = min(100, $score);
            $resumeVersion['strength_class'] = $score > 80 ? 'success' : ($score > 50 ? 'warning' : 'danger');
        }
        unset($resumeVersion);

        $recentApplications = (new ApplicationModel())
            ->select('applications.id, applications.job_id, jobs.title, jobs.description')
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->where('applications.candidate_id', $userId)
            ->orderBy('applications.applied_at', 'DESC')
            ->limit(10)
            ->findAll();

        $openJobs = $jobModel
            ->select('id, title, description')
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC')
            ->limit(10)
            ->findAll();

        $resumeTargets = [];
        foreach (array_merge($recentApplications, $openJobs) as $jobRow) {
            $jobId = (int) ($jobRow['job_id'] ?? $jobRow['id'] ?? 0);
            if ($jobId <= 0 || isset($resumeTargets[$jobId])) {
                continue;
            }

            $resumeTargets[$jobId] = [
                'job_id' => $jobId,
                'title' => (string) ($jobRow['title'] ?? 'Untitled Role'),
                'description' => (string) ($jobRow['description'] ?? ''),
            ];
        }

        return [
            'resumeVersions' => $resumeVersions,
            'resumeTargets' => array_values($resumeTargets),
            'activeTransition' => $transitionModel->getActiveTransition($userId),
            'resumeTemplates' => $templateRenderer->getTemplates(),
            'blockedResumeTemplates' => $blockedResumeTemplates,
            'profileReadiness' => $readiness,
        ];
    }

    private function getBlockedResumeTemplates(array $profile): array
    {
        $workExperiences = array_values(array_filter((array) ($profile['work_experiences'] ?? [])));
        $projects = array_values(array_filter((array) ($profile['projects'] ?? [])));
        $isFresher = (int) ($profile['is_fresher_candidate'] ?? 0) === 1;
        $hasThinExperienceContent = count($workExperiences) === 0 || (count($workExperiences) === 1 && count($projects) === 0);

        $blocked = [];
        if ($isFresher || $hasThinExperienceContent) {
            $blocked['executive_sidebar'] = 'Executive Sidebar is unavailable for fresher or limited-experience profiles. Use Modern Professional or Tech Focus instead.';
        }

        return $blocked;
    }
}
