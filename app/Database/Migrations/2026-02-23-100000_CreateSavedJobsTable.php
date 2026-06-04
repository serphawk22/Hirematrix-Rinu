<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSavedJobsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('saved_jobs')) {
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
            'job_id' => [
                'type' => 'INT',
                'constraint' => 11,
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
        $this->forge->addUniqueKey(['candidate_id', 'job_id'], 'saved_jobs_candidate_job_unique');
        $this->forge->addKey('candidate_id');
        $this->forge->addKey('job_id');
        $this->forge->addForeignKey('candidate_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('job_id', 'jobs', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('saved_jobs', true);
    }

    public function down()
    {
        $this->forge->dropTable('saved_jobs', true);
    }
}

