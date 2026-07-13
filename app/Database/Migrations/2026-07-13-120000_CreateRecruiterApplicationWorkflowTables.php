<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruiterApplicationWorkflowTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('recruiter_application_workflows')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'application_id' => ['type' => 'INT', 'constraint' => 11],
                'recruiter_id' => ['type' => 'INT', 'constraint' => 11],
                'follow_up_at' => ['type' => 'DATETIME', 'null' => true],
                'follow_up_completed_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['application_id', 'recruiter_id']);
            $this->forge->addKey(['recruiter_id', 'follow_up_at']);
            $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('recruiter_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('recruiter_application_workflows', true);
        }

        if (!$this->db->tableExists('recruiter_communication_outcomes')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
                'application_id' => ['type' => 'INT', 'constraint' => 11],
                'candidate_id' => ['type' => 'INT', 'constraint' => 11],
                'job_id' => ['type' => 'INT', 'constraint' => 11],
                'recruiter_id' => ['type' => 'INT', 'constraint' => 11],
                'channel' => ['type' => 'VARCHAR', 'constraint' => 20],
                'outcome' => ['type' => 'VARCHAR', 'constraint' => 40],
                'notes' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'occurred_at' => ['type' => 'DATETIME'],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['application_id', 'recruiter_id']);
            $this->forge->addKey(['recruiter_id', 'occurred_at']);
            $this->forge->addForeignKey('application_id', 'applications', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('candidate_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('job_id', 'jobs', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('recruiter_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('recruiter_communication_outcomes', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('recruiter_communication_outcomes', true);
        $this->forge->dropTable('recruiter_application_workflows', true);
    }
}
