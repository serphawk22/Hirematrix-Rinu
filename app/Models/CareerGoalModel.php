<?php

namespace App\Models;

use CodeIgniter\Model;

class CareerGoalModel extends Model
{
    protected $table = 'career_goals';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'aspiration', 'specific_goal', 'measurable_criteria',
        'achievable_steps', 'realistic_assessment', 'time_bound',
        'current_skills', 'target_skills', 'progress_percentage', 'status'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getUserGoals($userId)
    {
        return $this->where('user_id', $userId)
                   ->where('status', 'active')
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    public function updateProgress($goalId, $percentage)
    {
        return $this->update($goalId, ['progress_percentage' => $percentage]);
    }
}