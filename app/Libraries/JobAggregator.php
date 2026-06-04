<?php

namespace App\Libraries;

use GuzzleHttp\Client;

class JobAggregator
{
    private $client;
    private $cache;

    public function __construct()
    {
        $this->client = new Client();
        $this->cache  = service('cache');
    }

    /**
     * Fetch jobs for a company — LinkedIn primary, keyword search fallback.
     */
    public function fetchJobsByCompany(string $companyName, int $limit = 25): array
    {
        $cacheKey = 'jobs_company_v2_' . strtolower(str_replace(' ', '_', $companyName));

        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $jobs = $this->fetchFromLinkedIn($companyName, $limit);
        $jobs = $this->validateJobUrls($jobs);

        $this->cache->save($cacheKey, $jobs, 21600);

        log_message('info', 'Fetched ' . count($jobs) . ' valid jobs for ' . $companyName);

        return $jobs;
    }

    /**
     * Clear cache for a specific company.
     */
    public function clearCompanyCache(string $companyName): bool
    {
        $cacheKey = 'jobs_company_v2_' . strtolower(str_replace(' ', '_', $companyName));
        return $this->cache->delete($cacheKey);
    }

    /**
     * Fetch jobs from LinkedIn public search with two endpoint attempts.
     */
    private function fetchFromLinkedIn(string $companyName, int $limit = 25): array
    {
        $jobs = $this->fetchFromLinkedInAPI($companyName, $limit);

        if (empty($jobs)) {
            $jobs = $this->fetchFromLinkedInSearch($companyName, $limit);
        }

        return $jobs;
    }

    private function fetchFromLinkedInAPI(string $companyName, int $limit = 25): array
    {
        try {
            $response = $this->client->get('https://www.linkedin.com/jobs-guest/jobs/api/seeMoreJobPostings/search', [
                'query' => [
                    'keywords' => $companyName,
                    'start'    => 0,
                    'count'    => min($limit, 25),
                ],
                'headers' => [
                    'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.9',
                    'Referer'         => 'https://www.linkedin.com/jobs/search',
                ],
                'timeout'     => 15,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            return $this->parseLinkedInJobs((string) $response->getBody(), $companyName);
        } catch (\Exception $e) {
            log_message('error', 'LinkedIn API error: ' . $e->getMessage());
            return [];
        }
    }

    private function fetchFromLinkedInSearch(string $companyName, int $limit = 25): array
    {
        try {
            $response = $this->client->get('https://www.linkedin.com/jobs/search', [
                'query' => [
                    'keywords' => $companyName,
                    'position' => 1,
                    'pageNum'  => 0,
                ],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                    'Accept'     => 'text/html',
                ],
                'timeout'     => 15,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() !== 200) {
                return [];
            }

            return $this->parseLinkedInJobs((string) $response->getBody(), $companyName);
        } catch (\Exception $e) {
            log_message('error', 'LinkedIn search error: ' . $e->getMessage());
            return [];
        }
    }

    private function parseLinkedInJobs(string $html, string $companyName): array
    {
        if (empty($html)) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath    = new \DOMXPath($dom);
        $jobCards = null;

        foreach ([
            "//li[contains(@class, 'result-card')]",
            "//div[contains(@class, 'job-search-card')]",
            "//div[contains(@class, 'base-card')]",
        ] as $selector) {
            $jobCards = $xpath->query($selector);
            if ($jobCards && $jobCards->length > 0) {
                break;
            }
        }

        if (!$jobCards || $jobCards->length === 0) {
            return [];
        }

        $jobs = [];

        foreach ($jobCards as $card) {
            try {
                $titleNode    = $xpath->query(".//h3 | .//a[contains(@class, 'job-card-list__title')]", $card)->item(0);
                $companyNode  = $xpath->query(".//h4 | .//a[contains(@class, 'job-card-container__company-name')]", $card)->item(0);
                $locationNode = $xpath->query(".//span[contains(@class, 'location')] | .//span[contains(@class, 'job-card-container__metadata-item')]", $card)->item(0);
                $linkNode     = $xpath->query(".//a[@href]", $card)->item(0);
                $dateNode     = $xpath->query(".//time", $card)->item(0);

                $title    = $titleNode    ? trim($titleNode->textContent)    : '';
                $company  = $companyNode  ? trim($companyNode->textContent)  : $companyName;
                $location = $locationNode ? trim($locationNode->textContent) : 'Remote';
                $url      = $linkNode     ? $linkNode->getAttribute('href')  : '';
                $date     = $dateNode     ? $dateNode->getAttribute('datetime') : date('Y-m-d');

                if (!empty($url) && strpos($url, 'http') !== 0) {
                    $url = 'https://www.linkedin.com' . $url;
                }

                if (preg_match('/\/jobs\/view\/(\d+)/', $url, $matches)) {
                    $url = 'https://www.linkedin.com/jobs/view/' . $matches[1];
                }

                if (!empty($title) && !empty($url)) {
                    $jobs[] = [
                        'id'              => md5($url),
                        'title'           => $title,
                        'company'         => $company,
                        'location'        => $location,
                        'summary'         => '',
                        'url'             => $url,
                        'posted_date'     => $date,
                        'salary'          => 'Not specified',
                        'job_type'        => 'Full-time',
                        'source'          => 'linkedin',
                        'external_source' => 'LinkedIn',
                        'is_external'     => 1,
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        return $jobs;
    }

    /**
     * Filter out jobs with invalid or missing URLs.
     */
    private function validateJobUrls(array $jobs): array
    {
        return array_values(array_filter($jobs, function (array $job): bool {
            $url = $job['url'] ?? '';
            return !empty($url)
                && filter_var($url, FILTER_VALIDATE_URL)
                && preg_match('/^https?:\/\//i', $url);
        }));
    }

    /**
     * Search jobs by keyword and company.
     */
    public function searchJobs(string $keyword, string $companyName = '', int $limit = 25): array
    {
        $cacheKey = 'jobs_search_' . md5($keyword . $companyName);

        if ($cached = $this->cache->get($cacheKey)) {
            return $cached;
        }

        $query = trim($keyword . ' ' . $companyName);
        $jobs  = $this->fetchFromLinkedInAPI($query, $limit);
        $jobs  = $this->validateJobUrls($jobs);

        $this->cache->save($cacheKey, $jobs, 21600);

        return $jobs;
    }
}
