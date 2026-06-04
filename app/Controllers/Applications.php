<?php

namespace App\Controllers;

use App\Models\ApplicationModel;
use App\Models\CandidateResumeVersionModel;
use App\Models\JobModel;
use App\Models\RecruiterJobInvitationModel;

class Applications extends BaseController
{
    private const QUESTIONNAIRE_TYPES = ['text', 'textarea'];

    public function apply($jobId)
    {
        $session = session();
        $isAjax = $this->request->isAJAX();

        if (!$session->get('logged_in')) {
            if ($isAjax) {
                return $this->respondApplicationAction(false, 'Please log in to continue.', null, 401, base_url('login'));
            }
            return redirect()->to(base_url('login'));
        }

        $candidateId = $session->get('user_id');
        $jobModel = model('JobModel');
        $job = $jobModel
            ->where('id', (int) $jobId)
            ->where('status', 'open')
            ->first();

        if (!$job) {
            if ($isAjax) {
                return $this->respondApplicationAction(false, 'Job not found or no longer open.', null, 404);
            }
            return redirect()->to(base_url('jobs'))->with('error', 'Job not found or no longer open.');
        }

        // Enforce application deadline
        if (!empty($job['application_deadline'])) {
            if (strtotime($job['application_deadline'] . ' 23:59:59') < time()) {
                if ($isAjax) return $this->respondApplicationAction(false, 'The application deadline for this job has passed.', null, 410);
                return redirect()->back()->with('error', 'The application deadline for this job has passed.');
            }
        }

        if (JobModel::isExternalJob($job)) {
            $externalApplyUrl = trim((string) ($job['external_apply_url'] ?? ''));
            if (!filter_var($externalApplyUrl, FILTER_VALIDATE_URL)) {
                if ($isAjax) {
                    return $this->respondApplicationAction(false, 'This external job does not have a valid apply link.', null, 422);
                }
                return redirect()->back()->with('error', 'This external job does not have a valid apply link.');
            }

            if ($isAjax) {
                return $this->respondApplicationAction(
                    false,
                    'This listing uses an external application flow. Redirecting now.',
                    ['external' => true],
                    200,
                    $externalApplyUrl
                );
            }

            return redirect()->to($externalApplyUrl);
        }
        
        // Check if resume is uploaded
        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile((int) $candidateId) ?? $userModel->find($candidateId);
        
        if (empty($user['resume_path'])) {
            if ($isAjax) {
                return $this->respondApplicationAction(
                    false,
                    'Please upload your resume to continue your job application.',
                    null,
                    422,
                    base_url('candidate/profile')
                );
            }
            return redirect()->to(base_url('candidate/profile'))->with('error', 'Please upload your resume to continue your job application. You have been redirected to your profile page.');
        }
        
        $model = new ApplicationModel();

        $alreadyApplied = $model
            ->where('job_id', $jobId)
            ->where('candidate_id', $candidateId)
            ->where('status !=', 'withdrawn')
            ->first();

        if ($alreadyApplied) {
            if ($isAjax) {
                return $this->respondApplicationAction(false, 'You have already applied for this job.', [
                    'application_id' => (int) $alreadyApplied['id'],
                    'status' => (string) ($alreadyApplied['status'] ?? ''),
                    'status_label' => ucwords(str_replace('_', ' ', (string) ($alreadyApplied['status'] ?? ''))),
                    'already_applied' => true,
                ], 409);
            }
            return redirect()->back()->with('error', 'You have already applied for this job');
        }

        // Check skill mismatch - compare with resume AND github skills
        $skillsModel = model('CandidateSkillsModel');
        $githubModel = model('GithubAnalysisModel');
        
        $candidateSkills = $skillsModel->where('candidate_id', $candidateId)->first();
        $githubStats = $githubModel->where('candidate_id', $candidateId)->first();
        
        $jobTitle = strtolower($job['title'] ?? '');
        $jobSkills = strtolower($job['required_skills'] ?? '');
        $resumeSkills = strtolower($candidateSkills['skill_name'] ?? '');
        $githubLanguages = strtolower($githubStats['languages_used'] ?? '');
        
        // Combine resume and github skills
        $allCandidateSkills = $resumeSkills . ' ' . $githubLanguages;
        
        // Detect mismatch: job requires skills candidate doesn't have
        $hasJobTitleSkill = stripos($allCandidateSkills, $jobTitle) !== false;
        $hasRequiredSkills = false;
        
        // Check if candidate has any of the required skills
        $requiredSkillsList = explode(',', $jobSkills);
        foreach ($requiredSkillsList as $skill) {
            $skill = trim($skill);
            if (!empty($skill) && stripos($allCandidateSkills, $skill) !== false) {
                $hasRequiredSkills = true;
                break;
            }
        }
        
        $mismatch = !empty($jobTitle) && !empty($allCandidateSkills) && 
                    (!$hasJobTitleSkill && !$hasRequiredSkills);
        $aiPolicy = JobModel::normalizeAiPolicy($job['ai_interview_policy'] ?? JobModel::AI_POLICY_REQUIRED_HARD);
        $initialStatus = $aiPolicy === JobModel::AI_POLICY_OFF ? 'shortlisted' : 'applied';
        $db = \Config\Database::connect();
        $resumeVersion = null;
        if ($db->tableExists('candidate_resume_versions') && $db->fieldExists('resume_version_id', 'applications')) {
            $resumeVersion = (new CandidateResumeVersionModel())->getPreferredVersionForJob((int) $candidateId, (int) $jobId);
        }

        $payload = [
            'job_id' => $jobId,
            'candidate_id' => $candidateId,
            'status' => $initialStatus,
            'applied_at' => date('Y-m-d H:i:s')
        ];

        $questionnaire = $this->decodeQuestionnaire((string) ($job['application_questionnaire'] ?? ''));
        $questionnaireResponses = [];
        if ($db->fieldExists('questionnaire_responses', 'applications')) {
            [$responses, $questionnaireError] = $this->buildQuestionnaireResponses(
                $questionnaire,
                $this->request->getPost('questionnaire_response')
            );

            if ($questionnaireError !== null) {
                if ($isAjax) {
                    return $this->respondApplicationAction(false, $questionnaireError, null, 422);
                }

                return redirect()->back()->withInput()->with('error', $questionnaireError);
            }

            $questionnaireResponses = $responses;
            $payload['questionnaire_responses'] = $responses !== [] ? json_encode($responses) : null;
        }

        $knockoutFailure = $this->findKnockoutFailure($questionnaire, $questionnaireResponses);
        if ($knockoutFailure !== null) {
            $payload['status'] = 'filtered_out';
            $initialStatus = 'filtered_out';
        }

        if ($db->fieldExists('resume_version_id', 'applications')) {
            $payload['resume_version_id'] = (int) ($resumeVersion['id'] ?? 0) > 0 ? (int) $resumeVersion['id'] : null;
        }

        $model->insert($payload);
        $db = \Config\Database::connect();
        if ($db->tableExists('recruiter_job_invitations')) {
            (new RecruiterJobInvitationModel())->markAppliedForCandidateJob((int) $candidateId, (int) $jobId);
        }
        
        $applicationId = $model->getInsertID();
        $stageModel = model('StageHistoryModel');
        $stageModel->moveToStage($applicationId, 'Applied');
        if ($initialStatus === 'filtered_out') {
            $stageModel->moveToStage($applicationId, 'Filtered Out (Knock-out Question)');
        }
        if ($initialStatus === 'shortlisted') {
            $stageModel->moveToStage($applicationId, 'Shortlisted (AI Policy OFF)');
        }

        // Mask status for candidate UI response to ensure 'filtered_out' remains hidden
        $displayStatus = ($initialStatus === 'filtered_out') ? 'applied' : $initialStatus;

        $applySuccessMessage = $knockoutFailure !== null
            ? 'Application submitted successfully. It is now under recruiter review.'
            : $this->getApplySuccessMessage($aiPolicy);
        if ($mismatch) {
            // Store multiple suggestions as array
            $suggestions = $session->get('career_suggestions') ?? [];
            
            // Check if this job title already suggested
            $alreadySuggested = false;
            foreach ($suggestions as $existing) {
                if ($existing['job_title'] === $job['title']) {
                    $alreadySuggested = true;
                    break;
                }
            }
            
            // Add new suggestion if not already present
            if (!$alreadySuggested) {
                $suggestions[] = [
                    'job_title' => $job['title'],
                    'created_at' => time(),
                    'expires_at' => time() + (2 * 24 * 60 * 60)
                ];
                $session->set('career_suggestions', $suggestions);
            }
            
            if ($isAjax) {
                return $this->respondApplicationAction(true, $applySuccessMessage, [
                    'application_id' => $applicationId,
                    'status' => $displayStatus,
                    'status_label' => ucwords(str_replace('_', ' ', $displayStatus)),
                    'filtered_out' => $knockoutFailure !== null,
                    'redirect_url' => base_url('candidate/dashboard'),
                    'mismatch' => true,
                ]);
            }

            return redirect()->to('candidate/dashboard')->with('success', $applySuccessMessage);
        }

        if ($isAjax) {
            return $this->respondApplicationAction(true, $applySuccessMessage, [
                'application_id' => $applicationId,
                'status' => $displayStatus,
                'status_label' => ucwords(str_replace('_', ' ', $displayStatus)),
                'filtered_out' => $knockoutFailure !== null,
                'mismatch' => false,
            ]);
        }

        return redirect()->back()->with('success', $applySuccessMessage);
    }

    public function withdraw($applicationId)
    {
        $session = session();
        $isAjax = $this->request->isAJAX();

        if (!$session->get('logged_in') || $session->get('role') !== 'candidate') {
            if ($isAjax) {
                return $this->respondApplicationAction(false, 'Please log in to continue.', null, 401, base_url('login'));
            }
            return redirect()->to(base_url('login'));
        }

        $candidateId = (int) $session->get('user_id');
        $applicationModel = new ApplicationModel();

        $application = $applicationModel
            ->select('applications.*, jobs.status as job_status')
            ->join('jobs', 'jobs.id = applications.job_id', 'left')
            ->where('applications.id', (int) $applicationId)
            ->where('applications.candidate_id', $candidateId)
            ->first();

        if (!$application) {
            if ($isAjax) {
                return $this->respondApplicationAction(false, 'Application not found.', null, 404);
            }
            return redirect()->back()->with('error', 'Application not found.');
        }

        $status = (string) ($application['status'] ?? '');
        if ($status === 'withdrawn') {
            if ($isAjax) {
                return $this->respondApplicationAction(false, 'This application is already withdrawn.', [
                    'application_id' => (int) $applicationId,
                    'status' => 'withdrawn',
                    'status_label' => 'Withdrawn',
                ], 409);
            }
            return redirect()->back()->with('info', 'This application is already withdrawn.');
        }

        if (in_array($status, ['filtered_out', 'rejected', 'selected', 'hired'], true)) {
            if ($isAjax) {
                return $this->respondApplicationAction(false, 'This application can no longer be withdrawn.', null, 422);
            }
            return redirect()->back()->with('error', 'This application can no longer be withdrawn.');
        }

        if ($status === 'interview_slot_booked' || !empty($application['booking_id'])) {
            if ($isAjax) {
                return $this->respondApplicationAction(false, 'Booked interview applications cannot be withdrawn here.', null, 422);
            }
            return redirect()->back()->with('error', 'Booked interview applications cannot be withdrawn here.');
        }

        $applicationModel->update((int) $applicationId, ['status' => 'withdrawn']);
        model('StageHistoryModel')->moveToStage((int) $applicationId, 'Withdrawn by Candidate');

        if ($isAjax) {
            return $this->respondApplicationAction(true, 'Application withdrawn successfully.', [
                'application_id' => (int) $applicationId,
                'status' => 'withdrawn',
                'status_label' => 'Withdrawn',
                'status_badge' => 'secondary',
            ]);
        }

        return redirect()->to(base_url('candidate/applications'))->with('success', 'Application withdrawn successfully.');
    }

    private function respondApplicationAction(
        bool $success,
        string $message,
        ?array $data = null,
        int $statusCode = 200,
        ?string $redirectUrl = null
    ) {
        $payload = [
            'success' => $success,
            'message' => $message,
            'csrf_token_name' => csrf_token(),
            'csrf_hash' => csrf_hash(),
        ];

        if ($redirectUrl !== null) {
            $payload['redirect_url'] = $redirectUrl;
        }

        if ($data !== null) {
            $payload = array_merge($payload, $data);
        }

        return $this->response->setStatusCode($statusCode)->setJSON($payload);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function decodeQuestionnaire(string $rawQuestionnaire): array
    {
        if (trim($rawQuestionnaire) === '') {
            return [];
        }

        $decoded = json_decode($rawQuestionnaire, true);
        if (!is_array($decoded)) {
            return [];
        }

        $questions = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $id = trim((string) ($row['id'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));
            $type = strtolower(trim((string) ($row['type'] ?? 'textarea')));

            if ($id === '' || $label === '' || !in_array($type, self::QUESTIONNAIRE_TYPES, true)) {
                continue;
            }

            $questions[] = [
                'id' => $id,
                'label' => $label,
                'type' => $type,
                'placeholder' => trim((string) ($row['placeholder'] ?? '')),
                'required' => (bool) ($row['required'] ?? false),
                'knockout' => (bool) ($row['knockout'] ?? false),
                'knockout_answer' => trim((string) ($row['knockout_answer'] ?? '')),
                'knockout_match' => in_array(strtolower(trim((string) ($row['knockout_match'] ?? 'exact'))), ['exact', 'contains'], true)
                    ? strtolower(trim((string) ($row['knockout_match'] ?? 'exact')))
                    : 'exact',
            ];
        }

        return $questions;
    }

    /**
     * @param array<int, array<string, mixed>> $questionnaire
     * @param mixed $rawResponses
     * @return array{0: array<int, array<string, mixed>>, 1: string|null}
     */
    private function buildQuestionnaireResponses(array $questionnaire, $rawResponses): array
    {
        if (empty($questionnaire)) {
            return [[], null];
        }

        $rawResponses = is_array($rawResponses) ? $rawResponses : [];
        $responses = [];

        foreach ($questionnaire as $question) {
            $questionId = (string) ($question['id'] ?? '');
            $answer = trim((string) ($rawResponses[$questionId] ?? ''));

            if (!empty($question['required']) && $answer === '') {
                return [[], '"' . (string) ($question['label'] ?? 'This question') . '" is required.'];
            }

            if ($answer === '') {
                continue;
            }

            if (mb_strlen($answer) > 5000) {
                return [[], 'Each application response must be 5000 characters or fewer.'];
            }

            $responses[] = [
                'question_id' => $questionId,
                'label' => (string) ($question['label'] ?? ''),
                'type' => (string) ($question['type'] ?? 'textarea'),
                'answer' => $answer,
                'required' => (bool) ($question['required'] ?? false),
                'knockout' => (bool) ($question['knockout'] ?? false),
                'knockout_passed' => !empty($question['knockout'])
                    ? $this->answerMatchesKnockout($answer, (string) ($question['knockout_answer'] ?? ''), (string) ($question['knockout_match'] ?? 'exact'))
                    : null,
            ];
        }

        return [$responses, null];
    }

    /**
     * @param array<int, array<string, mixed>> $questionnaire
     * @param array<int, array<string, mixed>> $responses
     */
    private function findKnockoutFailure(array $questionnaire, array $responses): ?array
    {
        if (empty($questionnaire)) {
            return null;
        }

        $answerByQuestionId = [];
        foreach ($responses as $response) {
            $questionId = (string) ($response['question_id'] ?? '');
            if ($questionId !== '') {
                $answerByQuestionId[$questionId] = (string) ($response['answer'] ?? '');
            }
        }

        foreach ($questionnaire as $question) {
            if (empty($question['knockout'])) {
                continue;
            }

            $questionId = (string) ($question['id'] ?? '');
            $answer = trim((string) ($answerByQuestionId[$questionId] ?? ''));
            if (!$this->answerMatchesKnockout($answer, (string) ($question['knockout_answer'] ?? ''), (string) ($question['knockout_match'] ?? 'exact'))) {
                return [
                    'question_id' => $questionId,
                    'label' => (string) ($question['label'] ?? 'Knock-out question'),
                ];
            }
        }

        return null;
    }

    private function answerMatchesKnockout(string $answer, string $expectedRaw, string $matchType): bool
    {
        $answer = $this->normalizeScreeningAnswer($answer);
        $expectedAnswers = preg_split('/[,\r\n|;]+/', $expectedRaw) ?: [];
        $expectedAnswers = array_values(array_filter(array_map(fn (string $value): string => $this->normalizeScreeningAnswer($value), $expectedAnswers)));

        if ($answer === '' || empty($expectedAnswers)) {
            return false;
        }

        foreach ($expectedAnswers as $expected) {
            if ($matchType === 'contains') {
                if (str_contains($answer, $expected)) {
                    return true;
                }
                continue;
            }

            if ($answer === $expected) {
                return true;
            }
        }

        return false;
    }

    private function normalizeScreeningAnswer(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9+#.]+/', ' ', $value) ?? '';
        return trim(preg_replace('/\s+/', ' ', $value) ?? '');
    }

    private function getApplySuccessMessage(string $aiPolicy): string

    {
        if ($aiPolicy === JobModel::AI_POLICY_OFF) {
            return 'Job applied successfully. This job skips AI interview and moved to shortlist stage.';
        }

        if ($aiPolicy === JobModel::AI_POLICY_OPTIONAL) {
            return 'Job applied successfully. AI interview is optional for this job.';
        }

        return 'Job applied successfully.';
    }
}
