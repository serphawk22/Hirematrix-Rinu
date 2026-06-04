<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\CandidateSkillsModel;
use App\Models\EducationModel;
use App\Models\WorkExperienceModel;
use CodeIgniter\RESTful\ResourceController;

class ApiOnboardingController extends ResourceController
{
    protected $format = 'json';

    public function saveStep($step)
    {
        if ($step === 'resume') {
            return $this->saveResume((int) $this->request->getPost('user_id'));
        }

        $json = $this->request->getJSON();
        if (!$json || !isset($json->user_id)) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $candidateId = (int) $json->user_id;

        switch ($step) {
            case 'personal':
                return $this->savePersonal($candidateId, $json);
            case 'skills':
                return $this->saveSkills($candidateId, $json);
            case 'education':
                return $this->saveEducation($candidateId, $json);
            case 'experience':
                return $this->saveExperience($candidateId, $json);
            case 'preferences':
                return $this->savePreferences($candidateId, $json);
            case 'review':
                return $this->completeOnboarding($candidateId, $json);
            default:
                return $this->fail('Invalid step');
        }
    }

    private function savePersonal(int $candidateId, $json)
    {
        $userModel = new UserModel();
        $userModel->update($candidateId, [
            'name' => trim((string) ($json->name ?? '')),
            'phone' => trim((string) ($json->phone ?? '')),
            'onboarding_step' => 'resume', // Move to next step logic can be handled by app
        ]);

        $userModel->upsertCandidateProfile($candidateId, [
            'location' => trim((string) ($json->location ?? '')),
            'bio' => trim((string) ($json->bio ?? '')),
            'gender' => trim((string) ($json->gender ?? '')),
            'date_of_birth' => trim((string) ($json->date_of_birth ?? '')),
        ]);

        return $this->respond(['status' => 'success']);
    }

    private function saveResume(int $candidateId)
    {
        if (!$candidateId) {
            return $this->failUnauthorized('Missing user_id in payload');
        }

        $file = $this->request->getFile('resume');
        
        if (!$file || !$file->isValid()) {
            // Check if they already have one uploaded
            $user = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
            if (!empty($user['resume_path'])) {
                (new UserModel())->update($candidateId, ['onboarding_step' => 'skills']);
                return $this->respond(['status' => 'success', 'message' => 'Existing resume kept.']);
            }
            return $this->fail('Resume upload is required.');
        }

        $allowedTypes = ['pdf', 'docx', 'doc'];
        if (!in_array(strtolower((string) $file->getExtension()), $allowedTypes, true)) {
            return $this->fail('Only PDF, DOCX, or DOC files are allowed.');
        }

        $uploadPath = WRITEPATH . 'uploads/resumes/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        if (!$file->move($uploadPath)) {
            return $this->fail('Failed to save the file on server.');
        }

        // Copy file to FCPATH for direct direct public access on mobile
        $fcPath = FCPATH . 'uploads/resumes/';
        if (!is_dir($fcPath)) {
            mkdir($fcPath, 0755, true);
        }
        @copy($uploadPath . $file->getName(), $fcPath . $file->getName());

        (new UserModel())->upsertCandidateProfile($candidateId, [
            'resume_path' => 'uploads/resumes/' . $file->getName(),
        ]);
        (new UserModel())->update($candidateId, ['onboarding_step' => 'skills']);

        return $this->respond(['status' => 'success', 'path' => 'uploads/resumes/' . $file->getName()]);
    }

    private function saveSkills(int $candidateId, $json)
    {
        $skillsValue = trim((string) ($json->skills ?? ''));
        $skills = array_values(array_filter(array_map('trim', explode(',', $skillsValue))));
        
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

        (new UserModel())->update($candidateId, ['onboarding_step' => 'education']);
        return $this->respond(['status' => 'success']);
    }

    private function saveEducation(int $candidateId, $json)
    {
        $rows = [];
        foreach ($json->educations ?? [] as $edu) {
            $rows[] = [
                'user_id' => $candidateId,
                'degree' => $edu->degree,
                'field_of_study' => $edu->field_of_study,
                'institution' => $edu->institution,
                'start_year' => (int) $edu->start_year,
                'end_year' => (int) $edu->end_year,
                'grade' => $edu->grade ?? '',
            ];
        }

        $model = new EducationModel();
        $model->where('user_id', $candidateId)->delete();
        foreach ($rows as $row) {
            $model->insert($row);
        }

        (new UserModel())->update($candidateId, ['onboarding_step' => 'experience']);
        return $this->respond(['status' => 'success']);
    }

    private function saveExperience(int $candidateId, $json)
    {
        $isFresher = ($json->is_fresher ?? false) === true;
        $userModel = new UserModel();

        if ($isFresher) {
            $userModel->upsertCandidateProfile($candidateId, ['is_fresher_candidate' => 1]);
        } else {
            $userModel->upsertCandidateProfile($candidateId, ['is_fresher_candidate' => 0]);
            
            $rows = [];
            foreach ($json->experiences ?? [] as $exp) {
                $rows[] = [
                    'user_id' => $candidateId,
                    'job_title' => $exp->job_title,
                    'company_name' => $exp->company_name,
                    'employment_type' => $exp->employment_type ?? 'Full-time',
                    'location' => $exp->location ?? '',
                    'start_date' => $exp->start_date,
                    'end_date' => ($exp->is_current ?? false) ? null : ($exp->end_date ?? null),
                    'is_current' => ($exp->is_current ?? false) ? 1 : 0,
                    'description' => $exp->description ?? '',
                ];
            }

            $model = new WorkExperienceModel();
            $model->where('user_id', $candidateId)->delete();
            foreach ($rows as $row) {
                $model->insert($row);
            }
        }

        (new UserModel())->update($candidateId, ['onboarding_step' => 'preferences']);
        return $this->respond(['status' => 'success']);
    }

    private function savePreferences(int $candidateId, $json)
    {
        (new UserModel())->upsertCandidateProfile($candidateId, [
            'resume_headline' => trim((string) ($json->resume_headline ?? '')),
            'preferred_job_titles' => trim((string) ($json->preferred_job_titles ?? '')),
            'preferred_locations' => trim((string) ($json->preferred_locations ?? '')),
            'preferred_employment_type' => trim((string) ($json->preferred_employment_type ?? '')),
            'notice_period' => trim((string) ($json->notice_period ?? '')),
            'expected_salary' => isset($json->expected_salary) ? (float)$json->expected_salary : null,
        ]);

        (new UserModel())->update($candidateId, ['onboarding_step' => 'review']);
        return $this->respond(['status' => 'success']);
    }

    private function completeOnboarding(int $candidateId, $json)
    {
        (new UserModel())->update($candidateId, [
            'onboarding_completed' => 1,
            'onboarding_step' => 'completed'
        ]);
        return $this->respond(['status' => 'success']);
    }
}
