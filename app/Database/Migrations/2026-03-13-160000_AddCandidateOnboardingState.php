<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCandidateOnboardingState extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('users')) {
            $fields = [];

            if (!$this->db->fieldExists('onboarding_completed', 'users')) {
                $fields['onboarding_completed'] = [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                    'after' => 'phone_verified_at',
                ];
            }

            if (!$this->db->fieldExists('onboarding_step', 'users')) {
                $fields['onboarding_step'] = [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => array_key_exists('onboarding_completed', $fields)
                        ? 'onboarding_completed'
                        : 'phone_verified_at',
                ];
            }

            if (!$this->db->fieldExists('onboarding_completed_at', 'users')) {
                $fields['onboarding_completed_at'] = [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => array_key_exists('onboarding_step', $fields)
                        ? 'onboarding_step'
                        : (array_key_exists('onboarding_completed', $fields) ? 'onboarding_completed' : 'phone_verified_at'),
                ];
            }

            if (!empty($fields)) {
                $this->forge->addColumn('users', $fields);
            }
        }

        if ($this->db->tableExists('candidate_profiles') && !$this->db->fieldExists('is_fresher_candidate', 'candidate_profiles')) {
            $this->forge->addColumn('candidate_profiles', [
                'is_fresher_candidate' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'null' => false,
                    'default' => 0,
                    'after' => 'job_alert_notify_email',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('users')) {
            $drop = [];
            foreach (['onboarding_completed', 'onboarding_step', 'onboarding_completed_at'] as $field) {
                if ($this->db->fieldExists($field, 'users')) {
                    $drop[] = $field;
                }
            }
            if (!empty($drop)) {
                $this->forge->dropColumn('users', $drop);
            }
        }

        if ($this->db->tableExists('candidate_profiles') && $this->db->fieldExists('is_fresher_candidate', 'candidate_profiles')) {
            $this->forge->dropColumn('candidate_profiles', 'is_fresher_candidate');
        }
    }
}
