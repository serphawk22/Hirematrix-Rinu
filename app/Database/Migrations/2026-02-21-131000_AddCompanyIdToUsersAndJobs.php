<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCompanyIdToUsersAndJobs extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('company_id', 'users')) {
            $this->forge->addColumn('users', [
                'company_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'company_name',
                ],
            ]);
        }

        if (!$this->db->fieldExists('company_id', 'jobs')) {
            $this->forge->addColumn('jobs', [
                'company_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'recruiter_id',
                ],
            ]);
        }

        // Backfill users.company_id from company_name (recruiters only).
        $recruiters = $this->db->table('users')
            ->select('id, company_name')
            ->where('role', 'recruiter')
            ->get()
            ->getResultArray();

        foreach ($recruiters as $r) {
            $companyName = trim((string) ($r['company_name'] ?? ''));
            if ($companyName === '') {
                continue;
            }

            $existing = $this->db->table('companies')
                ->select('id')
                ->where('LOWER(name)', strtolower($companyName))
                ->get()
                ->getRowArray();

            if (!$existing) {
                $this->db->table('companies')->insert([
                    'name' => $companyName,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
                $companyId = (int) $this->db->insertID();
            } else {
                $companyId = (int) $existing['id'];
            }

            $this->db->table('users')
                ->where('id', (int) $r['id'])
                ->update(['company_id' => $companyId]);
        }

        // Backfill jobs.company_id from users.company_id and keep company snapshot.
        $jobs = $this->db->table('jobs')
            ->select('id, recruiter_id, company')
            ->get()
            ->getResultArray();

        foreach ($jobs as $job) {
            $recruiter = $this->db->table('users')
                ->select('company_id')
                ->where('id', (int) $job['recruiter_id'])
                ->get()
                ->getRowArray();

            $companyId = (int) ($recruiter['company_id'] ?? 0);
            if ($companyId <= 0) {
                $companyName = trim((string) ($job['company'] ?? ''));
                if ($companyName !== '') {
                    $existing = $this->db->table('companies')
                        ->select('id')
                        ->where('LOWER(name)', strtolower($companyName))
                        ->get()
                        ->getRowArray();

                    if (!$existing) {
                        $this->db->table('companies')->insert([
                            'name' => $companyName,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s'),
                        ]);
                        $companyId = (int) $this->db->insertID();
                    } else {
                        $companyId = (int) $existing['id'];
                    }
                }
            }

            if ($companyId > 0) {
                $this->db->table('jobs')->where('id', (int) $job['id'])->update(['company_id' => $companyId]);
            }
        }

        // Add indexes and foreign keys.
        $this->db->query('ALTER TABLE users ADD INDEX users_company_id_idx (company_id)');
        $this->db->query('ALTER TABLE jobs ADD INDEX jobs_company_id_idx (company_id)');
        $this->db->query('ALTER TABLE users ADD CONSTRAINT users_company_id_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON UPDATE CASCADE ON DELETE SET NULL');
        $this->db->query('ALTER TABLE jobs ADD CONSTRAINT jobs_company_id_fk FOREIGN KEY (company_id) REFERENCES companies(id) ON UPDATE CASCADE ON DELETE SET NULL');
    }

    public function down()
    {
        try {
            $this->db->query('ALTER TABLE jobs DROP FOREIGN KEY jobs_company_id_fk');
        } catch (\Throwable $e) {
        }
        try {
            $this->db->query('ALTER TABLE users DROP FOREIGN KEY users_company_id_fk');
        } catch (\Throwable $e) {
        }
        try {
            $this->db->query('ALTER TABLE jobs DROP INDEX jobs_company_id_idx');
        } catch (\Throwable $e) {
        }
        try {
            $this->db->query('ALTER TABLE users DROP INDEX users_company_id_idx');
        } catch (\Throwable $e) {
        }

        if ($this->db->fieldExists('company_id', 'jobs')) {
            $this->forge->dropColumn('jobs', 'company_id');
        }
        if ($this->db->fieldExists('company_id', 'users')) {
            $this->forge->dropColumn('users', 'company_id');
        }
    }
}

