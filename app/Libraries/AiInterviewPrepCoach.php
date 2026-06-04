<?php

namespace App\Libraries;

class AiInterviewPrepCoach
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';
    private int $cacheTtl = 21600;

    public function __construct()
    {
        $this->apiKey = (string) (getenv('OPENAI_API_KEY') ?: '');
    }

    public function generate(array $application, array $fallback): array
    {
        if ($this->apiKey === '') {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $cache = cache();
        $cacheKey = $this->buildCacheKey($application);
        $cached = $cache->get($cacheKey);
        if (is_array($cached) && !empty($cached['focus_skills'])) {
            return $cached;
        }

        $content = $this->callOpenAI($this->buildPrompt($application));
        $parsed = $this->parsePayload($content, $fallback);
        $cache->save($cacheKey, $parsed, $this->cacheTtl);

        return $parsed;
    }

    public function generateMockInterview(array $application, array $fallback): array
    {
        if ($this->apiKey === '') {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $cache = cache();
        $cacheKey = $this->buildCacheKey($application) . '_mock';
        $cached = $cache->get($cacheKey);
        if (is_array($cached) && !empty($cached['rounds'])) {
            return $cached;
        }

        $content = $this->callOpenAI($this->buildMockInterviewPrompt($application));
        $parsed = $this->parseMockInterviewPayload($content, $fallback);
        $cache->save($cacheKey, $parsed, $this->cacheTtl);

        return $parsed;
    }

    private function buildCacheKey(array $application): string
    {
        $signature = [
            'application_id' => (int) ($application['id'] ?? 0),
            'status' => (string) ($application['status'] ?? ''),
            'job_title' => (string) ($application['job_title'] ?? ''),
            'job_description' => (string) ($application['job_description'] ?? ''),
            'required_skills' => (string) ($application['required_skills'] ?? ''),
            'experience_level' => (string) ($application['experience_level'] ?? ''),
            'ai_interview_policy' => (string) ($application['ai_interview_policy'] ?? ''),
            'resume_version_title' => (string) ($application['resume_version_title'] ?? ''),
            'resume_version_target_role' => (string) ($application['resume_version_target_role'] ?? ''),
            'resume_version_summary' => (string) ($application['resume_version_summary'] ?? ''),
            'resume_version_highlight_skills' => (string) ($application['resume_version_highlight_skills'] ?? ''),
            'resume_version_content' => (string) ($application['resume_version_content'] ?? ''),
            'resume_version_updated_at' => (string) ($application['resume_version_updated_at'] ?? ''),
        ];

        return 'ai_interview_prep_' . sha1(json_encode($signature, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function buildPrompt(array $application): string
    {
        $resumeContext = $this->buildResumeContext($application);
        $payload = [
            'application_status' => (string) ($application['status'] ?? ''),
            'job' => [
                'title' => (string) ($application['job_title'] ?? ''),
                'company' => (string) ($application['company'] ?? ''),
                'description' => (string) ($application['job_description'] ?? ''),
                'required_skills' => (string) ($application['required_skills'] ?? ''),
                'experience_level' => (string) ($application['experience_level'] ?? ''),
                'ai_interview_policy' => (string) ($application['ai_interview_policy'] ?? ''),
            ],
            'resume_version' => $resumeContext,
        ];

        return "You are an interview coach for job seekers. Create a practical interview-preparation plan for the candidate based on the target job and the saved resume version they used for this application.\n\n"
            . "Return valid JSON only with this exact schema:\n"
            . "{\n"
            . "  \"title\": \"string\",\n"
            . "  \"focus_skills\": [\"skill1\", \"skill2\", \"skill3\"],\n"
            . "  \"talking_points\": [\"point1\", \"point2\", \"point3\"],\n"
            . "  \"likely_questions\": [\"question1\", \"question2\", \"question3\"],\n"
            . "  \"checklist\": [\"item1\", \"item2\", \"item3\"]\n"
            . "}\n\n"
            . "Rules:\n"
            . "- Keep the plan concise, recruiter-relevant, and specific to the job.\n"
            . "- Prefer practical behavioral and technical preparation, not generic motivation.\n"
            . "- Focus on what the candidate should say, prove, and revise before the next interview step.\n"
            . "- Use 3 to 6 items per list.\n"
            . "- Avoid markdown.\n"
            . "- If the AI interview policy is not OFF, include at least one checklist item suited for recorded or AI-screening rounds.\n\n"
            . "Context:\n"
            . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function buildMockInterviewPrompt(array $application): string
    {
        $resumeContext = $this->buildResumeContext($application);
        $payload = [
            'application_status' => (string) ($application['status'] ?? ''),
            'job' => [
                'title' => (string) ($application['job_title'] ?? ''),
                'company' => (string) ($application['company'] ?? ''),
                'description' => (string) ($application['job_description'] ?? ''),
                'required_skills' => (string) ($application['required_skills'] ?? ''),
                'experience_level' => (string) ($application['experience_level'] ?? ''),
                'ai_interview_policy' => (string) ($application['ai_interview_policy'] ?? ''),
            ],
            'resume_version' => $resumeContext,
        ];

        return "You are an expert mock interview coach for job seekers. Build a detailed, practical mock interview plan based on the job and the exact resume version used for the application.\n\n"
            . "Return valid JSON only with this exact schema:\n"
            . "{\n"
            . "  \"title\": \"string\",\n"
            . "  \"intro\": \"string\",\n"
            . "  \"focus_skills\": [\"skill1\", \"skill2\"],\n"
            . "  \"rounds\": [\n"
            . "    {\n"
            . "      \"name\": \"Technical Round\",\n"
            . "      \"objective\": \"string\",\n"
            . "      \"questions\": [\n"
            . "        {\"question\": \"string\", \"why_it_matters\": \"string\", \"answer_tip\": \"string\"}\n"
            . "      ]\n"
            . "    }\n"
            . "  ],\n"
            . "  \"answer_framework\": [\"tip1\", \"tip2\", \"tip3\"],\n"
            . "  \"evaluation_points\": [\"point1\", \"point2\", \"point3\"],\n"
            . "  \"final_checklist\": [\"item1\", \"item2\", \"item3\"]\n"
            . "}\n\n"
            . "Rules:\n"
            . "- Create 2 to 3 interview rounds.\n"
            . "- Provide 3 to 5 questions per round.\n"
            . "- Questions must be tailored to the job and resume context.\n"
            . "- Keep all guidance concrete and recruiter-relevant.\n"
            . "- Avoid markdown.\n"
            . "- If AI interview policy is not OFF, make at least one round suitable for AI screening or recorded responses.\n\n"
            . "Context:\n"
            . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function buildResumeContext(array $application): array
    {
        $content = trim((string) ($application['resume_version_content'] ?? ''));
        $parsedContent = [];
        if ($content !== '') {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $parsedContent = $decoded;
            }
        }

        $experienceItems = [];
        foreach ((array) ($parsedContent['sections']['experience']['items'] ?? []) as $item) {
            $headline = trim((string) ($item['headline'] ?? ''));
            $subhead = trim((string) ($item['subhead'] ?? ''));
            $bullets = array_values(array_slice(array_filter(array_map('trim', (array) ($item['bullets'] ?? []))), 0, 2));
            if ($headline === '' && $subhead === '' && empty($bullets)) {
                continue;
            }
            $experienceItems[] = [
                'headline' => $headline,
                'subhead' => $subhead,
                'meta' => trim((string) ($item['meta'] ?? '')),
                'bullets' => $bullets,
            ];
            if (count($experienceItems) >= 3) {
                break;
            }
        }

        $projectItems = [];
        foreach ((array) ($parsedContent['sections']['projects']['items'] ?? []) as $item) {
            $headline = trim((string) ($item['headline'] ?? ''));
            $subhead = trim((string) ($item['subhead'] ?? ''));
            $bullets = array_values(array_slice(array_filter(array_map('trim', (array) ($item['bullets'] ?? []))), 0, 2));
            if ($headline === '' && $subhead === '' && empty($bullets)) {
                continue;
            }
            $projectItems[] = [
                'headline' => $headline,
                'subhead' => $subhead,
                'meta' => trim((string) ($item['meta'] ?? '')),
                'bullets' => $bullets,
            ];
            if (count($projectItems) >= 2) {
                break;
            }
        }

        return [
            'title' => (string) ($application['resume_version_title'] ?? ''),
            'target_role' => (string) ($application['resume_version_target_role'] ?? ''),
            'summary' => (string) ($application['resume_version_summary'] ?? ''),
            'highlight_skills' => $this->normalizeSkills((string) ($application['resume_version_highlight_skills'] ?? '')),
            'experience' => $experienceItems,
            'projects' => $projectItems,
        ];
    }

    private function parsePayload(string $content, array $fallback): array
    {
        $data = json_decode($content, true);
        if (!is_array($data)) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $focusSkills = array_values(array_slice(array_filter(array_map('trim', (array) ($data['focus_skills'] ?? []))), 0, 6));
        $talkingPoints = array_values(array_slice(array_filter(array_map('trim', (array) ($data['talking_points'] ?? []))), 0, 6));
        $likelyQuestions = array_values(array_slice(array_filter(array_map('trim', (array) ($data['likely_questions'] ?? []))), 0, 6));
        $checklist = array_values(array_slice(array_filter(array_map('trim', (array) ($data['checklist'] ?? []))), 0, 6));

        if (empty($talkingPoints) || empty($likelyQuestions) || empty($checklist)) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        return [
            'title' => trim((string) ($data['title'] ?? ($fallback['title'] ?? 'Pre-interview Preparation Coach'))),
            'focus_skills' => !empty($focusSkills) ? $focusSkills : (array) ($fallback['focus_skills'] ?? []),
            'talking_points' => $talkingPoints,
            'likely_questions' => $likelyQuestions,
            'checklist' => $checklist,
            'source' => 'ai',
        ];
    }

    private function parseMockInterviewPayload(string $content, array $fallback): array
    {
        $data = json_decode($content, true);
        if (!is_array($data)) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        $rounds = [];
        foreach ((array) ($data['rounds'] ?? []) as $round) {
            $questions = [];
            foreach ((array) ($round['questions'] ?? []) as $question) {
                $questionText = trim((string) ($question['question'] ?? ''));
                if ($questionText === '') {
                    continue;
                }
                $questions[] = [
                    'question' => $questionText,
                    'why_it_matters' => trim((string) ($question['why_it_matters'] ?? '')),
                    'answer_tip' => trim((string) ($question['answer_tip'] ?? '')),
                ];
            }

            if (empty($questions)) {
                continue;
            }

            $rounds[] = [
                'name' => trim((string) ($round['name'] ?? 'Interview Round')),
                'objective' => trim((string) ($round['objective'] ?? '')),
                'questions' => array_slice($questions, 0, 5),
            ];
        }

        $answerFramework = array_values(array_slice(array_filter(array_map('trim', (array) ($data['answer_framework'] ?? []))), 0, 6));
        $evaluationPoints = array_values(array_slice(array_filter(array_map('trim', (array) ($data['evaluation_points'] ?? []))), 0, 6));
        $finalChecklist = array_values(array_slice(array_filter(array_map('trim', (array) ($data['final_checklist'] ?? []))), 0, 8));

        if (empty($rounds) || empty($answerFramework) || empty($finalChecklist)) {
            $fallback['source'] = 'fallback';
            return $fallback;
        }

        return [
            'title' => trim((string) ($data['title'] ?? ($fallback['title'] ?? 'Detailed Mock Interview'))),
            'intro' => trim((string) ($data['intro'] ?? ($fallback['intro'] ?? ''))),
            'focus_skills' => array_values(array_slice(array_filter(array_map('trim', (array) ($data['focus_skills'] ?? []))), 0, 8)),
            'rounds' => array_slice($rounds, 0, 3),
            'answer_framework' => $answerFramework,
            'evaluation_points' => $evaluationPoints,
            'final_checklist' => $finalChecklist,
            'source' => 'ai',
        ];
    }

    private function normalizeSkills(string $skills): array
    {
        if ($skills === '') {
            return [];
        }

        $decoded = json_decode($skills, true);
        if (is_array($decoded)) {
            return array_values(array_slice(array_filter(array_map('trim', $decoded)), 0, 10));
        }

        $parts = preg_split('/[,|\\/]+/', $skills) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $tokens[] = $part;
            }
        }

        return array_values(array_slice(array_unique($tokens), 0, 10));
    }

    private function callOpenAI(string $prompt): string
    {
        if (trim($this->apiKey) === '') {
            log_message('error', 'AI interview prep generation failed: missing OpenAI API key.');
            return '{}';
        }

        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [[
                'role' => 'system',
                'content' => 'You are an expert interview preparation coach. Return concise, valid JSON only.',
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
            $errorDetails = [];
            if ($curlError !== '') {
                $errorDetails[] = 'cURL: ' . $curlError;
            }

            $decodedError = is_string($response) ? json_decode($response, true) : null;
            if (is_array($decodedError) && isset($decodedError['error']) && is_array($decodedError['error'])) {
                $openAiError = $decodedError['error'];
                $messageParts = array_filter([
                    trim((string) ($openAiError['message'] ?? '')),
                    trim((string) ($openAiError['type'] ?? '')),
                    trim((string) ($openAiError['code'] ?? '')),
                ]);
                if (!empty($messageParts)) {
                    $errorDetails[] = 'OpenAI: ' . implode(' | ', $messageParts);
                }
            } elseif (is_string($response) && trim($response) !== '') {
                $errorDetails[] = 'OpenAI response: ' . trim($response);
            }

            log_message(
                'error',
                'AI interview prep generation failed: HTTP ' . $httpCode
                . (empty($errorDetails) ? '' : ' | ' . implode(' ; ', $errorDetails))
            );
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
