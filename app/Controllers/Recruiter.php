<?php

namespace App\Controllers;

use App\Models\CompanyModel;
use App\Models\JobModel;

class Recruiter extends BaseController
{
    private const QUESTIONNAIRE_TYPES = ['text', 'textarea'];
    private const POSTED_FOR_OPTIONS = ['own_company', 'client'];
    private const CLIENT_DISCLOSURE_OPTIONS = ['visible', 'confidential'];
    private const PAYROLL_TYPE_OPTIONS = ['company_payroll', 'client_payroll', 'consultancy_payroll', 'third_party_contract'];

    public function postJob()
    {
        $redirect = $this->ensureVerifiedRecruiter();
        if ($redirect !== null) {
            return $redirect;
        }

        return view('recruiter/post_job');
    }

    public function settings()
    {
        $redirect = $this->ensureVerifiedRecruiter();
        if ($redirect !== null) {
            return $redirect;
        }

        $activeTab = (string) ($this->request->getGet('tab') ?? 'account');
        $allowedTabs = ['account', 'workflow', 'mailbox', 'appearance', 'language'];
        if (!in_array($activeTab, $allowedTabs, true)) {
            $activeTab = 'account';
        }

        $mailboxConnection = null;
        if (\Config\Database::connect()->tableExists('recruiter_mailbox_connections')) {
            $mailboxConnection = (new \App\Models\RecruiterMailboxConnectionModel())
                ->where('recruiter_id', (int) session()->get('user_id'))
                ->orderBy('updated_at', 'DESC')
                ->first();
        }
        $mailboxConfig = config('RecruiterMailbox');
        $recruiterAccount = model('UserModel')->findRecruiterWithProfile((int) session()->get('user_id')) ?? [];
        $workflowSettings = [];
        if (\Config\Database::connect()->tableExists('recruiter_workflow_settings')) {
            $workflowSettings = (new \App\Models\RecruiterWorkflowSettingModel())->getForRecruiter((int) session()->get('user_id'));
        } else {
            $workflowSettings = (new \App\Models\RecruiterWorkflowSettingModel())->defaults();
        }

        return view('recruiter/settings', [
            'activeTab' => $activeTab,
            'mailboxConnection' => $mailboxConnection,
            'workflowSettings' => $workflowSettings,
            'mailboxProviders' => [
                'google' => !empty($mailboxConfig->google['client_id']) && !empty($mailboxConfig->google['client_secret']),
                'microsoft' => !empty($mailboxConfig->microsoft['client_id']) && !empty($mailboxConfig->microsoft['client_secret']),
            ],
            'verifiedRecruiterEmail' => strtolower(trim((string) ($recruiterAccount['official_email'] ?? $recruiterAccount['email'] ?? ''))),
        ]);
    }

    public function updateWorkflowSettings()
    {
        $redirect = $this->ensureVerifiedRecruiter();
        if ($redirect !== null) {
            return $redirect;
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('recruiter_workflow_settings')) {
            return redirect()->to(base_url('recruiter/settings?tab=workflow'))->with('error', 'Workflow settings are not ready yet. Run the latest migrations first.');
        }

        $subject = trim((string) $this->request->getPost('rejection_email_subject'));
        $body = trim((string) $this->request->getPost('rejection_email_body'));

        if ($subject === '' || mb_strlen($subject) > 255) {
            return redirect()->back()->withInput()->with('error', 'Rejection email subject is required and must be under 255 characters.');
        }

        if ($body === '' || mb_strlen($body) > 4000) {
            return redirect()->back()->withInput()->with('error', 'Rejection email body is required and must be under 4000 characters.');
        }

        (new \App\Models\RecruiterWorkflowSettingModel())->saveForRecruiter((int) session()->get('user_id'), [
            'send_rejection_email' => $this->request->getPost('send_rejection_email') === '1' ? 1 : 0,
            'rejection_email_subject' => $subject,
            'rejection_email_body' => $body,
            'rejection_email_use_mailbox' => $this->request->getPost('rejection_email_use_mailbox') === '1' ? 1 : 0,
            'rejection_email_allow_system_fallback' => $this->request->getPost('rejection_email_allow_system_fallback') === '1' ? 1 : 0,
            'rejection_email_cc_self' => $this->request->getPost('rejection_email_cc_self') === '1' ? 1 : 0,
        ]);

        return redirect()->to(base_url('recruiter/settings?tab=workflow'))->with('success', 'Hiring workflow settings saved.');
    }

    public function saveJob()
    {
        $redirect = $this->ensureVerifiedRecruiter();
        if ($redirect !== null) {
            return $redirect;
        }

        $session = session();

        $model = new JobModel();
        $userModel = model('UserModel');
        $companyModel = new CompanyModel();

        $title = trim((string) $this->request->getPost('title'));
        $category = trim((string) $this->request->getPost('category'));
        $description = trim((string) $this->request->getPost('description'));
        $location = trim((string) $this->request->getPost('location'));
        $requiredSkills = trim((string) $this->request->getPost('required_skills'));
        $experienceLevel = trim((string) $this->request->getPost('experience_level'));
        $employmentType = trim((string) $this->request->getPost('employment_type'));
        $salaryRange = trim((string) $this->request->getPost('salary_range'));
        $applicationDeadlineRaw = trim((string) $this->request->getPost('application_deadline'));
        $postedFor = $this->normalizeOption((string) $this->request->getPost('posted_for'), self::POSTED_FOR_OPTIONS, 'own_company');
        $clientCompanyName = trim((string) $this->request->getPost('client_company_name'));
        $clientDisclosure = $this->normalizeOption((string) $this->request->getPost('client_disclosure'), self::CLIENT_DISCLOSURE_OPTIONS, 'visible');
        $payrollType = $this->normalizeOption((string) $this->request->getPost('payroll_type'), self::PAYROLL_TYPE_OPTIONS, '');
        $aiInterviewPolicy = JobModel::normalizeAiPolicy($this->request->getPost('ai_interview_policy'));
        $minAiCutoffRaw = trim((string) $this->request->getPost('min_ai_cutoff_score'));
        $minAiCutoff = $minAiCutoffRaw === '' ? null : (int) $minAiCutoffRaw;
        $openings = (int) $this->request->getPost('openings');
        [$questionnaire, $questionnaireError] = $this->buildQuestionnairePayload($this->request->getPost('questionnaire'));

        $currentUserId = (int) $session->get('user_id');
        $user = $userModel->findRecruiterWithProfile($currentUserId) ?? $userModel->find($currentUserId);
        $companyId = (int) ($user['company_id'] ?? 0);
        $companyRow = $companyId > 0 ? $companyModel->find($companyId) : null;
        $company = trim((string) ($companyRow['name'] ?? ($user['company_name'] ?? '')));

        if ($title === '' || $category === '' || $description === '' || $location === '') {
            return redirect()->back()->withInput()->with('error', 'Title, category, description and location are required.');
        }

        if ($company === '') {
            return redirect()->back()->withInput()->with(
                'error',
                'Please set your company name in Company Profile before posting jobs.'
            );
        }

        if ($openings <= 0) {
            return redirect()->back()->withInput()->with('error', 'Openings must be greater than 0.');
        }

        if ($postedFor === 'client' && $clientCompanyName === '') {
            return redirect()->back()->withInput()->with('error', 'Client company name is required when posting for a client.');
        }

        if ($questionnaireError !== null) {
            return redirect()->back()->withInput()->with('error', $questionnaireError);
        }

        if (!JobModel::supportsAiInterviewForCategory($category)) {
            $aiInterviewPolicy = JobModel::AI_POLICY_OFF;
            $minAiCutoff = 0;
        }

        if ($aiInterviewPolicy !== JobModel::AI_POLICY_OFF) {
            if ($minAiCutoff === null) {
                return redirect()->back()->withInput()->with('error', 'Minimum AI cutoff score is required when AI interview is enabled.');
            }

            if ($minAiCutoff < 0 || $minAiCutoff > 100) {
                return redirect()->back()->withInput()->with('error', 'Minimum AI cutoff score must be between 0 and 100.');
            }
        } else {
            $minAiCutoff = 0;
        }

        $riskReasons = $this->runAutoJobChecks(
            $currentUserId,
            $title,
            $postedFor === 'client' ? $clientCompanyName : $company,
            $location,
            $description
        );
        if (!empty($riskReasons)) {
            return redirect()->back()->withInput()->with('error', 'Job blocked by automated checks: ' . implode(' | ', $riskReasons));
        }

        $applicationDeadline = null;
        if ($applicationDeadlineRaw !== '') {
            $parsedDate = \DateTime::createFromFormat('Y-m-d', $applicationDeadlineRaw);
            $dateErrors = \DateTime::getLastErrors();
            if (!$parsedDate || ($dateErrors['warning_count'] ?? 0) > 0 || ($dateErrors['error_count'] ?? 0) > 0) {
                return redirect()->back()->withInput()->with('error', 'Application deadline must be a valid date.');
            }
            $applicationDeadline = $parsedDate->format('Y-m-d');
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
            'min_ai_cutoff_score' => $minAiCutoff,
            'openings' => $openings,
            'recruiter_id' => $currentUserId,
            'created_at' => date('Y-m-d H:i:s')
        ];

        // Keep backward compatibility if DB is not migrated yet.
        $db = \Config\Database::connect();
        if ($db->fieldExists('employment_type', 'jobs')) {
            $data['employment_type'] = $employmentType !== '' ? $employmentType : null;
        }
        if ($db->fieldExists('posted_for', 'jobs')) {
            $data['posted_for'] = $postedFor;
        }
        if ($db->fieldExists('client_company_name', 'jobs')) {
            $data['client_company_name'] = $postedFor === 'client' ? $clientCompanyName : null;
        }
        if ($db->fieldExists('client_disclosure', 'jobs')) {
            $data['client_disclosure'] = $postedFor === 'client' ? $clientDisclosure : 'visible';
        }
        if ($db->fieldExists('payroll_type', 'jobs')) {
            $data['payroll_type'] = $payrollType !== '' ? $payrollType : null;
        }
        if ($db->fieldExists('candidate_fee_allowed', 'jobs')) {
            $data['candidate_fee_allowed'] = 0;
        }
        if ($db->fieldExists('salary_range', 'jobs')) {
            $data['salary_range'] = $salaryRange !== '' ? $salaryRange : null;
        }
        if ($db->fieldExists('application_deadline', 'jobs')) {
            $data['application_deadline'] = $applicationDeadline;
        }
        if ($db->fieldExists('application_questionnaire', 'jobs')) {
            $data['application_questionnaire'] = $questionnaire !== [] ? json_encode($questionnaire) : null;
        }
        if ($db->fieldExists('ai_interview_policy', 'jobs')) {
            $data['ai_interview_policy'] = $aiInterviewPolicy;
        }

        $model->insert($data);
        $jobId = (int) $model->getInsertID();
        if ($jobId > 0) {
            $job = $model->find($jobId);
            if (!empty($job)) {
                (new \App\Libraries\JobAlertService())->processNewJob($job);
            }
        }

        return redirect()->to(base_url('recruiter/jobs'))->with('success', 'Job Posted Successfully');
    }

    /**
     * @param mixed $rawQuestionnaire
     * @return array{0: array<int, array<string, mixed>>, 1: string|null}
     */
    private function buildQuestionnairePayload($rawQuestionnaire): array
    {
        if (!is_array($rawQuestionnaire)) {
            return [[], null];
        }

        $questions = [];
        foreach ($rawQuestionnaire as $index => $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = trim((string) ($row['label'] ?? ''));
            $type = strtolower(trim((string) ($row['type'] ?? 'textarea')));
            $placeholder = trim((string) ($row['placeholder'] ?? ''));
            $required = (int) ($row['required'] ?? 0) === 1;
            $knockout = (int) ($row['knockout'] ?? 0) === 1;
            $knockoutAnswer = trim((string) ($row['knockout_answer'] ?? ''));
            $knockoutMatch = strtolower(trim((string) ($row['knockout_match'] ?? 'exact')));
            $existingId = trim((string) ($row['id'] ?? ''));

            if ($label === '' && $placeholder === '') {
                continue;
            }

            if ($label === '') {
                return [[], 'Each application question needs a prompt.'];
            }

            if (!in_array($type, self::QUESTIONNAIRE_TYPES, true)) {
                return [[], 'Application questionnaire contains an unsupported field type.'];
            }

            if (mb_strlen($label) > 150) {
                return [[], 'Question prompts must be 150 characters or fewer.'];
            }

            if ($placeholder !== '' && mb_strlen($placeholder) > 200) {
                return [[], 'Question placeholders must be 200 characters or fewer.'];
            }

            if ($knockout) {
                if ($knockoutAnswer === '') {
                    return [[], 'Knock-out questions need an expected answer.'];
                }

                if (!in_array($knockoutMatch, ['exact', 'contains'], true)) {
                    return [[], 'Knock-out question match type is invalid.'];
                }

                $required = true;
            }

            $questions[] = [
                'id' => $existingId !== '' ? $existingId : 'q_' . substr(sha1($label . '|' . $index . '|' . microtime(true)), 0, 12),
                'label' => $label,
                'type' => $type,
                'placeholder' => $placeholder,
                'required' => $required,
                'knockout' => $knockout,
                'knockout_answer' => $knockoutAnswer,
                'knockout_match' => $knockoutMatch,
            ];
        }

        if (count($questions) > 8) {
            return [[], 'You can add up to 8 application questions per job.'];
        }

        return [$questions, null];
    }

    private function ensureVerifiedRecruiter()
    {
        $session = session();
        if (!$session->get('logged_in')) {
            return redirect()->to(base_url('login'));
        }

        if ($session->get('role') !== 'recruiter') {
            return redirect()->to(base_url('candidate/dashboard'))->with('error', 'Only recruiters can access this page.');
        }

        $userModel = model('UserModel');
        $user = $userModel->findRecruiterWithProfile((int) $session->get('user_id')) ?? $userModel->find($session->get('user_id'));
        if (!$user) {
            return redirect()->to(base_url('login'))->with('error', 'User not found.');
        }

        if (empty($user['email_verified_at'])) {
            return redirect()->to(base_url('recruiter/verification?email=' . urlencode((string) $user['email'])))
                ->with('error', 'Please verify your company email before posting jobs.');
        }

        if (array_key_exists('verification_status', $user) && (string) $user['verification_status'] !== 'verified') {
            $message = (string) $user['verification_status'] === 'rejected'
                ? 'Your recruiter verification was rejected. Contact support before posting jobs.'
                : 'Your consultancy account is pending admin verification before job posting is enabled.';

            return redirect()->to(base_url('recruiter/dashboard'))->with('error', $message);
        }

        if (array_key_exists('can_post_jobs', $user) && (int) $user['can_post_jobs'] !== 1) {
            return redirect()->to(base_url('recruiter/dashboard'))
                ->with('error', 'Job posting is not enabled for this recruiter account yet.');
        }

        return null;
    }
public function getAiReport()
{
    try {
        $data = $this->request->getJSON(true);

        $candidateId = (int)($data['candidate_id'] ?? 0);
        $jobrole     = trim($data['jobrole'] ?? '');

        if (!$candidateId) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'success' => false,
                    'message' => 'Candidate ID is required.'
                ]);
        }

        $db = \Config\Database::connect();
        $results = [];
        $violations = [];

        if ($db->tableExists('interview_results')) {
            $resultsBuilder = $db->table('interview_results')
                ->select('round_name, score, total_questions, percentage')
                ->where('candidate_id', $candidateId);

            if ($jobrole !== '' && $db->fieldExists('jobrole', 'interview_results')) {
                $resultsBuilder->where('jobrole', $jobrole);
            }

            $resultsQuery = $resultsBuilder
                ->orderBy('id', 'ASC')
                ->get();

            if ($resultsQuery !== false) {
                $results = $resultsQuery->getResultArray();
            } else {
                log_message('error', 'AI Report: failed to fetch interview_results for candidate ' . $candidateId);
            }
        }

        if ($db->tableExists('violations')) {
            $violationsBuilder = $db->table('violations')
                ->select('message, COUNT(*) as total')
                ->where('candidate_id', $candidateId);

            if ($jobrole !== '' && $db->fieldExists('jobrole', 'violations')) {
                $violationsBuilder->where('jobrole', $jobrole);
            }

            $violationsQuery = $violationsBuilder
                ->groupBy('message')
                ->orderBy('total', 'DESC')
                ->get();

            if ($violationsQuery !== false) {
                $violations = $violationsQuery->getResultArray();
            } else {
                log_message('error', 'AI Report: failed to fetch violations for candidate ' . $candidateId);
            }
        }

        return $this->response->setJSON([
            'success'    => true,
            'results'    => $results,
            'violations' => $violations
        ]);

    } catch (\Throwable $e) {

        log_message('error', 'AI Report Error: ' . $e->getMessage());

        return $this->response
            ->setStatusCode(500)
            ->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
    }
}
    private function normalizeOption(string $value, array $allowed, string $default): string
    {
        $value = trim($value);

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function runAutoJobChecks(int $recruiterId, string $title, string $company, string $location, string $description): array
    {
        $reasons = [];
        $jobModel = model('JobModel');
        $db = \Config\Database::connect();

        // 1) Limit number of jobs/day per recruiter.
        $todayCount = $jobModel
            ->where('recruiter_id', $recruiterId)
            ->where('DATE(created_at)', date('Y-m-d'))
            ->countAllResults();
        if ($todayCount >= 15) {
            $reasons[] = 'Daily posting limit reached (15 jobs/day).';
        }

        // 2) Duplicate detection for active postings.
        $duplicate = $jobModel
            ->where('recruiter_id', $recruiterId)
            ->where('LOWER(title)', strtolower($title))
            ->where('LOWER(company)', strtolower($company))
            ->where('LOWER(location)', strtolower($location))
            ->where('status', 'open')
            ->first();
        if ($duplicate) {
            $reasons[] = 'Duplicate active job detected for same title/company/location.';
        }

        // 3) Scam keyword detection.
        $content = strtolower($title . ' ' . $description);
        $blockedPhrases = [
            'pay to join',
            'registration fee',
            'security deposit',
            'earn money fast',
            'quick money',
            'investment required',
            'whatsapp only',
            'telegram only',
            'no interview direct joining'
        ];
        foreach ($blockedPhrases as $phrase) {
            if (str_contains($content, $phrase)) {
                $reasons[] = 'Blocked phrase detected: "' . $phrase . '"';
                break;
            }
        }

        // 4) Suspicious salary text detection in description.
        if (preg_match('/(salary|ctc|pay)\s*[:\-]?\s*(rs\.?|inr)?\s*([0-9]{1,9})/i', $description, $m)) {
            $amount = (int) ($m[3] ?? 0);
            if ($amount > 50000000 || ($amount > 0 && $amount < 3000)) {
                $reasons[] = 'Suspicious salary amount detected.';
            }
        }

        return $reasons;
    }
}
    
