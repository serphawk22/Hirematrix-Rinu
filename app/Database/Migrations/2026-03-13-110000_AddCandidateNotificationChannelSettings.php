<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCandidateNotificationChannelSettings extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('candidate_profiles')) {
            return;
        }

        $fields = [];

        if (!$this->db->fieldExists('job_alert_notify_in_app', 'candidate_profiles')) {
            $fields['job_alert_notify_in_app'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'after' => 'job_alerts_enabled',
            ];
        }

        if (!$this->db->fieldExists('job_alert_notify_email', 'candidate_profiles')) {
            $fields['job_alert_notify_email'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 1,
                'after' => array_key_exists('job_alert_notify_in_app', $fields)
                    ? 'job_alert_notify_in_app'
                    : 'job_alerts_enabled',
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
        if ($this->db->fieldExists('job_alert_notify_in_app', 'candidate_profiles')) {
            $drop[] = 'job_alert_notify_in_app';
        }
        if ($this->db->fieldExists('job_alert_notify_email', 'candidate_profiles')) {
            $drop[] = 'job_alert_notify_email';
        }

        if (!empty($drop)) {
            $this->forge->dropColumn('candidate_profiles', $drop);
        }
    }
}
