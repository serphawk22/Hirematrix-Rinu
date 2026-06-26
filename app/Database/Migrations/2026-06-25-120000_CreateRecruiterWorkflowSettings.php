<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruiterWorkflowSettings extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('recruiter_workflow_settings')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'recruiter_id' => ['type' => 'INT', 'constraint' => 11],
            'send_rejection_email' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'rejection_email_subject' => ['type' => 'VARCHAR', 'constraint' => 255],
            'rejection_email_body' => ['type' => 'TEXT'],
            'rejection_email_use_mailbox' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'rejection_email_allow_system_fallback' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'rejection_email_cc_self' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['recruiter_id']);
        $this->forge->createTable('recruiter_workflow_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('recruiter_workflow_settings', true);
    }
}
