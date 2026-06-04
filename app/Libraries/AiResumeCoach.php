<?php

namespace App\Libraries;

class AiResumeCoach
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private int $cacheTtl = 21600;

    public function __construct()
    {
        $this->apiKey = (string) (getenv('OPENAI_API_KEY') ?: '');
    }

    public function generate(int $candidateId, array $job, array $context, array $fallback): array
    {
        if ($this->apiKey === '') {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $cache = cache();
        $cacheKey = 'ai_resume_coach_' . sha1($candidateId . '|' . json_encode([
            'job_id' => (int) ($job['id'] ?? 0),
            'job_title' => (string) ($job['title'] ?? ''),
            'job_description' => (string) ($job['description'] ?? ''),
            'required_skills' => (string) ($job['required_skills'] ?? ''),
            'context' => $context,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $cached = $cache->get($cacheKey);
        if (is_array($cached) && !empty($cached['suggestions'])) {
            return $cached;
        }

        $content = $this->callOpenAI($this->buildPrompt($job, $context, $fallback));
        $parsed = $this->parsePayload($content, $fallback);
        $cache->save($cacheKey, $parsed, $this->cacheTtl);

        return $parsed;
    }

    private function buildPrompt(array $job, array $context, array $fallback): string
    {
        $payload = [
            'job' => [
                'title' => (string) ($job['title'] ?? ''),
                'description' => (string) ($job['description'] ?? ''),
                'required_skills' => (string) ($job['required_skills'] ?? ''),
                'experience_level' => (string) ($job['experience_level'] ?? ''),
            ],
            'candidate_context' => $context,
            'existing_score' => (int) ($fallback['score'] ?? 0),
            'existing_matched_skills' => (array) ($fallback['matched_skills'] ?? []),
            'existing_missing_skills' => (array) ($fallback['missing_skills'] ?? []),
        ];

        return "You are an expert ATS resume strategist. Build job-specific resume-improvement guidance for this candidate and target role.\n\n"
            . "Return valid JSON only with this exact schema:\n"
            . "{\n"
            . "  \"summary_suggestion\": \"string\",\n"
            . "  \"suggestions\": [\"item1\", \"item2\", \"item3\"],\n"
            . "  \"matched_skills\": [\"skill1\", \"skill2\"],\n"
            . "  \"missing_skills\": [\"skill1\", \"skill2\"],\n"
            . "  \"emphasis_skills\": [\"skill1\", \"skill2\"],\n"
            . "  \"score_adjustment\": -5\n"
            . "}\n\n"
            . "Rules:\n"
            . "- Keep advice concise and specific to the job.\n"
            . "- Suggestions must be realistic resume changes, not generic motivation.\n"
            . "- Matched skills should reflect current fit. Missing skills should reflect visible gaps.\n"
            . "- emphasis_skills should be the strongest skills to foreground in the resume.\n"
            . "- score_adjustment must be an integer between -10 and 10 and should only fine-tune the existing score, not replace it.\n"
            . "- Avoid markdown.\n\n"
            . "Context:\n"
            . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function parsePayload(string $content, array $fallback): array
    {
        $data = json_decode($content, true);
        if (!is_array($data)) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $suggestions = $this->cleanList((array) ($data['suggestions'] ?? []), 5);
        $matchedSkills = $this->cleanList((array) ($data['matched_skills'] ?? []), 8);
        $missingSkills = $this->cleanList((array) ($data['missing_skills'] ?? []), 8);
        $emphasisSkills = $this->cleanList((array) ($data['emphasis_skills'] ?? []), 6);
        $summarySuggestion = trim((string) ($data['summary_suggestion'] ?? ''));
        $scoreAdjustment = (int) ($data['score_adjustment'] ?? 0);
        $scoreAdjustment = max(-10, min(10, $scoreAdjustment));

        if ($summarySuggestion === '' || empty($suggestions)) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $baseScore = (int) ($fallback['score'] ?? 0);

        return [
            'score' => max(0, min(100, $baseScore + $scoreAdjustment)),
            'required_skills' => (array) ($fallback['required_skills'] ?? []),
            'matched_skills' => !empty($matchedSkills) ? $matchedSkills : (array) ($fallback['matched_skills'] ?? []),
            'missing_skills' => !empty($missingSkills) ? $missingSkills : (array) ($fallback['missing_skills'] ?? []),
            'emphasis_skills' => !empty($emphasisSkills) ? $emphasisSkills : (array) ($fallback['emphasis_skills'] ?? []),
            'suggestions' => $suggestions,
            'summary_suggestion' => $summarySuggestion,
            'resume_version' => $fallback['resume_version'] ?? null,
            'resume_studio_url' => (string) ($fallback['resume_studio_url'] ?? ''),
            'source' => 'ai',
        ];
    }

    private function cleanList(array $items, int $limit): array
    {
        return array_values(array_slice(array_filter(array_map(static function ($item): string {
            return trim((string) $item);
        }, $items)), 0, $limit));
    }

    private function callOpenAI(string $prompt): string
    {
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [[
                'role' => 'system',
                'content' => 'You are an expert resume strategist. Return concise, valid JSON only.',
            ], [
                'role' => 'user',
                'content' => $prompt,
            ]],
            'temperature' => 0.4,
            'max_tokens' => 1200,
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim($this->apiKey),
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError || $httpCode !== 200) {
            log_message('error', 'AI resume coach generation failed: ' . $curlError . ' HTTP ' . $httpCode);
            return '{}';
        }

        $payload = json_decode($response, true);
        if (is_array($payload)) {
            (new UsageAnalyticsService())->logOpenAiUsage($payload, '/v1/chat/completions', 'gpt-4o-mini');
        }
        $content = (string) ($payload['choices'][0]['message']['content'] ?? '{}');

        return $this->extractJSON($content);
    }

    private function extractJSON(string $content): string
    {
        $content = preg_replace('/```(?:json)?\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        $content = trim($content);

        $firstBrace = strpos($content, '{');
        $lastBrace = strrpos($content, '}');
        if ($firstBrace === false || $lastBrace === false || $lastBrace <= $firstBrace) {
            return '{}';
        }

        return substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
    }
}
