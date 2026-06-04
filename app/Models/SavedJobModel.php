<?php

namespace App\Models;

use CodeIgniter\Model;

class SavedJobModel extends Model
{
    protected $table = 'saved_jobs';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'candidate_id',
        'job_id',
        'mnc_external_job_id',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
