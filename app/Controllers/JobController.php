<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class JobController extends BaseController
{
    public function markVisited($jobId)
    {
        $db = \Config\Database::connect();

        // Update DB
        $updated = $db->table('jobs')
            ->where('id', $jobId)
            ->update(['visited_flag' => 1]);

        return $this->response->setJSON([
            'success' => $updated,
            'job_id'  => $jobId
        ]);
    }
}