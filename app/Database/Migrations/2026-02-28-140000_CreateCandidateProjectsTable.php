<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCandidateProjectsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('candidate_projects')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'project_name' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
            ],
            'role_name' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
            ],
            'tech_stack' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'project_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'project_summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'impact_metrics' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'start_date' => [
                'type' => 'DATE',
                'null' => true,
            ],
            'end_date' => [
                'type' => 'DATE',
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
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('candidate_projects', true);
    }

    public function down()
    {
        $this->forge->dropTable('candidate_projects', true);
    }
}
