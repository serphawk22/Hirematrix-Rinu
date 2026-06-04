<?php
namespace App\Models;

use CodeIgniter\Model;

class CandidateInterestsModel extends Model
{
    protected $table      = 'candidate_interests';
    protected $primaryKey = 'id';

    // One row per candidate; interests stored as comma-separated string
    protected $allowedFields = ['candidate_id', 'interest'];
}
