<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddOfficeTourFieldsToCompanies extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('companies')) {
            return;
        }

        $fields = [];

        if (!$this->db->fieldExists('office_tour_title', 'companies')) {
            $fields['office_tour_title'] = [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
                'after' => 'workplace_photos',
            ];
        }

        if (!$this->db->fieldExists('office_tour_url', 'companies')) {
            $fields['office_tour_url'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
                'after' => 'office_tour_title',
            ];
        }

        if (!$this->db->fieldExists('office_tour_summary', 'companies')) {
            $fields['office_tour_summary'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'office_tour_url',
            ];
        }

        if ($fields !== []) {
            $this->forge->addColumn('companies', $fields);
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('companies')) {
            return;
        }

        foreach (['office_tour_summary', 'office_tour_url', 'office_tour_title'] as $field) {
            if ($this->db->fieldExists($field, 'companies')) {
                $this->forge->dropColumn('companies', $field);
            }
        }
    }
}
