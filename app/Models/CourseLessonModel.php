<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseLessonModel extends Model
{
    protected $table = 'course_lessons';
    protected $primaryKey = 'id';
    protected $allowedFields = ['module_id', 'lesson_number', 'title', 'content', 'resources', 'exercises', 'is_completed'];

    public function getLessonsByModule($moduleId)
    {
        return $this->where('module_id', $moduleId)->orderBy('lesson_number', 'ASC')->findAll();
    }
}
