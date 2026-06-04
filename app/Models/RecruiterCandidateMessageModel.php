<?php

namespace App\Models;

use CodeIgniter\Model;

class RecruiterCandidateMessageModel extends Model
{
    protected $table = 'recruiter_candidate_messages';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'candidate_id',
        'recruiter_id',
        'application_id',
        'job_id',
        'sender_id',
        'sender_role',
        'message',
        'created_at',
    ];

    protected $useTimestamps = false;

    public function getThread(int $candidateId, int $recruiterId, ?int $applicationId = null): array
    {
        $builder = $this->where('candidate_id', $candidateId)
            ->where('recruiter_id', $recruiterId);

        if ($applicationId) {
            $builder->where('application_id', $applicationId);
        }

        return $builder->orderBy('created_at', 'ASC')->findAll();
    }
}

