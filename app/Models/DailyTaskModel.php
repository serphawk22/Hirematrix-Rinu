<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyTaskModel extends Model
{
    protected $table = 'daily_tasks';
    protected $primaryKey = 'id';
    protected $allowedFields = ['transition_id', 'task_title', 'task_description', 'duration_minutes', 'day_number', 'module_number', 'lesson_number', 'is_completed', 'completed_at'];
    protected $useTimestamps = false;

    public function getTasksByTransition($transitionId)
    {
        return $this->where('transition_id', $transitionId)->orderBy('day_number', 'ASC')->findAll();
    }

    public function markComplete($taskId)
    {
        return $this->update($taskId, ['is_completed' => 1, 'completed_at' => date('Y-m-d H:i:s')]);
    }
}
