<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCandidatePersonalDetailsFields extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('candidate_profiles')) {
            return;
        }

        $fields = [];

        if (!$this->db->fieldExists('gender', 'candidate_profiles')) {
            $fields['gender'] = [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'after' => 'bio',
            ];
        }

        if (!$this->db->fieldExists('date_of_birth', 'candidate_profiles')) {
            $fields['date_of_birth'] = [
                'type' => 'DATE',
                'null' => true,
                'after' => array_key_exists('gender', $fields) ? 'gender' : 'bio',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('candidate_profiles', $fields);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('candidate_profiles')) {
            return;
        }

        $drop = [];
        if ($this->db->fieldExists('gender', 'candidate_profiles')) {
            $drop[] = 'gender';
        }
        if ($this->db->fieldExists('date_of_birth', 'candidate_profiles')) {
            $drop[] = 'date_of_birth';
        }

        if (!empty($drop)) {
            $this->forge->dropColumn('candidate_profiles', $drop);
        }
    }
}
