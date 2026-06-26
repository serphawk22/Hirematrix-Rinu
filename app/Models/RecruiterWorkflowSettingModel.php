<?php

namespace App\Models;

use CodeIgniter\Model;

class RecruiterWorkflowSettingModel extends Model
{
    protected $table = 'recruiter_workflow_settings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'recruiter_id',
        'send_rejection_email',
        'rejection_email_subject',
        'rejection_email_body',
        'rejection_email_use_mailbox',
        'rejection_email_allow_system_fallback',
        'rejection_email_cc_self',
        'created_at',
        'updated_at',
    ];

    public function defaults(): array
    {
        return [
            'send_rejection_email' => 0,
            'rejection_email_subject' => 'Update on your application for {job_title}',
            'rejection_email_body' => "Hi {candidate_name},\n\nThank you for your interest in the {job_title} role at {company_name}. After careful review, we will not be moving forward with your application for this position.\n\nWe appreciate the time you invested and encourage you to apply for future roles that match your experience.\n\nBest regards,\n{recruiter_name}",
            'rejection_email_use_mailbox' => 1,
            'rejection_email_allow_system_fallback' => 1,
            'rejection_email_cc_self' => 0,
        ];
    }

    public function getForRecruiter(int $recruiterId): array
    {
        $settings = $this->where('recruiter_id', $recruiterId)->first();
        return array_merge($this->defaults(), $settings ?: []);
    }

    public function saveForRecruiter(int $recruiterId, array $data): bool
    {
        $existing = $this->where('recruiter_id', $recruiterId)->first();
        $payload = array_merge($data, ['recruiter_id' => $recruiterId, 'updated_at' => date('Y-m-d H:i:s')]);

        if ($existing) {
            return $this->update((int) $existing['id'], $payload);
        }

        $payload['created_at'] = date('Y-m-d H:i:s');
        return (bool) $this->insert($payload);
    }
}
