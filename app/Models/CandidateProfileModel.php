<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidateProfileModel extends Model
{
    protected $table = 'candidate_profiles';
    protected $primaryKey = 'user_id';
    protected $useAutoIncrement = false;
    protected $returnType = 'array';
    protected $allowedFields = [
        'user_id',
        'headline',
        'location',
        'bio',
        'gender',
        'date_of_birth',
        'resume_path',
        'profile_photo',
        'intro_video_path',
        'intro_video_pitch',
        'intro_video_target_role',
        'key_skills',
        'preferred_job_titles',
        'preferred_locations',
        'preferred_employment_type',
        'current_salary',
        'expected_salary',
        'notice_period',
        'allow_public_recruiter_visibility',
        'job_alerts_enabled',
        'job_alert_notify_in_app',
        'job_alert_notify_email',
        'is_fresher_candidate',
        'created_at',
        'updated_at',
    ];
}
