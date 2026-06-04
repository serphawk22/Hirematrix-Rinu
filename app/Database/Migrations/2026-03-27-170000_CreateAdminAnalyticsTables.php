<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdminAnalyticsTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('admin_api_usage_logs')) {
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
                    'unsigned' => true,
                    'null' => true,
                ],
                'user_email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'user_role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                ],
                'provider' => [
                    'type' => 'VARCHAR',
                    'constraint' => 60,
                    'default' => 'openai',
                ],
                'endpoint' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'model' => [
                    'type' => 'VARCHAR',
                    'constraint' => 120,
                    'null' => true,
                ],
                'prompt_tokens' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'completion_tokens' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'total_tokens' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'default' => 0,
                ],
                'estimated_cost_usd' => [
                    'type' => 'DECIMAL',
                    'constraint' => '12,6',
                    'default' => 0.000000,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('created_at');
            $this->forge->addKey('user_id');
            $this->forge->createTable('admin_api_usage_logs', true);
        }

        if (!$this->db->tableExists('user_login_performance_logs')) {
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
                    'unsigned' => true,
                    'null' => true,
                ],
                'user_email' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'user_role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 30,
                    'null' => true,
                ],
                'login_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'first_page_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'first_page_loaded_at' => [
                    'type' => 'DATETIME',
                    'null' => false,
                ],
                'duration_ms' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'default' => 0,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('login_at');
            $this->forge->addKey('user_id');
            $this->forge->createTable('user_login_performance_logs', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('admin_api_usage_logs', true);
        $this->forge->dropTable('user_login_performance_logs', true);
    }
}
