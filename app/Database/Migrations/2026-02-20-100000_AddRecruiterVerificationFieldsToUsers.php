<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRecruiterVerificationFieldsToUsers extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('company_name', 'users')) {
            $fields['company_name'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'role',
            ];
        }
        if (!$this->db->fieldExists('email_verification_token', 'users')) {
            $fields['email_verification_token'] = [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
                'after' => 'company_name',
            ];
        }
        if (!$this->db->fieldExists('email_verified_at', 'users')) {
            $fields['email_verified_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'email_verification_token',
            ];
        }
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
        if (!$this->db->fieldExists('phone_verified_at', 'users')) {
            $fields['phone_verified_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'phone_otp_expires_at',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('users', $fields);
        }
    }

    public function down()
    {
        $drop = [];
        foreach ([
            'company_name',
            'email_verification_token',
            'email_verified_at',
            'phone_otp',
            'phone_otp_expires_at',
            'phone_verified_at',
        ] as $field) {
            if ($this->db->fieldExists($field, 'users')) {
                $drop[] = $field;
            }
        }

        if (!empty($drop)) {
            $this->forge->dropColumn('users', $drop);
        }
    }
}

