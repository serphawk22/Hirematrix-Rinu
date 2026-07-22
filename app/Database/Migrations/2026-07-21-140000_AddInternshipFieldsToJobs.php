<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddInternshipFieldsToJobs extends Migration
{
    public function up()
    {
        $fields = [
            'internship_duration' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'internship_stipend' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'internship_start_date' => ['type' => 'DATE', 'null' => true],
            'internship_type' => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'work_mode' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'ppo_available' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
        ];

        foreach ($fields as $name => $definition) {
            if (!$this->db->fieldExists($name, 'jobs')) {
                $this->forge->addColumn('jobs', [$name => $definition]);
            }
        }
    }

    public function down()
    {
        foreach (['ppo_available', 'work_mode', 'internship_type', 'internship_start_date', 'internship_stipend', 'internship_duration'] as $field) {
            if ($this->db->fieldExists($field, 'jobs')) {
                $this->forge->dropColumn('jobs', $field);
            }
        }
    }
}
