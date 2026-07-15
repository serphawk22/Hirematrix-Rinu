<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefreshKnownCareerPageUrls extends Migration
{
    private const DELOITTE_CAREER_URL = 'https://www.deloitte.com/global/en/careers.html';

    public function up()
    {
        if ($this->db->tableExists('companies') && $this->db->fieldExists('career_page', 'companies')) {
            $this->db->table('companies')
                ->where('LOWER(name)', 'deloitte')
                ->update(['career_page' => self::DELOITTE_CAREER_URL]);
        }

        if ($this->db->tableExists('company_ats_mappings') && $this->db->fieldExists('career_url', 'company_ats_mappings')) {
            $this->db->table('company_ats_mappings')
                ->where('company_key', 'deloitte')
                ->update([
                    'career_url' => self::DELOITTE_CAREER_URL,
                    'last_verified_at' => date('Y-m-d H:i:s'),
                ]);
        }
    }

    public function down()
    {
        // External career URLs are mutable data; do not restore a known 404 URL.
    }
}
