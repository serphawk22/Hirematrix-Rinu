<?php

namespace App\Libraries;

class AiCandidateMatcher
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private float $minMatchScore = 60.0;

    public function __construct()
    {
        $this->apiKey = (string) (getenv('OPENAI_API_KEY') ?: '');
    }

    /**
     * Rank candidates for a specific job. Uses AI first, fallback scoring if unavailable.
     *
     * @param array<int, array<string, mixed>> $candidates
     * @return array<int, array<string, mixed>>
     */
    public function rankCandidatesForJob(array $job, array $candidates, int $limit = 20): array
    {
        if (empty($job) || empty($candidates)) {
            return [];
        }

        $aiRanked = $this->rankWithAi($job, $candidates, $limit);
        if (!empty($aiRanked)) {
            return $this->applyQualityGate($job, $aiRanked, $limit);
        }

        $fallbackRanked = $this->rankWithFallback($job, $candidates, $limit);
        return $this->applyQualityGate($job, $fallbackRanked, $limit);
    }

    /**
     * @param array<int, array<string, mixed>> $candidates
     * @return array<int, array<string, mixed>>
     */
    private function rankWithAi(array $job, array $candidates, int $limit): array
    {
        if ($this->apiKey === '') {
            return [];
        }

        $jobPayload = [
            'id' => (int) ($job['id'] ?? 0),
            'title' => (string) ($job['title'] ?? ''),
            'category' => (string) ($job['category'] ?? ''),
            'location' => (string) ($job['location'] ?? ''),
            'required_skills' => (string) ($job['required_skills'] ?? ''),
            'experience_level' => (string) ($job['experience_level'] ?? ''),
            'employment_type' => (string) ($job['employment_type'] ?? ''),
        ];

        $candidatePayload = array_map(static function (array $candidate): array {
            return [
                'candidate_id' => (int) ($candidate['id'] ?? 0),
                'name' => (string) ($candidate['name'] ?? ''),
                'location' => (string) ($candidate['location'] ?? ''),
                'skills' => (string) ($candidate['skill_name'] ?? ''),
                'total_experience_months' => (int) ($candidate['total_experience_months'] ?? 0),
                'resume_available' => !empty($candidate['resume_path']),
            ];
        }, $candidates);

        $prompt = "You are a recruiter assistant. Rank candidates for this job.\n\n"
            . "JOB:\n" . json_encode($jobPayload, JSON_PRETTY_PRINT) . "\n\n"
            . "CANDIDATES:\n" . json_encode($candidatePayload, JSON_PRETTY_PRINT) . "\n\n"
            . "Return ONLY valid JSON array with top {$limit} best matches:\n"
            . "[{\"candidate_id\": 12, \"score\": 91.5, \"reason\": \"Strong PHP/MySQL and relevant experience\"}]\n"
            . "Rules: score 0-100, reason max 1 short sentence, no markdown.";

        $content = $this->callOpenAi($prompt);
        if ($content === null) {
            return [];
        }

        $parsed = $this->parseJsonArray($content);
        if (empty($parsed)) {
            return [];
        }

        $byId = [];
        foreach ($candidates as $candidate) {
            $byId[(int) ($candidate['id'] ?? 0)] = $candidate;
        }

        $ranked = [];
        foreach ($parsed as $row) {
            $candidateId = (int) ($row['candidate_id'] ?? 0);
            if ($candidateId <= 0 || !isset($byId[$candidateId])) {
                continue;
            }

            $score = (float) ($row['score'] ?? 0);
            $score = max(0, min(100, $score));
            $reason = trim((string) ($row['reason'] ?? 'AI matched profile for this role.'));

            $candidate = $byId[$candidateId];
            $candidate['match_score'] = round($score, 1);
            $candidate['match_reason'] = $reason;
            $ranked[] = $candidate;
        }

        usort($ranked, static fn (array $a, array $b): int => ((float) ($b['match_score'] ?? 0)) <=> ((float) ($a['match_score'] ?? 0)));
        return array_slice($ranked, 0, $limit);
    }

    /**
     * @param array<int, array<string, mixed>> $candidates
     * @return array<int, array<string, mixed>>
     */
    private function rankWithFallback(array $job, array $candidates, int $limit): array
    {
        $requiredSkills = $this->tokenize((string) ($job['required_skills'] ?? ''));
        $requiredMonths = $this->extractRequiredExperienceMonths((string) ($job['experience_level'] ?? ''));
        $jobLocation = strtolower(trim((string) ($job['location'] ?? '')));

        foreach ($candidates as &$candidate) {
            $candidateSkills = $this->tokenize((string) ($candidate['skill_name'] ?? ''));
            $candidateMonths = (int) ($candidate['total_experience_months'] ?? 0);

            $skillScore = 0.0;
            if (!empty($requiredSkills)) {
                $matched = 0;
                foreach ($requiredSkills as $requiredSkill) {
                    if (in_array($requiredSkill, $candidateSkills, true)) {
                        $matched++;
                    }
                }
                $skillScore = ($matched / max(1, count($requiredSkills))) * 60;
            }

            $experienceScore = 0.0;
            if ($requiredMonths === null || $requiredMonths <= 0) {
                $experienceScore = 15;
            } else {
                $experienceScore = min(20, ($candidateMonths / $requiredMonths) * 20);
            }

            $locationScore = 0.0;
            $candidateLocation = strtolower(trim((string) ($candidate['location'] ?? '')));
            if ($jobLocation === '' || $candidateLocation === '') {
                $locationScore = 5;
            } elseif (str_contains($candidateLocation, $jobLocation) || str_contains($jobLocation, $candidateLocation)) {
                $locationScore = 10;
            }

            $resumeScore = !empty($candidate['resume_path']) ? 10 : 0;
            $total = max(0, min(100, $skillScore + $experienceScore + $locationScore + $resumeScore));

            $candidate['match_score'] = round($total, 1);
            $candidate['match_reason'] = $this->buildFallbackReason((int) round($skillScore), (int) round($experienceScore), (int) $candidateMonths);
        }
        unset($candidate);

        usort($candidates, static fn (array $a, array $b): int => ((float) ($b['match_score'] ?? 0)) <=> ((float) ($a['match_score'] ?? 0)));
        return array_slice($candidates, 0, $limit);
    }

    private function buildFallbackReason(int $skillScore, int $experienceScore, int $candidateMonths): string
    {
        if ($skillScore >= 40 && $experienceScore >= 12) {
            return 'Strong skills match with relevant experience.';
        }
        if ($skillScore >= 40) {
            return 'Strong skills match for this role.';
        }
        if ($experienceScore >= 12 || $candidateMonths >= 24) {
            return 'Good experience fit for this role.';
        }
        return 'Partial match based on available profile signals.';
    }

    /**
     * Quality gate:
     * - Hide candidates below minimum score.
     * - Require at least one required-skill overlap when job has required skills.
     *
     * @param array<int, array<string, mixed>> $ranked
     * @return array<int, array<string, mixed>>
     */
    private function applyQualityGate(array $job, array $ranked, int $limit): array
    {
        $requiredSkills = $this->tokenize((string) ($job['required_skills'] ?? ''));
        $jobKeywords = $requiredSkills;
        if (empty($jobKeywords)) {
            $jobKeywords = $this->extractJobKeywords(
                (string) ($job['title'] ?? ''),
                (string) ($job['category'] ?? '')
            );
        }
        $mustHaveSkillOverlap = !empty($jobKeywords);

        $requiredOverlapCount = $this->getRequiredOverlapCount(count($requiredSkills), count($jobKeywords));

        $filtered = array_values(array_filter($ranked, function (array $candidate) use ($jobKeywords, $mustHaveSkillOverlap, $requiredOverlapCount): bool {
            $score = (float) ($candidate['match_score'] ?? 0);
            if ($score < $this->minMatchScore) {
                return false;
            }

            if (!$mustHaveSkillOverlap) {
                return true;
            }

            $candidateSkills = $this->tokenize((string) ($candidate['skill_name'] ?? ''));
            $overlapCount = $this->countSkillOverlap($jobKeywords, $candidateSkills);
            return $overlapCount >= $requiredOverlapCount;
        }));

        usort($filtered, static fn (array $a, array $b): int => ((float) ($b['match_score'] ?? 0)) <=> ((float) ($a['match_score'] ?? 0)));
        return array_slice($filtered, 0, $limit);
    }

    /**
     * @param array<int, string> $requiredSkills
     * @param array<int, string> $candidateSkills
     */
    private function hasSkillOverlap(array $requiredSkills, array $candidateSkills): bool
    {
        foreach ($requiredSkills as $requiredSkill) {
            $required = strtolower(trim((string) $requiredSkill));
            if ($required === '') {
                continue;
            }

            foreach ($candidateSkills as $candidateSkill) {
                $candidate = strtolower(trim((string) $candidateSkill));
                if ($candidate === '') {
                    continue;
                }

                if ($required === $candidate) {
                    return true;
                }

                // Support simple partial matches like "react" vs "reactjs".
                if (strlen($required) >= 4 && str_contains($candidate, $required)) {
                    return true;
                }
                if (strlen($candidate) >= 4 && str_contains($required, $candidate)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function getRequiredOverlapCount(int $requiredSkillsCount, int $jobKeywordsCount): int
    {
        if ($requiredSkillsCount >= 4) {
            return 2;
        }
        if ($requiredSkillsCount >= 1) {
            return 1;
        }

        // When no explicit required skills are present, be stricter on role keywords.
        if ($jobKeywordsCount >= 4) {
            return 2;
        }
        return 1;
    }

    private function countSkillOverlap(array $requiredSkills, array $candidateSkills): int
    {
        $matchedRequired = [];
        foreach ($requiredSkills as $requiredSkill) {
            $required = strtolower(trim((string) $requiredSkill));
            if ($required === '') {
                continue;
            }

            foreach ($candidateSkills as $candidateSkill) {
                $candidate = strtolower(trim((string) $candidateSkill));
                if ($candidate === '') {
                    continue;
                }

                if ($required === $candidate) {
                    $matchedRequired[$required] = true;
                    break;
                }

                if (strlen($required) >= 4 && str_contains($candidate, $required)) {
                    $matchedRequired[$required] = true;
                    break;
                }
                if (strlen($candidate) >= 4 && str_contains($required, $candidate)) {
                    $matchedRequired[$required] = true;
                    break;
                }
            }
        }

        return count($matchedRequired);
    }

    /**
     * @return array<int, string>
     */
    private function extractJobKeywords(string $title, string $category): array
    {
        $text = strtolower(trim($title . ' ' . $category));
        if ($text === '') {
            return [];
        }

        $parts = preg_split('/[^a-z0-9+#.]+/', $text) ?: [];
        $stopWords = [
            'developer', 'engineer', 'specialist', 'associate', 'senior', 'junior',
            'lead', 'role', 'job', 'full', 'time', 'part', 'contract', 'internship',
            'for', 'and', 'with', 'the', 'a', 'an', 'in', 'of', 'to',
        ];
        $stopMap = array_fill_keys($stopWords, true);

        $keywords = [];
        foreach ($parts as $part) {
            $word = trim($part);
            if ($word === '' || strlen($word) < 3) {
                continue;
            }
            if (isset($stopMap[$word])) {
                continue;
            }
            $keywords[] = $word;
        }

        return array_values(array_unique($keywords));
    }

    private function extractRequiredExperienceMonths(string $experience): ?int
    {
        $value = strtolower(trim($experience));
        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (int) round(((float) $matches[1]) * 12);
        }

        if (preg_match('/(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (int) round(((float) $matches[1]) * 12);
        }

        return null;
    }

    /**
     * @return array<int, string>
     */
    private function tokenize(string $value): array
    {
        $parts = preg_split('/[,|\/]+/', strtolower($value)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $token = trim($part);
            if ($token !== '') {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    private function callOpenAi(string $prompt): ?string
    {
        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.2,
            'max_tokens' => 1800,
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            log_message('error', 'AI candidate matcher cURL error: ' . $curlError);
            return null;
        }

        if ($httpCode !== 200) {
            log_message('error', 'AI candidate matcher HTTP error: ' . $httpCode . ' body: ' . substr((string) $response, 0, 400));
            return null;
        }

        $decoded = json_decode((string) $response, true);
        if (is_array($decoded)) {
            (new UsageAnalyticsService())->logOpenAiUsage($decoded, '/v1/chat/completions', 'gpt-4o-mini');
        }
        return $decoded['choices'][0]['message']['content'] ?? null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function parseJsonArray(string $content): array
    {
        $trimmed = trim($content);
        $trimmed = preg_replace('/```(?:json)?/i', '', $trimmed) ?? $trimmed;
        $trimmed = str_replace('```', '', $trimmed);

        $firstBracket = strpos($trimmed, '[');
        $lastBracket = strrpos($trimmed, ']');
        if ($firstBracket === false || $lastBracket === false || $lastBracket <= $firstBracket) {
            return [];
        }

        $json = substr($trimmed, $firstBracket, $lastBracket - $firstBracket + 1);
        $parsed = json_decode($json, true);
        if (!is_array($parsed)) {
            return [];
        }

        return $parsed;
    }
}
