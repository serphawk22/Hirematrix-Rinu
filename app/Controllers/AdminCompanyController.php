<?php

namespace App\Controllers;

use App\Models\CompanyModel;
use CodeIgniter\Controller;

class AdminCompanyController extends Controller
{
    protected $db;
    private const CSV_HEADERS = [
        'name',
        'website',
        'industry',
        'company_type',
        'company_tags',
        'profile_status',
        'is_verified',
        'is_featured',
        'size',
        'founded_year',
        'hq',
        'branches',
        'short_description',
        'what_we_do',
        'career_page',
        'linkedin',
        'data_source_note',
    ];

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    // LIST PAGE
    public function index()
    {
        $search = $this->request->getGet('search');

        $builder = $this->db->table('companies');

        if (!empty($search)) {
            $builder->like('name', $search);
        }

        $companies = $builder
            ->orderBy('id', 'DESC')
            ->limit(100)
            ->get()
            ->getResultArray();

        return view('admin/companies', [
            'companies' => $companies,
            'search' => $search
        ]);
    }

    // SEARCH SUGGESTIONS
    public function suggestions()
    {
        $term = $this->request->getGet('term');

        $data = $this->db->table('companies')
            ->select('id, name')
            ->like('name', $term)
            ->limit(5)
            ->get()
            ->getResultArray();

        return $this->response->setJSON($data);
    }

    // FETCH SINGLE COMPANY (POPUP)
    public function getCompany($id)
    {
        $company = $this->db->table('companies')
            ->where('id', $id)
            ->get()
            ->getRowArray();

        return $this->response->setJSON($company);
    }

    public function delete($id)
{
    $this->db->table('companies')->where('id', $id)->delete();

    return $this->response->setJSON([
        'status' => 'success'
    ]);
}

    public function template()
    {
        $filename = 'company_profiles_template.csv';
        $response = $this->response;
        $response->setHeader('Content-Type', 'text/csv; charset=UTF-8');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, self::CSV_HEADERS);
        fputcsv($handle, [
            'Example Technologies',
            'https://example.com',
            'Information Technology Services',
            'Indian MNC',
            'indian mnc, services, enterprise, global indian',
            'public_seed',
            '0',
            '1',
            '1001-5000',
            '2010',
            'Bengaluru, Karnataka',
            'Bengaluru, Kochi, Chennai',
            'Short public profile summary shown to candidates.',
            'What the company does, written in candidate-friendly language.',
            'https://example.com/careers',
            'https://www.linkedin.com/company/example',
            'Imported by admin CSV. Verify before marking as verified.',
        ]);
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $response->setBody($csv ?: '');
    }

    public function import()
    {
        if (!$this->db->tableExists('companies')) {
            return redirect()->back()->with('error', 'The companies table does not exist yet. Run migrations first.');
        }

        $file = $this->request->getFile('csv_file');
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Please choose a valid CSV file.');
        }

        $extension = strtolower((string) $file->getClientExtension());
        if (!in_array($extension, ['csv', 'txt'], true)) {
            return redirect()->back()->with('error', 'Please upload a CSV file.');
        }

        $handle = fopen($file->getTempName(), 'r');
        if ($handle === false) {
            return redirect()->back()->with('error', 'Unable to read the uploaded file.');
        }

        $headerRow = fgetcsv($handle);
        if (!is_array($headerRow) || $headerRow === []) {
            fclose($handle);
            return redirect()->back()->with('error', 'The CSV file is empty.');
        }

        $headers = array_map(static function ($value): string {
            return strtolower(trim((string) $value));
        }, $headerRow);

        $companyModel = new CompanyModel();
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

            $name = trim((string) ($assoc['name'] ?? $assoc['company_name'] ?? ''));
            if ($name === '') {
                $skipped++;
                continue;
            }

            $existing = $this->db->table('companies')
                ->select('id')
                ->where('LOWER(name)', strtolower($name))
                ->get()
                ->getRowArray();

            $data = [
                'name' => $name,
                'website' => $assoc['website'] ?? '',
                'industry' => $assoc['industry'] ?? '',
                'company_type' => $assoc['company_type'] ?? '',
                'company_tags' => $assoc['company_tags'] ?? '',
                'profile_status' => $assoc['profile_status'] ?? 'public_seed',
                'is_verified' => $this->normalizeCsvBool((string) ($assoc['is_verified'] ?? '0')) ? 1 : 0,
                'is_featured' => $this->normalizeCsvBool((string) ($assoc['is_featured'] ?? '0')) ? 1 : 0,
                'size' => $assoc['size'] ?? '',
                'founded_year' => $assoc['founded_year'] ?? '',
                'hq' => $assoc['hq'] ?? '',
                'branches' => $assoc['branches'] ?? '',
                'short_description' => $assoc['short_description'] ?? '',
                'what_we_do' => $assoc['what_we_do'] ?? '',
                'career_page' => $assoc['career_page'] ?? '',
                'linkedin' => $assoc['linkedin'] ?? '',
                'data_source_note' => $assoc['data_source_note'] ?? 'Imported by admin CSV. Verify before marking as verified.',
                'contact_public' => $this->normalizeCsvBool((string) ($assoc['contact_public'] ?? '0')) ? 1 : 0,
            ];

            $companyId = $companyModel->upsertByName($name, $data);
            if ($companyId <= 0) {
                $skipped++;
                continue;
            }

            if (!empty($existing)) {
                $updated++;
            } else {
                $inserted++;
            }
        }

        fclose($handle);

        return redirect()->to(base_url('admin/companies'))
            ->with('success', "Company CSV import complete. {$inserted} added, {$updated} updated, {$skipped} skipped.");
    }

    private function normalizeCsvBool(string $value): bool
    {
        $value = strtolower(trim($value));
        return in_array($value, ['1', 'true', 'yes', 'y', 'on'], true);
    }
}
