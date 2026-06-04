<?php

namespace App\Models;

use CodeIgniter\Model;

class RecruiterCandidateActionModel extends Model
{
    public const ACTION_PROFILE_VIEWED = 'profile_viewed';
    public const ACTION_CONTACT_VIEWED = 'contact_viewed';
    public const ACTION_RESUME_DOWNLOADED = 'resume_downloaded';

    protected $table = 'recruiter_candidate_actions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'candidate_id',
        'recruiter_id',
        'application_id',
        'job_id',
        'action_type',
        'created_at',
    ];

    protected $useTimestamps = false;

    public function logAction(
        int $candidateId,
        int $recruiterId,
        string $actionType,
        ?int $applicationId = null,
        ?int $jobId = null,
        ?int $cooldownHours = null
    ): bool {
        if ($cooldownHours !== null && $cooldownHours > 0) {
            $cutoff = date('Y-m-d H:i:s', strtotime('-' . $cooldownHours . ' hours'));
            $builder = $this->builder();
            $builder->where('candidate_id', $candidateId)
                ->where('recruiter_id', $recruiterId)
                ->where('action_type', $actionType)
                ->where('created_at >=', $cutoff);

            if ($applicationId) {
                $builder->where('application_id', $applicationId);
            } else {
                $builder->where('application_id IS NULL', null, false);
            }

            if ($jobId) {
                $builder->where('job_id', $jobId);
            } else {
                $builder->where('job_id IS NULL', null, false);
            }

            $exists = $builder->countAllResults() > 0;
            if ($exists) {
                return false;
            }
        }

        $this->insert([
            'candidate_id' => $candidateId,
            'recruiter_id' => $recruiterId,
            'application_id' => $applicationId ?: null,
            'job_id' => $jobId ?: null,
            'action_type' => $actionType,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function getSummaryByApplicationIds(?int $candidateId, array $applicationIds, ?int $recruiterId = null): array
    {
        $applicationIds = array_values(array_unique(array_map('intval', $applicationIds)));
        if (empty($applicationIds)) {
            return [];
        }

        $builder = $this->select("
                application_id,
                COUNT(DISTINCT CASE WHEN action_type = '" . self::ACTION_PROFILE_VIEWED . "' THEN recruiter_id END) as profile_unique_recruiters,
                COUNT(DISTINCT CASE WHEN action_type = '" . self::ACTION_CONTACT_VIEWED . "' THEN recruiter_id END) as contact_unique_recruiters,
                COUNT(DISTINCT CASE WHEN action_type = '" . self::ACTION_RESUME_DOWNLOADED . "' THEN recruiter_id END) as resume_unique_recruiters,
                SUM(CASE WHEN action_type = '" . self::ACTION_PROFILE_VIEWED . "' THEN 1 ELSE 0 END) as profile_viewed_count,
                SUM(CASE WHEN action_type = '" . self::ACTION_CONTACT_VIEWED . "' THEN 1 ELSE 0 END) as contact_viewed_count,
                SUM(CASE WHEN action_type = '" . self::ACTION_RESUME_DOWNLOADED . "' THEN 1 ELSE 0 END) as resume_downloaded_count,
                MAX(created_at) as last_recruiter_activity_at
            ");

        if ($candidateId !== null) {
            $builder->where('candidate_id', $candidateId);
        }
        if ($recruiterId !== null) {
            $builder->where('recruiter_id', $recruiterId);
        }

        $rows = $builder->whereIn('application_id', $applicationIds)
            ->groupBy('application_id')
            ->findAll();

        $summary = [];
        foreach ($rows as $row) {
            $appId = (int) ($row['application_id'] ?? 0);
            $summary[$appId] = [
                'profile_unique_recruiters' => (int) ($row['profile_unique_recruiters'] ?? 0),
                'contact_unique_recruiters' => (int) ($row['contact_unique_recruiters'] ?? 0),
                'resume_unique_recruiters' => (int) ($row['resume_unique_recruiters'] ?? 0),
                'profile_viewed_count' => (int) ($row['profile_viewed_count'] ?? 0),
                'contact_viewed_count' => (int) ($row['contact_viewed_count'] ?? 0),
                'resume_downloaded_count' => (int) ($row['resume_downloaded_count'] ?? 0),
                'last_recruiter_activity_at' => $row['last_recruiter_activity_at'] ?? null,
            ];
        }

        return $summary;
    }

    public function getSummaryByApplicationJobMap(?int $candidateId, array $applicationJobMap, ?int $recruiterId = null): array
    {
        $applicationJobMap = array_filter($applicationJobMap, static fn ($jobId): bool => (int) $jobId > 0);
        if (empty($applicationJobMap)) {
            return [];
        }

        $jobIds = array_values(array_unique(array_map('intval', array_values($applicationJobMap))));
        $builder = $this->select("
                job_id,
                COUNT(DISTINCT CASE WHEN action_type = '" . self::ACTION_PROFILE_VIEWED . "' THEN recruiter_id END) as profile_unique_recruiters,
                COUNT(DISTINCT CASE WHEN action_type = '" . self::ACTION_CONTACT_VIEWED . "' THEN recruiter_id END) as contact_unique_recruiters,
                COUNT(DISTINCT CASE WHEN action_type = '" . self::ACTION_RESUME_DOWNLOADED . "' THEN recruiter_id END) as resume_unique_recruiters,
                SUM(CASE WHEN action_type = '" . self::ACTION_PROFILE_VIEWED . "' THEN 1 ELSE 0 END) as profile_viewed_count,
                SUM(CASE WHEN action_type = '" . self::ACTION_CONTACT_VIEWED . "' THEN 1 ELSE 0 END) as contact_viewed_count,
                SUM(CASE WHEN action_type = '" . self::ACTION_RESUME_DOWNLOADED . "' THEN 1 ELSE 0 END) as resume_downloaded_count,
                MAX(created_at) as last_recruiter_activity_at
            ");

        if ($candidateId !== null) {
            $builder->where('candidate_id', $candidateId);
        }
        if ($recruiterId !== null) {
            $builder->where('recruiter_id', $recruiterId);
        }

        $rows = $builder->where('application_id IS NULL', null, false)
            ->whereIn('job_id', $jobIds)
            ->groupBy('job_id')
            ->findAll();

        $jobToApplication = [];
        foreach ($applicationJobMap as $applicationId => $jobId) {
            $jobId = (int) $jobId;
            if ($jobId > 0 && !isset($jobToApplication[$jobId])) {
                $jobToApplication[$jobId] = (int) $applicationId;
            }
        }

        $summary = [];
        foreach ($rows as $row) {
            $jobId = (int) ($row['job_id'] ?? 0);
            $appId = $jobToApplication[$jobId] ?? 0;
            if ($appId <= 0) {
                continue;
            }

            $summary[$appId] = [
                'profile_unique_recruiters' => (int) ($row['profile_unique_recruiters'] ?? 0),
                'contact_unique_recruiters' => (int) ($row['contact_unique_recruiters'] ?? 0),
                'resume_unique_recruiters' => (int) ($row['resume_unique_recruiters'] ?? 0),
                'profile_viewed_count' => (int) ($row['profile_viewed_count'] ?? 0),
                'contact_viewed_count' => (int) ($row['contact_viewed_count'] ?? 0),
                'resume_downloaded_count' => (int) ($row['resume_downloaded_count'] ?? 0),
                'last_recruiter_activity_at' => $row['last_recruiter_activity_at'] ?? null,
            ];
        }

        return $summary;
    }
}
