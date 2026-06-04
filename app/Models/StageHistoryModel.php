<?php

namespace App\Models;

use CodeIgniter\Model;

class StageHistoryModel extends Model
{
    protected $table = 'stage_history';
    protected $allowedFields = [
        'application_id',
        'stage_name',
        'start_time',
        'end_time'
    ];

    public function moveToStage($applicationId, $newStage)
    {
        $db = \Config\Database::connect();

        // 1. Close previous stage
        $db->table('stage_history')
            ->where('application_id', $applicationId)
            ->where('end_time', null)
            ->update(['end_time' => date('Y-m-d H:i:s')]);

        // 2. Insert new stage
        $db->table('stage_history')->insert([
            'application_id' => $applicationId,
            'stage_name' => $newStage,
            'start_time' => date('Y-m-d H:i:s'),
            'end_time' => null
        ]);

        
    }
}
