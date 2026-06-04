<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseModuleModel extends Model
{
    protected $table = 'course_modules';
    protected $primaryKey = 'id';
    protected $allowedFields = ['transition_id', 'module_number', 'title', 'description', 'duration_weeks', 'content'];

    public function getModulesByTransition($transitionId)
    {
        return $this->where('transition_id', $transitionId)->orderBy('module_number', 'ASC')->findAll();
    }
}
