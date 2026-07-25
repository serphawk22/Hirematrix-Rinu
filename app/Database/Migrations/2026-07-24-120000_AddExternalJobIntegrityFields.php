<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddExternalJobIntegrityFields extends Migration
{
    public function up(): void
    {
        foreach (['jobs', 'mnc_external_jobs'] as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            $fields = [
                'external_url_hash' => ['type' => 'CHAR', 'constraint' => 64, 'null' => true],
                'external_last_checked_at' => ['type' => 'DATETIME', 'null' => true],
                'external_failure_count' => ['type' => 'INT', 'constraint' => 10, 'unsigned' => true, 'default' => 0],
                'external_validation_status' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            ];
            foreach ($fields as $name => $definition) {
                if (!$this->db->fieldExists($name, $table)) {
                    $this->forge->addColumn($table, [$name => $definition]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['jobs', 'mnc_external_jobs'] as $table) {
            if (!$this->db->tableExists($table)) {
                continue;
            }
            foreach (['external_validation_status', 'external_failure_count', 'external_last_checked_at', 'external_url_hash'] as $field) {
                if ($this->db->fieldExists($field, $table)) {
                    $this->forge->dropColumn($table, $field);
                }
            }
        }
    }
}
