<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCandidatePreferenceFields extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('candidate_profiles') && !$this->db->fieldExists('preferred_job_titles', 'candidate_profiles')) {
            $this->forge->addColumn('candidate_profiles', [
                'preferred_job_titles' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'key_skills',
                ],
            ]);
        }

        if ($this->db->tableExists('candidate_profiles') && !$this->db->fieldExists('preferred_employment_type', 'candidate_profiles')) {
            $this->forge->addColumn('candidate_profiles', [
                'preferred_employment_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => 'preferred_locations',
                ],
            ]);
        }

        if ($this->db->tableExists('job_alerts') && !$this->db->fieldExists('employment_type', 'job_alerts')) {
            $this->forge->addColumn('job_alerts', [
                'employment_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => 'location_keywords',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('job_alerts') && $this->db->fieldExists('employment_type', 'job_alerts')) {
            $this->forge->dropColumn('job_alerts', 'employment_type');
        }

        if ($this->db->tableExists('candidate_profiles') && $this->db->fieldExists('preferred_job_titles', 'candidate_profiles')) {
            $this->forge->dropColumn('candidate_profiles', 'preferred_job_titles');
        }

        if ($this->db->tableExists('candidate_profiles') && $this->db->fieldExists('preferred_employment_type', 'candidate_profiles')) {
            $this->forge->dropColumn('candidate_profiles', 'preferred_employment_type');
        }
    }
}
