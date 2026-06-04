<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRecruiterCandidateNotesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('recruiter_candidate_notes')) {
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
            'tags' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('candidate_id');
        $this->forge->addKey('recruiter_id');
        $this->forge->addKey(['candidate_id', 'recruiter_id'], false, true);
        $this->forge->addForeignKey('candidate_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('recruiter_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('recruiter_candidate_notes', true);
    }

    public function down()
    {
        $this->forge->dropTable('recruiter_candidate_notes', true);
    }
}
