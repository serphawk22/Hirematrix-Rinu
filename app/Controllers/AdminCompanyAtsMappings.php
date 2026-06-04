<?php

namespace App\Controllers;

use App\Models\CompanyAtsMappingModel;

class AdminCompanyAtsMappings extends BaseController
{
    private const CSV_HEADERS = [
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

    public function index()
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('company_ats_mappings')) {
            return view('admin/company_ats_mappings', [
                'mappings' => [],
                'editing' => null,
                'importTemplateUrl' => base_url('admin/company-ats-mappings/template'),
                'warning' => 'Run the company ATS mappings migration before editing records.',
            ]);
        }

        $model = new CompanyAtsMappingModel();
        $editId = (int) ($this->request->getGet('edit') ?: 0);

        return view('admin/company_ats_mappings', [
            'mappings' => $model->orderBy('priority', 'ASC')->orderBy('company_name', 'ASC')->paginate(10),
            'pager'    => $model->pager,
            'editing' => $editId > 0 ? $model->find($editId) : null,
            'importTemplateUrl' => base_url('admin/company-ats-mappings/template'),
        ]);
    }

    public function save()
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('company_ats_mappings')) {
            return redirect()->back()->withInput()->with('error', 'The company_ats_mappings table does not exist yet. Run the migration first.');
        }

        $rules = [
            'company_name' => 'required|min_length[2]|max_length[255]',
            'platform' => 'required|max_length[50]',
            'priority' => 'permit_empty|integer',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $model = new CompanyAtsMappingModel();
        $id = (int) ($this->request->getPost('id') ?: 0);
        $now = date('Y-m-d H:i:s');

        $data = [
            'company_name' => trim((string) $this->request->getPost('company_name')),
            'company_key' => $model->normalizeCompanyKey((string) ($this->request->getPost('company_key') ?: $this->request->getPost('company_name'))),
            'aliases' => trim((string) $this->request->getPost('aliases')),
            'platform' => trim((string) $this->request->getPost('platform')),
            'platform_slug' => trim((string) $this->request->getPost('platform_slug')),
            'career_url' => trim((string) $this->request->getPost('career_url')),
            'website_url' => trim((string) $this->request->getPost('website_url')),
            'notes' => trim((string) $this->request->getPost('notes')),
            'is_enabled' => $this->request->getPost('is_enabled') ? 1 : 0,
            'priority' => max(1, (int) ($this->request->getPost('priority') ?: 100)),
            'last_verified_at' => trim((string) $this->request->getPost('last_verified_at')) ?: null,
            'updated_at' => $now,
        ];

        if ($id > 0) {
            $existing = $model->find($id);
            if (!$existing) {
                return redirect()->back()->withInput()->with('error', 'Mapping not found.');
            }

            $model->update($id, $data);
            return redirect()->to(base_url('admin/company-ats-mappings'))->with('success', 'Mapping updated.');
        }

        $data['created_at'] = $now;
        $model->insert($data);
        return redirect()->to(base_url('admin/company-ats-mappings'))->with('success', 'Mapping added.');
    }

    public function delete(int $id)
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('company_ats_mappings')) {
            return redirect()->to(base_url('admin/company-ats-mappings'))->with('error', 'The company_ats_mappings table does not exist yet. Run the migration first.');
        }

        $model = new CompanyAtsMappingModel();
        $model->delete($id);

        return redirect()->to(base_url('admin/company-ats-mappings'))->with('success', 'Mapping deleted.');
    }

    public function template()
    {
        $filename = 'company_ats_mappings_template.csv';
        $response = $this->response;
        $response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::CSV_HEADERS);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $response->setBody($csv ?: '');
    }

    public function import()
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('company_ats_mappings')) {
            return redirect()->back()->with('error', 'The company_ats_mappings table does not exist yet. Run the migration first.');
        }

        $file = $this->request->getFile('csv_file');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Please choose a valid CSV file.');
        }

        $extension = strtolower((string) $file->getClientExtension());
        if (!in_array($extension, ['csv', 'txt'], true)) {
            return redirect()->back()->with('error', 'Please upload a CSV file.');
        }

        $path = $file->getTempName();
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return redirect()->back()->with('error', 'Unable to read the uploaded file.');
        }

        $headerRow = fgetcsv($handle);
        if (!is_array($headerRow) || $headerRow === []) {
            fclose($handle);
            return redirect()->back()->with('error', 'The CSV file is empty.');
        }

        $headers = array_map(static fn ($value): string => strtolower(trim((string) $value)), $headerRow);
        $model = new CompanyAtsMappingModel();
        $now = date('Y-m-d H:i:s');
        $inserted = 0;
        $updated = 0;
        $skipped = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($row === [null] || $row === []) {
                continue;
            }

            $assoc = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = trim((string) ($row[$index] ?? ''));
            }

            $companyName = trim((string) ($assoc['company_name'] ?? ''));
            if ($companyName === '') {
                $skipped++;
                continue;
            }

            $companyKey = trim((string) ($assoc['company_key'] ?? ''));
            $companyKey = $companyKey !== '' ? $model->normalizeCompanyKey($companyKey) : $model->normalizeCompanyKey($companyName);

            $data = [
                'company_name' => $companyName,
                'company_key' => $companyKey,
                'aliases' => trim((string) ($assoc['aliases'] ?? '')),
                'platform' => trim((string) ($assoc['platform'] ?? 'custom')) ?: 'custom',
                'platform_slug' => trim((string) ($assoc['platform_slug'] ?? '')),
                'career_url' => trim((string) ($assoc['career_url'] ?? '')),
                'website_url' => trim((string) ($assoc['website_url'] ?? '')),
                'notes' => trim((string) ($assoc['notes'] ?? '')),
                'is_enabled' => $this->normalizeCsvBool((string) ($assoc['is_enabled'] ?? '1')) ? 1 : 0,
                'priority' => max(1, (int) ($assoc['priority'] ?? 100)),
                'last_verified_at' => trim((string) ($assoc['last_verified_at'] ?? '')) ?: $now,
                'updated_at' => $now,
            ];

            $existing = $model->where('company_key', $companyKey)->first();
            if ($existing) {
                $model->update((int) $existing['id'], $data);
                $updated++;
            } else {
                $data['created_at'] = $now;
                $model->insert($data);
                $inserted++;
            }
        }

        fclose($handle);

        return redirect()->to(base_url('admin/company-ats-mappings'))
            ->with('success', "CSV import complete. {$inserted} added, {$updated} updated, {$skipped} skipped.");
    }

    private function normalizeCsvBool(string $value): bool
    {
        $value = strtolower(trim($value));
        return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
