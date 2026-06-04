<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruiterCandidateMessagesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('recruiter_candidate_messages')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'candidate_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'recruiter_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'application_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'job_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'null' => true,
            ],
            'sender_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'sender_role' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'message' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('candidate_id');
        $this->forge->addKey('recruiter_id');
        $this->forge->addKey('application_id');
        $this->forge->addKey('job_id');
        $this->forge->addForeignKey('candidate_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('recruiter_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('application_id', 'applications', 'id', 'SET NULL', 'CASCADE');
        $this->forge->addForeignKey('job_id', 'jobs', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('recruiter_candidate_messages', true);
    }

    public function down()
    {
        $this->forge->dropTable('recruiter_candidate_messages', true);
    }
}

