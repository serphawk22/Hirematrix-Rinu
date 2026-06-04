<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CleanupLegacyPhpInterviewArtifacts extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('applications')) {
            // Normalize legacy PHP interview statuses into current flow statuses.
            $this->db->query("UPDATE applications SET status = 'applied' WHERE status IN ('ai_interview_started', 'ai_interview_completed')");

            if ($this->db->fieldExists('ai_interview_id', 'applications')) {
                $this->forge->dropColumn('applications', 'ai_interview_id');
            }
        }

        // Drop legacy PHP interview storage tables if present.
        if ($this->db->tableExists('ai_interviews')) {
            $this->forge->dropTable('ai_interviews', true);
        }

        // Keep interview_sessions because the browser-based AI interview flow
        // now uses it as the canonical interview summary table.
    }

    public function down()
    {
        if ($this->db->tableExists('applications') && !$this->db->fieldExists('ai_interview_id', 'applications')) {
            $this->forge->addColumn('applications', [
                'ai_interview_id' => [
                    'type' => 'INT',
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'interview_slot',
                ],
            ]);
        }
    }
}
