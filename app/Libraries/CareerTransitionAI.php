<?php

namespace App\Libraries;

class CareerTransitionAI
{
    public function __construct()
    {
        // API keys loaded from environment in each method
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

    public function generateCourseContent($currentRole, $targetRole, $skillGaps)
    {
        set_time_limit(300);
        
        $skills = is_array($skillGaps) ? implode(', ', array_slice($skillGaps, 0, 3)) : $skillGaps;
        
        $prompt = "Create a professional career transition course: {$currentRole} → {$targetRole}. Skill gaps: {$skills}.

Generate 3 modules, 2 lessons each. Each lesson: 400+ words with practical steps and examples.

JSON format:
{
  \"modules\": [
    {
      \"number\": 1,
      \"title\": \"[Specific topic for {$targetRole}]\",
      \"description\": \"[What you'll learn]\",
      \"weeks\": 2,
      \"lessons\": [
        {
          \"number\": 1,
          \"title\": \"[Specific skill]\",
          \"content\": \"## Overview\n[What and why]\n\n## Step-by-Step Guide\n### Step 1\n[Instructions with examples]\n### Step 2\n[More steps]\n\n## Real-World Application\n[How it's used]\n\n## Common Challenges\n[Issues and solutions]\n\n## Key Takeaways\n[Summary]\",
          \"resources\": [\"[URL1]\", \"[URL2]\"],
          \"exercises\": [\"[Task1]\", \"[Task2]\"]
        }
      ]
    }
  ],
  \"daily_tasks\": [{\"day\": 1, \"title\": \"Module 1 - Lesson 1\", \"description\": \"Complete lesson\", \"duration\": 10, \"module\": 1, \"lesson\": 1}]
}

Make content practical and specific to {$targetRole}. Assume learner has {$currentRole} experience.";

        $response = $this->callOpenAI($prompt);
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['modules']) || empty($data['modules'])) {
            log_message('error', 'AI generation failed - using fallback. Response: ' . substr($response, 0, 500));
            log_message('error', 'JSON decode error: ' . json_last_error_msg());
            return $this->getFallbackCourse($currentRole, $targetRole, $skillGaps);
        }
        
        log_message('info', 'AI course generated successfully');
        return $data;
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

    private function callOpenAI($prompt)
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
                'content' => 'You are a career transition expert who creates practical, actionable learning content for any profession.'
            ], [
                'role' => 'user',
                'content' => $prompt
            ]],
            'temperature' => 0.7,
            'max_tokens' => 16000,
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
            CURLOPT_TIMEOUT => 90
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
