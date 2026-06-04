<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropDeprecatedCandidateColumnsFromUsers extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('users') || !$this->db->tableExists('candidate_profiles')) {
            return;
        }

        // Final sync before dropping legacy columns.
        $this->db->query("UPDATE candidate_profiles cp
            INNER JOIN users u ON u.id = cp.user_id
            SET
                cp.location = COALESCE(cp.location, u.location),
                cp.bio = COALESCE(cp.bio, u.bio),
                cp.resume_path = COALESCE(cp.resume_path, u.resume_path),
                cp.profile_photo = COALESCE(cp.profile_photo, u.profile_photo),
                cp.headline = COALESCE(cp.headline, u.resume_headline),
                cp.key_skills = COALESCE(cp.key_skills, u.key_skills),
                cp.preferred_locations = COALESCE(cp.preferred_locations, u.preferred_locations),
                cp.current_salary = COALESCE(cp.current_salary, u.current_salary),
                cp.expected_salary = COALESCE(cp.expected_salary, u.expected_salary),
                cp.notice_period = COALESCE(cp.notice_period, u.notice_period)
            WHERE u.role = 'candidate'");

        $columns = [
            'resume_path',
            'profile_photo',
            'location',
            'bio',
            'resume_headline',
            'key_skills',
            'preferred_locations',
            'current_salary',
            'expected_salary',
            'notice_period',
        ];

        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, 'users')) {
                $this->forge->dropColumn('users', $column);
            }
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('users')) {
            return;
        }

        if (!$this->db->fieldExists('resume_path', 'users')) {
            $this->forge->addColumn('users', [
                'resume_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'created_at',
                ],
            ]);
        }

        if (!$this->db->fieldExists('profile_photo', 'users')) {
            $this->forge->addColumn('users', [
                'profile_photo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'resume_path',
                ],
            ]);
        }

        if (!$this->db->fieldExists('location', 'users')) {
            $this->forge->addColumn('users', [
                'location' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                    'after' => 'profile_photo',
                ],
            ]);
        }

        if (!$this->db->fieldExists('bio', 'users')) {
            $this->forge->addColumn('users', [
                'bio' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'location',
                ],
            ]);
        }

        if (!$this->db->fieldExists('resume_headline', 'users')) {
            $this->forge->addColumn('users', [
                'resume_headline' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'bio',
                ],
            ]);
        }

        if (!$this->db->fieldExists('key_skills', 'users')) {
            $this->forge->addColumn('users', [
                'key_skills' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'resume_headline',
                ],
            ]);
        }

        if (!$this->db->fieldExists('preferred_locations', 'users')) {
            $this->forge->addColumn('users', [
                'preferred_locations' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                    'after' => 'key_skills',
                ],
            ]);
        }

        if (!$this->db->fieldExists('current_salary', 'users')) {
            $this->forge->addColumn('users', [
                'current_salary' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'after' => 'preferred_locations',
                ],
            ]);
        }

        if (!$this->db->fieldExists('expected_salary', 'users')) {
            $this->forge->addColumn('users', [
                'expected_salary' => [
                    'type' => 'DECIMAL',
                    'constraint' => '10,2',
                    'null' => true,
                    'after' => 'current_salary',
                ],
            ]);
        }

        if (!$this->db->fieldExists('notice_period', 'users')) {
            $this->forge->addColumn('users', [
                'notice_period' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => 'expected_salary',
                ],
            ]);
        }

        if ($this->db->tableExists('candidate_profiles')) {
            $this->db->query("UPDATE users u
                INNER JOIN candidate_profiles cp ON cp.user_id = u.id
                SET
                    u.location = cp.location,
                    u.bio = cp.bio,
                    u.resume_path = cp.resume_path,
                    u.profile_photo = cp.profile_photo,
                    u.resume_headline = cp.headline,
                    u.key_skills = cp.key_skills,
                    u.preferred_locations = cp.preferred_locations,
                    u.current_salary = cp.current_salary,
                    u.expected_salary = cp.expected_salary,
                    u.notice_period = cp.notice_period
                WHERE u.role = 'candidate'");
        }
    }
}
