<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOperationalFieldsToAdminApiUsageLogs extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('admin_api_usage_logs')) {
            return;
        }

        $fields = [];
        if (!$this->db->fieldExists('usage_units', 'admin_api_usage_logs')) {
            $fields['usage_units'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 1,
                'after' => 'total_tokens',
            ];
        }
        if (!$this->db->fieldExists('http_status_code', 'admin_api_usage_logs')) {
            $fields['http_status_code'] = [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'null' => true,
                'after' => 'usage_units',
            ];
        }
        if (!$this->db->fieldExists('latency_ms', 'admin_api_usage_logs')) {
            $fields['latency_ms'] = [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'http_status_code',
            ];
        }
        if (!$this->db->fieldExists('is_success', 'admin_api_usage_logs')) {
            $fields['is_success'] = [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'after' => 'latency_ms',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('admin_api_usage_logs', $fields);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('admin_api_usage_logs')) {
            return;
        }

        foreach (['is_success', 'latency_ms', 'http_status_code', 'usage_units'] as $field) {
            if ($this->db->fieldExists($field, 'admin_api_usage_logs')) {
                $this->forge->dropColumn('admin_api_usage_logs', $field);
            }
        }
    }
}
