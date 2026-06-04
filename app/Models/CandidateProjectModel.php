<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidateProjectModel extends Model
{
    protected $table = 'candidate_projects';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'user_id',
        'project_name',
        'role_name',
        'tech_stack',
        'project_url',
        'project_summary',
        'impact_metrics',
        'start_date',
        'end_date',
    ];

    public function getByUser(int $userId): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('updated_at', 'DESC')
            ->findAll();
    }
}
