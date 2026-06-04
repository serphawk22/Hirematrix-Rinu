<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPasswordResetFieldsToUsers extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('password_reset_token', 'users')) {
            $fields['password_reset_token'] = [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
                'after' => 'email_verified_at',
            ];
        }

        if (!$this->db->fieldExists('password_reset_expires_at', 'users')) {
            $fields['password_reset_expires_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'password_reset_token',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('users', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('password_reset_expires_at', 'users')) {
            $this->forge->dropColumn('users', 'password_reset_expires_at');
        }

        if ($this->db->fieldExists('password_reset_token', 'users')) {
            $this->forge->dropColumn('users', 'password_reset_token');
        }
    }
}
