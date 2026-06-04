<?php

namespace App\Models;

use CodeIgniter\Model;

class WorkExperienceModel extends Model
{
    protected $table = 'work_experiences';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'job_title', 'company_name', 'employment_type', 'location', 'start_date', 'end_date', 'is_current', 'description'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getByUser($userId)
    {
        return $this->where('user_id', $userId)->orderBy('start_date', 'DESC')->findAll();
    }

    public function getCurrentRole($userId)
    {
        return $this->where('user_id', $userId)->where('is_current', 1)->first();
    }
}
