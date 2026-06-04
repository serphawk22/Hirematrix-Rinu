<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnsureCompanyProfilesTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('company_profiles')) {
            return;
        }

        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'recruiter_id' => ['type' => 'INT', 'constraint' => 11],
            'company_name' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'company_logo' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'company_website' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'company_industry' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'company_size' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'company_hq' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'company_branches' => ['type' => 'TEXT', 'null' => true],
            'company_short_description' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'company_what_we_do' => ['type' => 'TEXT', 'null' => true],
            'company_mission_values' => ['type' => 'TEXT', 'null' => true],
            'company_contact_email' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'company_contact_phone' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'company_contact_public' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('recruiter_id', 'company_profiles_recruiter_unique');
        $this->forge->addForeignKey('recruiter_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('company_profiles', true);
    }

    public function down()
    {
        // Keep data safe on rollback for this safeguard migration.
    }
}
