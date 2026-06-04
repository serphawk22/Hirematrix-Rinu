<?php

namespace App\Models;

use CodeIgniter\Model;

class JobAlertModel extends Model
{
    protected $table = 'job_alerts';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'candidate_id',
        'role_keywords',
        'location_keywords',
        'employment_type',
        'skills_keywords',
        'salary_min',
        'salary_max',
        'notify_email',
        'notify_in_app',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = false;

    public function syncFromCandidateProfile(int $candidateId): void
    {
        $userModel = new \App\Models\UserModel();
        $skillsModel = new \App\Models\CandidateSkillsModel();

        $profile = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId) ?? [];
        $skillsRow = $skillsModel->where('candidate_id', $candidateId)->first();

        $roleKeywords = trim((string) ($profile['preferred_job_titles'] ?? ''));
        if ($roleKeywords === '') {
            $roleKeywords = trim((string) ($profile['resume_headline'] ?? ''));
        }

        $salaryMin = null;
        if (!empty($profile['expected_salary'])) {
            $salaryMin = (int) round(((float) $profile['expected_salary']) * 100000);
        }

        $payload = [
            'candidate_id' => $candidateId,
            'role_keywords' => $roleKeywords !== '' ? $roleKeywords : null,
            'location_keywords' => ($location = trim((string) ($profile['preferred_locations'] ?? ''))) !== '' ? $location : null,
            'employment_type' => ($employmentType = trim((string) ($profile['preferred_employment_type'] ?? ''))) !== '' ? $employmentType : null,
            'skills_keywords' => ($skills = trim((string) ($skillsRow['skill_name'] ?? ''))) !== '' ? $skills : null,
            'salary_min' => $salaryMin,
            'salary_max' => null,
            'notify_email' => (int) ($profile['job_alert_notify_email'] ?? 1) === 1 ? 1 : 0,
            'notify_in_app' => (int) ($profile['job_alert_notify_in_app'] ?? 1) === 1 ? 1 : 0,
            'is_active' => (int) ($profile['job_alerts_enabled'] ?? 1) === 1 ? 1 : 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $existing = $this->where('candidate_id', $candidateId)->orderBy('id', 'ASC')->findAll();
        if (empty($existing)) {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $this->insert($payload);
            return;
        }

        $firstId = (int) ($existing[0]['id'] ?? 0);
        if ($firstId > 0) {
            $this->update($firstId, $payload);
        }

        if (count($existing) > 1) {
            $extraIds = array_map('intval', array_column(array_slice($existing, 1), 'id'));
            if (!empty($extraIds)) {
                $this->whereIn('id', $extraIds)->delete();
            }
        }
    }
}
