<?php

namespace App\Libraries;

class AiJobSearchStrategyCoach
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private int $cacheTtl = 21600;

    public function __construct()
    {
        $this->apiKey = (string) (getenv('OPENAI_API_KEY') ?: '');
    }

    public function generate(int $candidateId, array $context, array $fallback): array
    {
        if ($this->apiKey === '') {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $cache = cache();
        $cacheKey = 'job_search_strategy_' . sha1($candidateId . '|' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        $cached = $cache->get($cacheKey);
        if (is_array($cached) && !empty($cached['priority_actions'])) {
            return $cached;
        }

        $content = $this->callOpenAI($this->buildPrompt($context));
        $parsed = $this->parsePayload($content, $fallback);
        $cache->save($cacheKey, $parsed, $this->cacheTtl);

        return $parsed;
    }

    private function buildPrompt(array $context): string
    {
        return "You are a job search strategy coach for a job portal candidate. Build a concise but practical search strategy based on the candidate profile, application behavior, and current suggested jobs.\n\n"
            . "Return valid JSON only with this exact schema:\n"
            . "{\n"
            . "  \"title\": \"string\",\n"
            . "  \"summary\": \"string\",\n"
            . "  \"target_roles\": [\"role1\", \"role2\", \"role3\"],\n"
            . "  \"priority_actions\": [\"action1\", \"action2\", \"action3\"],\n"
            . "  \"profile_fixes\": [\"fix1\", \"fix2\", \"fix3\"],\n"
            . "  \"application_strategy\": [\"step1\", \"step2\", \"step3\"],\n"
            . "  \"weekly_plan\": [\"item1\", \"item2\", \"item3\"],\n"
            . "  \"watchouts\": [\"warning1\", \"warning2\"],\n"
            . "  \"recommended_job_ids\": [1, 2, 3]\n"
            . "}\n\n"
            . "Rules:\n"
            . "- Keep advice specific to this candidate context, not generic career advice.\n"
            . "- Focus on what the candidate should do over the next 1 to 2 weeks.\n"
            . "- Use 3 to 6 items per list.\n"
            . "- Choose recommended_job_ids only from the suggested jobs in context.\n"
            . "- Avoid markdown.\n\n"
            . "Context:\n"
            . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function parsePayload(string $content, array $fallback): array
    {
        $data = json_decode($content, true);
        if (!is_array($data)) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $priorityActions = $this->cleanList($data['priority_actions'] ?? [], 6);
        $profileFixes = $this->cleanList($data['profile_fixes'] ?? [], 6);
        $applicationStrategy = $this->cleanList($data['application_strategy'] ?? [], 6);
        $weeklyPlan = $this->cleanList($data['weekly_plan'] ?? [], 7);
        $watchouts = $this->cleanList($data['watchouts'] ?? [], 5);
        $targetRoles = $this->cleanList($data['target_roles'] ?? [], 5);
        $recommendedJobIds = array_values(array_slice(array_filter(array_map('intval', (array) ($data['recommended_job_ids'] ?? []))), 0, 3));

        if (empty($priorityActions) || empty($applicationStrategy) || empty($weeklyPlan)) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        return [
            'title' => trim((string) ($data['title'] ?? ($fallback['title'] ?? 'Job Search Strategy Coach'))),
            'summary' => trim((string) ($data['summary'] ?? ($fallback['summary'] ?? ''))),
            'target_roles' => !empty($targetRoles) ? $targetRoles : (array) ($fallback['target_roles'] ?? []),
            'priority_actions' => $priorityActions,
            'profile_fixes' => !empty($profileFixes) ? $profileFixes : (array) ($fallback['profile_fixes'] ?? []),
            'application_strategy' => $applicationStrategy,
            'weekly_plan' => $weeklyPlan,
            'watchouts' => !empty($watchouts) ? $watchouts : (array) ($fallback['watchouts'] ?? []),
            'recommended_job_ids' => $recommendedJobIds,
            'source' => 'ai',
        ];
    }

    private function cleanList(array $items, int $limit): array
    {
        return array_values(array_slice(array_filter(array_map('trim', $items)), 0, $limit));
    }

    private function callOpenAI(string $prompt): string
    {
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [[
                'role' => 'system',
                'content' => 'You are an expert job search strategist. Return concise, valid JSON only.',
            ], [
                'role' => 'user',
                'content' => $prompt,
            ]],
            'temperature' => 0.4,
            'max_tokens' => 1400,
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
            log_message('error', 'AI job search strategy generation failed: ' . $curlError . ' HTTP ' . $httpCode);
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
