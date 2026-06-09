<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCandidateJobVisitsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('candidate_job_visits')) {
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
            'visited_at' => [
                'type' => 'DATETIME',
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
        $this->forge->addUniqueKey(['candidate_id', 'job_id'], 'candidate_job_visits_candidate_job_unique');
        $this->forge->addKey('candidate_id');
        $this->forge->addKey('job_id');
        $this->forge->createTable('candidate_job_visits', true);
    }

    public function down()
    {
        $this->forge->dropTable('candidate_job_visits', true);
    }
}
