<?php

namespace App\Models;

use CodeIgniter\Model;

class RecruiterProfileModel extends Model
{
    protected $table = 'recruiter_profiles';
    protected $primaryKey = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'full_name',
        'phone',
        'designation',
        'company_name_snapshot',
        'recruiter_type',
        'verification_status',
        'agency_registration_number',
        'gst_number',
        'website',
        'official_email',
        'can_post_jobs',
        'created_at',
        'updated_at',
    ];
}
