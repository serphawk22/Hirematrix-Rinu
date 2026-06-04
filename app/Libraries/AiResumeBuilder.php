<?php

namespace App\Libraries;

class AiResumeBuilder
{
    public function buildResume(array $profile, string $targetRole, array $options = []): array
    {
        $targetRole = trim($targetRole);
        $templateKey = trim((string) ($options['template_key'] ?? 'modern_professional'));
        if ($targetRole === '') {
            return $this->buildFallbackResume($profile, 'Professional', $options);
        }

        $response = $this->callOpenAI($this->buildPrompt($profile, $targetRole, $templateKey, $options));
        $data = json_decode($response, true);

        if (!is_array($data) || empty($data['sections'])) {
            return $this->buildFallbackResume($profile, $targetRole, $options);
        }

        $normalized = $this->normalizeResumePayload($data, $profile, $targetRole, $templateKey);

        return [
            'title' => trim((string) ($normalized['title'] ?? ($targetRole . ' Resume'))),
            'summary' => trim((string) ($normalized['summary'] ?? '')),
            'highlight_skills' => array_values(array_filter(array_map('trim', (array) ($normalized['highlight_skills'] ?? [])))),
            'content' => json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'template_key' => $normalized['template_key'],
        ];
    }

    private function buildPrompt(array $profile, string $targetRole, string $templateKey, array $options): string
    {
        $currentRole = (string) ($options['current_role'] ?? '');
        $jobTitle = (string) ($options['job_title'] ?? '');
        $jobDescription = (string) ($options['job_description'] ?? '');
        $transitionSummary = (string) ($options['transition_summary'] ?? '');

        $payload = [
            'candidate_name' => $profile['name'] ?? '',
            'headline_role' => $currentRole,
            'target_role' => $targetRole,
            'preferred_template' => $templateKey,
            'bio' => $profile['bio'] ?? '',
            'location' => $profile['location'] ?? '',
            'skills' => $profile['skills'] ?? [],
            'github_languages' => $profile['github_languages'] ?? [],
            'interests' => $profile['interests'] ?? [],
            'work_experiences' => $profile['work_experiences'] ?? [],
            'education' => $profile['education'] ?? [],
            'certifications' => $profile['certifications'] ?? [],
            'projects' => $profile['projects'] ?? [],
            'job_context' => [
                'title' => $jobTitle,
                'description' => $jobDescription,
            ],
            'career_transition' => $transitionSummary,
        ];

        return "Create a role-specific resume in valid JSON only. The tone should be strong, concise, and ATS-friendly. Organize the resume into structured sections suitable for a premium modern template.\n\nReturn this schema exactly:\n{\n  \"title\": \"string\",\n  \"summary\": \"string\",\n  \"highlight_skills\": [\"skill1\", \"skill2\"],\n  \"sections\": {\n    \"skills\": {\n      \"title\": \"Technical Skills\",\n      \"groups\": [\n        {\"label\": \"Languages\", \"items\": [\"PHP\", \"JavaScript\"]},\n        {\"label\": \"Frameworks\", \"items\": [\"Laravel\", \"React\"]}\n      ]\n    },\n    \"experience\": {\n      \"title\": \"Professional Experience\",\n      \"items\": [\n        {\n          \"headline\": \"Role title\",\n          \"subhead\": \"Company\",\n          \"meta\": \"Dates | Location\",\n          \"bullets\": [\"impact bullet\", \"impact bullet\"]\n        }\n      ]\n    },\n    \"projects\": {\n      \"title\": \"Projects\",\n      \"items\": [\n        {\n          \"headline\": \"Project name\",\n          \"subhead\": \"Role | Tech stack\",\n          \"meta\": \"Dates | URL if relevant\",\n          \"bullets\": [\"what was built\", \"impact or metric\"]\n        }\n      ]\n    },\n    \"education\": {\n      \"title\": \"Education\",\n      \"items\": [{\"headline\": \"Degree\", \"subhead\": \"Institution\", \"meta\": \"Years\", \"bullets\": []}]\n    },\n    \"certifications\": {\n      \"title\": \"Certifications\",\n      \"items\": [{\"headline\": \"Certification\", \"subhead\": \"Issuer\", \"meta\": \"Date\", \"bullets\": []}]\n    }\n  }\n}\n\nRules:\n- Use quantified, recruiter-friendly bullets.\n- Keep bullets short and high signal.\n- Include only sections that have useful content.\n- Do not use the heading Selected Skills.\n- Do not create any dedicated career-transition or narrative section.\n- If career-transition context is relevant, fold it into the summary only.\n- Respect the preferred template mood, but return only structured JSON.\n\nCandidate profile:\n" . json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private function buildFallbackResume(array $profile, string $targetRole, array $options = []): array
    {
        $templateKey = trim((string) ($options['template_key'] ?? 'modern_professional'));
        $skills = array_values(array_filter(array_map('trim', array_merge((array) ($profile['skills'] ?? []), (array) ($profile['github_languages'] ?? [])))));
        $workExperiences = (array) ($profile['work_experiences'] ?? []);
        $education = (array) ($profile['education'] ?? []);
        $certifications = (array) ($profile['certifications'] ?? []);
        $projects = (array) ($profile['projects'] ?? []);
        $summaryParts = [];

        if (!empty($profile['bio'])) {
            $summaryParts[] = trim((string) $profile['bio']);
        }
        if (!empty($options['current_role'])) {
            $summaryParts[] = 'Current background: ' . trim((string) $options['current_role']) . '.';
        }
        $summaryParts[] = 'Targeting ' . $targetRole . ' opportunities with an emphasis on transferable strengths, relevant projects, and measurable impact.';
        if (!empty($options['transition_summary'])) {
            $summaryParts[] = trim((string) $options['transition_summary']);
        }

        $sections = [];

        if (!empty($skills)) {
            $sections['skills'] = [
                'title' => 'Technical Skills',
                'groups' => $this->groupSkills($skills),
            ];
        }

        if (!empty($workExperiences)) {
            $sections['experience'] = [
                'title' => 'Professional Experience',
                'items' => array_map(function (array $experience) use ($targetRole): array {
                    return [
                        'headline' => trim((string) ($experience['job_title'] ?? 'Role')),
                        'subhead' => trim((string) ($experience['company_name'] ?? 'Company')),
                        'meta' => $this->buildMetaLine($experience),
                        'bullets' => [
                            trim((string) ($experience['description'] ?? '')) !== ''
                                ? preg_replace('/\s+/', ' ', trim((string) $experience['description']))
                                : 'Highlight outcomes, tools, and transferable achievements aligned with ' . $targetRole . '.',
                        ],
                    ];
                }, $workExperiences),
            ];
        }

        if (!empty($projects)) {
            $sections['projects'] = [
                'title' => 'Projects',
                'items' => array_map(function (array $project): array {
                    $subheadParts = array_values(array_filter([
                        trim((string) ($project['role_name'] ?? '')),
                        trim((string) ($project['tech_stack'] ?? '')),
                    ]));

                    $metaParts = array_values(array_filter([
                        $this->formatDateRange((string) ($project['start_date'] ?? ''), (string) ($project['end_date'] ?? '')),
                        trim((string) ($project['project_url'] ?? '')),
                    ]));

                    $bullets = array_values(array_filter([
                        trim((string) ($project['project_summary'] ?? '')),
                        trim((string) ($project['impact_metrics'] ?? '')),
                    ]));

                    return [
                        'headline' => trim((string) ($project['project_name'] ?? 'Project')),
                        'subhead' => implode(' | ', $subheadParts),
                        'meta' => implode(' | ', $metaParts),
                        'bullets' => $bullets,
                    ];
                }, $projects),
            ];
        }

        if (!empty($education)) {
            $sections['education'] = [
                'title' => 'Education',
                'items' => array_map(function (array $item): array {
                    return [
                        'headline' => trim((string) ($item['degree'] ?? 'Degree')),
                        'subhead' => trim((string) ($item['institution'] ?? 'Institution')),
                        'meta' => trim((string) (($item['start_year'] ?? '') . ' - ' . ($item['end_year'] ?? ''))),
                        'bullets' => array_values(array_filter([
                            trim((string) ($item['field_of_study'] ?? '')),
                        ])),
                    ];
                }, $education),
            ];
        }

        if (!empty($certifications)) {
            $sections['certifications'] = [
                'title' => 'Certifications',
                'items' => array_map(function (array $item): array {
                    return [
                        'headline' => trim((string) ($item['certification_name'] ?? 'Certification')),
                        'subhead' => trim((string) ($item['issuing_organization'] ?? 'Issuer')),
                        'meta' => trim((string) ($item['issue_date'] ?? '')),
                        'bullets' => [],
                    ];
                }, $certifications),
            ];
        }

        $payload = $this->normalizeResumePayload([
            'title' => $targetRole . ' Resume',
            'summary' => trim(implode(' ', array_filter($summaryParts))),
            'highlight_skills' => array_slice($skills, 0, 10),
            'sections' => $sections,
        ], $profile, $targetRole, $templateKey);

        return [
            'title' => $payload['title'],
            'summary' => $payload['summary'],
            'highlight_skills' => $payload['highlight_skills'],
            'content' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'template_key' => $payload['template_key'],
        ];
    }

    private function normalizeResumePayload(array $data, array $profile, string $targetRole, string $templateKey): array
    {
        $sections = is_array($data['sections'] ?? null) ? $data['sections'] : [];
        $normalizedTemplateKey = $templateKey !== '' ? $templateKey : 'modern_professional';
        $experienceItems = (array) ($sections['experience']['items'] ?? []);
        $projectItems = (array) ($sections['projects']['items'] ?? []);
        $isFresherCandidate = (int) ($profile['is_fresher_candidate'] ?? 0) === 1;

        if ($normalizedTemplateKey === 'executive_sidebar' && ($isFresherCandidate || empty($experienceItems))) {
            $normalizedTemplateKey = !empty($projectItems) ? 'tech_compact' : 'modern_professional';
        }

        return [
            'template_key' => $normalizedTemplateKey,
            'name' => (string) ($profile['name'] ?? 'Candidate'),
            'target_role' => $targetRole,
            'title' => trim((string) ($data['title'] ?? ($targetRole . ' Resume'))),
            'summary' => trim((string) ($data['summary'] ?? '')),
            'highlight_skills' => array_values(array_filter(array_map('trim', (array) ($data['highlight_skills'] ?? [])))),
            'sections' => $sections,
        ];
    }

    private function buildMetaLine(array $experience): string
    {
        $parts = [];
        $start = trim((string) ($experience['start_date'] ?? ''));
        $end = (int) ($experience['is_current'] ?? 0) === 1 ? 'Present' : trim((string) ($experience['end_date'] ?? ''));
        $location = trim((string) ($experience['location'] ?? ''));

        if ($start !== '' || $end !== '') {
            $parts[] = $this->formatDateRange($start, $end);
        }
        if ($location !== '') {
            $parts[] = $location;
        }

        return implode(' | ', $parts);
    }

    private function formatDateRange(string $start, string $end): string
    {
        $start = trim($start);
        $end = trim($end);

        if ($start === '' && $end === '') {
            return '';
        }

        return trim($start . ' - ' . ($end !== '' ? $end : 'Present'), ' -');
    }

    private function groupSkills(array $skills): array
    {
        $categories = [
            'Languages' => ['php', 'javascript', 'typescript', 'python', 'java', 'c#', 'c++', 'go', 'ruby', 'sql', 'html', 'css'],
            'Frameworks' => ['laravel', 'codeigniter', 'react', 'vue', 'angular', 'django', 'flask', 'express', 'node.js', 'node', 'spring', 'bootstrap'],
            'Tools & Platforms' => ['mysql', 'postgresql', 'mongodb', 'docker', 'aws', 'azure', 'gcp', 'git', 'github', 'linux', 'kubernetes', 'redis'],
        ];

        $grouped = [];
        $used = [];

        foreach ($categories as $label => $keywords) {
            $groupItems = [];
            foreach ($skills as $skill) {
                $skill = trim((string) $skill);
                if ($skill === '') {
                    continue;
                }
                $skillLower = strtolower($skill);
                foreach ($keywords as $keyword) {
                    if (str_contains($skillLower, $keyword)) {
                        $groupItems[] = $skill;
                        $used[$skillLower] = true;
                        break;
                    }
                }
            }
            $groupItems = array_values(array_unique($groupItems));
            if (!empty($groupItems)) {
                $grouped[] = ['label' => $label, 'items' => $groupItems];
            }
        }

        $other = [];
        foreach ($skills as $skill) {
            $skill = trim((string) $skill);
            if ($skill === '') {
                continue;
            }
            if (!isset($used[strtolower($skill)])) {
                $other[] = $skill;
            }
        }

        $other = array_values(array_unique($other));
        if (!empty($other)) {
            $grouped[] = ['label' => 'Other', 'items' => $other];
        }

        return $grouped;
    }

    private function callOpenAI(string $prompt): string
    {
        $apiKey = getenv('OPENAI_API_KEY');
        if (empty($apiKey)) {
            return '{}';
        }

        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [[
                'role' => 'system',
                'content' => 'You are an expert resume strategist. Return concise, valid JSON only.',
            ], [
                'role' => 'user',
                'content' => $prompt,
            ]],
            'temperature' => 0.5,
            'max_tokens' => 4000,
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim($apiKey),
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError || $httpCode !== 200) {
            log_message('error', 'AI resume generation failed: ' . $curlError . ' HTTP ' . $httpCode);
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
