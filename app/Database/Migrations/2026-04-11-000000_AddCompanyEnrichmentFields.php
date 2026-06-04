<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompanyEnrichmentFields extends Migration
{
    public function up()
    {
        $fields = [
            'career_page' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'website',
            ],
            'linkedin' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'career_page',
            ],
            'twitter' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'linkedin',
            ],
            'facebook' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'twitter',
            ],
            'instagram' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'facebook',
            ],
            'youtube' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'instagram',
            ],
            'source' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
                'after' => 'youtube',
            ],
            'last_enriched_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'source',
            ],
        ];

        foreach ($fields as $fieldName => $definition) {
            if (!$this->db->fieldExists($fieldName, 'companies')) {
                $this->forge->addColumn('companies', [$fieldName => $definition]);
            }
        }
    }

    public function down()
    {
        foreach (['last_enriched_at', 'source', 'youtube', 'instagram', 'facebook', 'twitter', 'linkedin', 'career_page'] as $fieldName) {
            if ($this->db->fieldExists($fieldName, 'companies')) {
                $this->forge->dropColumn('companies', $fieldName);
            }
        }
    }
}
