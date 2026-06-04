<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropUnusedPhoneOtpColumnsFromUsers extends Migration
{
    public function up()
    {
        $drop = [];

        foreach (['phone_otp', 'phone_otp_expires_at'] as $field) {
            if ($this->db->fieldExists($field, 'users')) {
                $drop[] = $field;
            }
        }

        if (!empty($drop)) {
            $this->forge->dropColumn('users', $drop);
        }
    }

    public function down()
    {
        $fields = [];

        if (!$this->db->fieldExists('phone_otp', 'users')) {
            $fields['phone_otp'] = [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => true,
                'after' => 'email_verified_at',
            ];
        }

        if (!$this->db->fieldExists('phone_otp_expires_at', 'users')) {
            $fields['phone_otp_expires_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'phone_otp',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('users', $fields);
        }
    }
}
