<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class JobController extends BaseController
{
    public function markVisited($jobId)
    {
        $jobId = (int) $jobId;
        if ($jobId <= 0 || session()->get('role') !== 'candidate') {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'job_id' => $jobId,
                'csrf' => csrf_hash(),
            ]);
        }

        $db = \Config\Database::connect();

        $exists = (bool) $db->table('jobs')
            ->select('id')
            ->where('id', $jobId)
            ->get()
            ->getRowArray();

        if (!$exists) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'job_id' => $jobId,
                'csrf' => csrf_hash(),
            ]);
        }

        $this->markCandidateJobVisited($db, (int) session()->get('user_id'), $jobId);

        if ($db->fieldExists('visited_flag', 'jobs')) {
            $db->table('jobs')
                ->where('id', $jobId)
                ->update(['visited_flag' => 1]);
        }

        return $this->response->setJSON([
            'success' => true,
            'job_id' => $jobId,
            'visited' => true,
            'csrf' => csrf_hash(),
        ]);
    }

    private function markCandidateJobVisited($db, int $candidateId, int $jobId): void
    {
        if ($candidateId <= 0 || $jobId <= 0) {
            return;
        }

        $this->ensureCandidateJobVisitsTable($db);
        $now = date('Y-m-d H:i:s');

        $db->query(
            'INSERT INTO candidate_job_visits (candidate_id, job_id, visited_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE visited_at = VALUES(visited_at), updated_at = VALUES(updated_at)',
            [$candidateId, $jobId, $now, $now, $now]
        );
    }

    private function ensureCandidateJobVisitsTable($db): void
    {
        if ($db->tableExists('candidate_job_visits')) {
            return;
        }

        $db->query(
            'CREATE TABLE IF NOT EXISTS candidate_job_visits (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                candidate_id INT NOT NULL,
                job_id INT NOT NULL,
                visited_at DATETIME NOT NULL,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                PRIMARY KEY (id),
                UNIQUE KEY candidate_job_visits_candidate_job_unique (candidate_id, job_id),
                KEY candidate_job_visits_candidate_id_index (candidate_id),
                KEY candidate_job_visits_job_id_index (job_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
        );
    }
}
