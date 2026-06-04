<?php

namespace App\Models;

use CodeIgniter\Model;

class CareerConversationModel extends Model
{
    protected $table = 'career_conversations';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'session_id', 'message', 'response', 
        'message_type', 'stage', 'created_at'
    ];
    protected $useTimestamps = false;
    protected $createdField = 'created_at';
}