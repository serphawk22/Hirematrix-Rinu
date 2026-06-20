<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruiterMailboxTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('recruiter_mailbox_connections')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'recruiter_id' => ['type' => 'INT', 'constraint' => 11],
                'provider' => ['type' => 'VARCHAR', 'constraint' => 20],
                'email' => ['type' => 'VARCHAR', 'constraint' => 190],
                'access_token' => ['type' => 'TEXT'],
                'refresh_token' => ['type' => 'TEXT', 'null' => true],
                'token_expires_at' => ['type' => 'DATETIME', 'null' => true],
                'scopes' => ['type' => 'TEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'connected'],
                'last_synced_at' => ['type' => 'DATETIME', 'null' => true],
                'last_error' => ['type' => 'TEXT', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('recruiter_id');
            $this->forge->addUniqueKey(['recruiter_id', 'provider']);
            $this->forge->createTable('recruiter_mailbox_connections', true);
        }

        if (!$this->db->tableExists('recruiter_email_activities')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'connection_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'recruiter_id' => ['type' => 'INT', 'constraint' => 11],
                'candidate_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'application_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'job_id' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
                'provider_message_id' => ['type' => 'VARCHAR', 'constraint' => 255],
                'provider_thread_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'direction' => ['type' => 'VARCHAR', 'constraint' => 12],
                'from_email' => ['type' => 'VARCHAR', 'constraint' => 190],
                'to_email' => ['type' => 'VARCHAR', 'constraint' => 190],
                'subject' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'body_text' => ['type' => 'TEXT', 'null' => true],
                'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'synced'],
                'occurred_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['recruiter_id', 'candidate_id']);
            $this->forge->addKey('application_id');
            $this->forge->addUniqueKey(['connection_id', 'provider_message_id']);
            $this->forge->createTable('recruiter_email_activities', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('recruiter_email_activities', true);
        $this->forge->dropTable('recruiter_mailbox_connections', true);
    }
}
