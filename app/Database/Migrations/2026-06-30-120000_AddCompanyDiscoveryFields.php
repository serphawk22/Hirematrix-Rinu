<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompanyDiscoveryFields extends Migration
{
    public function up(): void
    {
        $fields = [
            'company_type' => [
                'type' => 'VARCHAR',
                'constraint' => 80,
                'null' => true,
                'after' => 'industry',
            ],
            'company_tags' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'company_type',
            ],
            'profile_status' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
                'null' => false,
                'default' => 'unclaimed',
                'after' => 'company_tags',
            ],
            'is_verified' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'after' => 'profile_status',
            ],
            'is_featured' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'null' => false,
                'default' => 0,
                'after' => 'is_verified',
            ],
            'data_source_note' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'last_enriched_at',
            ],
        ];

        foreach ($fields as $fieldName => $definition) {
            if (!$this->db->fieldExists($fieldName, 'companies')) {
                $this->forge->addColumn('companies', [$fieldName => $definition]);
            }
        }
    }

    public function down(): void
    {
        foreach (['data_source_note', 'is_featured', 'is_verified', 'profile_status', 'company_tags', 'company_type'] as $fieldName) {
            if ($this->db->fieldExists($fieldName, 'companies')) {
                $this->forge->dropColumn('companies', $fieldName);
            }
        }
    }
}
