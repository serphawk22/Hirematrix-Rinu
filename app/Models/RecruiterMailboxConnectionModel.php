<?php

namespace App\Models;

use CodeIgniter\Model;

class RecruiterMailboxConnectionModel extends Model
{
    protected $table = 'recruiter_mailbox_connections';
    protected $returnType = 'array';
    protected $allowedFields = [
        'recruiter_id', 'provider', 'email', 'access_token', 'refresh_token',
        'token_expires_at', 'scopes', 'status', 'last_synced_at', 'last_error',
        'created_at', 'updated_at',
    ];

    public function getConnectedForRecruiter(int $recruiterId): ?array
    {
        return $this->where('recruiter_id', $recruiterId)
            ->where('status', 'connected')
            ->orderBy('updated_at', 'DESC')
            ->first();
    }
}
