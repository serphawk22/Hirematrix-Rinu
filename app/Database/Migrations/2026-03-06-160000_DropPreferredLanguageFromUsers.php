<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropPreferredLanguageFromUsers extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('users') && $this->db->fieldExists('preferred_language', 'users')) {
            $this->forge->dropColumn('users', 'preferred_language');
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('users') || $this->db->fieldExists('preferred_language', 'users')) {
            return;
        }

        $this->forge->addColumn('users', [
            'preferred_language' => [
                'type' => 'VARCHAR',
                'constraint' => 5,
                'null' => true,
                'default' => 'en',
                'after' => 'google_id',
            ],
        ]);
    }
}
