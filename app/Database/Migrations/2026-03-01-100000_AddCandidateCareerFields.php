<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCandidateCareerFields extends Migration
{
    public function up()
    {
        $fields = [
            'resume_headline' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'bio',
            ],
            'preferred_locations' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
                'after' => 'resume_headline',
            ],
            'current_salary' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'preferred_locations',
            ],
            'expected_salary' => [
                'type' => 'DECIMAL',
                'constraint' => '10,2',
                'null' => true,
                'after' => 'current_salary',
            ],
            'notice_period' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'expected_salary',
            ],
        ];

        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['resume_headline', 'preferred_locations', 'current_salary', 'expected_salary', 'notice_period']);
    }
}
