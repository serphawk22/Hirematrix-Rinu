<?php

namespace App\Models;

use CodeIgniter\Model;

class PremiumMentorMessageModel extends Model
{
    protected $table = 'premium_mentor_messages';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'session_id',
        'role',
        'content',
        'compacted',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getRecentByUserId($userId, $limit = 12)
    {
        return $this->where('user_id', $userId)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);
    }

    public function getUncompactedByUserId($userId, $limit = 14)
    {
        return $this->where('user_id', $userId)
            ->where('compacted', 0)
            ->orderBy('created_at', 'ASC')
            ->findAll($limit);
    }
}
