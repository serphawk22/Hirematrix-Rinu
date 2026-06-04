<?php

namespace App\Models;

use CodeIgniter\Model;

class RecruiterJobInvitationModel extends Model
{
    public const STATUS_SENT = 'sent';
    public const STATUS_VIEWED = 'viewed';
    public const STATUS_APPLIED = 'applied';

    protected $table = 'recruiter_job_invitations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'recruiter_id',
        'candidate_id',
        'job_id',
        'message',
        'status',
        'invited_at',
        'viewed_at',
        'applied_at',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;

    public function findActiveInvitation(int $recruiterId, int $candidateId, int $jobId): ?array
    {
        return $this->where('recruiter_id', $recruiterId)
            ->where('candidate_id', $candidateId)
            ->where('job_id', $jobId)
            ->whereIn('status', [self::STATUS_SENT, self::STATUS_VIEWED])
            ->orderBy('id', 'DESC')
            ->first();
    }

    public function createInvitation(int $recruiterId, int $candidateId, int $jobId, string $message = ''): int
    {
        $now = date('Y-m-d H:i:s');

        $this->insert([
            'recruiter_id' => $recruiterId,
            'candidate_id' => $candidateId,
            'job_id' => $jobId,
            'message' => $message !== '' ? $message : null,
            'status' => self::STATUS_SENT,
            'invited_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return (int) $this->getInsertID();
    }

    public function getLatestForCandidateJob(int $candidateId, int $jobId): ?array
    {
        return $this->select('recruiter_job_invitations.*, jobs.title AS job_title, jobs.company AS company_name, users.name AS recruiter_name')
            ->join('jobs', 'jobs.id = recruiter_job_invitations.job_id', 'left')
            ->join('users', 'users.id = recruiter_job_invitations.recruiter_id', 'left')
            ->where('recruiter_job_invitations.candidate_id', $candidateId)
            ->where('recruiter_job_invitations.job_id', $jobId)
            ->orderBy('recruiter_job_invitations.id', 'DESC')
            ->first();
    }

    public function markViewed(int $invitationId): bool
    {
        $row = $this->find($invitationId);
        if (!$row || ($row['status'] ?? '') !== self::STATUS_SENT) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        return (bool) $this->update($invitationId, [
            'status' => self::STATUS_VIEWED,
            'viewed_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function markAppliedForCandidateJob(int $candidateId, int $jobId): bool
    {
        $invitation = $this->where('candidate_id', $candidateId)
            ->where('job_id', $jobId)
            ->whereIn('status', [self::STATUS_SENT, self::STATUS_VIEWED])
            ->orderBy('id', 'DESC')
            ->first();

        if (!$invitation) {
            return false;
        }

        $now = date('Y-m-d H:i:s');

        return (bool) $this->update((int) $invitation['id'], [
            'status' => self::STATUS_APPLIED,
            'applied_at' => $now,
            'updated_at' => $now,
            'viewed_at' => $invitation['viewed_at'] ?? $now,
        ]);
    }
}
            