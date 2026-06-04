<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAiInterviewPolicyToJobs extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('ai_interview_policy', 'jobs')) {
            $this->forge->addColumn('jobs', [
                'ai_interview_policy' => [
                    'type' => 'ENUM',
                    'constraint' => ['OFF', 'OPTIONAL', 'REQUIRED_SOFT', 'REQUIRED_HARD'],
                    'default' => 'REQUIRED_HARD',
                    'null' => false,
                    'after' => 'min_ai_cutoff_score',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('ai_interview_policy', 'jobs')) {
            $this->forge->dropColumn('jobs', 'ai_interview_policy');
        }
    }
}

