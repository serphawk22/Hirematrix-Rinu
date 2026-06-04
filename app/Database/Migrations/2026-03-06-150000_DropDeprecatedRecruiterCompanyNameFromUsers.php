<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class DropDeprecatedRecruiterCompanyNameFromUsers extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('users')) {
            return;
        }

        if ($this->db->tableExists('recruiter_profiles') && $this->db->fieldExists('company_name', 'users')) {
            $this->db->query("UPDATE recruiter_profiles rp
                INNER JOIN users u ON u.id = rp.user_id
                LEFT JOIN companies c ON c.id = u.company_id
                SET rp.company_name_snapshot = COALESCE(rp.company_name_snapshot, c.name, u.company_name)
                WHERE u.role = 'recruiter'");
        }

        if ($this->db->fieldExists('company_name', 'users')) {
            $this->forge->dropColumn('users', 'company_name');
        }
    }

    public function down()
    {
        if (!$this->db->tableExists('users')) {
            return;
        }

        if (!$this->db->fieldExists('company_name', 'users')) {
            $this->forge->addColumn('users', [
                'company_name' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'role',
                ],
            ]);
        }

        if ($this->db->tableExists('recruiter_profiles')) {
            $this->db->query("UPDATE users u
                INNER JOIN recruiter_profiles rp ON rp.user_id = u.id
                LEFT JOIN companies c ON c.id = u.company_id
                SET u.company_name = COALESCE(c.name, rp.company_name_snapshot)
                WHERE u.role = 'recruiter'");
        }
    }
}
