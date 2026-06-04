<?php
namespace App\Models;

use CodeIgniter\Model;

class JobSuggestionModel extends Model
{
    protected $table = 'job_suggestions';
    protected $allowedFields = ['candidate_id', 'job_id', 'score', 'reason', 'created_at'];
}
