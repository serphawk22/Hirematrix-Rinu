<?php

namespace App\Libraries;

use App\Models\JobModel;

class ExternalJobIngestionService
{
    private bool $schemaChecked = false;

    public function getOrCreateSystemRecruiterId(): int
    {
        $this->ensureExternalSchemaReady();

        $db = \Config\Database::connect();
        $users = $db->table('users');
        $row = $users
            ->where('email', JobModel::EXTERNAL_SYSTEM_RECRUITER_EMAIL)
            ->where('role', 'recruiter')
            ->get()
            ->getRowArray();

        if (!empty($row['id'])) {
            return (int) $row['id'];
        }

        $now = date('Y-m-d H:i:s');
        try {
            $randomSecret = bin2hex(random_bytes(16));
        } catch (\Throwable $e) {
            $randomSecret = uniqid('external_jobs_', true);
        }

        $users->insert([
            'name' => JobModel::EXTERNAL_SYSTEM_RECRUITER_NAME,
            'email' => JobModel::EXTERNAL_SYSTEM_RECRUITER_EMAIL,
            'phone' => '0000000000',
            'password' => password_hash($randomSecret, PASSWORD_DEFAULT),
            'role' => 'recruiter',
            'email_verified_at' => $now,
            'phone_verified_at' => $now,
            'created_at' => $now,
        ]);

        return (int) $db->insertID();
    }

    /**
     * @param array{
     *   title:string,
     *   company:string,
     *   location:string,
     *   description:string,
     *   category?:string,
     *   required_skills?:string,
     *   experience_level?:string,
     *   employment_type?:string,
     *   salary_range?:string|null,
     *   application_deadline?:string|null,
     *   openings?:int,
     *   source:string,
     *   apply_url:string
     * } $payload
     */
    public function ingestOne(array $payload): int
    {
        $result = $this->ingestOneWithResult($payload);
        return (int) $result['id'];
    }

    /**
     * @param array{
     *   title:string,
     *   company:string,
     *   location:string,
     *   description:string,
     *   category?:string,
     *   required_skills?:string,
     *   experience_level?:string,
     *   employment_type?:string,
     *   salary_range?:string|null,
     *   application_deadline?:string|null,
     *   openings?:int,
     *   source:string,
     *   apply_url:string
     * } $payload
     * @return array{id:int, inserted:bool}
     */
    public function ingestOneWithResult(array $payload): array
    {
        $this->ensureExternalSchemaReady();

        $title = trim((string) ($payload['title'] ?? ''));
        $company = trim((string) ($payload['company'] ?? ''));
        $location = trim((string) ($payload['location'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));
        $source = trim((string) ($payload['source'] ?? ''));
        $applyUrl = trim((string) ($payload['apply_url'] ?? ''));

        if ($title === '' || $company === '' || $location === '' || $description === '' || $source === '' || $applyUrl === '') {
            throw new \InvalidArgumentException('title, company, location, description, source, and apply_url are required for external jobs.');
        }

        if (!filter_var($applyUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('apply_url must be a valid URL.');
        }

        $jobModel = new JobModel();
        $existing = $jobModel
            ->where('is_external', 1)
            ->where('external_apply_url', $applyUrl)
            ->first();
        if (!empty($existing['id'])) {
            return [
                'id' => (int) $existing['id'],
                'inserted' => false,
            ];
        }

        $recruiterId = $this->getOrCreateSystemRecruiterId();
        $insertData = [
            'title' => $title,
            'category' => trim((string) ($payload['category'] ?? 'External')),
            'recruiter_id' => $recruiterId,
            'company_id' => null,
            'is_external' => 1,
            'external_source' => $source,
            'external_apply_url' => $applyUrl,
            'company' => $company,
            'location' => $location,
            'description' => $description,
            'required_skills' => trim((string) ($payload['required_skills'] ?? '')),
            'experience_level' => trim((string) ($payload['experience_level'] ?? 'Not specified')),
            'min_ai_cutoff_score' => 0,
            'ai_interview_policy' => JobModel::AI_POLICY_OFF,
            'openings' => max(1, (int) ($payload['openings'] ?? 1)),
            'status' => 'open',
            'employment_type' => trim((string) ($payload['employment_type'] ?? 'Full Time')),
            'salary_range' => $payload['salary_range'] ?? null,
            'application_deadline' => $payload['application_deadline'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        $jobModel->insert($insertData);

        return [
            'id' => (int) $jobModel->getInsertID(),
            'inserted' => true,
        ];
    }

    private function ensureExternalSchemaReady(): void
    {
        if ($this->schemaChecked) {
            return;
        }

        $db = \Config\Database::connect();
        $requiredFields = ['is_external', 'external_source', 'external_apply_url'];
        foreach ($requiredFields as $field) {
            if (!$db->fieldExists($field, 'jobs')) {
                throw new \RuntimeException(
                    'Jobs external fields are missing. Run: php spark migrate'
                );
            }
        }

        $this->schemaChecked = true;
    }
}
