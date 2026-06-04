<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEmployerBrandingFieldsToCompanies extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('companies')) {
            return;
        }

        $fields = [];

        if (!$this->db->fieldExists('culture_summary', 'companies')) {
            $fields['culture_summary'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'mission_values',
            ];
        }

        if (!$this->db->fieldExists('employee_benefits', 'companies')) {
            $fields['employee_benefits'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'culture_summary',
            ];
        }

        if (!$this->db->fieldExists('workplace_photos', 'companies')) {
            $fields['workplace_photos'] = [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'employee_benefits',
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

        foreach (['workplace_photos', 'employee_benefits', 'culture_summary'] as $field) {
            if ($this->db->fieldExists($field, 'companies')) {
                $this->forge->dropColumn('companies', $field);
            }
        }
    }
}
