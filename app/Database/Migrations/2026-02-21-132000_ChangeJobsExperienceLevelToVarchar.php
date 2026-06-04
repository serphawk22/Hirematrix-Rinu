<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ChangeJobsExperienceLevelToVarchar extends Migration
{
    public function up()
    {
        if ($this->db->fieldExists('experience_level', 'jobs')) {
            $this->db->query('ALTER TABLE jobs MODIFY experience_level VARCHAR(100) NULL');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('experience_level', 'jobs')) {
            $this->db->query("ALTER TABLE jobs MODIFY experience_level ENUM('fresher','junior','mid','senior') NULL");
        }
    }
}

