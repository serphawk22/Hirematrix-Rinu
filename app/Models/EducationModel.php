<?php

namespace App\Models;

use CodeIgniter\Model;

class EducationModel extends Model
{
    protected $table = 'education';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'degree', 'field_of_study', 'institution', 'start_year', 'end_year', 'grade'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getByUser($userId)
    {
        return $this->where('user_id', $userId)->orderBy('end_year', 'DESC')->findAll();
    }
}
