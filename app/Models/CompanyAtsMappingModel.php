<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyAtsMappingModel extends Model
{
    protected $table = 'company_ats_mappings';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_name',
        'company_key',
        'aliases',
        'platform',
        'platform_slug',
        'career_url',
        'website_url',
        'notes',
        'is_enabled',
        'priority',
        'last_verified_at',
    ];

    public function normalizeCompanyKey(string $companyName): string
    {
        $key = strtolower(trim($companyName));
        $key = preg_replace('/[^a-z0-9]+/', '-', $key) ?? '';
        return trim($key, '-');
    }

    /**
     * Normalize a company name for loose matching against aliases and display names.
     * This strips punctuation and common business suffixes so "Wipro Ltd" can match "Wipro".
     */
    public function normalizeCompanyMatchKey(string $companyName): string
    {
        $key = strtolower(trim($companyName));
        $key = preg_replace('/[^a-z0-9]+/', ' ', $key) ?? '';
        $key = preg_replace('/\b(limited|ltd|inc|llc|llp|plc|corp|corporation|company|co|technologies|technology|solutions|services|systems|group|holdings)\b/', ' ', $key) ?? '';
        $key = preg_replace('/\s+/', ' ', $key) ?? '';
        return trim($key);
    }

    private function splitAliases(string $aliases): array
    {
        if (trim($aliases) === '') {
            return [];
        }

        $parts = preg_split('/[|,\r\n;]+/', $aliases) ?: [];
        return array_values(array_filter(array_map('trim', $parts)));
    }

    public function findMatchingMapping(string $companyName): ?array
    {
        $normalized = $this->normalizeCompanyKey($companyName);
        $matchKey = $this->normalizeCompanyMatchKey($companyName);
        if ($normalized === '') {
            return null;
        }

        $rows = $this->where('is_enabled', 1)
            ->orderBy('priority', 'ASC')
            ->orderBy('company_name', 'ASC')
            ->findAll();

        foreach ($rows as $row) {
            $rowKey = $this->normalizeCompanyKey((string) ($row['company_key'] ?? $row['company_name'] ?? ''));
            $rowName = strtolower(trim((string) ($row['company_name'] ?? '')));
            $rowMatchKey = $this->normalizeCompanyMatchKey((string) ($row['company_name'] ?? ''));
            $aliases = $this->splitAliases((string) ($row['aliases'] ?? ''));
            $aliasMatchKeys = array_map(fn (string $alias): string => $this->normalizeCompanyMatchKey($alias), $aliases);

            if ($rowKey !== '' && ($rowKey === $normalized || str_contains($rowKey, $normalized) || str_contains($normalized, $rowKey))) {
                return $row;
            }

            if ($rowMatchKey !== '' && ($rowMatchKey === $matchKey || str_contains($rowMatchKey, $matchKey) || str_contains($matchKey, $rowMatchKey))) {
                return $row;
            }

            foreach ($aliasMatchKeys as $aliasMatchKey) {
                if ($aliasMatchKey === '') {
                    continue;
                }

                if ($aliasMatchKey === $matchKey || str_contains($aliasMatchKey, $matchKey) || str_contains($matchKey, $aliasMatchKey)) {
                    return $row;
                }
            }
        }

        return null;
    }

    public function upsertByCompanyName(string $companyName, array $data): int
    {
        $companyKey = $this->normalizeCompanyKey($companyName);
        $data['company_name'] = trim($data['company_name'] ?? $companyName);
        $data['company_key'] = $data['company_key'] ?? $companyKey;

        $existing = $this->where('company_key', $companyKey)->first();
        if ($existing) {
            $this->update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        return (int) $this->insert($data, true);
    }
}
