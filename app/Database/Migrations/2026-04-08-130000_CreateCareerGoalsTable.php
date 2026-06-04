<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCareerGoalsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('career_goals')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'aspiration' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'specific_goal' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'measurable_criteria' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'achievable_steps' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'realistic_assessment' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'time_bound' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'current_skills' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'target_skills' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'progress_percentage' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'completed', 'paused'],
                'default' => 'active',
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
        $this->forge->addKey('status');
        $this->forge->addKey('updated_at');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'career_goals_user_fk');
        $this->forge->createTable('career_goals', true);
    }

    public function down()
    {
        if ($this->db->tableExists('career_goals')) {
            $this->forge->dropTable('career_goals', true);
        }
    }
}
