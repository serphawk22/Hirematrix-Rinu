<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCandidateIntroVideoFields extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('candidate_profiles')) {
            return;
        }

        $fields = $this->db->getFieldNames('candidate_profiles');

        if (!in_array('intro_video_path', $fields, true)) {
            $this->forge->addColumn('candidate_profiles', [
                'intro_video_path' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'profile_photo',
                ],
            ]);
        }

        if (!in_array('intro_video_pitch', $fields, true)) {
            $this->forge->addColumn('candidate_profiles', [
                'intro_video_pitch' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'intro_video_path',
                ],
            ]);
        }

        if (!in_array('intro_video_target_role', $fields, true)) {
            $this->forge->addColumn('candidate_profiles', [
                'intro_video_target_role' => [
                    'type' => 'VARCHAR',
                    'constraint' => 150,
                    'null' => true,
                    'after' => 'intro_video_pitch',
                ],
            ]);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('candidate_profiles')) {
            return;
        }

        $fields = $this->db->getFieldNames('candidate_profiles');
        foreach (['intro_video_target_role', 'intro_video_pitch', 'intro_video_path'] as $field) {
            if (in_array($field, $fields, true)) {
                $this->forge->dropColumn('candidate_profiles', $field);
            }
        }
    }
}
