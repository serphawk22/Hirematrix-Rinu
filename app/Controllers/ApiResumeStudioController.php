<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\UserModel;
use App\Models\JobModel;
use App\Models\ApplicationModel;
use App\Models\CareerTransitionModel;
use App\Models\CandidateResumeVersionModel;
use App\Models\CandidateSkillsModel;
use App\Models\CandidateInterestsModel;
use App\Models\GithubAnalysisModel;
use App\Models\WorkExperienceModel;
use App\Models\EducationModel;
use App\Models\CertificationModel;
use App\Models\CandidateProjectModel;
use App\Libraries\AiResumeBuilder;
use App\Libraries\ResumeTemplateRenderer;

class ApiResumeStudioController extends ResourceController
{
    protected $format = 'json';

    public function getStudioData($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->fail('Invalid Candidate ID');
        }

        $userModel = new UserModel();
        $user = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId) ?? [];

        if (!$user) {
            return $this->fail('User not found');
        }

        $studioData = $this->buildResumeStudioData($candidateId, $user);

        return $this->respond([
            'status' => 'success',
            'data' => $studioData
        ]);
    }

    public function generate()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        $generationMode = trim((string) $this->request->getPost('generation_mode'));
        $targetRole = trim((string) $this->request->getPost('target_role'));
        $jobId = (int) ($this->request->getPost('job_id') ?? 0);
        $makePrimary = (int) ($this->request->getPost('make_primary') ?? 0) === 1;
        $templateKey = trim((string) $this->request->getPost('template_key'));

        if ($candidateId <= 0) {
            return $this->fail('candidate_id is required.');
        }

        // Check readiness
        $readiness = $this->getProfileReadinessForResume($candidateId);
        if (!$readiness['is_ready']) {
            return $this->fail('Profile incomplete: ' . implode(', ', $readiness['missing_details']));
        }

        if (!in_array($generationMode, ['role', 'job'], true)) {
            return $this->fail('Choose either Generate By Role or Generate For Specific Job.');
        }

        $job = null;
        if ($generationMode === 'job' && $jobId > 0) {
            $job = (new JobModel())->find($jobId);
            $targetRole = trim((string) ($job['title'] ?? ''));
        }

        if ($generationMode === 'role') {
            if ($targetRole === '') {
                return $this->fail('Target role is required for Role generation.');
            }
            if ($jobId > 0) {
                return $this->fail('Choose only one source.');
            }
        }

        if ($generationMode === 'job') {
            if ($jobId <= 0 || !$job) {
                return $this->fail('Select a valid job for Job-specific generation.');
            }
        }

        $profile = $this->buildResumeProfileSnapshot($candidateId);
        $blockedTemplates = $this->getBlockedResumeTemplates($profile);
        if ($templateKey !== '' && isset($blockedTemplates[$templateKey])) {
            return $this->fail($blockedTemplates[$templateKey]);
        }

        $currentRole = $this->detectCurrentRole($profile);
        
        $resumeBuilder = new AiResumeBuilder();
        $resume = $resumeBuilder->buildResume($profile, $targetRole, [
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

        return $this->respond([
            'status' => 'success',
            'message' => $jobId > 0
                ? 'AI resume version generated for the selected job.'
                : 'Role-based AI resume saved for this target role.'
        ]);
    }

    public function syncTransition()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        if ($candidateId <= 0) {
            return $this->fail('candidate_id is required.');
        }

        $transitionModel = new CareerTransitionModel();
        $activeTransition = $transitionModel->getActiveTransition($candidateId);

        if (!$activeTransition) {
            return $this->fail('No active career transition found.');
        }

        $profile = $this->buildResumeProfileSnapshot($candidateId);
        $targetRole = trim((string) ($activeTransition['target_role'] ?? ''));
        $currentRole = trim((string) ($activeTransition['current_role'] ?? $this->detectCurrentRole($profile)));
        $skillGaps = json_decode((string) ($activeTransition['skill_gaps'] ?? '[]'), true);
        $transitionSummary = 'Career transition in progress from ' . $currentRole . ' to ' . $targetRole . '.'
            . (!empty($skillGaps) ? ' Current focus areas: ' . implode(', ', array_slice((array) $skillGaps, 0, 6)) . '.' : '');

        $resumeBuilder = new AiResumeBuilder();
        $resume = $resumeBuilder->buildResume($profile, $targetRole, [
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

        return $this->respond([
            'status' => 'success',
            'message' => 'Career-transition resume refreshed and set as primary version.'
        ]);
    }

    public function setPrimary()
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        $versionId = (int) $this->request->getPost('version_id');

        if ($candidateId <= 0 || $versionId <= 0) {
            return $this->fail('candidate_id and version_id are required.');
        }

        $resumeVersionModel = new CandidateResumeVersionModel();
        $version = $resumeVersionModel->find($versionId);

        if (!$version || (int) ($version['candidate_id'] ?? 0) !== $candidateId) {
            return $this->fail('Resume version not found.');
        }

        $resumeVersionModel->setPrimaryVersion($candidateId, $versionId);

        return $this->respond([
            'status' => 'success',
            'message' => 'Primary AI resume version updated.'
        ]);
    }

    public function delete($id = null)
    {
        $candidateId = (int) $this->request->getPost('candidate_id');
        $versionId = (int) ($this->request->getPost('version_id') ?? $id);

        if ($candidateId <= 0 || $versionId <= 0) {
            return $this->fail('candidate_id and version_id are required.');
        }

        $resumeVersionModel = new CandidateResumeVersionModel();
        $resumeVersion = $resumeVersionModel->find($versionId);
        if (!$resumeVersion || (int) ($resumeVersion['candidate_id'] ?? 0) !== $candidateId) {
            return $this->fail('Resume version not found.');
        }

        $wasPrimary = (int) ($resumeVersion['is_primary'] ?? 0) === 1;
        $resumeVersionModel->delete($versionId);

        if ($wasPrimary) {
            $replacement = $resumeVersionModel
                ->where('candidate_id', $candidateId)
                ->orderBy('updated_at', 'DESC')
                ->first();

            if ($replacement) {
                $resumeVersionModel->setPrimaryVersion($candidateId, (int) $replacement['id']);
            }
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Resume version deleted successfully.'
        ]);
    }

    public function downloadResumeVersion($versionId, $candidateId)
    {
        $versionId = (int) $versionId;
        $candidateId = (int) $candidateId;

        $resumeVersion = (new CandidateResumeVersionModel())->find($versionId);
        if (!$resumeVersion || (int) ($resumeVersion['candidate_id'] ?? 0) !== $candidateId) {
            return $this->response->setStatusCode(404)->setBody('Resume version not found.');
        }

        $user = (new UserModel())->find($candidateId) ?? [];
        $renderer = new ResumeTemplateRenderer();
        try {
            $pdfPath = $renderer->createPdfFile((string) ($resumeVersion['content'] ?? ''), [
                'name' => (string) ($user['name'] ?? 'Candidate'),
                'target_role' => (string) ($resumeVersion['target_role'] ?? ''),
                'summary' => (string) ($resumeVersion['summary'] ?? ''),
                'highlight_skills' => $this->splitCsvList((string) ($resumeVersion['highlight_skills'] ?? '')),
            ], (string) (($user['name'] ?? 'candidate') . '-' . ($resumeVersion['target_role'] ?? 'resume')));

            return $this->response->download($pdfPath, null)->setFileName(basename($pdfPath));
        } catch (\Exception $e) {
            return $this->response->setStatusCode(500)->setBody('PDF download failed.');
        }
    }

    public function previewResumeVersion($versionId, $candidateId)
    {
        $versionId = (int) $versionId;
        $candidateId = (int) $candidateId;

        $resumeVersion = (new CandidateResumeVersionModel())->find($versionId);
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

    // Copy support helpers from Candidate controller
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

    private function splitCsvList(string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $value))));
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
            $resumeVersion['template_key'] = $decoded['template_key'] ?? 'modern_professional';
            
            // Generate clean stateless URLs for download and preview
            $resumeVersion['download_url'] = base_url('api/resume-studio/download-pdf/' . $resumeVersion['id'] . '/' . $userId);
            $resumeVersion['preview_url'] = base_url('api/resume-studio/preview-html/' . $resumeVersion['id'] . '/' . $userId);

            // Calculate a pseudo-strength score
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

        // Format template choices for api consumer
        $templatesList = [];
        foreach ($templateRenderer->getTemplates() as $key => $tpl) {
            $templatesList[] = [
                'key' => $key,
                'label' => $tpl['label'],
                'description' => $tpl['description'],
                'preview_class' => $tpl['preview_class'] ?? 'modern',
                'accent' => $tpl['accent'] ?? '#2563eb',
                'badge' => $tpl['badge'] ?? '',
                'is_blocked' => isset($blockedResumeTemplates[$key]),
                'block_reason' => $blockedResumeTemplates[$key] ?? ''
            ];
        }

        return [
            'resumeVersions' => $resumeVersions,
            'resumeTargets' => array_values($resumeTargets),
            'activeTransition' => $transitionModel->getActiveTransition($userId),
            'resumeTemplates' => $templatesList,
            'profileReadiness' => $readiness,
        ];
    }
}
