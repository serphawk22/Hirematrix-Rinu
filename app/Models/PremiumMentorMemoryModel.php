<?php

namespace App\Models;

use CodeIgniter\Model;

class PremiumMentorMemoryModel extends Model
{
    protected $table = 'premium_mentor_memories';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id',
        'memory_summary',
        'key_facts',
        'last_compacted_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getByUserId($userId)
    {
        return $this->where('user_id', $userId)->first();
    }
}
