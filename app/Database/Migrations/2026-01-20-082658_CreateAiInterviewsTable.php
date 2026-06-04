<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiInterviewsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'candidate_id' => ['type' => 'INT', 'unsigned' => true],
            'job_id' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'skills_tested' => ['type' => 'JSON'],
            'github_languages' => ['type' => 'JSON', 'null' => true],
            'questions' => ['type' => 'JSON'],
            'answers' => ['type' => 'JSON', 'null' => true],
            'technical_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'communication_score' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'overall_rating' => ['type' => 'DECIMAL', 'constraint' => '5,2', 'null' => true],
            'ai_decision' => ['type' => 'ENUM', 'constraint' => ['pending', 'qualified', 'rejected'], 'default' => 'pending'],
            'ai_feedback' => ['type' => 'JSON', 'null' => true],
            'status' => ['type' => 'ENUM', 'constraint' => ['created', 'in_progress', 'completed'], 'default' => 'created'],
            'started_at' => ['type' => 'DATETIME', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('candidate_id');
        $this->forge->addKey('job_id');
        $this->forge->createTable('ai_interviews');
    }

    public function down()
    {
        $this->forge->dropTable('ai_interviews');

    }
}
