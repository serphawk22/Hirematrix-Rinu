<?php

namespace App\Models;

use CodeIgniter\Model;

class RecruiterEmailActivityModel extends Model
{
    protected $table = 'recruiter_email_activities';
    protected $returnType = 'array';
    protected $allowedFields = [
        'connection_id', 'recruiter_id', 'candidate_id', 'application_id', 'job_id',
        'provider_message_id', 'provider_thread_id', 'direction', 'from_email',
        'to_email', 'subject', 'body_text', 'status', 'occurred_at', 'notified_at', 'created_at',
    ];
}
