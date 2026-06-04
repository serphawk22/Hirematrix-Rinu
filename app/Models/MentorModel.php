<?php

namespace App\Models;

use CodeIgniter\Model;

class MentorModel extends Model
{
    protected $table = 'mentors';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name', 'email', 'phone', 'expertise', 'specializations',
        'hourly_rate', 'rating', 'total_sessions', 'bio',
        'experience_years', 'availability', 'profile_image', 'status'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';

    public function getActiveMentors()
    {
        return $this->where('status', 'active')
                   ->orderBy('rating', 'DESC')
                   ->findAll();
    }

    public function searchByExpertise($keywords)
    {
        $builder = $this->builder();
        
        foreach ($keywords as $keyword) {
            $builder->orLike('expertise', $keyword)
                   ->orLike('specializations', $keyword);
        }
        
        return $builder->where('status', 'active')
                      ->orderBy('rating', 'DESC')
                      ->get()
                      ->getResultArray();
    }
}