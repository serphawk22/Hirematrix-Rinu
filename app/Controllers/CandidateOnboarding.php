<?php

namespace App\Controllers;

use App\Libraries\CandidateOnboardingService;
use App\Models\CandidateSkillsModel;
use App\Models\EducationModel;
use App\Models\UserModel;
use App\Models\WorkExperienceModel;

class CandidateOnboarding extends BaseController
{
    private CandidateOnboardingService $onboarding;

    public function __construct()
    {
        $this->onboarding = new CandidateOnboardingService();
    }

    public function index()
    {
        return redirect()->to(base_url('candidate/onboarding/' . $this->onboarding->getNextStep((int) session()->get('user_id'))));
    }

    public function step(?string $step = null)
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $candidateId = (int) session()->get('user_id');
        if ($this->onboarding->isComplete($candidateId)) {
            return redirect()->to(base_url('candidate/dashboard'));
        }

        $step = $this->normalizeStep($step);
        $accessibleSteps = $this->onboarding->getAccessibleSteps($candidateId);
        if (!in_array($step, $accessibleSteps, true)) {
            return redirect()->to(base_url('candidate/onboarding/' . $this->onboarding->getNextStep($candidateId)));
        }

        $userModel = new UserModel();
        $user = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId) ?? [];
        $user['current_salary'] = $this->formatSalaryForDisplay($user['current_salary'] ?? null);
        $user['expected_salary'] = $this->formatSalaryForDisplay($user['expected_salary'] ?? null);
        $skillsRow = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->first();
        $educationRows = (new EducationModel())->where('user_id', $candidateId)->orderBy('id', 'ASC')->findAll();
        $experienceRows = (new WorkExperienceModel())->where('user_id', $candidateId)->orderBy('id', 'ASC')->findAll();

        return view('candidate/onboarding', [
            'activeStep' => $step,
            'steps' => CandidateOnboardingService::STEPS,
            'accessibleSteps' => $accessibleSteps,
            'completionMap' => $this->onboarding->getCompletionMap($candidateId),
            'progressPercent' => $this->onboarding->getProgressPercent($candidateId),
            'user' => $user,
            'skillsValue' => (string) ($skillsRow['skill_name'] ?? ''),
            'educationRows' => $educationRows,
            'experienceRows' => $experienceRows,
        ]);
    }

    public function save(string $step)
    {
        if (session()->get('role') !== 'candidate') {
            return redirect()->to(base_url('login'))->with('error', 'Unauthorized');
        }

        $candidateId = (int) session()->get('user_id');
        $step = $this->normalizeStep($step);
        if (!in_array($step, $this->onboarding->getAccessibleSteps($candidateId), true)) {
            return redirect()->to(base_url('candidate/onboarding/' . $this->onboarding->getNextStep($candidateId)))
                ->with('error', 'Complete the current onboarding step before moving ahead.');
        }

        switch ($step) {
            case 'personal':
                return $this->savePersonal($candidateId);
            case 'resume':
                return $this->saveResume($candidateId);
            case 'skills':
                return $this->saveSkills($candidateId);
            case 'education':
                return $this->saveEducation($candidateId);
            case 'experience':
                return $this->saveExperience($candidateId);
            case 'preferences':
                return $this->savePreferences($candidateId);
            case 'review':
                return $this->completeOnboarding($candidateId);
            default:
                return redirect()->to(base_url('candidate/onboarding'));
        }
    }

    private function savePersonal(int $candidateId)
    {
        if (!$this->validate([
            'name' => 'required|min_length[3]',
            'email' => 'required|valid_email',
            'phone' => 'required|min_length[10]',
            'location' => 'required|min_length[2]',
            'bio' => 'required|min_length[20]',
            'gender' => 'required',
            'date_of_birth' => 'required',
        ])) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        $email = strtolower(trim((string) $this->request->getPost('email')));
        $userModel = new UserModel();
        $existing = $userModel->where('email', $email)->where('id !=', $candidateId)->first();
        if ($existing) {
            return redirect()->back()->withInput()->with('error', 'Email is already in use by another account.');
        }

        $userModel->update($candidateId, [
            'name' => trim((string) $this->request->getPost('name')),
            'email' => $email,
            'phone' => trim((string) $this->request->getPost('phone')),
        ]);

        $userModel->upsertCandidateProfile($candidateId, [
            'location' => trim((string) $this->request->getPost('location')),
            'bio' => trim((string) $this->request->getPost('bio')),
            'gender' => trim((string) $this->request->getPost('gender')),
            'date_of_birth' => trim((string) $this->request->getPost('date_of_birth')),
        ]);

        session()->set('user_name', trim((string) $this->request->getPost('name')));

        return $this->redirectToNextStep($candidateId, 'personal', 'Personal details saved.');
    }

    private function saveResume(int $candidateId)
    {
        $user = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
        $file = $this->request->getFile('resume');

        if ((!$file || !$file->isValid()) && !empty($user['resume_path'])) {
            return $this->redirectToNextStep($candidateId, 'resume', 'Resume already available.');
        }

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Resume upload is required to continue.');
        }

        $allowedTypes = ['pdf', 'docx', 'doc'];
        if (!in_array(strtolower((string) $file->getExtension()), $allowedTypes, true)) {
            return redirect()->back()->with('error', 'Only PDF, DOCX, or DOC files are allowed.');
        }

        $uploadPath = WRITEPATH . 'uploads/resumes/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        if (!$file->move($uploadPath)) {
            return redirect()->back()->with('error', 'Failed to upload resume.');
        }

        
        // Copy file to FCPATH for direct public access on mobile
        $fcPath = FCPATH . 'uploads/resumes/';
        if (!is_dir($fcPath)) {
            mkdir($fcPath, 0755, true);
        }
        @copy($uploadPath . $file->getName(), $fcPath . $file->getName());

        (new UserModel())->upsertCandidateProfile($candidateId, [
            'resume_path' => 'uploads/resumes/' . $file->getName(),
        ]);

        return $this->redirectToNextStep($candidateId, 'resume', 'Resume uploaded successfully.');
    }

    private function saveSkills(int $candidateId)
    {
        $skillsValue = trim((string) $this->request->getPost('skills'));
        if ($skillsValue === '') {
            return redirect()->back()->withInput()->with('error', 'Add at least one skill to continue.');
        }

        $skills = array_values(array_filter(array_map('trim', explode(',', $skillsValue))));
        if (empty($skills)) {
            return redirect()->back()->withInput()->with('error', 'Add valid comma-separated skills to continue.');
        }

        $model = new CandidateSkillsModel();
        $existing = $model->where('candidate_id', $candidateId)->first();
        $payload = [
            'candidate_id' => $candidateId,
            'skill_name' => implode(', ', $skills),
        ];

        if ($existing) {
            $model->update((int) $existing['id'], $payload);
        } else {
            $model->insert($payload);
        }

        return $this->redirectToNextStep($candidateId, 'skills', 'Skills saved successfully.');
    }

    private function saveEducation(int $candidateId)
    {
        $degrees = $this->request->getPost('degree');
        $fields = $this->request->getPost('field_of_study');
        $institutions = $this->request->getPost('institution');
        $startYears = $this->request->getPost('start_year');
        $endYears = $this->request->getPost('end_year');
        $grades = $this->request->getPost('grade');

        $rows = [];
        foreach ((array) $degrees as $index => $degree) {
            $row = [
                'degree' => trim((string) $degree),
                'field_of_study' => trim((string) ($fields[$index] ?? '')),
                'institution' => trim((string) ($institutions[$index] ?? '')),
                'start_year' => trim((string) ($startYears[$index] ?? '')),
                'end_year' => trim((string) ($endYears[$index] ?? '')),
                'grade' => trim((string) ($grades[$index] ?? '')),
            ];

            if ($row['degree'] === '' && $row['field_of_study'] === '' && $row['institution'] === '' && $row['start_year'] === '' && $row['end_year'] === '') {
                continue;
            }

            if ($row['degree'] === '' || mb_strlen($row['degree']) < 2 || $row['field_of_study'] === '' || mb_strlen($row['field_of_study']) < 2 || $row['institution'] === '' || mb_strlen($row['institution']) < 2 || $row['start_year'] === '' || !is_numeric($row['start_year']) || $row['end_year'] === '' || !is_numeric($row['end_year'])) {
                return redirect()->back()->withInput()->with('error', 'Each education entry must include valid degree, field of study, institution, start year, and end year.');
            }

            $rows[] = [
                'user_id' => $candidateId,
                'degree' => $row['degree'],
                'field_of_study' => $row['field_of_study'],
                'institution' => $row['institution'],
                'start_year' => (int) $row['start_year'],
                'end_year' => (int) $row['end_year'],
                'grade' => $row['grade'],
            ];
        }

        if (empty($rows)) {
            return redirect()->back()->withInput()->with('error', 'Add at least one education entry to continue.');
        }

        $model = new EducationModel();
        $model->where('user_id', $candidateId)->delete();
        foreach ($rows as $row) {
            $model->insert($row);
        }

        return $this->redirectToNextStep($candidateId, 'education', 'Education saved successfully.');
    }

    private function saveExperience(int $candidateId)
    {
        $isFresher = $this->request->getPost('is_fresher_candidate') === '1';
        $userModel = new UserModel();

        if ($isFresher) {
            $userModel->upsertCandidateProfile($candidateId, [
                'is_fresher_candidate' => 1,
            ]);

            return $this->redirectToNextStep($candidateId, 'experience', 'Fresher status saved.');
        }

        $userModel->upsertCandidateProfile($candidateId, [
            'is_fresher_candidate' => 0,
        ]);

        $jobTitles = $this->request->getPost('job_title');
        $companyNames = $this->request->getPost('company_name');
        $employmentTypes = $this->request->getPost('employment_type');
        $locations = $this->request->getPost('location');
        $startDates = $this->request->getPost('start_date');
        $endDates = $this->request->getPost('end_date');
        $currentFlags = (array) ($this->request->getPost('is_current') ?? []);
        $descriptions = $this->request->getPost('description');

        $rows = [];
        foreach ((array) $jobTitles as $index => $jobTitle) {
            $row = [
                'job_title' => trim((string) $jobTitle),
                'company_name' => trim((string) ($companyNames[$index] ?? '')),
                'employment_type' => trim((string) ($employmentTypes[$index] ?? 'Full-time')),
                'location' => trim((string) ($locations[$index] ?? '')),
                'start_date' => trim((string) ($startDates[$index] ?? '')),
                'end_date' => trim((string) ($endDates[$index] ?? '')),
                'is_current' => isset($currentFlags[$index]) ? 1 : 0,
                'description' => trim((string) ($descriptions[$index] ?? '')),
            ];

            if ($row['job_title'] === '' && $row['company_name'] === '' && $row['start_date'] === '') {
                continue;
            }

            if ($row['job_title'] === '' || mb_strlen($row['job_title']) < 2 || $row['company_name'] === '' || mb_strlen($row['company_name']) < 2 || $row['start_date'] === '') {
                return redirect()->back()->withInput()->with('error', 'Each experience entry must include valid job title, company name, and start date.');
            }

            $rows[] = [
                'user_id' => $candidateId,
                'job_title' => $row['job_title'],
                'company_name' => $row['company_name'],
                'employment_type' => $row['employment_type'] !== '' ? $row['employment_type'] : 'Full-time',
                'location' => $row['location'],
                'start_date' => $row['start_date'],
                'end_date' => $row['is_current'] ? null : ($row['end_date'] !== '' ? $row['end_date'] : null),
                'is_current' => $row['is_current'],
                'description' => $row['description'],
            ];
        }

        if (empty($rows)) {
            return redirect()->back()->withInput()->with('error', 'Add at least one experience entry or mark yourself as a fresher.');
        }

        $model = new WorkExperienceModel();
        $model->where('user_id', $candidateId)->delete();
        foreach ($rows as $row) {
            $model->insert($row);
        }

        return $this->redirectToNextStep($candidateId, 'experience', 'Experience saved successfully.');
    }

    private function savePreferences(int $candidateId)
    {
        if (!$this->validate([
            'preferred_job_titles' => 'required|min_length[2]',
            'preferred_locations' => 'required|min_length[2]',
            'preferred_employment_type' => 'required|min_length[2]',
            'notice_period' => 'required|min_length[2]',
        ])) {
            return redirect()->back()->withInput()->with('validation', $this->validator);
        }

        (new UserModel())->upsertCandidateProfile($candidateId, [
            'resume_headline' => trim((string) $this->request->getPost('resume_headline')),
            'preferred_job_titles' => trim((string) $this->request->getPost('preferred_job_titles')),
            'preferred_locations' => trim((string) $this->request->getPost('preferred_locations')),
            'preferred_employment_type' => trim((string) $this->request->getPost('preferred_employment_type')),
            'notice_period' => trim((string) $this->request->getPost('notice_period')),
            'expected_salary' => $this->normalizeSalaryToLpa($this->request->getPost('expected_salary')),
        ]);
        model('JobAlertModel')->syncFromCandidateProfile($candidateId);

        return $this->redirectToNextStep($candidateId, 'preferences', 'Career preferences saved successfully.');
    }

    private function completeOnboarding(int $candidateId)
    {
        if ($this->onboarding->getNextStep($candidateId) !== 'review') {
            return redirect()->to(base_url('candidate/onboarding/' . $this->onboarding->getNextStep($candidateId)))
                ->with('error', 'Complete all required onboarding steps before finishing.');
        }

        $this->onboarding->updateStepState($candidateId, 'review', true);

        return redirect()->to(base_url('candidate/dashboard'))->with('success', 'Profile onboarding completed successfully.');
    }

    private function redirectToNextStep(int $candidateId, string $currentStep, string $message)
    {
        // Use the service to determine the actual next incomplete step
        $nextStep = $this->onboarding->getNextStep($candidateId);

        // Update the progress tracker in the database
        $this->onboarding->updateStepState($candidateId, $nextStep, false);

        return redirect()->to(base_url('candidate/onboarding/' . $nextStep))->with('success', $message);
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

    private function formatSalaryForDisplay($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $salary = (float) $value;
        if ($salary > 1000) {
            $salary = $salary / 100000;
        }

        return number_format($salary, 2, '.', '');
    }

    private function normalizeStep(?string $step): string
    {
        $step = trim((string) $step);
        if (!in_array($step, CandidateOnboardingService::STEPS, true)) {
            return CandidateOnboardingService::STEPS[0];
        }

        return $step;
    }
}
