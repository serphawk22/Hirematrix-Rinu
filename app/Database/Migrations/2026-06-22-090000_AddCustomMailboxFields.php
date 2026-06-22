<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCustomMailboxFields extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('recruiter_mailbox_connections')) {
            return;
        }
        $fields = [
            'imap_host' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'after' => 'email'],
            'imap_port' => ['type' => 'INT', 'constraint' => 5, 'null' => true, 'after' => 'imap_host'],
            'imap_encryption' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'imap_port'],
            'smtp_host' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'after' => 'imap_encryption'],
            'smtp_port' => ['type' => 'INT', 'constraint' => 5, 'null' => true, 'after' => 'smtp_host'],
            'smtp_encryption' => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true, 'after' => 'smtp_port'],
            'mailbox_username' => ['type' => 'VARCHAR', 'constraint' => 190, 'null' => true, 'after' => 'smtp_encryption'],
            'mailbox_password' => ['type' => 'TEXT', 'null' => true, 'after' => 'mailbox_username'],
        ];
        foreach ($fields as $name => $definition) {
            if (!$this->db->fieldExists($name, 'recruiter_mailbox_connections')) {
                $this->forge->addColumn('recruiter_mailbox_connections', [$name => $definition]);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('recruiter_mailbox_connections')) {
            foreach (['imap_host', 'imap_port', 'imap_encryption', 'smtp_host', 'smtp_port', 'smtp_encryption', 'mailbox_username', 'mailbox_password'] as $field) {
                if ($this->db->fieldExists($field, 'recruiter_mailbox_connections')) {
                    $this->forge->dropColumn('recruiter_mailbox_connections', $field);
                }
            }
        }
    }
}
