<?php

namespace App\Database\Migrations;

use App\Models\JobModel;
use CodeIgniter\Database\Migration;

class AddExternalSourceFieldsToJobs extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('is_external', 'jobs')) {
            $this->forge->addColumn('jobs', [
                'is_external' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'after' => 'company_id',
                ],
            ]);
        }

        if (!$this->db->fieldExists('external_source', 'jobs')) {
            $this->forge->addColumn('jobs', [
                'external_source' => [
                    'type' => 'VARCHAR',
                    'constraint' => 191,
                    'null' => true,
                    'after' => 'is_external',
                ],
            ]);
        }

        if (!$this->db->fieldExists('external_apply_url', 'jobs')) {
            $this->forge->addColumn('jobs', [
                'external_apply_url' => [
                    'type' => 'VARCHAR',
                    'constraint' => 500,
                    'null' => true,
                    'after' => 'external_source',
                ],
            ]);
        }

        $this->ensureExternalJobsSystemRecruiter();
    }

    public function down()
    {
        if ($this->db->fieldExists('external_apply_url', 'jobs')) {
            $this->forge->dropColumn('jobs', 'external_apply_url');
        }

        if ($this->db->fieldExists('external_source', 'jobs')) {
            $this->forge->dropColumn('jobs', 'external_source');
        }

        if ($this->db->fieldExists('is_external', 'jobs')) {
            $this->forge->dropColumn('jobs', 'is_external');
        }
    }

    private function ensureExternalJobsSystemRecruiter(): void
    {
        if (!$this->db->tableExists('users')) {
            return;
        }

        $users = $this->db->table('users');
        $existing = $users
            ->where('email', JobModel::EXTERNAL_SYSTEM_RECRUITER_EMAIL)
            ->where('role', 'recruiter')
            ->get()
            ->getRowArray();

        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $recruiterId = (int) $existing['id'];
            $users->where('id', $recruiterId)->update([
                'name' => JobModel::EXTERNAL_SYSTEM_RECRUITER_NAME,
                'email_verified_at' => $existing['email_verified_at'] ?? $now,
                'phone_verified_at' => $existing['phone_verified_at'] ?? $now,
            ]);
        } else {
            try {
                $randomSecret = bin2hex(random_bytes(16));
            } catch (\Throwable $e) {
                $randomSecret = uniqid('external_jobs_', true);
            }

            $users->insert([
                'name' => JobModel::EXTERNAL_SYSTEM_RECRUITER_NAME,
                'email' => JobModel::EXTERNAL_SYSTEM_RECRUITER_EMAIL,
                'phone' => '0000000000',
                'password' => password_hash($randomSecret, PASSWORD_DEFAULT),
                'role' => 'recruiter',
                'email_verified_at' => $now,
                'phone_verified_at' => $now,
                'created_at' => $now,
            ]);
            $recruiterId = (int) $this->db->insertID();
        }

        if ($recruiterId <= 0 || !$this->db->tableExists('recruiter_profiles')) {
            return;
        }

        $profileTable = $this->db->table('recruiter_profiles');
        $profile = $profileTable
            ->where('user_id', $recruiterId)
            ->get()
            ->getRowArray();

        if ($profile) {
            $profileTable->where('user_id', $recruiterId)->update([
                'full_name' => JobModel::EXTERNAL_SYSTEM_RECRUITER_NAME,
                'phone' => (string) ($profile['phone'] ?? '0000000000') !== '' ? (string) ($profile['phone'] ?? '0000000000') : '0000000000',
                'designation' => (string) ($profile['designation'] ?? 'Automated Job Importer') !== ''
                    ? (string) ($profile['designation'] ?? 'Automated Job Importer')
                    : 'Automated Job Importer',
                'company_name_snapshot' => JobModel::EXTERNAL_SYSTEM_RECRUITER_NAME,
                'updated_at' => $now,
            ]);
            return;
        }

        $profileTable->insert([
            'user_id' => $recruiterId,
            'full_name' => JobModel::EXTERNAL_SYSTEM_RECRUITER_NAME,
            'phone' => '0000000000',
            'designation' => 'Automated Job Importer',
            'company_name_snapshot' => JobModel::EXTERNAL_SYSTEM_RECRUITER_NAME,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
