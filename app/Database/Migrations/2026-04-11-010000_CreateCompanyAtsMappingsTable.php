<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompanyAtsMappingsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('company_ats_mappings')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'company_name' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'company_key' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false,
            ],
            'aliases' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'platform' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true,
            ],
            'platform_slug' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'career_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'website_url' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_enabled' => [
                'type' => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
            ],
            'priority' => [
                'type' => 'INT',
                'constraint' => 11,
                'default' => 100,
            ],
            'last_verified_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('company_key', 'company_ats_mappings_company_key_unique');
        $this->forge->addKey('platform', false);
        $this->forge->createTable('company_ats_mappings', true);

        $now = date('Y-m-d H:i:s');
        $defaults = [
            [
                'company_name' => 'Wipro',
                'company_key' => 'wipro',
                'aliases' => "Wipro Limited\nWipro Technologies",
                'platform' => 'generic',
                'platform_slug' => null,
                'career_url' => 'https://www.wipro.com/en-US/wipro-in-us/',
                'website_url' => 'https://www.wipro.com/',
                'notes' => 'Official company site; job listings may be localized or JavaScript-rendered.',
                'is_enabled' => 1,
                'priority' => 10,
                'last_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_name' => 'Infosys',
                'company_key' => 'infosys',
                'aliases' => "Infosys Limited\nInfosys Ltd",
                'platform' => 'generic',
                'platform_slug' => null,
                'career_url' => 'https://digitalcareers.infosys.com/infosys/global-careers',
                'website_url' => 'https://www.infosys.com/',
                'notes' => 'Official global careers portal.',
                'is_enabled' => 1,
                'priority' => 10,
                'last_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_name' => 'TCS',
                'company_key' => 'tcs',
                'aliases' => "Tata Consultancy Services\nTata Consultancy Services Limited",
                'platform' => 'generic',
                'platform_slug' => null,
                'career_url' => 'https://www.tcs.com/careers',
                'website_url' => 'https://www.tcs.com/',
                'notes' => 'Official careers landing page with regional career links.',
                'is_enabled' => 1,
                'priority' => 10,
                'last_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_name' => 'Accenture',
                'company_key' => 'accenture',
                'aliases' => "Accenture plc",
                'platform' => 'workday',
                'platform_slug' => null,
                'career_url' => 'https://www.accenture.com/us-en/careers',
                'website_url' => 'https://www.accenture.com/',
                'notes' => 'Accenture careers pages are region-specific and commonly route into Workday-backed job search.',
                'is_enabled' => 1,
                'priority' => 10,
                'last_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'company_name' => 'Cognizant',
                'company_key' => 'cognizant',
                'aliases' => "Cognizant Technology Solutions",
                'platform' => 'generic',
                'platform_slug' => null,
                'career_url' => 'https://careers.cognizant.com/global-en/jobs/',
                'website_url' => 'https://www.cognizant.com/',
                'notes' => 'Official job search page with searchable listings and region switches.',
                'is_enabled' => 1,
                'priority' => 10,
                'last_verified_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        $this->db->table('company_ats_mappings')->insertBatch($defaults);
    }

    public function down()
    {
        $this->forge->dropTable('company_ats_mappings', true);
    }
}
