<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidateResumeVersionModel extends Model
{
    protected $table = 'candidate_resume_versions';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'candidate_id',
        'job_id',
        'application_id',
        'career_transition_id',
        'title',
        'target_role',
        'source_role',
        'generation_source',
        'base_resume_path',
        'summary',
        'highlight_skills',
        'content',
        'is_primary',
        'last_synced_at',
    ];

    public function getForCandidate(int $candidateId): array
    {
        return $this->select('candidate_resume_versions.*, jobs.title as job_title')
            ->join('jobs', 'jobs.id = candidate_resume_versions.job_id', 'left')
            ->where('candidate_resume_versions.candidate_id', $candidateId)
            ->orderBy('candidate_resume_versions.is_primary', 'DESC')
            ->orderBy('candidate_resume_versions.updated_at', 'DESC')
            ->findAll();
    }

    public function setPrimaryVersion(int $candidateId, int $versionId): void
    {
        $this->where('candidate_id', $candidateId)->set(['is_primary' => 0])->update();
        $this->update($versionId, ['is_primary' => 1]);
    }

    public function getPreferredVersionForJob(int $candidateId, int $jobId): ?array
    {
        $jobVersion = $this->where('candidate_id', $candidateId)
            ->where('job_id', $jobId)
            ->orderBy('updated_at', 'DESC')
            ->first();

        if ($jobVersion) {
            return $jobVersion;
        }

        return $this->where('candidate_id', $candidateId)
            ->orderBy('is_primary', 'DESC')
            ->orderBy('updated_at', 'DESC')
            ->first();
    }

    public function findRoleBasedVersion(int $candidateId, string $targetRole): ?array
    {
        return $this->where('candidate_id', $candidateId)
            ->where('generation_source', 'role_based')
            ->where('job_id', null)
            ->where('LOWER(target_role) =', strtolower(trim($targetRole)))
            ->first();
    }

    public function findJobVersion(int $candidateId, int $jobId): ?array
    {
        return $this->where('candidate_id', $candidateId)
            ->where('job_id', $jobId)
            ->orderBy('updated_at', 'DESC')
            ->first();
    }
}
