<?php 
namespace App\Models;

use CodeIgniter\Model;

class CandidateSkillsModel extends Model
{
    protected $table = 'candidate_skills';
    protected $allowedFields = ['candidate_id', 'skill_name'];
}
