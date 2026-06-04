<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCandidatePrivacyAndAlertSettings extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('candidate_profiles')) {
            return;
        }

        $fields = [];

        if (!$this->db->fieldExists('allow_public_recruiter_visibility', 'candidate_profiles')) {
            $fields['allow_public_recruiter_visibility'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'after' => 'notice_period',
            ];
        }

        if (!$this->db->fieldExists('job_alerts_enabled', 'candidate_profiles')) {
            $fields['job_alerts_enabled'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'after' => array_key_exists('allow_public_recruiter_visibility', $fields)
                    ? 'allow_public_recruiter_visibility'
                    : 'notice_period',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('candidate_profiles', $fields);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('candidate_profiles')) {
            return;
        }

        $drop = [];
        if ($this->db->fieldExists('allow_public_recruiter_visibility', 'candidate_profiles')) {
            $drop[] = 'allow_public_recruiter_visibility';
        }
        if ($this->db->fieldExists('job_alerts_enabled', 'candidate_profiles')) {
            $drop[] = 'job_alerts_enabled';
        }

        if (!empty($drop)) {
            $this->forge->dropColumn('candidate_profiles', $drop);
        }
    }
}
