<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFoundedYearToCompanies extends Migration
{
    public function up(): void
    {
        $db = \Config\Database::connect();
        if (!$db->fieldExists('founded_year', 'companies')) {
            $this->forge->addColumn('companies', [
                'founded_year' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'size',
                ],
            ]);
        }
    }

    public function down(): void
    {
        $db = \Config\Database::connect();
        if ($db->fieldExists('founded_year', 'companies')) {
            $this->forge->dropColumn('companies', 'founded_year');
        }
    }
}
