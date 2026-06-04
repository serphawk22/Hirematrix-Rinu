<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJobAlertsTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('job_alerts')) {
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
                'role_keywords' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'location_keywords' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'skills_keywords' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'salary_min' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'salary_max' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                ],
                'notify_email' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'notify_in_app' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
                ],
                'is_active' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 1,
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
            $this->forge->addForeignKey('candidate_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('job_alerts', true);
        }

        if (!$this->db->tableExists('job_alert_deliveries')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'job_alert_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                ],
                'job_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'candidate_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'email_sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'in_app_sent_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('job_alert_id');
            $this->forge->addKey('job_id');
            $this->forge->addKey('candidate_id');
            $this->forge->addUniqueKey(['job_alert_id', 'job_id'], 'job_alert_job_unique');
            $this->forge->addForeignKey('job_alert_id', 'job_alerts', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('job_id', 'jobs', 'id', 'CASCADE', 'CASCADE');
            $this->forge->addForeignKey('candidate_id', 'users', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('job_alert_deliveries', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('job_alert_deliveries')) {
            $this->forge->dropTable('job_alert_deliveries', true);
        }

        if ($this->db->tableExists('job_alerts')) {
            $this->forge->dropTable('job_alerts', true);
        }
    }
}
