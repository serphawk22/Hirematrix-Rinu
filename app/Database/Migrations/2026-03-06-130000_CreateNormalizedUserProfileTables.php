<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNormalizedUserProfileTables extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('candidate_profiles')) {
            $this->forge->addField([
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'headline' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'location' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                ],
                'bio' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'resume_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'profile_photo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                ],
                'key_skills' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'preferred_locations' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                ],
                'current_salary' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                ],
                'expected_salary' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                ],
                'notice_period' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
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
            $this->forge->addKey('user_id', true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'candidate_profiles_user_fk');
            $this->forge->createTable('candidate_profiles', true);
        }

        if (!$this->db->tableExists('recruiter_profiles')) {
            $this->forge->addField([
                'user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'full_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                ],
                'phone' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'null' => true,
                ],
                'designation' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                ],
                'company_name_snapshot' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
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
            $this->forge->addKey('user_id', true);
            $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'recruiter_profiles_user_fk');
            $this->forge->createTable('recruiter_profiles', true);
        }

        if (!$this->db->tableExists('recruiter_company_map')) {
            $this->forge->addField([
                'id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'auto_increment' => true,
                ],
                'recruiter_user_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'company_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                ],
                'is_admin' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
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
            $this->forge->addKey('recruiter_user_id');
            $this->forge->addKey('company_id');
            $this->forge->addUniqueKey(['recruiter_user_id', 'company_id'], 'recruiter_company_map_unique');
            $this->forge->addForeignKey('recruiter_user_id', 'users', 'id', 'CASCADE', 'CASCADE', 'recruiter_company_map_user_fk');
            $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE', 'recruiter_company_map_company_fk');
            $this->forge->createTable('recruiter_company_map', true);
        }

        // Backfill candidate profiles from users (non-destructive).
        $this->db->query("INSERT INTO candidate_profiles (
                user_id, headline, location, bio, resume_path, profile_photo, key_skills,
                preferred_locations, current_salary, expected_salary, notice_period, created_at, updated_at
            )
            SELECT
                u.id,
                u.resume_headline,
                u.location,
                u.bio,
                u.resume_path,
                u.profile_photo,
                u.key_skills,
                u.preferred_locations,
                u.current_salary,
                u.expected_salary,
                u.notice_period,
                u.created_at,
                NOW()
            FROM users u
            LEFT JOIN candidate_profiles cp ON cp.user_id = u.id
            WHERE u.role = 'candidate' AND cp.user_id IS NULL");

        // Backfill recruiter profiles from users (non-destructive).
        $this->db->query("INSERT INTO recruiter_profiles (
                user_id, full_name, phone, company_name_snapshot, created_at, updated_at
            )
            SELECT
                u.id,
                u.name,
                u.phone,
                u.company_name,
                u.created_at,
                NOW()
            FROM users u
            LEFT JOIN recruiter_profiles rp ON rp.user_id = u.id
            WHERE u.role = 'recruiter' AND rp.user_id IS NULL");

        // Backfill recruiter-company mapping from users.company_id.
        $this->db->query("INSERT INTO recruiter_company_map (
                recruiter_user_id, company_id, is_admin, created_at, updated_at
            )
            SELECT
                u.id,
                u.company_id,
                1,
                u.created_at,
                NOW()
            FROM users u
            LEFT JOIN recruiter_company_map rcm
                ON rcm.recruiter_user_id = u.id AND rcm.company_id = u.company_id
            WHERE u.role = 'recruiter'
              AND u.company_id IS NOT NULL
              AND u.company_id > 0
              AND rcm.id IS NULL");
    }

    public function down()
    {
        if ($this->db->tableExists('recruiter_company_map')) {
            $this->forge->dropTable('recruiter_company_map', true);
        }

        if ($this->db->tableExists('recruiter_profiles')) {
            $this->forge->dropTable('recruiter_profiles', true);
        }

        if ($this->db->tableExists('candidate_profiles')) {
            $this->forge->dropTable('candidate_profiles', true);
        }
    }
}
