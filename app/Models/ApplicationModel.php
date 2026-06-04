<?php

namespace App\Models;

use CodeIgniter\Model;

class ApplicationModel extends Model
{
    protected $table = 'applications';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'job_id',
        'candidate_id',
        'resume_version_id',
        'questionnaire_responses',
        'status',
        'interview_slot',
        'booking_id',
        'applied_at'
    ];
}
