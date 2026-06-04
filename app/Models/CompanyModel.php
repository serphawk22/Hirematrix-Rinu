<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyModel extends Model
{
    protected $table = 'companies';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name',
        'logo',
        'website',
        'career_page',
        'linkedin',
        'twitter',
        'facebook',
        'instagram',
        'youtube',
        'industry',
        'size',
        'founded_year',
        'hq',
        'branches',
        'short_description',
        'what_we_do',
        'mission_values',
        'culture_summary',
        'employee_benefits',
        'workplace_photos',
        'office_tour_title',
        'office_tour_url',
        'office_tour_summary',
        'contact_email',
        'contact_phone',
        'contact_public',
        'source',
        'last_enriched_at',
    ];

    public function upsertByName(string $name, array $data): int
    {
        $data = $this->filterToExistingFields($data);
        $name = trim($name);

        // Always try to find existing first (case-insensitive)
        $existing = $this->db->table($this->table)
            ->select('id')
            ->where('LOWER(name)', strtolower($name))
            ->get()
            ->getRowArray();

        if (!empty($existing['id'])) {
            $this->update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        // Also try exact match to catch case-sensitive unique key collisions
        $existing = $this->db->table($this->table)
            ->select('id')
            ->where('name', $name)
            ->get()
            ->getRowArray();

        if (!empty($existing['id'])) {
            $this->update((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        try {
            return (int) $this->insert($data, true);
        } catch (\Throwable $e) {
            // Race condition: another request inserted between our SELECT and INSERT
            // Fetch again and update instead
            $existing = $this->db->table($this->table)
                ->select('id')
                ->where('LOWER(name)', strtolower($name))
                ->get()
                ->getRowArray();

            if (!empty($existing['id'])) {
                $this->update((int) $existing['id'], $data);
                return (int) $existing['id'];
            }

            log_message('error', 'CompanyModel::upsertByName failed for "' . $name . '": ' . $e->getMessage());
            return 0;
        }
    }

    public function normalizeBrandKey(string $name): string
    {
        $value = strtolower(trim($name));
        if ($value === '') {
            return '';
        }

        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $stopWords = [
            'limited', 'ltd', 'llc', 'inc', 'incorporated', 'corp', 'corporation',
            'pvt', 'private', 'public', 'solutions', 'solution', 'technologies',
            'technology', 'technovations', 'systems', 'services', 'service',
            'global', 'group', 'company', 'co', 'the',
        ];

        $parts = preg_split('/\s+/', trim($value)) ?: [];
        $parts = array_values(array_filter($parts, static function (string $part) use ($stopWords): bool {
            return $part !== '' && !in_array($part, $stopWords, true);
        }));

        if (empty($parts)) {
            return '';
        }

        return implode(' ', $parts);
    }

    private function filterToExistingFields(array $data): array
    {
        $fieldNames = [];

        try {
            $fieldNames = $this->db->getFieldNames($this->table) ?: [];
        } catch (\Throwable $e) {
            return $data;
        }

        if (empty($fieldNames)) {
            return $data;
        }

        $allowed = array_flip($fieldNames);
        return array_intersect_key($data, $allowed);
    }
}
