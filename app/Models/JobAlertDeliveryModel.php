<?php

namespace App\Models;

use CodeIgniter\Model;

class JobAlertDeliveryModel extends Model
{
    protected $table = 'job_alert_deliveries';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'job_alert_id',
        'job_id',
        'candidate_id',
        'email_sent_at',
        'in_app_sent_at',
        'created_at',
    ];

    protected $useTimestamps = false;
}

