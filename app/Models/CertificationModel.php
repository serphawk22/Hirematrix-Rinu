<?php

namespace App\Models;

use CodeIgniter\Model;

class CertificationModel extends Model
{
    protected $table = 'certifications';
    protected $primaryKey = 'id';
    protected $allowedFields = ['user_id', 'certification_name', 'issuing_organization', 'issue_date', 'expiry_date', 'credential_id', 'credential_url'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getByUser($userId)
    {
        return $this->where('user_id', $userId)->orderBy('issue_date', 'DESC')->findAll();
    }
}
