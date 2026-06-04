<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGoogleIdToUsers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('google_id', 'users')) {
            $this->forge->addColumn('users', [
                'google_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 191,
                    'null' => true,
                    'after' => 'email',
                ],
            ]);

            $this->db->query('CREATE UNIQUE INDEX users_google_id_unique ON users (google_id)');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('google_id', 'users')) {
            try {
                $this->db->query('DROP INDEX users_google_id_unique ON users');
            } catch (\Throwable $e) {
                // Ignore if index is absent.
            }

            $this->forge->dropColumn('users', 'google_id');
        }
    }
}