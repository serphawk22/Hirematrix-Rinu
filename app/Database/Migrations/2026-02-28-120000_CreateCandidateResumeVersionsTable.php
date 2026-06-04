<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCandidateResumeVersionsTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('candidate_resume_versions')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'auto_increment' => true,
                ],
                'candidate_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'job_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'application_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'career_transition_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'title' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                ],
                'target_role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                    'null' => true,
                ],
                'source_role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 180,
                    'null' => true,
                ],
                'generation_source' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'default' => 'role_based',
                ],
                'base_resume_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'summary' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'highlight_skills' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'content' => [
                    'type' => 'LONGTEXT',
                ],
                'is_primary' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                ],
                'last_synced_at' => [
                    'type' => 'DATETIME',
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
            $this->forge->addKey('job_id');
            $this->forge->addKey('application_id');
            $this->forge->addKey('career_transition_id');
            $this->forge->addForeignKey('candidate_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('job_id', 'jobs', 'id', 'SET NULL', 'CASCADE');
            $this->forge->addForeignKey('application_id', 'applications', 'id', 'SET NULL', 'CASCADE');
            $this->forge->createTable('candidate_resume_versions', true);
        }

        if (!$this->db->fieldExists('resume_version_id', 'applications')) {
            $this->forge->addColumn('applications', [
                'resume_version_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'candidate_id',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('resume_version_id', 'applications')) {
            $this->forge->dropColumn('applications', 'resume_version_id');
        }

        $this->forge->dropTable('candidate_resume_versions', true);
    }
}
