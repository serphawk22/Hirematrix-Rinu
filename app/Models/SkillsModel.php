<?php

namespace App\Models;

use CodeIgniter\Model;

class SkillsModel extends Model
{
    protected $table = 'skills';
    protected $primaryKey = 'id';
    protected $allowedFields = ['skill_name', 'category', 'aliases'];

    public function getAllSkills()
    {
        return $this->findAll();
    }

    public function getSkillsByCategory($category)
    {
        return $this->where('category', $category)->findAll();
    }

    public function searchSkill($name)
    {
        return $this->groupStart()
                    ->like('skill_name', $name)
                    ->orLike('aliases', $name)
                    ->groupEnd()
                    ->findAll();
    }
}
