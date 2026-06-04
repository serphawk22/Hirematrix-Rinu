<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyReviewModel extends Model
{
    protected $table = 'company_reviews';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id',
        'candidate_id',
        'review_type',
        'rating',
        'headline',
        'review_text',
        'pros',
        'cons',
        'status',
    ];
}
