<?php

namespace App\Models;

use CodeIgniter\Model;

class MncJobModel extends Model
{
    protected $table            = 'mnc_external_jobs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'company_name',
        'title',
        'location',
        'apply_url',
        'source_platform',
        'posted_at_raw',
        'is_active',
        'last_sync_at',
    ];

    public function getCachedJobs(string $companyName, int $limit = 100): array
    {
        $limit      = max(1, min(100, $limit));
        $fetchLimit = min(100, max($limit * 2, 20)); // Reduced from 300 to 100 max

        try {
            // Reconnect if connection is stale
            if (!$this->db->connID || !$this->db->connID->ping()) {
                $this->db->reconnect();
            }

            return $this->where('company_name', $companyName)
                        ->where('last_sync_at >=', date('Y-m-d H:i:s', strtotime('-48 hours')))
                        ->where('is_active', 1)
                        ->orderBy('last_sync_at', 'DESC')
                        ->limit($fetchLimit)
                        ->findAll();
        } catch (\Throwable $e) {
            log_message('error', 'MncJobModel::getCachedJobs failed: ' . $e->getMessage());
            
            // Try reconnecting once
            try {
                $this->db->close();
                $this->db->initialize();
                
                return $this->where('company_name', $companyName)
                            ->where('last_sync_at >=', date('Y-m-d H:i:s', strtotime('-48 hours')))
                            ->where('is_active', 1)
                            ->orderBy('last_sync_at', 'DESC')
                            ->limit($fetchLimit)
                            ->findAll();
            } catch (\Throwable $retryError) {
                log_message('error', 'MncJobModel::getCachedJobs retry failed: ' . $retryError->getMessage());
                return [];
            }
        }
    }
}
