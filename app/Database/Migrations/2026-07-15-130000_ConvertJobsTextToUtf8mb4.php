<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class ConvertJobsTextToUtf8mb4 extends Migration
{
    public function up()
    {
        if ($this->db->DBDriver === 'MySQLi' && $this->db->tableExists('jobs')) {
            $this->db->query(
                'ALTER TABLE jobs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
        }
    }

    public function down()
    {
        // Converting back to MySQL utf8 would be lossy for four-byte characters.
    }
}
