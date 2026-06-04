<?php

namespace App\Libraries;

/**
 * MncJobIngestor: AI-powered discovery of MNC jobs via Search + LLM.
 */
class MncJobIngestor
{
    private $apiKey;
    private $tavilyApiKey;

    public function __construct()
    {
        $this->apiKey = getenv('OPENAI_API_KEY');
        $this->tavilyApiKey = getenv('TAVILY_API_KEY');
    }

    /**
     * Discovers jobs for a specific MNC. Official career pages are preferred;
     * LinkedIn public search is only used when official sources produce no jobs.
     *
     * @param array<string, mixed>|null $mapping
     * @param array<string, mixed>|null $companyInfo
     * @return array<int, array<string, mixed>>
     */
    public function discoverJobs(string $companyName, int $limit = 10, ?array $mapping = null, ?array $companyInfo = null): array
    {
        try {
            \Config\Database::connect()->reconnect();
        } catch (\Throwable $e) {
            log_message('error', 'MncJobIngestor: Initial DB reconnect failed: ' . $e->getMessage());
        }

        $limit = max(1, min(100, $limit));
        $careerUrls = $this->buildOfficialCareerUrlCandidates($companyName, $mapping, $companyInfo);

        $failedHosts = [];

        // Step 1: Visit official career pages directly and extract job posting links.
        foreach ($careerUrls as $careerUrl) {
            $host = parse_url($careerUrl, PHP_URL_HOST);
            if (isset($failedHosts[$host])) continue;

            $officialPageText = $this->fetchOfficialCareerPageText($companyName, $careerUrl, $limit);
            if ($officialPageText === '') $failedHosts[$host] = true;
            
            if ($officialPageText !== '') {
                \Config\Database::connect()->reconnect();
                $jobs = $this->parseResultsWithAi($companyName, $officialPageText, $limit, $careerUrl, false);
                if (!empty($jobs)) {
                    return $this->tagJobsWithSource($jobs, $careerUrl);
                }
            }
        }

        // Step 2: If a career page is dynamic, search only the official domain/ATS.
        foreach ($careerUrls as $careerUrl) {
            $officialSnippets = $this->fetchPublicSearchSnippetsForDomain($companyName, $careerUrl, $limit);
            if ($officialSnippets !== '') {
                \Config\Database::connect()->reconnect();
                $jobs = $this->parseResultsWithAi($companyName, $officialSnippets, $limit, $careerUrl, false);
                if (!empty($jobs)) {
                    return $this->tagJobsWithSource($jobs, $careerUrl);
                }
            }
        }

        // Step 3: Company-specific LinkedIn jobs page. This avoids keyword-only matches.
        foreach ($this->buildLinkedInCompanyJobsUrlCandidates($companyName, $companyInfo) as $linkedinJobsUrl) {
            $linkedinCompanyJobsText = $this->fetchLinkedInCompanyJobsPageText($companyName, $linkedinJobsUrl, $limit);
            if ($linkedinCompanyJobsText !== '') {
                $jobs = $this->parseResultsWithAi($companyName, $linkedinCompanyJobsText, $limit, $linkedinJobsUrl, true);
                if (!empty($jobs)) {
                    return $this->tagJobsWithSource($jobs, 'LinkedIn');
                }
            }
        }

        // Step 4: Structured Google Jobs fallback, filtered by employer name.
        $googleJobsText = $this->fetchGoogleJobsSnippets($companyName, $limit);
        if ($googleJobsText !== '') {
            $jobs = $this->parseResultsWithAi($companyName, $googleJobsText, $limit, null, true);
            if (!empty($jobs)) {
                return $this->tagJobsWithSource($jobs, 'Google Jobs');
            }
        }

        // Step 5: Final fallback only: broad LinkedIn public search.
        $linkedinSnippets = $this->fetchPublicSearchSnippetsForLinkedIn($companyName, $limit);
        if ($linkedinSnippets !== '') {
            return $this->tagJobsWithSource(
                $this->parseResultsWithAi($companyName, $linkedinSnippets, $limit, null, true),
                'LinkedIn'
            );
        }

        // Step 6: Fallback to other general job boards (Indeed, Glassdoor)
        $aggregatorSnippets = $this->fetchPublicSearchSnippetsForAggregators($companyName, $limit);
        if ($aggregatorSnippets !== '') {
            return $this->tagJobsWithSource(
                $this->parseResultsWithAi($companyName, $aggregatorSnippets, $limit, null, true), // allowAggregators = true for general aggregators
                'Aggregators'
            );
        }

        return [];
    }

    /**
     * @param array<string, mixed>|null $mapping
     * @param array<string, mixed>|null $companyInfo
     * @return array<int, string>
     */
    private function buildOfficialCareerUrlCandidates(string $companyName, ?array $mapping, ?array $companyInfo): array
    {
        $urls = [];

        foreach ([
            $mapping['career_url'] ?? null,
            $companyInfo['career_page'] ?? null,
            $companyInfo['website'] ?? null,
            $mapping['website_url'] ?? null,
        ] as $url) {
            $normalized = $this->normalizeUrl((string) $url);
            if ($normalized !== '') {
                $urls[] = $normalized;
            }
        }

        $discovered = $this->discoverOfficialCareerUrl($companyName);
        if ($discovered !== '') {
            $urls[] = $discovered;
        }

        $expanded = [];
        foreach (array_unique($urls) as $url) {
            $expanded[] = $url;
            $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
            if ($path === '') {
                $base = rtrim($url, '/');
                foreach (['careers', 'career', 'jobs', 'en/careers'] as $suffix) {
                    $expanded[] = $base . '/' . $suffix;
                }
            }
        }

        return array_values(array_unique($expanded));
    }

    /**
     * @param array<string, mixed>|null $companyInfo
     * @return array<int, string>
     */
    private function buildLinkedInCompanyJobsUrlCandidates(string $companyName, ?array $companyInfo): array
    {
        $urls = [];
        $linkedinUrl = $this->normalizeUrl((string) ($companyInfo['linkedin'] ?? ''));
        if ($linkedinUrl !== '') {
            $linkedinUrl = preg_replace('#/about/?$#i', '', $linkedinUrl) ?? $linkedinUrl;
            $linkedinUrl = preg_replace('#/jobs/?$#i', '', $linkedinUrl) ?? $linkedinUrl;
            $urls[] = rtrim($linkedinUrl, '/') . '/jobs';
        }

        $slug = $this->slugifyCompanyName($companyName);
        if ($slug !== '') {
            foreach (['www', 'in', 'ca', 'uk'] as $region) {
                $urls[] = 'https://' . $region . '.linkedin.com/company/' . $slug . '/jobs';
            }
        }

        return array_values(array_unique($urls));
    }

    private function fetchLinkedInCompanyJobsPageText(string $companyName, string $linkedinJobsUrl, int $limit): string
    {
        $html = $this->curlGet($linkedinJobsUrl, 20);
        if ($html === '') {
            return '';
        }

        $links = $this->extractLikelyJobLinks($html, $linkedinJobsUrl);
        if (empty($links)) {
            return '';
        }

        $textBlock = "LinkedIn company jobs page inspected for {$companyName}: {$linkedinJobsUrl}\n";
        $count = 0;
        foreach ($links as $link) {
            $count++;
            $textBlock .= "LinkedIn Company Job (Employer: {$companyName}): Title: {$link['text']}. Apply Link: {$link['url']}. Posted: Recently.\n";
            if ($count >= max($limit * 3, 30)) {
                break;
            }
        }

        return $textBlock;
    }

    private function fetchGoogleJobsSnippets(string $company, int $limit): string
    {
        $results = $this->tavilySearch("jobs at " . $company, max($limit * 3, 30), 'basic');
        if (empty($results)) return '';

        $textBlock = '';
        foreach ($results as $job) {
            $textBlock .= "Tavily Search Result (Job Context): Title: " . ($job['title'] ?? '') .
                ". Employer: " . $company .
                ". Apply Link: " . ($job['url'] ?? '') .
                ". Description: " . ($job['content'] ?? '') . "\n";
        }
        return $textBlock;
    }

    private function discoverOfficialCareerUrl(string $companyName): string
    {
        $results = $this->tavilySearch('"' . $companyName . '" (careers OR jobs) official landing page', 8, 'basic');
        if (empty($results)) return '';

        foreach ($results as $result) {
            $link = $this->normalizeUrl((string) ($result['url'] ?? ''));
            if ($link === '' || $this->isLinkedInUrl($link)) {
                continue;
            }

            $linkLower = strtolower($link);
            $path = (string) parse_url($linkLower, PHP_URL_PATH);
            // Prioritize URLs that belong to a specific career/job section
            if (preg_match('/(career|job|opening|join|work-with|recruit)/i', $path) || str_contains($linkLower, 'careers.') || str_contains($linkLower, 'jobs.')) {
                 return $link;
            }
        }

        return '';
    }

    private function fetchOfficialCareerPageText(string $companyName, string $careerUrl, int $limit): string
    {
        $html = $this->curlGet($careerUrl, 20);
        if ($html === '') {
            return '';
        }

        $links = $this->extractLikelyJobLinks($html, $careerUrl);
        if (empty($links)) {
            return '';
        }

        $sourceHost = parse_url($careerUrl, PHP_URL_HOST) ?: $careerUrl;
        $textBlock = "Official career page inspected for {$companyName}: {$careerUrl}\n";
        $count = 0;

        foreach ($links as $link) {
            $count++;
            $textBlock .= "Official Career Page Link (Source: {$sourceHost}): Title/Text: {$link['text']}. Link: {$link['url']}.\n";
            if ($count >= max($limit * 5, 50)) {
                break;
            }
        }

        return $textBlock;
    }

    /**
     * @return array<int, array{text: string, url: string}>
     */
    private function extractLikelyJobLinks(string $html, string $baseUrl): array
    {
        $links = [];
        $seen = [];

        $previous = libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $loaded = $dom->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return [];
        }

        foreach ($dom->getElementsByTagName('a') as $anchor) {
            $href = trim((string) $anchor->getAttribute('href'));
            $text = $this->cleanText($anchor->textContent ?? '');
            if ($href === '' || str_starts_with($href, '#') || str_starts_with(strtolower($href), 'javascript:')) {
                continue;
            }

            $url = $this->resolveUrl($href, $baseUrl);
            if ($url === '' || isset($seen[$url]) || !$this->looksLikeJobPostingUrl($url, $text)) {
                continue;
            }

            $seen[$url] = true;
            $links[] = [
                'text' => $text !== '' ? $text : $url,
                'url' => $url,
            ];
        }

        return $links;
    }

    private function looksLikeJobPostingUrl(string $url, string $text): bool
    {
        $haystack = strtolower($url . ' ' . $text);
        $positivePatterns = [
            'jobdetail', 'job-detail', 'jobs/view', '/job/', '/jobs/', '/careers/job',
            '/company-job/description/',
            'requisition', 'reqid', 'jobid', 'job_id', 'gh_jid=', 'lever.co/',
            'greenhouse.io/', 'myworkdayjobs.com/', 'smartrecruiters.com/',
            'successfactors.', 'icims.com/', 'ashbyhq.com/', 'boards.greenhouse.io/',
        ];

        $hasPositive = false;
        foreach ($positivePatterns as $pattern) {
            if (str_contains($haystack, $pattern)) {
                $hasPositive = true;
                break;
            }
        }

        if (!$hasPositive) {
            return false;
        }

        $genericTexts = [
            'view all jobs', 'search jobs', 'all jobs', 'job search', 'careers',
            'join our talent community', 'privacy', 'terms', 'cookie',
        ];
        $textLower = strtolower(trim($text));
        foreach ($genericTexts as $genericText) {
            if ($textLower === $genericText) {
                return false;
            }
        }

        return true;
    }

    private function fetchPublicSearchSnippetsForDomain(string $company, string $siteUrl, int $limit = 10): string
    {
        if (!$this->tavilyApiKey) {
            log_message('error', 'MncJobIngestor: TAVILY_API_KEY is missing in .env for domain search');
            return '';
        }

        $limit = max(1, min(100, $limit));
        $numResults = max(10, min(100, $limit * 3));

        $host = parse_url($siteUrl, PHP_URL_HOST) ?: $siteUrl;
        $query = "site:$host \"$company\" (\"job description\" OR \"apply now\" OR reqid OR requisition OR \"job id\" OR inurl:job OR inurl:jobs)";
        $sourcePlatform = $host;

        // Domain searches benefit from advanced depth to find deep job links
        return $this->performTavilySearchAndFormat($query, $numResults, $sourcePlatform, true, 'advanced');
    }

    private function fetchPublicSearchSnippetsForLinkedIn(string $company, int $limit = 10): string
    {
        if (!$this->tavilyApiKey) {
            log_message('error', 'MncJobIngestor: TAVILY_API_KEY is missing in .env for LinkedIn search');
            return '';
        }

        $limit = max(1, min(100, $limit));
        $numResults = max(10, min(100, $limit * 3));

        // Combine queries to reduce API round-trips
        $query = "site:linkedin.com/jobs/ \"$company\" (view OR search) jobs openings";
        $sourcePlatform = 'LinkedIn';

        // LinkedIn fallback works fine with 'basic' depth
        return $this->performTavilySearchAndFormat($query, $numResults, $sourcePlatform, true, 'basic');
    }

    private function fetchPublicSearchSnippetsForAggregators(string $company, int $limit = 10): string
    {
        if (!$this->tavilyApiKey) {
            log_message('error', 'MncJobIngestor: TAVILY_API_KEY is missing in .env for aggregator search');
            return '';
        }

        $limit = max(1, min(100, $limit));
        $numResults = max(10, min(100, $limit * 3));

        // One query for all aggregators
        $query = "\"$company\" (jobs OR careers) (site:indeed.com OR site:glassdoor.com OR site:naukri.com)";
        $sourcePlatform = 'Aggregators';

        // Aggregator fallback works fine with 'basic' depth
        return $this->performTavilySearchAndFormat($query, $numResults, $sourcePlatform, false, 'basic');
    }

    private function parseResultsWithAi(string $company, string $rawText, int $limit, ?string $officialSourceUrl = null, bool $allowAggregators = false): array
    {
        if (!$this->apiKey) return [];

        $jobs = $this->extractDirectJobsFromSearchText($company, $rawText, $limit);
        if (count($jobs) >= $limit) {
            log_message('info', "MncJobIngestor: Successfully extracted " . count($jobs) . " jobs for $company via direct search parsing (no AI needed).");
            return array_values($jobs);
        }

        log_message('info', "MncJobIngestor: Direct extraction found " . count($jobs) . " jobs for $company. Proceeding to AI for remaining " . ($limit - count($jobs)) . " slots.");

        foreach ($this->splitTextIntoChunks($rawText, 4500) as $chunk) {
            $chunkJobs = $this->parseChunkWithAi($company, $chunk, $limit - count($jobs), $officialSourceUrl, $allowAggregators);
            foreach ($chunkJobs as $job) {
                $applyUrl = trim((string) ($job['apply_url'] ?? ''));
                if ($applyUrl === '') {
                    continue;
                }

                $jobs[$applyUrl] = $job;
                if (count($jobs) >= $limit) {
                    break 2;
                }
            }
        }

        return array_values($jobs);
    }

    /**
     * Extract obvious direct job results before using AI. This helps for search
     * results like "ITC Infotech hiring SAP Consultant..." with a /jobs/view/ URL.
     *
     * @return array<string, array<string, mixed>>
     */
    private function extractDirectJobsFromSearchText(string $company, string $rawText, int $limit): array
    {
        $jobs = [];
        $companyPattern = preg_quote($company, '/');
        $lines = preg_split('/\r\n|\r|\n/', $rawText) ?: [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'Tavily Search Result (Job Context):')) {
                if (preg_match('/Title:\s*(.*?)\.\s*Employer:\s*(.*?)\.\s*Apply Link:\s*(.*?)\.\s*Description:/i', $line, $matches) !== 1) {
                    continue;
                }

                $title = trim($matches[1]);
                $employer = trim($matches[2]);
                $applyUrl = trim($matches[3]);

                if ($title === '' || $employer === '' || $applyUrl === '') {
                    continue;
                }

                $jobs[$applyUrl] = [
                    'title' => $title,
                    'employer' => $employer,
                    'location' => 'Not specified',
                    'apply_url' => $applyUrl,
                    'posted_at_raw' => 'Recently',
                    'source_platform' => 'Search Discovery',
                ];

                if (count($jobs) >= $limit) {
                    break;
                }

                continue;
            }

            if (stripos($line, '/jobs/') === false) {
                continue;
            }

            if (preg_match('/Title:\s*(.*?)\.\s*Link:\s*(.*?)\.\s*Snippet:\s*(.*)$/i', $line, $matches) !== 1) {
                continue;
            }

            $titleText = trim($matches[1]);
            $applyUrl = trim($matches[2]);
            $snippet = trim($matches[3]);
            if ($titleText === '' || $applyUrl === '') {
                continue;
            }

            $roleTitle = $titleText;
            if (preg_match('/^' . $companyPattern . '\s+hiring\s+(.+?)(?:\s+in\s+.+)?(?:\s+\|\s+LinkedIn)?$/i', $titleText, $titleMatches) === 1) {
                $roleTitle = trim($titleMatches[1]);
            } elseif (preg_match('/^(.+?)\s+at\s+' . $companyPattern . '(?:\s+\|\s+LinkedIn)?$/i', $titleText, $titleMatches) === 1) {
                $roleTitle = trim($titleMatches[1]);
            }

            $roleTitle = preg_replace('/\s+\|\s+LinkedIn$/i', '', $roleTitle) ?? $roleTitle;
            $roleTitle = trim($roleTitle);
            if ($roleTitle === '' || strcasecmp($roleTitle, $company) === 0) {
                continue;
            }

            $location = 'Not specified';
            if (preg_match('/' . $companyPattern . '\s+(.+?)(?:\s+\d+\s+(?:minute|hour|day|week|month)s?\s+ago|\s+Over\s+\d+|\s+See who|\s+\[Button|\s*$)/i', $snippet, $locationMatches) === 1) {
                $candidateLocation = trim($locationMatches[1]);
                if ($candidateLocation !== '') {
                    $location = $candidateLocation;
                }
            }

            $jobs[$applyUrl] = [
                'title' => $roleTitle,
                'employer' => $company,
                'location' => $location,
                'apply_url' => $applyUrl,
                'posted_at_raw' => 'Recently',
                'source_platform' => 'LinkedIn',
            ];

            if (count($jobs) >= $limit) {
                break;
            }
        }

        return $jobs;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseChunkWithAi(string $company, string $rawText, int $limit, ?string $officialSourceUrl, bool $allowLinkedIn): array
    {
        if ($limit <= 0 || !$this->apiKey) return [];

        $sourceRule = $allowLinkedIn
            ? "LinkedIn, Indeed, and Glassdoor public job pages are allowed because official sources did not return jobs. For these results, assume the employer is '$company' unless explicitly stated otherwise in the snippet."
            : "Only extract jobs from the official company career site or its official ATS source. Do not use LinkedIn, Indeed, Glassdoor, or other third-party aggregator links.";

        $prompt = "Act as a high-precision recruitment data extractor for MNC career portals.
        I will provide search result snippets for jobs at '$company'. 
        Your task is to identify and extract a maximum of $limit specific, active job openings.
        Prioritize job postings that appear to be from the company's official career site or a reputable ATS.
        Official source URL/domain when available: " . ($officialSourceUrl ?? 'Not provided') . ".
        $sourceRule

        Return a JSON object with a key 'jobs' containing an array of job objects.
        Each job object must have: 'title' (the specific role name), 'employer' (the hiring company), 'location' (City, State/Country), 'apply_url' (the direct link to the posting), 'posted_at_raw' (relative time like '2 days ago'), and 'source_platform'.

        CRITICAL RULES (apply these strictly):
        1. ONLY extract individual job roles. Absolutely DO NOT extract generic search results, career page links, or company overview pages. Examples of what to AVOID as a 'title': 'Jobs in USA', 'Careers at $company', 'Hiring at $company', 'Job Openings', 'Workday Careers'.
        2. The 'title' MUST be a specific job position (e.g., 'Senior Software Engineer', 'Product Manager', 'Data Scientist').
        3. The 'apply_url' MUST be a direct link to the specific job posting, not a general career page. Prioritize links containing ATS domains (e.g., 'myworkdayjobs.com', 'greenhouse.io', 'lever.co', 'jobs.smartrecruiters.com') or direct links from the company's own domain.
        4. If 'location' is not clearly visible for a specific job, use 'Global' or 'Remote' if implied, otherwise 'Not specified'.
        5. Ensure 'posted_at_raw' is a relative time (e.g., '2 days ago', '3 weeks ago'). If not available, use 'Recently'.
        6. The employer must be '$company' or an obvious same-company alias. If the employer is different, skip that job.
        
        TEXT TO ANALYZE:
        " . $rawText;

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.2
            ]), 
            CURLOPT_SSL_VERIFYPEER => (ENVIRONMENT !== 'development'), 
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false) {
            log_message('error', 'MncJobIngestor: OpenAI cURL Error: ' . $error);
            return [];
        }

        $data = json_decode($response, true);
        if ($httpCode !== 200 || isset($data['error'])) {
            $msg = is_array($data) ? ($data['error']['message'] ?? 'Unknown error') : 'Response not JSON';
            log_message('error', "MncJobIngestor: OpenAI API Error (HTTP $httpCode) for $company: $msg. Full Response: " . $response);
            return [];
        }

        $finishReason = $data['choices'][0]['finish_reason'] ?? 'unknown';
        if ($finishReason === 'length') {
            log_message('warning', "MncJobIngestor: AI response for $company was truncated due to length limits.");
        }

        $content = $data['choices'][0]['message']['content'] ?? null;
        if (!$content) {
            log_message('error', "MncJobIngestor: OpenAI returned empty content for $company.");
            return [];
        }

        $result = json_decode($content, true);
        if ($result === null) {
            log_message('error', "MncJobIngestor: Failed to decode JSON from AI content for $company. Error: " . json_last_error_msg() . ". Content preview: " . substr($content, 0, 500));
            return [];
        }

        // Handle cases where AI returns a wrapper key
        $jobs = $result['jobs'] ?? $result['openings'] ?? (is_array($result) ? $result : []);
        if (empty($jobs)) {
            log_message('notice', "MncJobIngestor: AI extraction completed for $company but 0 jobs were found in the text chunk.");
        }

        return $jobs;
    }

    /**
     * LinkedIn can be useful as a discovery signal, but candidates should land on
     * the company's own posting when we can confidently find the same role.
     *
     * @param array<string, mixed>|null $mapping
     * @param array<string, mixed>|null $companyInfo
     */
    public function resolveOfficialApplyUrl(string $companyName, string $title, string $currentApplyUrl, ?array $mapping = null, ?array $companyInfo = null): string
    {
        $currentApplyUrl = $this->normalizeUrl($currentApplyUrl);
        $title = trim($title);

        if ($currentApplyUrl === '' || $title === '' || !$this->isLinkedInUrl($currentApplyUrl) || !$this->tavilyApiKey) {
            return $currentApplyUrl;
        }

        foreach ($this->findOfficialApplyUrlCandidates($companyName, $title, $mapping, $companyInfo) as $candidateUrl) {
            $candidateUrl = $this->normalizeUrl($candidateUrl);
            if ($candidateUrl === '' || $this->isLinkedInUrl($candidateUrl)) {
                continue;
            }

            if (!$this->looksLikeJobPostingUrl($candidateUrl, $title)) {
                continue;
            }

            if (!$this->officialCandidateBelongsToCompany($candidateUrl, $companyName, $mapping, $companyInfo)) {
                continue;
            }

            return $candidateUrl;
        }

        return $currentApplyUrl;
    }

    /**
     * @param array<string, mixed>|null $mapping
     * @param array<string, mixed>|null $companyInfo
     * @return array<int, string>
     */
    private function findOfficialApplyUrlCandidates(string $companyName, string $title, ?array $mapping, ?array $companyInfo): array
    {
        $queries = [];
        $hosts = [];

        foreach ([
            $mapping['career_url'] ?? null,
            $companyInfo['career_page'] ?? null,
            $mapping['website_url'] ?? null,
            $companyInfo['website'] ?? null,
        ] as $url) {
            $host = strtolower((string) (parse_url($this->normalizeUrl((string) $url), PHP_URL_HOST) ?: ''));
            $host = preg_replace('/^www\./', '', $host) ?? $host;
            if ($host !== '') {
                $hosts[] = $host;
            }
        }

        foreach (array_values(array_unique($hosts)) as $host) {
            $queries[] = 'site:' . $host . ' "' . $title . '" "' . $companyName . '" (apply OR "job description" OR reqid OR requisition OR "job id")';
        }

        $companyKey = $this->normalizeCompanyKey($companyName);
        foreach ([
            'myworkdayjobs.com',
            'greenhouse.io',
            'lever.co',
            'smartrecruiters.com',
            'successfactors.com',
            'icims.com',
            'ashbyhq.com',
        ] as $atsHost) {
            $queries[] = 'site:' . $atsHost . ' "' . $title . '" "' . $companyName . '"';
            if ($companyKey !== '') {
                $queries[] = 'site:' . $atsHost . ' "' . $title . '" "' . $companyKey . '"';
            }
        }

        $candidates = [];
        $seen = []; // Use a smaller number of queries for faster resolution
        foreach (array_slice(array_values(array_unique($queries)), 0, 5) as $query) {
            $results = $this->tavilySearch($query, 5, 'basic'); // Use basic depth for faster URL resolution
            foreach ($results as $result) {
                $link = $this->normalizeUrl((string) ($result['url'] ?? ''));
                    $candidateTitle = (string) ($result['title'] ?? '');
                $snippet = (string) ($result['content'] ?? '');

                    if ($link === '' || isset($seen[$link]) || !$this->titleLooksLikeSameRole($title, $candidateTitle . ' ' . $snippet)) {
                        continue;
                    }

                    $seen[$link] = true;
                    $candidates[] = $link;
            }
        }

        return $candidates;
    }

    /**
     * @param array<string, mixed>|null $mapping
     * @param array<string, mixed>|null $companyInfo
     */
    private function officialCandidateBelongsToCompany(string $url, string $companyName, ?array $mapping, ?array $companyInfo): bool
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?: ''));
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $companyKey = $this->normalizeCompanyKey($companyName);

        if ($host !== '' && $this->hostLooksOfficialForCompany($host, $companyKey)) {
            return true;
        }

        foreach ([
            $mapping['career_url'] ?? null,
            $companyInfo['career_page'] ?? null,
            $mapping['website_url'] ?? null,
            $companyInfo['website'] ?? null,
        ] as $sourceUrl) {
            $sourceHost = strtolower((string) (parse_url($this->normalizeUrl((string) $sourceUrl), PHP_URL_HOST) ?: ''));
            $sourceHost = preg_replace('/^www\./', '', $sourceHost) ?? $sourceHost;
            if ($sourceHost !== '' && ($host === $sourceHost || str_ends_with($host, '.' . $sourceHost))) {
                return true;
            }
        }

        $hostKey = $this->normalizeCompanyKey($host);
        foreach ([
            'greenhouse.io',
            'lever.co',
            'myworkdayjobs.com',
            'smartrecruiters.com',
            'successfactors.',
            'icims.com',
            'ashbyhq.com',
        ] as $atsHost) {
            if (str_contains($host, $atsHost)) {
                return $this->companyKeysMatch($companyKey, $hostKey) || str_contains($hostKey, $companyKey);
            }
        }

        return false;
    }

    /**
     * Uses AI to discover company metadata (industry, HQ, size, website, etc.)
     */
    public function discoverCompanyProfile(string $companyName): array
    {
        if (!$this->apiKey) return [];

        $prompt = "Provide a professional metadata profile for the company '$companyName' in valid JSON format.
        Fields required: 
        - name: Official Name
        - industry: Primary industry
        - hq: City, Country
        - size: Employee range (e.g. 10,001+)
        - website: Official URL
        - short_description: 1-2 sentence overview
        - linkedin, twitter, facebook, instagram, youtube: Official URLs. Do not include logo URLs.
        
        Return ONLY the JSON object. Do not suggest Clearbit or other third-party logo service URLs.";

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.3
            ]),
            CURLOPT_SSL_VERIFYPEER => (ENVIRONMENT !== 'development'),
            CURLOPT_TIMEOUT => 20
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) return [];

        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? '';
        return json_decode($content, true) ?: [];
    }

    private function performTavilySearchAndFormat(string $query, int $numResults, string $sourcePlatform, bool $filterJobsView, string $depth = 'advanced'): string
    {
        $textBlock = "";
        $results = $this->tavilySearch($query, $numResults, $depth); // Line 800
        foreach ($results as $result) {
            $link = (string) ($result['url'] ?? '');
            if ($filterJobsView && !str_contains(strtolower($link), '/jobs/')) {
                continue;
            }
            // The format here matches the regex used in extractDirectJobsFromSearchText
            $textBlock .= "Tavily Search Result (Source: " . $sourcePlatform . "): Title: " . ($result['title'] ?? '') . ". Link: " . $link . ". Snippet: " . ($result['content'] ?? '') . "\n";
        }
        return $textBlock;
    }

    private function tavilySearch(string $query, int $maxResults = 10, string $depth = 'advanced'): array
    {
        if (!$this->tavilyApiKey) {
            return [];
        }

        $ch = curl_init('https://api.tavily.com/search');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'api_key' => $this->tavilyApiKey,
                'query' => $query,
                'max_results' => $maxResults,
                'search_depth' => $depth
            ]),
            CURLOPT_SSL_VERIFYPEER => (ENVIRONMENT !== 'development'), 
            CURLOPT_TIMEOUT => 60 // Increased timeout to 60 seconds
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            $errorMsg = curl_error($ch);
            $errorNo = curl_errno($ch);
            log_message('error', "MncJobIngestor: Tavily API Error (HTTP $httpCode, cURL Error $errorNo: $errorMsg). Response: " . ($response ?: 'No response'));

            return [];
        }

        $data = json_decode($response, true);
        return $data['results'] ?? [];
    }

    private function tagJobsWithSource(array $jobs, string $source): array
    {
        foreach ($jobs as &$job) {
            if (empty($job['source_platform'])) {
                $job['source_platform'] = $source;
            }
        }
        return $jobs;
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || $url === '#') return '';
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }
        return rtrim($url, '/');
    }

    private function isLinkedInUrl(string $url): bool
    {
        return str_contains(strtolower($url), 'linkedin.com');
    }

    private function slugifyCompanyName(string $name): string
    {
        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9\s-]/', '', $name) ?? $name;
        $name = preg_replace('/[\s-]+/', ' ', $name) ?? $name;
        return str_replace(' ', '-', trim($name));
    }

    private function curlGet(string $url, int $timeout = 15): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => (ENVIRONMENT !== 'development'),
            CURLOPT_HTTPHEADER     => ['Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8']
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return is_string($response) ? $response : '';
    }

    private function cleanText(string $text): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        return trim($text);
    }

    private function resolveUrl(string $href, string $baseUrl): string
    {
        if (preg_match('#^https?://#i', $href)) return $href;
        
        $parts = parse_url($baseUrl);
        $scheme = $parts['scheme'] ?? 'https';
        $host = $parts['host'] ?? '';
        
        if (str_starts_with($href, '//')) return $scheme . ':' . $href;
        if (str_starts_with($href, '/')) return $scheme . '://' . $host . $href;
        
        $path = $parts['path'] ?? '/';
        $dir = dirname($path);
        if ($dir === '.') $dir = '';
        
        return $scheme . '://' . $host . rtrim($dir, '/') . '/' . ltrim($href, '/');
    }

    private function splitTextIntoChunks(string $text, int $maxChars = 4000): array
    {
        if (mb_strlen($text) <= $maxChars) return [$text];
        
        $chunks = [];
        while (mb_strlen($text) > 0) {
            if (mb_strlen($text) <= $maxChars) {
                $chunks[] = $text;
                break;
            }
            
            $breakpoint = mb_strrpos(mb_substr($text, 0, $maxChars), "\n");
            if ($breakpoint === false) $breakpoint = mb_strrpos(mb_substr($text, 0, $maxChars), " ");
            if ($breakpoint === false) $breakpoint = $maxChars;
            
            $chunks[] = mb_substr($text, 0, $breakpoint);
            $text = mb_substr($text, $breakpoint);
        }
        return $chunks;
    }

    private function normalizeCompanyKey(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\b(limited|ltd|inc|llc|llp|plc|corp|corporation|company|co|technologies|technology|solutions|services|systems|group|holdings|private|pvt)\b/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';
        return str_replace(' ', '', trim($value));
    }

    private function hostLooksOfficialForCompany(string $host, string $companyKey): bool
    {
        $host = strtolower($host);
        $host = preg_replace('/^www\./', '', $host) ?? $host;
        $parts = explode('.', $host);

        foreach ($parts as $part) {
            if ($this->companyKeysMatch($companyKey, $this->normalizeCompanyKey($part))) {
                return true;
            }
        }

        return false;
    }

    private function companyKeysMatch(string $left, string $right): bool
    {
        if ($left === '' || $right === '') {
            return false;
        }

        return $left === $right || str_contains($left, $right) || str_contains($right, $left);
    }


    private function meaningfulTitleTokens(string $title): array
    {
        $title = strtolower(trim($title));
        $parts = preg_split('/[^a-z0-9+#.]+/', $title) ?: [];
        $stopWords = array_fill_keys([
            'at', 'in', 'for', 'and', 'with', 'the', 'a', 'an', 'of', 'to',
            'developer', 'engineer', 'manager', 'analyst', 'specialist', 'architect',
            'lead', 'senior', 'junior', 'staff', 'principal', 'director', 'head',
            'role', 'position', 'job', 'opening', 'opportunity', 'hiring', 'remote',
            'fulltime', 'full-time', 'parttime', 'part-time', 'contract', 'internship',
            'associate', 'consultant', 'expert', 'professional', 'entry', 'level',
            'i', 'ii', 'iii', 'iv', // for levels like 'Engineer II'
        ], true);

        $tokens = [];
        foreach ($parts as $part) {
            $token = trim($part);
            if ($token === '' || strlen($token) < 2 || isset($stopWords[$token])) {
                continue;
            }
            $tokens[] = $token;
        }

        return array_values(array_unique($tokens));
    }


    private function titleLooksLikeSameRole(string $expectedTitle, string $candidateText): bool
    {
        $expectedTokens = $this->meaningfulTitleTokens($expectedTitle);
        if (empty($expectedTokens)) {
            return false; // Cannot compare if expected title has no meaningful tokens
        }

        $candidateTokens = $this->meaningfulTitleTokens($candidateText);
        if (empty($candidateTokens)) {
            return false; // Cannot compare if candidate text has no meaningful tokens
        }

        $overlap = array_intersect($expectedTokens, $candidateTokens);
        $overlapCount = count($overlap);

        // Require at least 50% overlap of the expected title's meaningful tokens
        // or at least 1 token if the expected title is very short (e.g., "PHP")
        $minOverlap = max(1, (int) ceil(count($expectedTokens) * 0.5));

        return $overlapCount >= $minOverlap;
    }
}
