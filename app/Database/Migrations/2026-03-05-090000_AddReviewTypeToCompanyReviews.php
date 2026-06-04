<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddReviewTypeToCompanyReviews extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('company_reviews')) {
            return;
        }

        if (!$this->db->fieldExists('review_type', 'company_reviews')) {
            $this->forge->addColumn('company_reviews', [
                'review_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'interview',
                    'after' => 'candidate_id',
                ],
            ]);
        }

        $this->db->table('company_reviews')
            ->set('review_type', 'interview')
            ->where('review_type IS NULL', null, false)
            ->orWhere('review_type', '')
            ->update();
    }

    public function down()
    {
        if (!$this->db->tableExists('company_reviews')) {
            return;
        }

        if ($this->db->fieldExists('review_type', 'company_reviews')) {
            $this->forge->dropColumn('company_reviews', 'review_type');
        }
    }
}
