<?php

namespace App\Controllers;

use App\Models\CompanyModel;
use App\Models\CandidateSkillsModel;
use App\Models\JobModel;
use App\Models\UserModel;
use CodeIgniter\Controller;

class Companies extends Controller
{
    private const DEFAULT_CITIES = [
        'Bangalore',
        'Hyderabad',
        'Pune',
        'Mumbai',
        'Delhi',
        'Chennai',
        'Ahmedabad',
        'Noida',
        'Gurugram',
        'Kochi',
    ];

    public function index()
    {
        $defaults = $this->getCandidateSearchDefaults();

        return view('company/companies', [
            'featuredCompanies' => $this->getFeaturedCompanies(),
            'popularRoles'      => $this->getPopularRoles(),
            'popularCities'     => $this->getPopularCities(),
            'defaultRole'       => $defaults['role'],
            'defaultCity'       => $defaults['city'],
        ]);
    }
    
    public function initLocalCompanies()
    {
        return $this->response->setJSON([
            'featuredCompanies' => $this->getFeaturedCompanies(),
            'popularRoles'      => $this->getPopularRoles(),
            'popularCities'     => $this->getPopularCities(),
        ]);
    }


    public function resolveLocation()
    {
        $latitude = filter_var($this->request->getGet('latitude'), FILTER_VALIDATE_FLOAT);
        $longitude = filter_var($this->request->getGet('longitude'), FILTER_VALIDATE_FLOAT);

        if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return $this->response->setStatusCode(422)->setJSON(['error' => 'Invalid coordinates.']);
        }

        $url = 'https://nominatim.openstreetmap.org/reverse?' . http_build_query([
            'format' => 'jsonv2',
            'lat' => $latitude,
            'lon' => $longitude,
            'zoom' => 10,
            'addressdetails' => 1,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['User-Agent: HireMatrixCandidatePortal/1.0'],
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!is_string($body) || $status >= 400) {
            return $this->response->setStatusCode(502)->setJSON(['error' => 'Unable to identify your city right now.']);
        }

        $address = (array) ((json_decode($body, true)['address'] ?? []));
        foreach (['city', 'town', 'municipality', 'village', 'county', 'state_district', 'state'] as $key) {
            $city = trim((string) ($address[$key] ?? ''));
            if ($city !== '') {
                return $this->response->setJSON(['city' => $city]);
            }
        }

        return $this->response->setStatusCode(404)->setJSON(['error' => 'Your city could not be determined.']);
    }

    public function fetchCompanies()
    {
        $role = trim((string) $this->request->getGet('role'));
        $city = trim((string) $this->request->getGet('city'));

        if ($role === '' || $city === '') {
            return $this->response->setStatusCode(422)->setJSON([
                'error' => 'Please enter both a role and a city.',
            ]);
        }

        $companies = $this->searchLocalCompanies($role, $city);

        if (empty($companies)) {
            $companies = $this->searchExternalCompanies($role, $city);
        }

        return $this->response->setJSON($companies);
    }

    public function suggest()
    {
        $term = trim((string) $this->request->getGet('term'));
        $type = trim((string) $this->request->getGet('type'));

        if (mb_strlen($term) < 2) {
            return $this->response->setJSON([]);
        }

        $suggestions = $type === 'city'
            ? $this->suggestCities($term)
            : $this->suggestRoles($term);

        return $this->response->setJSON(array_slice($suggestions, 0, 6));
    }

    private function getFeaturedCompanies(): array
    {
        try {
            $rows = (new CompanyModel())
                ->select('companies.id, companies.name, companies.logo, companies.website, companies.industry, companies.hq, companies.short_description, COUNT(jobs.id) AS open_jobs')
                ->join('jobs', "jobs.company_id = companies.id AND jobs.status = 'open'", 'left')
                ->groupBy('companies.id')
                ->orderBy('open_jobs', 'DESC')
                ->orderBy('companies.name', 'ASC')
                ->findAll(9);

            $companies = [];
            foreach ($rows as $row) {
                $companies[] = $this->formatCompanyRow($row, (string) ($row['hq'] ?? ''), (int) ($row['open_jobs'] ?? 0));
            }

            if (!empty($companies)) {
                return $companies;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Local company featured list failed: ' . $e->getMessage());
        }

        return $this->fallbackCompanies();
    }

    private function getCandidateSearchDefaults(): array
    {
        $candidateId = (int) (session()->get('user_id') ?? 0);
        if ($candidateId <= 0 || session()->get('role') !== 'candidate') {
            return ['role' => '', 'city' => ''];
        }

        try {
            $profile = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
            $city = $this->firstProfileValue((string) ($profile['preferred_locations'] ?? ''));
            if ($city === '') {
                $city = $this->firstProfileValue((string) ($profile['location'] ?? ''));
            }

            $role = $this->firstProfileValue((string) ($profile['preferred_job_titles'] ?? ''));
            if ($role === '') {
                $skills = (new CandidateSkillsModel())
                    ->where('candidate_id', $candidateId)
                    ->findAll(1);
                $role = $this->firstProfileValue((string) ($skills[0]['skill_name'] ?? $profile['key_skills'] ?? ''));
            }

            return ['role' => $role, 'city' => $city];
        } catch (\Throwable $e) {
            log_message('warning', 'Candidate local-company defaults failed: ' . $e->getMessage());
            return ['role' => '', 'city' => ''];
        }
    }

    private function firstProfileValue(string $value): string
    {
        $parts = preg_split('/[,|;\n]+/', $value) ?: [];
        return trim((string) ($parts[0] ?? ''));
    }

    private function searchLocalCompanies(string $role, string $city): array
    {
        try {
            $rows = (new JobModel())
                ->select('jobs.company_id, jobs.company, jobs.location, companies.logo, companies.website, companies.industry, companies.short_description, COUNT(jobs.id) AS open_jobs')
                ->join('companies', 'companies.id = jobs.company_id', 'left')
                ->where('jobs.status', 'open')
                ->groupStart()
                    ->like('jobs.title', $role)
                    ->orLike('jobs.category', $role)
                    ->orLike('jobs.required_skills', $role)
                ->groupEnd()
                ->like('jobs.location', $city)
                ->groupBy('jobs.company_id, jobs.company, jobs.location, companies.logo, companies.website, companies.industry, companies.short_description')
                ->orderBy('open_jobs', 'DESC')
                ->findAll(12);

            $companies = [];
            foreach ($rows as $row) {
                $companies[] = $this->formatCompanyRow($row, $city, (int) ($row['open_jobs'] ?? 0));
            }

            return $companies;
        } catch (\Throwable $e) {
            log_message('warning', 'Local company search failed: ' . $e->getMessage());
            return [];
        }
    }

    private function searchExternalCompanies(string $role, string $city): array
    {
        $apiKey = trim((string) env('TAVILY_API_KEY'));
        if ($apiKey === '') {
            return [];
        }

        $payload = [
            'query'               => $role . ' companies hiring in ' . $city . ' official careers',
            'topic'               => 'general',
            'search_depth'        => 'basic',
            'max_results'         => 12,
            'country'             => 'india',
            'include_answer'      => false,
            'include_raw_content' => false,
            'include_images'      => false,
            'include_favicon'     => true,
            'exclude_domains'     => [
                'glassdoor.co.in',
                'ambitionbox.com',
                'indeed.com',
                'naukri.com',
                'linkedin.com',
            ],
        ];

        $ch = curl_init('https://api.tavily.com/search');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_TIMEOUT        => 8,
            CURLOPT_CONNECTTIMEOUT => 4,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $status >= 400) {
            log_message('warning', 'Tavily company search failed: ' . ($error ?: 'HTTP ' . $status));
            return [];
        }

        $data = json_decode((string) $response, true);
        $results = is_array($data['results'] ?? null) ? $data['results'] : [];
        $companies = [];

        foreach ($results as $result) {
            $title = trim((string) ($result['title'] ?? ''));
            $link = trim((string) ($result['url'] ?? ''));
            if ($title === '' || !$this->looksLikeCompanyResult($title, $link)) {
                continue;
            }

            $name = $this->extractCompanyNameFromResult($title);
            if ($name === '') {
                continue;
            }

            $exists = false;
            foreach ($companies as $company) {
                if (strcasecmp((string) ($company['name'] ?? ''), $name) === 0) {
                    $exists = true;
                    break;
                }
            }
            if ($exists) {
                continue;
            }

            $companies[] = [
                'name'        => $name,
                'location'    => $city,
                'website'     => filter_var($link, FILTER_VALIDATE_URL) ? $link : '',
                'industry'    => 'Hiring lead',
                'description' => trim((string) ($result['content'] ?? 'Found from Tavily search results. Verify openings on the company careers page.')),
                'open_jobs'   => 0,
                'jobs_url'    => base_url('jobs?' . http_build_query(['company' => $name, 'location' => $city])),
                'source'      => 'web',
            ];

            if (count($companies) >= 9) {
                break;
            }
        }

        return $companies;
    }

    private function extractCompanyNameFromResult(string $title): string
    {
        $name = trim((string) preg_split('/\|| - |: /', $title)[0]);
        $name = preg_replace('/\b(careers|jobs|job openings|hiring|official careers|apply now)\b/i', '', $name) ?? $name;
        $name = preg_replace('/\s+/', ' ', $name) ?? $name;

        return trim($name, " \t\n\r\0\x0B-:|");
    }

    private function suggestRoles(string $term): array
    {
        $roles = [];

        try {
            $rows = (new JobModel())
                ->select('title')
                ->where('status', 'open')
                ->like('title', $term)
                ->groupBy('title')
                ->orderBy('title', 'ASC')
                ->findAll(12);

            foreach ($rows as $row) {
                $title = trim((string) ($row['title'] ?? ''));
                if ($title !== '') {
                    $roles[] = $title;
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Role suggestions failed: ' . $e->getMessage());
        }

        $fallback = [
            'Frontend Developer',
            'Backend Developer',
            'Full Stack Developer',
            'PHP Developer',
            'React Developer',
            'Data Analyst',
            'UI UX Designer',
            'Digital Marketing Executive',
            'Business Analyst',
            'QA Engineer',
        ];

        return $this->filterUniqueStartsOrContains(array_merge($roles, $fallback), $term);
    }

    private function suggestCities(string $term): array
    {
        $cities = [];

        try {
            $rows = (new JobModel())
                ->select('location')
                ->where('status', 'open')
                ->like('location', $term)
                ->groupBy('location')
                ->orderBy('location', 'ASC')
                ->findAll(20);

            foreach ($rows as $row) {
                foreach (preg_split('/[,|\/]+/', (string) ($row['location'] ?? '')) ?: [] as $part) {
                    $city = trim($part);
                    if ($city !== '') {
                        $cities[] = $city;
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', 'City suggestions failed: ' . $e->getMessage());
        }

        return $this->filterUniqueStartsOrContains(array_merge($cities, self::DEFAULT_CITIES), $term);
    }

    private function getPopularRoles(): array
    {
        return array_slice($this->suggestRoles('developer'), 0, 5);
    }

    private function getPopularCities(): array
    {
        return array_slice(self::DEFAULT_CITIES, 0, 6);
    }

    private function formatCompanyRow(array $row, string $location, int $openJobs): array
    {
        $name = trim((string) ($row['name'] ?? $row['company'] ?? 'Company'));

        return [
            'name'        => $name,
            'location'    => trim($location) !== '' ? trim($location) : trim((string) ($row['location'] ?? 'India')),
            'website'     => trim((string) ($row['website'] ?? '')),
            'logo'        => trim((string) ($row['logo'] ?? '')),
            'industry'    => trim((string) ($row['industry'] ?? 'Technology')),
            'description' => trim((string) ($row['short_description'] ?? 'Explore this company profile and current job openings on HireMatrix.')),
            'open_jobs'   => $openJobs,
            'jobs_url'    => base_url('jobs?' . http_build_query(['company' => $name])),
            'source'      => 'portal',
        ];
    }

    private function fallbackCompanies(): array
    {
        $rows = [
            ['name' => 'QED42', 'location' => 'Pune', 'website' => 'https://www.qed42.com', 'industry' => 'Digital Experience', 'description' => 'Product engineering and digital platform teams with strong web technology work.'],
            ['name' => 'Josh Software', 'location' => 'Pune', 'website' => 'https://www.joshsoftware.com', 'industry' => 'Software Services', 'description' => 'Engineering services company known for Ruby, cloud, and product development work.'],
            ['name' => 'BrowserStack', 'location' => 'Mumbai', 'website' => 'https://www.browserstack.com', 'industry' => 'Developer Tools', 'description' => 'Cloud testing platform with roles across engineering, product, support, and operations.'],
            ['name' => 'CleverTap', 'location' => 'Mumbai', 'website' => 'https://clevertap.com', 'industry' => 'SaaS', 'description' => 'Customer engagement platform hiring across engineering, analytics, sales, and success.'],
            ['name' => 'Darwinbox', 'location' => 'Hyderabad', 'website' => 'https://darwinbox.com', 'industry' => 'HR Tech', 'description' => 'Enterprise HR SaaS company with product, engineering, and implementation teams.'],
            ['name' => 'Razorpay', 'location' => 'Bangalore', 'website' => 'https://razorpay.com', 'industry' => 'Fintech', 'description' => 'Payments and banking platform with opportunities in engineering, risk, product, and operations.'],
        ];

        return array_map(fn (array $row): array => $this->formatCompanyRow($row, $row['location'], 0), $rows);
    }

    private function filterUniqueStartsOrContains(array $items, string $term): array
    {
        $term = strtolower($term);
        $unique = [];

        foreach ($items as $item) {
            $value = trim((string) $item);
            if ($value === '' || stripos($value, $term) === false) {
                continue;
            }

            $key = strtolower($value);
            $unique[$key] = $value;
        }

        uasort($unique, static function (string $a, string $b) use ($term): int {
            $aStarts = str_starts_with(strtolower($a), $term);
            $bStarts = str_starts_with(strtolower($b), $term);
            if ($aStarts !== $bStarts) {
                return $aStarts ? -1 : 1;
            }

            return strcasecmp($a, $b);
        });

        return array_values($unique);
    }

    private function looksLikeCompanyResult(string $title, string $link): bool
    {
        $text = strtolower($title . ' ' . $link);
        $blocked = ['top ', 'best ', 'list of', 'reviews', 'glassdoor', 'ambitionbox', 'indeed', 'naukri', 'linkedin/jobs'];

        foreach ($blocked as $word) {
            if (str_contains($text, $word)) {
                return false;
            }
        }

        return true;
    }
}
