<?php

namespace App\Libraries;

class ExternalJobScraperService
{
    private const DEFAULT_SOURCES = ['remotive', 'remoteok', 'arbeitnow'];

    private ExternalJobIngestionService $ingestionService;

    public function __construct(?ExternalJobIngestionService $ingestionService = null)
    {
        $this->ingestionService = $ingestionService ?? new ExternalJobIngestionService();
    }

    /**
     * @param array<int, string> $sources
     * @return array{
     *   requested_limit:int,
     *   fetched_count:int,
     *   imported_count:int,
     *   skipped_count:int,
     *   source_stats:array<string, array{fetched:int, imported:int, skipped:int, errors:array<int, string>}>
     * }
     */
    public function scrapeAndIngest(
        int $limit = 30,
        array $sources = [],
        string $keyword = '',
        string $location = ''
    ): array {
        $limit = max(1, min(100, $limit));
        $normalizedSources = $this->normalizeSources($sources);
        $sourceStats = [];
        $allJobs = [];
        $seenUrls = [];

        $fetchTargetPerSource = max(20, (int) ceil(($limit * 2) / max(1, count($normalizedSources))));

        foreach ($normalizedSources as $source) {
            $sourceStats[$source] = [
                'fetched' => 0,
                'imported' => 0,
                'skipped' => 0,
                'errors' => [],
            ];

            try {
                $rows = $this->fetchJobsBySource($source, $fetchTargetPerSource, $keyword);
                $sourceStats[$source]['fetched'] = count($rows);

                foreach ($rows as $row) {
                    $normalized = $this->normalizeExternalRow($source, $row);
                    if ($normalized === null) {
                        continue;
                    }

                    if (!$this->matchesLocation((string) $normalized['location'], $location)) {
                        continue;
                    }

                    $applyUrl = strtolower(trim((string) ($normalized['apply_url'] ?? '')));
                    if ($applyUrl === '' || isset($seenUrls[$applyUrl])) {
                        continue;
                    }
                    $seenUrls[$applyUrl] = true;
                    $allJobs[] = $normalized;
                }
            } catch (\Throwable $e) {
                $sourceStats[$source]['errors'][] = $e->getMessage();
            }
        }

        $importedCount = 0;
        $skippedCount = 0;
        $processed = 0;

        foreach ($allJobs as $job) {
            if ($processed >= $limit) {
                break;
            }

            $source = (string) ($job['source'] ?? 'unknown');
            try {
                $result = $this->ingestionService->ingestOneWithResult($job);
                if ((bool) ($result['inserted'] ?? false)) {
                    $importedCount++;
                    if (isset($sourceStats[$source])) {
                        $sourceStats[$source]['imported']++;
                    }
                } else {
                    $skippedCount++;
                    if (isset($sourceStats[$source])) {
                        $sourceStats[$source]['skipped']++;
                    }
                }
                $processed++;
            } catch (\Throwable $e) {
                if (isset($sourceStats[$source])) {
                    $sourceStats[$source]['errors'][] = $e->getMessage();
                }
            }
        }

        return [
            'requested_limit' => $limit,
            'fetched_count' => array_sum(array_map(static fn (array $stats): int => (int) ($stats['fetched'] ?? 0), $sourceStats)),
            'imported_count' => $importedCount,
            'skipped_count' => $skippedCount,
            'source_stats' => $sourceStats,
        ];
    }

    /**
     * @param array<int, string> $sources
     * @return array<int, string>
     */
    private function normalizeSources(array $sources): array
    {
        if (empty($sources)) {
            return self::DEFAULT_SOURCES;
        }

        $allowed = array_flip(self::DEFAULT_SOURCES);
        $clean = [];
        foreach ($sources as $source) {
            $source = strtolower(trim((string) $source));
            if ($source !== '' && isset($allowed[$source])) {
                $clean[] = $source;
            }
        }

        $clean = array_values(array_unique($clean));
        return empty($clean) ? self::DEFAULT_SOURCES : $clean;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchJobsBySource(string $source, int $limit, string $keyword = ''): array
    {
        return match ($source) {
            'remotive' => $this->fetchRemotiveJobs($limit, $keyword),
            'remoteok' => $this->fetchRemoteOkJobs($limit, $keyword),
            'arbeitnow' => $this->fetchArbeitnowJobs($limit, $keyword),
            default => [],
        };
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRemotiveJobs(int $limit, string $keyword = ''): array
    {
        $url = 'https://remotive.com/api/remote-jobs';
        if ($keyword !== '') {
            $url .= '?search=' . rawurlencode($keyword);
        }

        $payload = $this->requestJson($url);
        $jobs = $payload['jobs'] ?? [];
        if (!is_array($jobs)) {
            return [];
        }

        return array_slice($jobs, 0, $limit);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchRemoteOkJobs(int $limit, string $keyword = ''): array
    {
        $rows = $this->requestJson('https://remoteok.com/api');
        if (!is_array($rows)) {
            return [];
        }

        $jobs = [];
        $needle = strtolower(trim($keyword));
        foreach ($rows as $row) {
            if (!is_array($row) || isset($row['legal'])) {
                continue;
            }

            if ($needle !== '') {
                $blob = strtolower((string) ($row['position'] ?? '') . ' ' . (string) ($row['description'] ?? ''));
                if (!str_contains($blob, $needle)) {
                    continue;
                }
            }

            $jobs[] = $row;
            if (count($jobs) >= $limit) {
                break;
            }
        }

        return $jobs;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchArbeitnowJobs(int $limit, string $keyword = ''): array
    {
        $payload = $this->requestJson('https://www.arbeitnow.com/api/job-board-api');
        $jobs = $payload['data'] ?? [];
        if (!is_array($jobs)) {
            return [];
        }

        $results = [];
        $needle = strtolower(trim($keyword));
        foreach ($jobs as $row) {
            if (!is_array($row)) {
                continue;
            }

            if ($needle !== '') {
                $blob = strtolower((string) ($row['title'] ?? '') . ' ' . (string) ($row['description'] ?? ''));
                if (!str_contains($blob, $needle)) {
                    continue;
                }
            }

            $results[] = $row;
            if (count($results) >= $limit) {
                break;
            }
        }

        return $results;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>|null
     */
    private function normalizeExternalRow(string $source, array $row): ?array
    {
        $mapped = match ($source) {
            'remotive' => [
                'title' => (string) ($row['title'] ?? ''),
                'company' => (string) ($row['company_name'] ?? ''),
                'location' => (string) ($row['candidate_required_location'] ?? 'Remote'),
                'description' => (string) ($row['description'] ?? ''),
                'category' => (string) ($row['category'] ?? 'External'),
                'required_skills' => implode(', ', array_values(array_filter((array) ($row['tags'] ?? []), 'is_string'))),
                'experience_level' => 'Not specified',
                'employment_type' => (string) ($row['job_type'] ?? 'Full-time'),
                'source' => 'remotive',
                'apply_url' => (string) ($row['url'] ?? ''),
            ],
            'remoteok' => [
                'title' => (string) ($row['position'] ?? ''),
                'company' => (string) ($row['company'] ?? ''),
                'location' => (string) ($row['location'] ?? 'Remote'),
                'description' => (string) ($row['description'] ?? ''),
                'category' => 'External',
                'required_skills' => implode(', ', array_values(array_filter((array) ($row['tags'] ?? []), 'is_string'))),
                'experience_level' => 'Not specified',
                'employment_type' => 'Full-time',
                'source' => 'remoteok',
                'apply_url' => $this->resolveRemoteOkUrl((string) ($row['apply_url'] ?? $row['url'] ?? '')),
            ],
            'arbeitnow' => [
                'title' => (string) ($row['title'] ?? ''),
                'company' => (string) ($row['company_name'] ?? ''),
                'location' => (string) ($row['location'] ?? 'Remote'),
                'description' => (string) ($row['description'] ?? ''),
                'category' => 'External',
                'required_skills' => implode(', ', array_values(array_filter((array) ($row['tags'] ?? []), 'is_string'))),
                'experience_level' => 'Not specified',
                'employment_type' => 'Full-time',
                'source' => 'arbeitnow',
                'apply_url' => (string) ($row['url'] ?? ''),
            ],
            default => null,
        };

        if ($mapped === null) {
            return null;
        }

        $mapped['title'] = $this->cleanText((string) ($mapped['title'] ?? ''));
        $mapped['company'] = $this->cleanText((string) ($mapped['company'] ?? ''));
        $mapped['location'] = $this->cleanText((string) ($mapped['location'] ?? ''));
        $mapped['description'] = $this->cleanText((string) ($mapped['description'] ?? ''), 9000);
        $mapped['category'] = $this->cleanText((string) ($mapped['category'] ?? 'External'));
        $mapped['required_skills'] = $this->cleanText((string) ($mapped['required_skills'] ?? ''));
        $mapped['experience_level'] = $this->cleanText((string) ($mapped['experience_level'] ?? 'Not specified'));
        $mapped['employment_type'] = $this->cleanText((string) ($mapped['employment_type'] ?? 'Full-time'));
        $mapped['apply_url'] = trim((string) ($mapped['apply_url'] ?? ''));

        if (
            $mapped['title'] === ''
            || $mapped['company'] === ''
            || $mapped['location'] === ''
            || $mapped['description'] === ''
            || $mapped['apply_url'] === ''
            || !filter_var($mapped['apply_url'], FILTER_VALIDATE_URL)
            || $this->isBlockedPlaceholderUrl((string) $mapped['apply_url'])
        ) {
            return null;
        }

        return $mapped;
    }

    private function resolveRemoteOkUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        if (str_starts_with($url, '/')) {
            return 'https://remoteok.com' . $url;
        }

        return 'https://remoteok.com/' . ltrim($url, '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function requestJson(string $url): array
    {
        $client = \Config\Services::curlrequest([
            'timeout' => 25,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'HireMatrix External Job Importer',
                'Accept' => 'application/json,text/plain,*/*',
            ],
        ]);

        $provider = $this->resolveProviderFromUrl($url);
        $operation = 'fetch_jobs_feed';
        $usage = new UsageAnalyticsService();
        $start = microtime(true);

        try {
            $response = $client->get($url);
            $status = (int) $response->getStatusCode();
            $latencyMs = (int) round((microtime(true) - $start) * 1000);
            $usage->logExternalApiUsage(
                $provider,
                (string) parse_url($url, PHP_URL_PATH),
                $operation,
                $status,
                $latencyMs,
                1,
                $status >= 200 && $status < 400
            );

            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException('HTTP ' . $status . ' while fetching: ' . $url);
            }

            $decoded = json_decode((string) $response->getBody(), true);
            if (!is_array($decoded)) {
                throw new \RuntimeException('Invalid JSON from: ' . $url);
            }

            return $decoded;
        } catch (\Throwable $e) {
            $latencyMs = (int) round((microtime(true) - $start) * 1000);
            $usage->logExternalApiUsage(
                $provider,
                (string) parse_url($url, PHP_URL_PATH),
                $operation . '_exception',
                null,
                $latencyMs,
                1,
                false
            );
            throw $e;
        }
    }

    private function resolveProviderFromUrl(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if (str_contains($host, 'remotive.com')) {
            return 'remotive';
        }
        if (str_contains($host, 'remoteok.com')) {
            return 'remoteok';
        }
        if (str_contains($host, 'arbeitnow.com')) {
            return 'arbeitnow';
        }

        return 'external';
    }

    private function cleanText(string $value, int $maxLength = 255): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        $value = trim($value);
        return mb_substr($value, 0, $maxLength);
    }

    private function matchesLocation(string $jobLocation, string $locationFilter): bool
    {
        $locationFilter = strtolower(trim($locationFilter));
        if ($locationFilter === '') {
            return true;
        }

        return str_contains(strtolower($jobLocation), $locationFilter);
    }

    private function isBlockedPlaceholderUrl(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return true;
        }

        $blockedHosts = [
            'example.com',
            'example.org',
            'example.net',
            'partner.example.com',
        ];

        if (in_array($host, $blockedHosts, true)) {
            return true;
        }

        foreach ($blockedHosts as $blockedHost) {
            if (str_ends_with($host, '.' . $blockedHost)) {
                return true;
            }
        }

        return false;
    }
}
