<?php

namespace App\Libraries;

class GithubAnalyzer
{
    private $token;

    public function __construct()
    {
        $this->token = env('GITHUB_TOKEN');

        if (!$this->token) {
            log_message('error', 'GitHub token missing in .env');
        }
    }

    private function request($url)
    {
        $headers = [
            "User-Agent: JobPortal"
        ];

        if ($this->token) {
            $headers[] = "Authorization: token " . $this->token;
        }

        $ch = curl_init($url);
        $start = microtime(true);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'JobPortal',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 25,
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        (new UsageAnalyticsService())->logExternalApiUsage(
            'github',
            (string) parse_url((string) $url, PHP_URL_PATH),
            'rest_get',
            $status > 0 ? $status : null,
            (int) round((microtime(true) - $start) * 1000),
            1,
            $response !== false && $curlError === '' && $status >= 200 && $status < 400
        );

        if ($response === false || $curlError !== '' || $status < 200 || $status >= 400) {
            return [];
        }

        return json_decode($response, true);
    }


    public function fetchRepos($username)
    {
        $allRepos = [];
        $page = 1;
        $perPage = 100; // max allowed by GitHub

        do {
            $repos = $this->request(
                "https://api.github.com/users/{$username}/repos?per_page={$perPage}&page={$page}"
            );

            if (empty($repos)) {
                break;
            }

            $allRepos = array_merge($allRepos, $repos);
            $page++;

        } while (count($repos) === $perPage);

        return $allRepos;
    }

    public function fetchCommitCount($username, $repoName)
    {
        $url = "https://api.github.com/repos/{$username}/{$repoName}/commits?per_page=1";

        $ch = curl_init($url);

        $start = microtime(true);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_USERAGENT => 'JobPortal',
            CURLOPT_HTTPHEADER => [
                "Authorization: token {$this->token}",
                "Accept: application/vnd.github+json"
            ],
        ]);

        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        (new UsageAnalyticsService())->logExternalApiUsage(
            'github',
            (string) parse_url($url, PHP_URL_PATH),
            'repo_commit_count',
            $status > 0 ? $status : null,
            (int) round((microtime(true) - $start) * 1000),
            1,
            $response !== false && $curlError === '' && $status >= 200 && $status < 400
        );

        if ($response === false || $curlError !== '' || $status < 200 || $status >= 400) {
            curl_close($ch);
            return 0;
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($response, 0, $headerSize);

        curl_close($ch);

        // Extract total commit count from Link header
        if (preg_match('/<[^>]*page=(\d+)[^>]*>; rel="last"/', $headers, $matches)) {
            return (int) $matches[1];
        }

        // Repo has 0 or 1 commit
        return 1;
    }




    public function fetchLanguages($username, $repoName)
    {
        $langs = $this->request("https://api.github.com/repos/$username/$repoName/languages");
        return array_keys($langs);
    }

    public function analyze($username)
    {
        $repos = $this->fetchRepos($username);

        $repoCount = count($repos);
        $totalCommits = 0;
        $languages = [];

        foreach ($repos as $repo) {
            $repoName = $repo['name'];

            $totalCommits += $this->fetchCommitCount($username, $repoName);
            $languages = array_merge($languages, $this->fetchLanguages($username, $repoName));
        }

        return [
            'repo_count' => $repoCount,
            'commit_count' => $totalCommits,
            'languages' => array_unique($languages),
            'github_score' => $this->generateScore($repoCount, $totalCommits)
        ];
    }

    private function generateScore($repos, $commits)
    {
        $score = min(10, round(($repos * 0.5) + ($commits * 0.05)));
        return $score;
    }
}
