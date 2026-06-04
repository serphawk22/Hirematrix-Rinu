<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\JobModel;

class RecruiterJobs extends BaseController
{
    private const QUESTIONNAIRE_TYPES = ['text', 'textarea'];
    private const POSTED_FOR_OPTIONS = ['own_company', 'client'];
    private const CLIENT_DISCLOSURE_OPTIONS = ['visible', 'confidential'];
    private const PAYROLL_TYPE_OPTIONS = ['company_payroll', 'client_payroll', 'consultancy_payroll', 'third_party_contract'];

    public function index()
    {
        $jobModel = model('JobModel');
        $applicationModel = model('ApplicationModel');
        $currentUserId = session()->get('user_id');

        $jobs = $jobModel
            ->select('jobs.*, COUNT(applications.id) as application_count')
            ->join('applications', 'applications.job_id = jobs.id', 'left')
            ->where('jobs.recruiter_id', $currentUserId)
            ->groupBy('jobs.id')
            ->orderBy('jobs.created_at', 'DESC')
            ->findAll();

        return view('recruiter/jobs/index', [
            'jobs' => $jobs,
            'unread_count' => model('NotificationModel')->getUnreadCount($currentUserId)
        ]);
    }

    public function edit($jobId)
    {
        $jobModel = model('JobModel');
        $currentUserId = session()->get('user_id');

        $job = $jobModel->where('id', $jobId)->where('recruiter_id', $currentUserId)->first();
        
        if (!$job) {
            return redirect()->to('recruiter/jobs')->with('error', 'Job not found');
        }

        return view('recruiter/jobs/edit', ['job' => $job]);
    }

    public function update($jobId)
    {
        $jobModel = model('JobModel');
        $currentUserId = session()->get('user_id');

        $job = $jobModel->where('id', $jobId)->where('recruiter_id', $currentUserId)->first();
        
        if (!$job) {
            return redirect()->to('recruiter/jobs')->with('error', 'Job not found');
        }

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
        $openings = (int) $this->request->getPost('openings');
        $minAiCutoffRaw = trim((string) $this->request->getPost('min_ai_cutoff_score'));
        $minAiCutoff = $minAiCutoffRaw === '' ? null : (int) $minAiCutoffRaw;
        [$questionnaire, $questionnaireError] = $this->buildQuestionnairePayload($this->request->getPost('questionnaire'));

        if ($title === '' || $category === '' || $description === '' || $location === '') {
            return redirect()->back()->withInput()->with('error', 'Title, category, description and location are required.');
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
            'description' => $description,
            'location' => $location,
            'required_skills' => $requiredSkills,
            'experience_level' => $experienceLevel,
            'employment_type' => $employmentType,
            'openings' => $openings
        ];

        // Keep backward compatibility if DB is not migrated yet.
        $db = \Config\Database::connect();
        if ($db->fieldExists('ai_interview_policy', 'jobs')) {
            $aiInterviewPolicy = JobModel::normalizeAiPolicy($this->request->getPost('ai_interview_policy'));

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

            $data['ai_interview_policy'] = $aiInterviewPolicy;
            if ($db->fieldExists('min_ai_cutoff_score', 'jobs')) {
                $data['min_ai_cutoff_score'] = $minAiCutoff;
            }
        }

        if ($db->fieldExists('salary_range', 'jobs')) {
            $data['salary_range'] = $salaryRange !== '' ? $salaryRange : null;
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

        if ($db->fieldExists('application_deadline', 'jobs')) {
            $data['application_deadline'] = $applicationDeadline;
        }

        if ($db->fieldExists('application_questionnaire', 'jobs')) {
            $data['application_questionnaire'] = $questionnaire !== [] ? json_encode($questionnaire) : null;
        }

        $jobModel->update($jobId, $data);

        return redirect()->to('recruiter/jobs')->with('success', 'Job updated successfully');
    }

    public function close($jobId)
    {
        $jobModel = model('JobModel');
        $currentUserId = session()->get('user_id');

        $job = $jobModel->where('id', $jobId)->where('recruiter_id', $currentUserId)->first();
        
        if (!$job) {
            return redirect()->to('recruiter/jobs')->with('error', 'Job not found');
        }

        $jobModel->update($jobId, ['status' => 'closed']);

        return redirect()->to('recruiter/jobs')->with('success', 'Job closed successfully');
    }

    public function reopen($jobId)
    {
        $jobModel = model('JobModel');
        $currentUserId = session()->get('user_id');

        $job = $jobModel->where('id', $jobId)->where('recruiter_id', $currentUserId)->first();
        
        if (!$job) {
            return redirect()->to('recruiter/jobs')->with('error', 'Job not found');
        }

        $jobModel->update($jobId, ['status' => 'open']);

        return redirect()->to('recruiter/jobs')->with('success', 'Job reopened successfully');
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

    private function normalizeOption(string $value, array $allowed, string $default): string
    {
        $value = trim($value);

        return in_array($value, $allowed, true) ? $value : $default;
    }
}
