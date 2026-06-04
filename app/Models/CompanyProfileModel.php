<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyProfileModel extends Model
{
    protected $table = 'company_profiles';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'recruiter_id',
        'company_name',
        'company_logo',
        'company_website',
        'company_industry',
        'company_size',
        'company_hq',
        'company_branches',
        'company_short_description',
        'company_what_we_do',
        'company_mission_values',
        'company_contact_email',
        'company_contact_phone',
        'company_contact_public',
    ];
}

