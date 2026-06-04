<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSalaryRangeAndDeadlineToJobs extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('salary_range', 'jobs')) {
            $this->forge->addColumn('jobs', [
                'salary_range' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'employment_type',
                ],
            ]);
        }

        if (!$this->db->fieldExists('application_deadline', 'jobs')) {
            $this->forge->addColumn('jobs', [
                'application_deadline' => [
                    'type'  => 'DATE',
                    'null'  => true,
                    'after' => 'salary_range',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('application_deadline', 'jobs')) {
            $this->forge->dropColumn('jobs', 'application_deadline');
        }

        if ($this->db->fieldExists('salary_range', 'jobs')) {
            $this->forge->dropColumn('jobs', 'salary_range');
        }
    }
}
