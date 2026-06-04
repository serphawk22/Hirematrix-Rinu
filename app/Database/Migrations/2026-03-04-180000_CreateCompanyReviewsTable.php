<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCompanyReviewsTable extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('company_reviews')) {
            return;
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'company_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'candidate_id' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'review_type' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'interview',
            ],
            'rating' => [
                'type' => 'TINYINT',
                'constraint' => 1,
            ],
            'headline' => [
                'type' => 'VARCHAR',
                'constraint' => 180,
                'null' => true,
            ],
            'review_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'pros' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'cons' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'published',
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
        $this->forge->addKey('company_id');
        $this->forge->addKey('candidate_id');
        $this->forge->addKey('review_type');
        $this->forge->addKey(['company_id', 'candidate_id']);
        $this->forge->addForeignKey('company_id', 'companies', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('candidate_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('company_reviews', true);
    }

    public function down()
    {
        $this->forge->dropTable('company_reviews', true);
    }
}
