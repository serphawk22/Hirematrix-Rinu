<?php

namespace App\Libraries;

class RecruiterCandidateAccessService
{
    /**
     * Recruiters may access public candidates or private candidates who have
     * applied to one of their jobs.
     */
    public function canAccess(int $candidateId, int $recruiterId): bool
    {
        if ($candidateId <= 0 || $recruiterId <= 0) {
            return false;
        }

        $db = \Config\Database::connect();
        $candidate = $db->table('users')
            ->select('users.role, candidate_profiles.allow_public_recruiter_visibility')
            ->join('candidate_profiles', 'candidate_profiles.user_id = users.id', 'left')
            ->where('users.id', $candidateId)
            ->get()
            ->getRowArray();

        if (!$candidate || ($candidate['role'] ?? '') !== 'candidate') {
            return false;
        }

        if ((int) ($candidate['allow_public_recruiter_visibility'] ?? 0) === 1) {
            return true;
        }

        return $db->table('applications')
            ->join('jobs', 'jobs.id = applications.job_id')
            ->where('applications.candidate_id', $candidateId)
            ->where('jobs.recruiter_id', $recruiterId)
            ->countAllResults() > 0;
    }

    /** Apply the same access policy to recruiter candidate-list queries. */
    public function applyVisibilityFilter($builder, int $recruiterId): void
    {
        $recruiterId = max(0, $recruiterId);

        $builder->groupStart()
            ->where('candidate_profiles.allow_public_recruiter_visibility', 1)
            ->orWhere(
                'users.id IN (SELECT applications.candidate_id FROM applications INNER JOIN jobs ON jobs.id = applications.job_id WHERE jobs.recruiter_id = ' . $recruiterId . ')',
                null,
                false
            )
            ->groupEnd();
    }
}
