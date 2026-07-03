<?php

namespace App\Libraries;

class CareerTransitionAI
{
    public function __construct()
    {
        // API keys loaded from environment in each method
    }

    public function buildContextSummary(array $context): string
    {
        $currentRole = trim((string) ($context['current_role'] ?? ''));
        $targetRole = trim((string) ($context['target_role'] ?? ''));
        $skills = array_values(array_filter(array_map('trim', (array) ($context['candidate_skills'] ?? []))));
        $company = trim((string) ($context['current_company'] ?? ''));
        $bio = trim((string) ($context['candidate_bio'] ?? ''));

        $parts = [];
        if ($currentRole !== '') {
            $parts[] = 'Current role: ' . $currentRole;
        }
        if ($targetRole !== '') {
            $parts[] = 'Target role: ' . $targetRole;
        }
        if (!empty($skills)) {
            $parts[] = 'Candidate strengths: ' . implode(', ', $skills);
        }
        if ($company !== '') {
            $parts[] = 'Current company: ' . $company;
        }
        if ($bio !== '') {
            $parts[] = 'Background: ' . $bio;
        }

        return implode(' | ', $parts);
    }

    public function buildGenerationMetadata(string $stage, bool $shouldExpand = false, int $moduleCount = 4): array
    {
        return [
            'stage' => $stage,
            'should_expand' => $shouldExpand,
            'module_count' => $moduleCount,
        ];
    }

    private function buildEducationalCoursePrompt(string $currentRole, string $targetRole, string $skills): string
    {
        return <<<PROMPT
Create a professional career transition course from {$currentRole} to {$targetRole}.
Skill gaps to close: {$skills}

*** REAL EDUCATIONAL CONTENT (like Udemy), NOT guidance ***
DO write actual teaching content with definitions, examples, code, workflows, and exercises.
DO NOT write "study X", "research", or "understand best practices".

Generate 4 modules. Each module must contain 2-4 focused lessons, depending on topic complexity and covered skill gaps.
Use 2 lessons only for narrow modules, 3 lessons for normal modules, and 4 lessons for broad modules that combine multiple tools or concepts.
Each lesson content can be a short preview because the full lesson is generated when the learner opens it.

Use this structure in each lesson:
## Outcome
## Core Concepts
## Real-World Example
## Step-by-Step Implementation
## Practice Exercise
## Completion Checklist
## Resources

Return ONLY valid JSON with:
{
  "modules": [{
    "number": 1,
    "title": "Specific {$targetRole} Capability",
    "description": "Concrete outcome",
    "weeks": 2,
    "covered_skill_gaps": ["gap1", "gap2"],
    "lessons": [{
      "number": 1,
      "title": "Specific focused lesson",
      "covered_skill_gaps": ["gap1"],
      "content": "Short preview of the lesson outcome and practical work",
      "resources": ["https://docs.example.com"],
      "exercises": ["Specific task"]
    }]
  }],
  "daily_tasks": []
}

Rules:
- Use real tools/frameworks for {$targetRole}
- Include concrete examples and code where relevant
- Split broad skill gaps across multiple lessons instead of compressing a whole module into one lesson
- Sound like a senior {$targetRole} engineer teaching a junior learner
- Specific to {$targetRole}, not generic career advice
- Learner is coming from {$currentRole}
PROMPT;
    }

    private function buildCompactEducationalCoursePrompt(string $currentRole, string $targetRole, string $skills): string
    {
        return <<<PROMPT
Create a compact course from {$currentRole} to {$targetRole}.
Skill gaps: {$skills}

Write practical teaching content only. Return valid JSON with 4 modules and 2-4 lessons per module.
Each lesson should include a short preview with outcome, concepts, example, exercise, checklist, resources.
Use real examples and tools for {$targetRole}.
PROMPT;
    }

    private function buildStagedCoursePrompt(string $currentRole, string $targetRole, string $skills, string $stage, int $moduleNumber = 1, ?string $moduleTitle = null, ?string $moduleDescription = null, array $coveredSkillGaps = [], string $contextSummary = ''): string
    {
        $moduleTitle = trim((string) $moduleTitle);
        $moduleDescription = trim((string) $moduleDescription);
        $coveredGaps = implode(', ', array_values(array_filter(array_map('trim', $coveredSkillGaps))));
        $moduleContext = $moduleTitle !== '' ? "\nModule title: {$moduleTitle}\nModule description: {$moduleDescription}" : '';
        $contextText = $contextSummary !== '' ? "\nCandidate context: {$contextSummary}" : '';

        if ($stage === 'module') {
            return <<<PROMPT
Create one practical learning module for a career transition from {$currentRole} to {$targetRole}.
Skill gaps to close: {$skills}
Covered gaps: {$coveredGaps}{$contextText}
{$moduleContext}

Return ONLY valid JSON with one module containing 2-4 focused lessons.
Use 2 lessons for narrow modules, 3 lessons for normal modules, and 4 lessons for broad modules or modules covering multiple skill gaps.
{
  "number": {$moduleNumber},
  "title": "Specific module title",
  "description": "Concrete outcome",
  "weeks": 2,
  "covered_skill_gaps": ["gap1"],
  "lessons": [{
    "number": 1,
    "title": "Specific lesson title",
    "covered_skill_gaps": ["gap1"],
    "content": "Short lesson preview with outcome, concepts, example, exercise, checklist, resources",
    "resources": ["https://docs.example.com"],
    "exercises": ["Specific task"]
  }]
}

Rules:
- Do not return only one lesson unless the module is extremely narrow.
- Every covered skill gap must appear in at least one lesson.
- For database, backend, frontend, cloud, AI, analytics, security, or framework modules, prefer 3-4 lessons.
PROMPT;
        }

        return <<<PROMPT
Create a short course outline from {$currentRole} to {$targetRole}.
Skill gaps to close: {$skills}{$contextText}

Return ONLY valid JSON using this exact schema:
{
  "modules": [
    {
      "number": 1,
      "title": "Specific capability",
      "description": "Concrete outcome",
      "weeks": 2,
      "covered_skill_gaps": ["gap1", "gap2"]
    }
  ]
}

Provide 4 modules in the "modules" array.
PROMPT;
    }

    public function analyzeTransition($currentRole, $targetRole)
    {
        $prompt = "Analyze career transition from {$currentRole} to {$targetRole}. Provide ONLY valid JSON:
{
  \"skill_gaps\": [\"skill1\", \"skill2\", \"skill3\"],
  \"timeline\": \"X weeks\",
  \"roadmap\": [{\"phase\": \"Phase 1\", \"duration\": \"2 weeks\", \"focus\": \"description\"}],
  \"daily_tasks\": [
    {\"day\": 1, \"title\": \"Module 1 - Lesson 1\", \"description\": \"Start with first lesson\", \"duration\": 10, \"module\": 1, \"lesson\": 1},
    {\"day\": 2, \"title\": \"Module 1 - Lesson 2\", \"description\": \"Continue learning\", \"duration\": 10, \"module\": 1, \"lesson\": 2}
  ]
}";

        $response = $this->callOpenAI($prompt);
        $data = json_decode($response, true);
        if (is_array($data)) {
            (new UsageAnalyticsService())->logOpenAiUsage($data, '/v1/chat/completions', 'gpt-4o-mini');
        }
        
        if (!$data || !isset($data['skill_gaps'])) {
            return $this->getFallbackData($currentRole, $targetRole);
        }
        
        return $data;
    }

    public function generateCourseContent($currentRole, $targetRole, $skillGaps, array $context = [])
    {
        set_time_limit(300);
        
        $skillList = is_array($skillGaps)
            ? array_values(array_filter(array_map('trim', $skillGaps)))
            : array_values(array_filter(array_map('trim', explode(',', (string) $skillGaps))));
        $skills = implode(', ', array_slice($skillList, 0, 8));

        $stagedCourse = $this->generateCourseContentInStages($currentRole, $targetRole, $skills, $context);
        if (!empty($stagedCourse['modules']) && !$this->hasEmptyLessonContent($stagedCourse)) {
            log_message('info', 'AI course generated successfully via staged workflow');
            return $stagedCourse;
        }

        if (!empty($stagedCourse['modules'])) {
            log_message('warning', 'Staged course generated with empty lesson content; trying Udemy-style fallback.');
            $udemyCourse = $this->generateUdemyStyleCourseContent($currentRole, $targetRole, $skills, $context);
            if (!empty($udemyCourse['modules']) && !$this->hasEmptyLessonContent($udemyCourse)) {
                log_message('info', 'Udemy-style fallback produced complete lesson content');
                return $udemyCourse;
            }
        }

        $prompt = $this->buildEducationalCoursePrompt($currentRole, $targetRole, $skills);

        $response = $this->callOpenAI($prompt, 6000, 120);
        $data = json_decode($response, true);

        if (!$data || !isset($data['modules']) || empty($data['modules'])) {
            $compactPrompt = $this->buildCompactEducationalCoursePrompt($currentRole, $targetRole, $skills);
            $response = $this->callOpenAI($compactPrompt, 4000, 90);
            $data = json_decode($response, true);
        }
        
        if (!$data || !isset($data['modules']) || empty($data['modules'])) {
            log_message('error', 'AI generation failed - using fallback. Response: ' . substr($response, 0, 500));
            log_message('error', 'JSON decode error: ' . json_last_error_msg());
            return $this->getFallbackCourse($currentRole, $targetRole, $skillGaps);
        }

        if ($this->isCourseTooBrief($data)) {
            log_message('warning', 'AI course content was too brief; requesting expanded course content.');
            $expandedData = $this->expandBriefCourseContent($currentRole, $targetRole, $skills, $data);
            if (!empty($expandedData['modules']) && !$this->isCourseTooBrief($expandedData)) {
                $data = $expandedData;
            } else {
                log_message('warning', 'Expanded course content was still brief; using detailed fallback course.');
                return $this->getFallbackCourse($currentRole, $targetRole, $skillGaps);
            }
        }
        
        log_message('info', 'AI course generated successfully');
        return $data;
    }

    public function generateLessonContent(
        string $currentRole,
        string $targetRole,
        array $skillGaps,
        array $module,
        array $lesson,
        array $context = []
    ): array {
        set_time_limit(180);

        $skills = implode(', ', array_slice(array_values(array_filter(array_map('trim', $skillGaps))), 0, 8));
        $moduleTitle = trim((string) ($module['title'] ?? 'Course module'));
        $moduleDescription = trim((string) ($module['description'] ?? ''));
        $lessonTitle = trim((string) ($lesson['title'] ?? 'Lesson'));
        $lessonNumber = (int) ($lesson['lesson_number'] ?? $lesson['number'] ?? 1);
        $contextSummary = $this->buildContextSummary($context);

        $prompt = "Create ONE full Udemy-style lesson for a career transition course.

Current role: {$currentRole}
Target role: {$targetRole}
Skill gaps to cover: {$skills}
Module: {$moduleTitle}
Module description: {$moduleDescription}
Lesson {$lessonNumber}: {$lessonTitle}
Learner context: {$contextSummary}

Return ONLY valid JSON with this exact schema:
{
  \"content\": \"markdown lesson text\",
  \"resources\": [\"https://...\"],
  \"exercises\": [\"exercise text\"]
}

The content must be 1200-1800 words and must read like an actual course lesson, not an outline or advice checklist.
Teach the topic directly with explanations, examples, and guided work.
Use these markdown sections exactly:
## What You Will Build
## Lesson
## Guided Walkthrough
## Worked Example
## Practice Lab
## Common Mistakes
## Career Application
## Knowledge Check
## Completion Checklist

Requirements:
- Include concrete code, workflow, commands, formulas, scripts, or artifacts where relevant to the lesson.
- The Guided Walkthrough must have at least 7 detailed steps with enough detail to follow inside the platform.
- The Worked Example must be realistic for {$targetRole}, not generic.
- The Practice Lab must produce a portfolio-ready artifact.
- The Knowledge Check must include at least 5 questions with short expected answers.
- Avoid phrases like \"review provided resources\", \"research this topic\", or \"read documentation\" as the main teaching.
- Resources must be real high-quality URLs from official documentation or trusted learning references.
- Exercises must be hands-on and specific.";

        $response = $this->callOpenAI($prompt, 6000, 120);
        $data = json_decode($response, true);

        if (!is_array($data) || trim((string) ($data['content'] ?? '')) === '') {
            return [];
        }

        return [
            'content' => trim((string) ($data['content'] ?? '')),
            'resources' => array_values(array_filter(array_map('trim', (array) ($data['resources'] ?? [])))),
            'exercises' => array_values(array_filter(array_map('trim', (array) ($data['exercises'] ?? [])))),
        ];
    }

    public function generateModuleLessons(
        string $currentRole,
        string $targetRole,
        array $skillGaps,
        array $module,
        array $context = []
    ): array {
        set_time_limit(120);

        $skills = implode(', ', array_slice(array_values(array_filter(array_map('trim', $skillGaps))), 0, 8));
        $moduleNumber = (int) ($module['module_number'] ?? $module['number'] ?? 1);
        $moduleTitle = trim((string) ($module['title'] ?? 'Course module'));
        $moduleDescription = trim((string) ($module['description'] ?? ''));
        $coveredSkillGaps = array_values(array_filter(array_map('trim', (array) ($module['covered_skill_gaps'] ?? $skillGaps))));
        $contextSummary = $this->buildContextSummary($context);

        $prompt = $this->buildStagedCoursePrompt(
            $currentRole,
            $targetRole,
            $skills,
            'module',
            $moduleNumber,
            $moduleTitle,
            $moduleDescription,
            $coveredSkillGaps,
            $contextSummary
        );
        $response = $this->callOpenAI($prompt, 3600, 90);
        $data = json_decode($response, true);
        $lessons = is_array($data) ? (array) ($data['lessons'] ?? []) : [];

        return array_values(array_filter(array_map(static function (array $lesson): array {
            $title = trim((string) ($lesson['title'] ?? ''));
            if ($title === '') {
                return [];
            }

            return [
                'number' => (int) ($lesson['number'] ?? 1),
                'title' => $title,
                'covered_skill_gaps' => array_values(array_filter(array_map('trim', (array) ($lesson['covered_skill_gaps'] ?? [])))),
                'content' => trim((string) ($lesson['content'] ?? '')),
                'resources' => array_values(array_filter(array_map('trim', (array) ($lesson['resources'] ?? [])))),
                'exercises' => array_values(array_filter(array_map('trim', (array) ($lesson['exercises'] ?? [])))),
            ];
        }, $lessons)));
    }

    private function generateCourseContentInStages(string $currentRole, string $targetRole, string $skills, array $context = []): array
    {
        $outlineData = $this->buildCourseOutlineInStages($currentRole, $targetRole, $skills, $context);
        if (empty($outlineData['modules'])) {
            return [];
        }

        $modules = [];
        foreach ($outlineData['modules'] as $moduleSpec) {
            $moduleData = $this->buildModuleInStages($currentRole, $targetRole, $skills, $moduleSpec, $context);
            if (!empty($moduleData['lessons'])) {
                $modules[] = $moduleData;
            }
        }

        if (empty($modules)) {
            return [];
        }

        $dailyTasks = [];
        $day = 1;
        foreach ($modules as $moduleIndex => $module) {
            foreach ($module['lessons'] ?? [] as $lessonIndex => $lesson) {
                $dailyTasks[] = [
                    'day' => $day++,
                    'title' => 'Module ' . ($module['number'] ?? ($moduleIndex + 1)) . ' - ' . ($lesson['title'] ?? 'Lesson'),
                    'description' => 'Complete lesson: ' . ($lesson['title'] ?? 'Lesson'),
                    'duration' => 10,
                    'module' => $module['number'] ?? ($moduleIndex + 1),
                    'lesson' => $lesson['number'] ?? ($lessonIndex + 1),
                ];
            }
        }

        return [
            'modules' => $modules,
            'daily_tasks' => $dailyTasks,
        ];
    }

    private function hasEmptyLessonContent(array $courseData): bool
    {
        foreach (($courseData['modules'] ?? []) as $module) {
            foreach (($module['lessons'] ?? []) as $lesson) {
                if (trim((string) ($lesson['content'] ?? '')) === '') {
                    return true;
                }
            }
        }

        return false;
    }

    private function generateUdemyStyleCourseContent(string $currentRole, string $targetRole, string $skills, array $context = []): array
    {
        $fallback = new CareerTransitionAIv2();
        $prompt = $fallback->buildEducationalPrompt($currentRole, $targetRole, $skills);
        $response = $fallback->callOpenAI($prompt);
        $data = json_decode($response, true);

        if (!is_array($data) || empty($data['modules'])) {
            return [];
        }

        foreach ($data['modules'] as &$module) {
            $module['lessons'] = array_values(array_map(static function (array $lesson): array {
                return [
                    'number' => (int) ($lesson['number'] ?? 1),
                    'title' => trim((string) ($lesson['title'] ?? 'Lesson')),
                    'covered_skill_gaps' => array_values(array_filter(array_map('trim', (array) ($lesson['covered_skill_gaps'] ?? [])))),
                    'content' => trim((string) ($lesson['content'] ?? '')),
                    'resources' => array_values(array_filter(array_map('trim', (array) ($lesson['resources'] ?? [])))),
                    'exercises' => array_values(array_filter(array_map('trim', (array) ($lesson['exercises'] ?? [])))),
                ];
            }, (array) ($module['lessons'] ?? [])));
        }
        unset($module);

        return $data;
    }

    private function buildCourseOutlineInStages(string $currentRole, string $targetRole, string $skills, array $context = []): array
    {
        $metadata = $this->buildGenerationMetadata('outline', true, 4);
        $contextSummary = $this->buildContextSummary($context);
        $prompt = $this->buildStagedCoursePrompt($currentRole, $targetRole, $skills, 'outline', 1, null, null, [], $contextSummary);
        $response = $this->callOpenAI($prompt, 2500, 75);
        $data = json_decode($response, true);

        if (is_array($data) && !empty($data['modules'])) {
            log_message('info', 'Staged outline generated with ' . count($data['modules']) . ' modules. Metadata: ' . json_encode($metadata));
            return $data;
        }

        log_message('warning', 'Staged outline generation returned invalid outline, falling back');
        return [];
    }

    private function buildModuleInStages(string $currentRole, string $targetRole, string $skills, array $moduleSpec, array $context = []): array
    {
        $moduleNumber = (int) ($moduleSpec['number'] ?? 1);
        $moduleTitle = trim((string) ($moduleSpec['title'] ?? ''));
        $moduleDescription = trim((string) ($moduleSpec['description'] ?? ''));
        $weeks = (int) ($moduleSpec['weeks'] ?? 1);
        $coveredSkillGaps = array_values(array_filter(array_map('trim', (array) ($moduleSpec['covered_skill_gaps'] ?? [])))) ;
        $metadata = $this->buildGenerationMetadata('module', false, 1);
        $contextSummary = $this->buildContextSummary($context);

        $prompt = $this->buildStagedCoursePrompt($currentRole, $targetRole, $skills, 'module', $moduleNumber, $moduleTitle, $moduleDescription, $coveredSkillGaps, $contextSummary);
        $response = $this->callOpenAI($prompt, 2800, 90);
        $data = json_decode($response, true);

        if (is_array($data) && !empty($data['lessons'])) {
            log_message('info', 'Staged module generated for module ' . $moduleNumber . '. Metadata: ' . json_encode($metadata));
            return [
                'number' => $moduleNumber,
                'title' => $moduleTitle !== '' ? $moduleTitle : 'Practical module',
                'description' => $moduleDescription !== '' ? $moduleDescription : 'Build the key capability for ' . $targetRole,
                'weeks' => $weeks,
                'covered_skill_gaps' => $coveredSkillGaps,
                'lessons' => array_values(array_map(static function (array $lesson): array {
                    return [
                        'number' => (int) ($lesson['number'] ?? 1),
                        'title' => trim((string) ($lesson['title'] ?? 'Lesson')),
                        'covered_skill_gaps' => array_values(array_filter(array_map('trim', (array) ($lesson['covered_skill_gaps'] ?? [])))),
                        'content' => trim((string) ($lesson['content'] ?? '')),
                        'resources' => array_values(array_filter(array_map('trim', (array) ($lesson['resources'] ?? [])))),
                        'exercises' => array_values(array_filter(array_map('trim', (array) ($lesson['exercises'] ?? [])))),
                    ];
                }, (array) ($data['lessons'] ?? []))),
            ];
        }

        $fallbackLessonTitle = $moduleTitle !== '' ? 'Apply ' . $moduleTitle : 'Build the target skill';
        return [
            'number' => $moduleNumber,
            'title' => $moduleTitle !== '' ? $moduleTitle : 'Practical module',
            'description' => $moduleDescription !== '' ? $moduleDescription : 'Build the key capability for ' . $targetRole,
            'weeks' => $weeks,
            'covered_skill_gaps' => $coveredSkillGaps,
            'lessons' => [[
                'number' => 1,
                'title' => $fallbackLessonTitle,
                'covered_skill_gaps' => $coveredSkillGaps,
                'content' => 'Build practical competency in ' . ($moduleTitle !== '' ? $moduleTitle : $targetRole) . ' by practicing a small real-world scenario, documenting your workflow, and reflecting on how the skill maps to your upcoming role.',
                'resources' => ['https://docs.example.com'],
                'exercises' => ['Complete one portfolio-ready deliverable tied to this module.'],
            ]],
        ];
    }

    private function isCourseTooBrief(array $courseData): bool
    {
        $lessonCount = 0;
        $totalWords = 0;

        foreach (($courseData['modules'] ?? []) as $module) {
            foreach (($module['lessons'] ?? []) as $lesson) {
                $lessonCount++;
                $wordCount = str_word_count(strip_tags((string) ($lesson['content'] ?? '')));
                $totalWords += $wordCount;
                if ($wordCount < 800) {
                    return true;
                }
            }
        }

        if ($lessonCount === 0) {
            return true;
        }

        return ($totalWords / $lessonCount) < 950;
    }

    private function expandBriefCourseContent(string $currentRole, string $targetRole, string $skills, array $courseData): array
    {
        $compactCourse = json_encode($courseData, JSON_UNESCAPED_SLASHES);
        if (!$compactCourse) {
            return [];
        }

        $prompt = "Expand this career transition course from {$currentRole} to {$targetRole}. Skill gaps: {$skills}.

Return ONLY valid JSON in the same schema. Keep the same module and lesson numbers/titles, but rewrite every lesson content field to be 1000-1400 words.

Each lesson content must include:
## Outcome
## Why This Matters
## Concepts
## Step-by-Step Work with at least 6 detailed steps
## {$targetRole} Example with a realistic workplace or portfolio scenario
## Mini Project with a portfolio-ready deliverable
## Common Mistakes with fixes
## Interview Readiness with talking points and likely questions
## Completion Checklist with at least 7 checks

Make every lesson practical, role-specific, and detailed enough that a learner can execute it without another source. Do not compress the content into short bullets.

Course JSON to expand:
{$compactCourse}";

        $response = $this->callOpenAI($prompt);
        $expanded = json_decode($response, true);

        return is_array($expanded) ? $expanded : [];
    }

    private function getFallbackCourse($currentRole, $targetRole, $skillGaps)
    {
        $skills = is_array($skillGaps) ? implode(', ', $skillGaps) : $skillGaps;
        
        $modules = [
            [
                'number' => 1,
                'title' => 'Foundation Skills for ' . $targetRole,
                'description' => 'Master the fundamental concepts required for transitioning to ' . $targetRole,
                'weeks' => 4,
                'lessons' => [
                    [
                        'number' => 1,
                        'title' => 'Understanding Core Concepts',
                        'content' => "Begin your journey by understanding the fundamental principles that define {$targetRole}. This role requires a solid grasp of {$skills}.\n\nKey Concepts:\n\nFirst, research industry standards and best practices. Understanding the landscape is crucial - study what makes professionals successful in this role. Read official documentation, follow industry leaders on social media, and join relevant online communities.\n\nSecond, understand how these skills interconnect. No skill exists in isolation - they work together to solve real-world problems. For example, if you're learning a new programming language, understand how it integrates with databases, APIs, and frontend frameworks.\n\nThird, build a strong theoretical foundation before diving into practical applications. While hands-on practice is important, understanding the 'why' behind concepts will make you a better problem-solver. Study design patterns, architectural principles, and the reasoning behind best practices.\n\nPractical Approach:\n\nCreate a personal knowledge base documenting key concepts, terminologies, and best practices. Use tools like Notion, Obsidian, or even a simple markdown file in GitHub. This becomes your reference throughout the learning journey.\n\nStudy real-world use cases and analyze how professionals approach problem-solving. Look at open-source projects, read technical blogs, and watch conference talks. Pay attention to how experienced developers structure their code and make decisions.\n\nFinally, practice explaining concepts in simple terms. If you can teach something, you truly understand it. Write blog posts, create tutorials, or explain concepts to friends. This reinforces your learning and builds your personal brand.",
                        'resources' => [
                            'https://www.coursera.org/courses?query=' . urlencode($targetRole),
                            'https://www.udemy.com/courses/search/?q=' . urlencode($targetRole),
                            'https://roadmap.sh/'
                        ],
                        'exercises' => [
                            'Create a comprehensive mind map of key concepts in ' . $targetRole,
                            'Write a 500-word summary explaining the role requirements to a beginner',
                            'List 10 companies hiring for this role and analyze their common requirements'
                        ]
                    ],
                    [
                        'number' => 2,
                        'title' => 'Hands-on Practice',
                        'content' => "Theory alone is insufficient - practical application is crucial for mastering {$targetRole}.\n\nSetting Up Your Environment:\n\nStart by setting up a proper development environment. Install necessary tools, configure your IDE, and familiarize yourself with the ecosystem. Don't skip this step - a well-configured environment saves hours of frustration later.\n\nUse version control (Git) from day one. Even for small projects, commit regularly with meaningful messages. This builds good habits and creates a portfolio of your progress. Push your code to GitHub to make it accessible and shareable.\n\nBuilding Projects:\n\nBegin with tutorials but don't just copy-paste code. Type everything manually and experiment with modifications. Ask yourself: 'What happens if I change this?' Breaking things and fixing them is how you truly learn.\n\nGradually increase project complexity. Start with a simple 'Hello World', then build a calculator, then a todo app, then something more complex. Each project should challenge you slightly beyond your current comfort zone.\n\nFocus on writing clean, maintainable code following industry best practices. Use meaningful variable names, write comments for complex logic, and structure your code logically. Bad habits formed early are hard to break.\n\nLearning from Others:\n\nJoin online communities like Stack Overflow, Reddit, or Discord servers related to your target role. Don't just ask questions - answer them too. Teaching others reinforces your own understanding.\n\nParticipate in code reviews. Share your projects and ask for feedback. Be open to criticism - every critique is an opportunity to improve. Similarly, review others' code to learn different approaches.\n\nBuild at least 3-5 small projects that demonstrate your understanding. Document each project thoroughly with README files explaining your approach, challenges faced, and solutions implemented. This becomes your portfolio.",
                        'resources' => [
                            'https://github.com/topics/' . urlencode(strtolower($targetRole)),
                            'https://stackoverflow.com/',
                            'https://www.freecodecamp.org/'
                        ],
                        'exercises' => [
                            'Complete 10 beginner-level coding challenges on LeetCode or HackerRank',
                            'Build a simple project using your new skills and deploy it online',
                            'Contribute to an open-source project on GitHub (even fixing typos counts!)'
                        ]
                    ]
                ]
            ],
            [
                'number' => 2,
                'title' => 'Advanced Techniques',
                'description' => 'Deepen your expertise with advanced concepts',
                'weeks' => 4,
                'lessons' => [
                    [
                        'number' => 1,
                        'title' => 'Advanced Technical Skills',
                        'content' => "Now that you have a foundation, it's time to dive deeper into advanced topics that separate beginners from professionals in {$targetRole}.\n\nDesign Patterns and Architecture:\n\nStudy common design patterns like Singleton, Factory, Observer, and Strategy. These aren't just academic concepts - they're proven solutions to recurring problems. Understanding when and how to apply them is crucial.\n\nLearn about architectural principles like SOLID, DRY (Don't Repeat Yourself), and KISS (Keep It Simple, Stupid). These principles guide you in writing maintainable, scalable code that other developers can understand and extend.\n\nUnderstand different architectural styles: MVC, microservices, serverless, event-driven architecture. Each has its use cases, advantages, and trade-offs. Know when to use which approach.\n\nPerformance and Optimization:\n\nLearn about performance optimization techniques. Understand time and space complexity (Big O notation). Profile your applications to identify bottlenecks. Remember: premature optimization is the root of all evil, but knowing how to optimize when needed is essential.\n\nStudy caching strategies, database indexing, and query optimization. Many performance issues stem from inefficient database operations. Learn to write efficient queries and use appropriate indexes.\n\nTesting and Quality:\n\nMaster testing methodologies: unit tests, integration tests, end-to-end tests. Write tests before or alongside your code (TDD/BDD). Tests are documentation that never goes out of date and give you confidence to refactor.\n\nUnderstand debugging techniques. Learn to use debuggers effectively, read stack traces, and systematically isolate issues. Good debugging skills save countless hours.\n\nContinuous Learning:\n\nRead source code of popular libraries and frameworks. This exposes you to professional coding standards and advanced techniques. Don't just use libraries - understand how they work internally.\n\nAttend webinars, watch conference talks, and follow industry experts. Technology evolves rapidly - staying current is part of the job. Subscribe to newsletters, podcasts, and blogs in your field.",
                        'resources' => [
                            'https://refactoring.guru/design-patterns',
                            'https://www.patterns.dev/',
                            'https://martinfowler.com/'
                        ],
                        'exercises' => [
                            'Refactor an existing project using at least 3 design patterns',
                            'Write comprehensive unit tests achieving 80%+ code coverage',
                            'Optimize a slow application and document the improvements with benchmarks'
                        ]
                    ],
                    [
                        'number' => 2,
                        'title' => 'Production-Ready Applications',
                        'content' => "Professional developers build applications that are maintainable, scalable, and production-ready. This lesson covers what it takes to deploy and maintain real-world applications.\n\nCI/CD and DevOps:\n\nLearn about Continuous Integration and Continuous Deployment. Set up automated pipelines that run tests, check code quality, and deploy automatically. Tools like GitHub Actions, Jenkins, or GitLab CI make this accessible.\n\nUnderstand containerization with Docker. Containers ensure your application runs consistently across different environments. Learn to write Dockerfiles and use docker-compose for multi-container applications.\n\nExplore orchestration with Kubernetes if working with microservices. While complex, Kubernetes is industry-standard for managing containerized applications at scale.\n\nCloud Platforms:\n\nStudy major cloud platforms: AWS, Azure, or Google Cloud. You don't need to master all services, but understand core offerings: compute (EC2, Lambda), storage (S3), databases (RDS), and networking (VPC).\n\nLearn Infrastructure as Code (IaC) using tools like Terraform or CloudFormation. Managing infrastructure through code makes it reproducible, version-controlled, and easier to maintain.\n\nMonitoring and Observability:\n\nImplement logging using structured logging libraries. Good logs are invaluable for debugging production issues. Log meaningful information but avoid logging sensitive data.\n\nSet up error tracking with tools like Sentry or Rollbar. Know when things break in production before users complain. Configure alerts for critical errors.\n\nImplement performance monitoring and APM (Application Performance Monitoring). Tools like New Relic or DataDog help identify performance bottlenecks in production.\n\nSecurity Best Practices:\n\nUnderstand common security vulnerabilities (OWASP Top 10): SQL injection, XSS, CSRF, etc. Learn how to prevent them. Security isn't optional - it's fundamental.\n\nImplement proper authentication and authorization. Use established libraries and frameworks rather than rolling your own. Understand OAuth, JWT, and session management.\n\nPractice defense in depth: validate all inputs, sanitize outputs, use HTTPS, keep dependencies updated, and follow the principle of least privilege.",
                        'resources' => [
                            'https://12factor.net/',
                            'https://aws.amazon.com/getting-started/',
                            'https://owasp.org/www-project-top-ten/'
                        ],
                        'exercises' => [
                            'Deploy an application to a cloud platform with proper CI/CD pipeline',
                            'Set up monitoring, logging, and alerting for a production application',
                            'Implement authentication and authorization with proper security measures'
                        ]
                    ]
                ]
            ],
            [
                'number' => 3,
                'title' => 'Career Preparation',
                'description' => 'Prepare for job interviews and build portfolio',
                'weeks' => 4,
                'lessons' => [
                    [
                        'number' => 1,
                        'title' => 'Building Your Portfolio',
                        'content' => "Your portfolio is your professional showcase - it's often more important than your resume for technical roles.\n\nCreating Your Portfolio Website:\n\nBuild a personal website that highlights your projects, skills, and achievements. Keep it simple, fast, and mobile-responsive. Your portfolio itself demonstrates your technical skills.\n\nInclude an 'About Me' section that tells your story. Why are you transitioning to {$targetRole}? What drives you? Make it personal and authentic.\n\nShowcase 3-5 of your best projects. Quality over quantity - it's better to have three polished projects than ten half-finished ones.\n\nProject Case Studies:\n\nFor each project, write a detailed case study explaining:\n- The problem you were solving\n- Your approach and technical decisions\n- Challenges faced and how you overcame them\n- The impact or results\n- Technologies used and why\n\nInclude screenshots, diagrams, and code snippets. Make it easy for recruiters to understand your work even if they're not technical.\n\nProvide links to live demos and GitHub repositories. Ensure your code is clean, well-documented, and includes a comprehensive README.\n\nGitHub Profile Optimization:\n\nYour GitHub profile is your technical resume. Ensure it's polished:\n- Complete profile with photo and bio\n- Pinned repositories showcasing your best work\n- Consistent commit history (shows you code regularly)\n- Well-documented repositories with clear README files\n- Meaningful commit messages\n\nContribute to open-source projects. Even small contributions (documentation, bug fixes) demonstrate collaboration skills and initiative.\n\nContent Creation:\n\nWrite technical blog posts about your learning journey. Share insights, tutorials, or solutions to problems you've solved. This demonstrates communication skills and helps others.\n\nCreate video demos of your projects. A 2-3 minute walkthrough showing functionality and explaining technical decisions is powerful.\n\nBe active on LinkedIn. Share your projects, write posts about what you're learning, and engage with the community. Networking is crucial for career transitions.",
                        'resources' => [
                            'https://github.com/topics/portfolio-website',
                            'https://dev.to/',
                            'https://www.linkedin.com/'
                        ],
                        'exercises' => [
                            'Create a professional portfolio website and deploy it',
                            'Write 3 technical blog posts about your learning journey',
                            'Record a 5-minute video demo of your best project'
                        ]
                    ],
                    [
                        'number' => 2,
                        'title' => 'Interview Preparation',
                        'content' => "Preparing systematically for technical interviews is crucial for successfully transitioning to {$targetRole}.\n\nTechnical Interview Preparation:\n\nPractice coding challenges daily on platforms like LeetCode, HackerRank, or CodeSignal. Start with easy problems and gradually increase difficulty. Aim to solve at least 100-150 problems.\n\nFocus on data structures and algorithms: arrays, linked lists, trees, graphs, sorting, searching, dynamic programming. These form the foundation of technical interviews.\n\nUnderstand time and space complexity (Big O notation). You'll be asked to analyze the efficiency of your solutions. Practice explaining your thought process clearly.\n\nSystem Design Interviews:\n\nFor senior roles, study system design. Learn to design scalable systems: load balancers, caching, databases, microservices, message queues.\n\nPractice explaining trade-offs. There's rarely one 'correct' answer in system design - it's about understanding pros and cons of different approaches.\n\nStudy real-world architectures: how does Twitter handle millions of tweets? How does Netflix stream video globally? Learn from these examples.\n\nBehavioral Interviews:\n\nPrepare stories using the STAR method (Situation, Task, Action, Result). Have examples ready for:\n- Challenging projects you've worked on\n- Times you've failed and what you learned\n- Conflicts with team members and how you resolved them\n- Leadership and initiative\n\nBe honest about your career transition. Frame it positively - you're not running from something, you're running toward something. Explain what excites you about the new role.\n\nJob Search Strategy:\n\nResearch companies thoroughly before applying. Tailor your resume and cover letter for each position. Generic applications rarely succeed.\n\nNetwork actively. Many jobs are filled through referrals before they're even posted. Attend meetups, conferences, and online events. Connect with people in your target role.\n\nPrepare thoughtful questions to ask interviewers. This shows genuine interest and helps you evaluate if the company is right for you.\n\nMock Interviews:\n\nPractice mock interviews with peers or use platforms like Pramp. Getting comfortable with the interview format is crucial.\n\nRecord yourself explaining technical concepts. Watch the recordings to improve your communication.\n\nStay Positive:\n\nRejections are part of the process. Each interview is practice for the next one. Learn from feedback and keep improving.\n\nKeep track of applications in a spreadsheet. Follow up professionally after interviews. Persistence pays off.",
                        'resources' => [
                            'https://leetcode.com/',
                            'https://www.pramp.com/',
                            'https://www.glassdoor.com/Interview/'
                        ],
                        'exercises' => [
                            'Solve 50 coding problems on LeetCode (mix of easy, medium, hard)',
                            'Complete 5 mock interviews with peers or online platforms',
                            'Apply to 20 relevant job positions with tailored resumes'
                        ]
                    ]
                ]
            ]
        ];

        $dailyTasks = [];
        $day = 1;
        foreach ($modules as $module) {
            foreach ($module['lessons'] as $lesson) {
                $dailyTasks[] = [
                    'day' => $day++,
                    'title' => 'Module ' . $module['number'] . ' - ' . $lesson['title'],
                    'description' => 'Complete lesson: ' . $lesson['title'],
                    'duration' => 10,
                    'module' => $module['number'],
                    'lesson' => $lesson['number']
                ];
            }
        }

        return [
            'modules' => $modules,
            'daily_tasks' => $dailyTasks
        ];
    }

    private function getFallbackData($currentRole, $targetRole)
    {
        return [
            'skill_gaps' => ['Core Skills', 'Best Practices', 'Industry Tools'],
            'timeline' => '12 weeks',
            'roadmap' => [
                ['phase' => 'Foundation', 'duration' => '4 weeks', 'focus' => 'Learn fundamentals'],
                ['phase' => 'Advanced', 'duration' => '4 weeks', 'focus' => 'Master advanced topics'],
                ['phase' => 'Career Prep', 'duration' => '4 weeks', 'focus' => 'Build portfolio']
            ],
            'daily_tasks' => [
                ['day' => 1, 'title' => 'Module 1 - Understanding Core Concepts', 'description' => 'Complete first lesson', 'duration' => 10, 'module' => 1, 'lesson' => 1],
                ['day' => 2, 'title' => 'Module 1 - Hands-on Practice', 'description' => 'Complete second lesson', 'duration' => 10, 'module' => 1, 'lesson' => 2],
                ['day' => 3, 'title' => 'Module 2 - Advanced Technical Skills', 'description' => 'Complete third lesson', 'duration' => 10, 'module' => 2, 'lesson' => 1]
            ]
        ];
    }

    private function callOpenAI($prompt, int $maxTokens = 6000, int $timeoutSeconds = 120)
    {
        $apiKey = getenv('OPENAI_API_KEY');
        if (empty($apiKey)) {
            log_message('error', 'OpenAI API key missing from .env');
            return '{}';
        }
        
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => [[
                'role' => 'system',
                'content' => 'You are a career transition expert who creates practical, actionable learning content for any profession. Generate content efficiently without extra preamble.'
            ], [
                'role' => 'user',
                'content' => $prompt
            ]],
            'temperature' => 0.6,
            'max_tokens' => $maxTokens,
            'stream' => false
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
            CURLOPT_TIMEOUT => $timeoutSeconds,
            CURLOPT_CONNECTTIMEOUT => 20
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || !empty($curlError)) {
            log_message('error', 'OpenAI cURL error: ' . $curlError);
            return '{}';
        }

        if ($httpCode !== 200) {
            log_message('error', 'OpenAI API Error: HTTP ' . $httpCode . ' - ' . substr($response, 0, 500));
            return '{}';
        }

        $data = json_decode($response, true);
        
        if (!isset($data['choices'][0]['message']['content'])) {
            log_message('error', 'OpenAI response missing content. Response: ' . substr($response, 0, 500));
            return '{}';
        }
        
        // Check if response was truncated
        $finishReason = $data['choices'][0]['finish_reason'] ?? 'unknown';
        if ($finishReason === 'length') {
            log_message('error', 'OpenAI response truncated due to max_tokens limit');
            return '{}';
        }
        
        $content = $data['choices'][0]['message']['content'];
        $extracted = $this->extractJSON($content);
        
        log_message('info', 'OpenAI response extracted. Length: ' . strlen($extracted) . ', Finish reason: ' . $finishReason);
        
        return $extracted;
    }
    
    private function extractJSON($content)
    {
        // Remove markdown code blocks if present
        $content = preg_replace('/```(?:json)?\s*/', '', $content);
        $content = preg_replace('/```\s*$/', '', $content);
        $content = trim($content);
        
        // Find the first { and last } to extract complete JSON
        $firstBrace = strpos($content, '{');
        $lastBrace = strrpos($content, '}');
        
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $json = substr($content, $firstBrace, $lastBrace - $firstBrace + 1);
            
            // Validate it's proper JSON
            $test = json_decode($json, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                log_message('info', 'Valid JSON extracted, length: ' . strlen($json));
                return $json;
            } else {
                log_message('error', 'Extracted JSON is invalid: ' . json_last_error_msg());
            }
        }
        
        log_message('error', 'No valid JSON found. Content preview: ' . substr($content, 0, 300));
        return '{}';
    }
}
