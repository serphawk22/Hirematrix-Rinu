<?php

namespace App\Libraries;

/**
 * RecruiterChatbotService — RAG engine for the recruiter AI assistant.
 *
 * Retrieves relevant data from the database based on the recruiter's question,
 * builds a context-rich prompt, and calls OpenAI for a natural-language answer.
 */
class RecruiterChatbotService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = (string) (getenv('OPENAI_API_KEY') ?: '');
    }

    /**
     * Answer a question using the recruiter's data as context.
     *
     * @return array{answer: string, data_summary: array, actions?: array}
     */
    public function answer(int $recruiterId, string $question, array $chatContext = []): array
    {
        $question = trim($question);
        if ($question === '') {
            return [
                'answer' => 'Please type a question so I can help you.',
                'data_summary' => [],
                'actions' => [],
            ];
        }

        $actionResult = $this->handleActionRequest($recruiterId, $question, $chatContext);
        if ($actionResult !== null) {
            return $actionResult;
        }

        if ($this->apiKey === '') {
            return [
                'answer' => 'The AI answer mode is not available because the OpenAI API key is not configured. I can still help with recruiter tasks from the action buttons, including drafting job descriptions, screening questions, shortlisting candidates, interview slots, and exports.',
                'data_summary' => [],
                'actions' => [],
            ];
        }

        // 1. Classify intent & retrieve relevant data.
        $intent = $this->classifyIntent($question);
        $retrievedData = $this->retrieveData($recruiterId, $intent, $question);

        // 2. Build the system prompt with data context.
        $systemPrompt = $this->buildSystemPrompt($recruiterId);

        // 3. Build the user message with retrieved data.
        $dataContext = $this->formatRetrievedData($retrievedData);
        $userMessage = "Recruiter question: {$question}\n\n"
            . "Here is the relevant data from the recruiter's account:\n"
            . "{$dataContext}\n\n"
            . "Please answer the question conversationally based on this data. "
            . "If the data does not contain enough information to answer, say so honestly. "
            . "Keep your answer concise and helpful.";

        // 4. Call OpenAI.
        $answer = $this->callOpenAi($systemPrompt, $userMessage);
        $actions = [];

        if (!empty($retrievedData['candidates']) && is_array($retrievedData['candidates'])) {
            $actions = $this->buildCandidateActionCardsFromCandidateRows($recruiterId, $retrievedData['candidates']);
        }

        if ($answer === null) {
            $answer = 'I ran into a problem fetching the answer. Please try again in a moment.';
        }

        return [
            'answer' => $answer,
            'data_summary' => $retrievedData,
            'actions' => $actions,
        ];
    }

    // ─── Intent classification ────────────────────────────────

    private function handleActionRequest(int $recruiterId, string $question, array $chatContext = []): ?array
    {
        $question = $this->expandContextualQuestion($question, $chatContext);

        if (preg_match('/^\s*confirm\b.*\b(send|message|mail|email)\b.*\bcandidates?\b/i', $question)) {
            return $this->actionResult('send_candidate_message', $this->sendBatchCandidateMessageFromPrompt($recruiterId, $question, $chatContext));
        }

        if (preg_match('/^\s*confirm\b.*\b(send|message)\b.*\bcandidate\s*#?\s*\d+\b/i', $question)) {
            return $this->actionResult('send_candidate_message', $this->sendCandidateMessageFromPrompt($recruiterId, $question));
        }

        if (preg_match('/^\s*confirm\b.*\b(shortlist|reject)\b.*\bapplications?\s*#?\s*\d+/i', $question)) {
            return $this->actionResult('update_application_status', $this->updateApplicationsFromPrompt($recruiterId, $question));
        }

        if ($this->isBatchStatusByScoreRequest($question)) {
            return $this->buildBatchStatusByScoreConfirmation($recruiterId, $question);
        }

        if ($this->isBatchMessageByScoreRequest($question)) {
            return $this->buildBatchMessageByScoreConfirmation($recruiterId, $question, $chatContext);
        }

        if ($this->isInterviewInviteRequest($question)) {
            $inviteResult = $this->buildInterviewInviteProposal($recruiterId, $question, $chatContext);
            if ($inviteResult !== null) {
                return $inviteResult;
            }
        }

        if (preg_match('/\b(send|message)\b/i', $question)
            && !preg_match('/:\s*\S/is', $question)
            && !preg_match('/\b(draft|write|generate|prepare)\b/i', $question)) {
            $targetContext = $this->resolveCandidateContextForAction($recruiterId, $question, $chatContext);
            if ($targetContext) {
                return $this->buildDefaultMessageProposal($recruiterId, $targetContext);
            }
        }

        if (preg_match('/\b(shortlist|reject)\b.*\bapplications?\s*#?\s*\d+/i', $question)) {
            $isConfirmed = preg_match('/^\s*confirm\b/i', $question) === 1;
            if (!$isConfirmed) {
                return $this->buildApplicationStatusConfirmation($recruiterId, $question);
            }

            return $this->actionResult('update_application_status', $this->updateApplicationsFromPrompt($recruiterId, $question));
        }

        if (preg_match('/\b(send|message)\b.*\bcandidate\s*#?\s*\d+\b/i', $question)) {
            $isConfirmed = preg_match('/^\s*confirm\b/i', $question) === 1;
            if (!$isConfirmed) {
                return $this->buildCandidateMessageConfirmation($recruiterId, $question);
            }

            return $this->actionResult('send_candidate_message', $this->sendCandidateMessageFromPrompt($recruiterId, $question));
        }

        if (preg_match('/\b(reject|rejection|shortlist|shortlisted|outreach|follow[- ]?up|invite)\b.*\b(email|message|template|draft)\b/i', $question)
            || preg_match('/\b(draft|write|generate)\b.*\b(email|message)\b/i', $question)) {
            $draftResult = $this->draftRecruiterMessage($recruiterId, $question, $chatContext);
            return $this->actionResult('draft_message', $draftResult['answer'], $draftResult['actions']);
        }

        if (preg_match('/\b(screening|screen|interview)\b.*\b(questions?|questionnaire)\b/i', $question)) {
            $screeningResult = $this->generateScreeningQuestions($recruiterId, $question, $chatContext);
            return $this->actionResult('screening_questions', $screeningResult['answer'], $screeningResult['actions']);
        }

        if (preg_match('/\b(draft|write|create|generate|improve|post)\b.*\b(job description|jd|job post|job posting|job)\b/i', $question)
            || preg_match('/\bjob description\b/i', $question)
            || preg_match('/\bpost\s+(?:a\s+)?job\s+(?:for|as|role|position)\b/i', $question)) {
            $jobDraft = $this->draftJobDescription($recruiterId, $question);
            return $this->actionResult('draft_job_description', $jobDraft['answer'], $jobDraft['actions']);
        }

        if ($this->isTopCandidateRequest($question)) {
            return $this->buildTopCandidateAnswer($recruiterId, $question);
        }

        if (preg_match('/\b(shortlist|filter|find|show|rank|list|view|get)\b.*\b(candidates?|applicants?)\b/i', $question)
            || preg_match('/\b(candidates?|applicants?)\b.*\b(for|in)\b.*\b(job|role|position)\b/i', $question)) {
            $candidateResult = $this->suggestCandidates($recruiterId, $question);
            return $this->actionResult('filter_candidates', $candidateResult['answer'], $candidateResult['actions']);
        }

        if (preg_match('/\b(suggest|show|find|available)\b.*\b(slots?|interviews?|schedule)\b/i', $question)) {
            $slotResult = $this->suggestInterviewSlots($recruiterId, $question);
            return $this->actionResult('suggest_interview_slots', $slotResult['answer'], $slotResult['actions']);
        }

        if (preg_match('/\b(export|download)\b.*\b(candidates?|applications?|jobs?|data|excel|csv|leaderboard|overview|funnel)\b/i', $question)) {
            $exportResult = $this->buildExportAnswer($recruiterId, $question);
            return $this->actionResult('export_data', $exportResult['answer'], $exportResult['actions']);
        }

        return null;
    }

    private function actionResult(string $action, string $answer, array $actions = []): array
    {
        return [
            'answer' => $answer,
            'data_summary' => ['action' => $action],
            'actions' => $actions,
        ];
    }

    private function isInterviewInviteRequest(string $question): bool
    {
        return preg_match('/\b(send|draft|write|prepare|create)\b.*\b(interview|invite|invitation)\b/i', $question) === 1
            || preg_match('/\b(interview|invite|invitation)\b.*\b(send|draft|write|prepare|create)\b/i', $question) === 1;
    }

    private function isTopCandidateRequest(string $question): bool
    {
        return preg_match('/\b(highest|best|top|strongest)\b.*\b(ats|match|score|candidate|applicant)\b/i', $question) === 1
            || preg_match('/\b(ats|match|score)\b.*\b(highest|best|top|strongest)\b/i', $question) === 1;
    }

    private function isBatchStatusByScoreRequest(string $question): bool
    {
        return preg_match('/\b(reject|shortlist)\b.*\b(anyone|all|candidates?|applicants?)\b.*\b(below|under|less than|above|over|at least)\b.*\d{1,3}\s*%?/i', $question) === 1;
    }

    private function isBatchMessageByScoreRequest(string $question): bool
    {
        return preg_match('/\b(send|message|mail|email)\b.*\b(candidates?|applicants?)\b.*\b(below|under|less than|above|over|at least)\b.*\d{1,3}\s*%?/i', $question) === 1
            || preg_match('/\b(send|message|mail|email)\b.*\b(above|previous|last|this|that)\s+(mail|email|message|draft)\b.*\b(below|under|less than|above|over|at least)\b.*\d{1,3}\s*%?/i', $question) === 1;
    }

    private function buildTopCandidateAnswer(int $recruiterId, string $question): array
    {
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        $jobId = (int) ($job['id'] ?? 0);
        $candidates = $this->fetchCandidateMatches($recruiterId, $jobId, $this->extractSkillsText($question), 0);
        if (empty($candidates)) {
            $scope = $job ? ' for ' . (string) ($job['title'] ?? 'that job') : '';
            return $this->actionResult('top_candidate', 'No candidates found' . $scope . '.', []);
        }

        $top = $candidates[0];
        $score = (int) ($top['match_score'] ?? 0);
        $skillSummary = $this->buildSkillMatchSummary($top);
        $answer = sprintf(
            '%s - ATS %d/100%s',
            (string) ($top['candidate_name'] ?? 'Candidate'),
            $score,
            $skillSummary !== '' ? ' (' . $skillSummary . ')' : ''
        );

        return $this->actionResult('top_candidate', $answer, $this->buildCandidateActionCards([$top]));
    }

    private function buildInterviewInviteProposal(int $recruiterId, string $question, array $chatContext): ?array
    {
        $candidateContext = $this->resolveCandidateContextForAction($recruiterId, $question, $chatContext);
        if (!$candidateContext) {
            return null;
        }

        $candidateName = (string) ($candidateContext['candidate_name'] ?? 'Candidate');
        $jobTitle = (string) ($candidateContext['job_title'] ?? 'the role');
        $company = (string) ($candidateContext['company'] ?? $this->getRecruiterCompany($recruiterId));
        $slotOptions = $this->getUpcomingInterviewSlotOptions($recruiterId, (int) ($candidateContext['job_id'] ?? 0), 3);
        $slotText = !empty($slotOptions)
            ? "Are you available for any of these slots?\n- " . implode("\n- ", $slotOptions)
            : 'Could you share 2-3 times that work for you this week?';

        $subject = 'Interview Invitation - ' . $jobTitle;
        $body = "Hi {candidate_name},\n\nWe would like to invite you for an interview for the {$jobTitle}"
            . ($company !== '' ? " role at {$company}" : ' role')
            . ".\n\n{$slotText}\n\nBest regards,\n{recruiter_name}";
        $body = $this->personalizeRecruiterMessage($body, $candidateContext, $recruiterId);

        $answer = "Draft ready -\n\n"
            . "Subject: {$subject}\n\n{$body}";

        return $this->actionResult('propose_interview_invite', $answer, [
            $this->buildMessageDraftAction($candidateContext, $subject, $body, 'Interview invite to ' . $candidateName, true),
        ]);
    }

    private function buildMessageDraftAction(array $candidateContext, string $subject, string $body, string $title, bool $isInterviewInvite = false): array
    {
        $candidateId = (int) ($candidateContext['candidate_id'] ?? 0);
        $applicationId = (int) ($candidateContext['application_id'] ?? 0);
        $jobId = (int) ($candidateContext['job_id'] ?? 0);
        $candidateName = (string) ($candidateContext['candidate_name'] ?? ('candidate #' . $candidateId));
        $draftCommand = 'send message to candidate #' . $candidateId . ': Subject: ' . $subject . "\n\n" . $body;
        $draftCommandPrefix = 'send message to candidate #' . $candidateId . ': ';
        $buttons = [
            [
                'label' => $isInterviewInvite ? 'Send Now' : 'Send',
                'kind' => 'primary',
                'silent' => true,
                'command' => 'confirm ' . $draftCommand,
            ],
            [
                'label' => 'Edit',
                'kind' => 'draft',
                'command' => $draftCommand,
                'command_prefix' => $draftCommandPrefix,
                'draft_text' => 'Subject: ' . $subject . "\n\n" . $body,
            ],
        ];

        if ($isInterviewInvite && $jobId > 0) {
            $buttons[] = [
                'label' => 'Change Slots',
                'kind' => 'link',
                'url' => base_url('recruiter/slots/create?job_id=' . $jobId),
            ];
        } else {
            $buttons[] = [
                'label' => 'Cancel',
                'kind' => 'secondary',
            ];
        }

        return [
            'type' => 'message_draft',
            'candidate_id' => $candidateId,
            'candidate_name' => $candidateName,
            'application_id' => $applicationId,
            'job_id' => $jobId,
            'job_title' => (string) ($candidateContext['job_title'] ?? ''),
            'subject' => $subject,
            'message_body' => $body,
            'command' => $draftCommand,
            'command_prefix' => $draftCommandPrefix,
            'draft_text' => 'Subject: ' . $subject . "\n\n" . $body,
            'title' => $title,
            'meta' => 'Subject: ' . $subject,
            'detail' => $body,
            'buttons' => $buttons,
        ];
    }

    private function expandContextualQuestion(string $question, array $chatContext): string
    {
        $lastCandidate = is_array($chatContext['last_candidate'] ?? null) ? $chatContext['last_candidate'] : [];
        $lastDraft = is_array($chatContext['last_draft'] ?? null) ? $chatContext['last_draft'] : [];
        $candidateId = (int) ($lastCandidate['candidate_id'] ?? 0);
        $applicationId = (int) ($lastCandidate['application_id'] ?? 0);
        if ($candidateId <= 0) {
            return $question;
        }

        if ($this->isBatchMessageByScoreRequest($question)) {
            return $question;
        }

        $mentionsPreviousCandidate = preg_match('/\b(above|previous|last|this|that)\s+(candidate|applicant|person)\b/i', $question)
            || preg_match('/\b(to|for|with)\s+(him|her|them)\b/i', $question);

        if (!$mentionsPreviousCandidate) {
            return $question;
        }

        $mentionsPreviousDraft = preg_match('/\b(above|previous|last|this|that)\s+(follow[- ]?up|message|draft|email)\b/i', $question)
            || preg_match('/\b(follow[- ]?up|message|draft|email)\b.*\b(above|previous|last|this|that)\b/i', $question);
        if ($mentionsPreviousDraft && !empty($lastDraft['command'])) {
            if (preg_match('/\b(send|deliver)\b/i', $question)) {
                return 'confirm ' . (string) $lastDraft['command'];
            }

            return (string) $lastDraft['command'];
        }

        if ($applicationId > 0
            && !preg_match('/\bapplications?\s*#?\s*\d+\b/i', $question)
            && preg_match('/\b(shortlist|reject)\b/i', $question, $statusMatch)) {
            return $statusMatch[1] . ' application #' . $applicationId;
        }

        if (!preg_match('/\bcandidate\s*#?\s*\d+\b/i', $question)
            && preg_match('/\b(send|message)\b/i', $question)
        ) {
            if (preg_match('/:\s*(.+)$/is', $question, $messageMatch)) {
                return 'send message to candidate #' . $candidateId . ': ' . trim((string) $messageMatch[1]);
            }

            return 'send message to candidate #' . $candidateId;
        }

        return $question;
    }

    private function draftJobDescription(int $recruiterId, string $question): array
    {
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        $title = (string) ($job['title'] ?? $this->extractRoleTitle($question));
        $title = $title !== '' ? $title : 'the role';
        $company = (string) ($job['company'] ?? $this->getRecruiterCompany($recruiterId));
        $location = (string) ($job['location'] ?? 'Flexible');
        $employmentType = (string) ($job['employment_type'] ?? 'Full-time');
        $skills = $this->normalizeList((string) ($job['required_skills'] ?? $this->extractSkillsText($question)));
        if (empty($skills)) {
            $skills = ['Relevant professional experience', 'Clear communication', 'Problem solving'];
        }

        $lines = [
            'We are looking for a strong ' . $title . ' to join our team and contribute to high-impact work from day one. The ideal candidate is comfortable owning responsibilities, collaborating with stakeholders, and delivering reliable outcomes.',
            '',
            'Key responsibilities',
            '- Own day-to-day execution for the role and communicate progress clearly.',
            '- Collaborate with cross-functional teams to understand requirements and deliver quality work.',
            '- Identify improvements, reduce blockers, and support a smooth team workflow.',
            '- Document decisions, learnings, and handoffs where needed.',
            '',
            'Required skills',
        ];

        foreach (array_slice($skills, 0, 8) as $skill) {
            $lines[] = '- ' . $skill;
        }

        $lines[] = '';
        $lines[] = 'Nice to have';
        $lines[] = '- Experience in a similar domain or fast-moving environment.';
        $lines[] = '- Ability to mentor others or improve team processes.';
        $draft = implode("\n", $lines);

        return [
            'answer' => 'Job description draft ready for ' . $title . '.',
            'actions' => [[
                'type' => 'job_draft',
                'title' => 'Job description draft',
                'meta' => trim($title . ($company !== '' ? ' | ' . $company : '')),
                'detail' => $draft,
                'buttons' => [
                    [
                        'label' => 'Use This',
                        'kind' => 'prefill_job',
                        'url' => base_url('recruiter/post_job'),
                        'job_prefill' => [
                            'title' => $title,
                            'description' => $draft,
                            'location' => $location,
                            'employment_type' => $employmentType,
                            'required_skills' => implode(', ', $skills),
                        ],
                    ],
                    [
                        'label' => 'Copy Draft',
                        'kind' => 'copy',
                        'copy_text' => $draft,
                    ],
                    [
                        'label' => 'Regenerate',
                        'kind' => 'primary',
                        'command' => 'draft job description for ' . $title,
                    ],
                ],
            ]],
        ];
    }

    private function generateScreeningQuestions(int $recruiterId, string $question, array $chatContext = []): array
    {
        $jobResolution = $this->resolveJobForScreening($recruiterId, $question, $chatContext);
        if (($jobResolution['status'] ?? '') === 'ambiguous') {
            return [
                'answer' => 'You have ' . count($jobResolution['jobs']) . ' open jobs matching that:',
                'actions' => $this->buildJobChoiceActions($jobResolution['jobs'], 'create screening questions'),
            ];
        }

        $job = $jobResolution['job'] ?? null;
        $title = (string) ($job['title'] ?? $this->extractRoleTitle($question) ?: 'this role');
        $skills = $this->normalizeList((string) ($job['required_skills'] ?? $this->extractSkillsText($question)));

        $heading = 'Screening questions for ' . $title . ':';
        if (($jobResolution['reason'] ?? '') === 'posted_yesterday') {
            $heading = 'That is "' . $title . "\".\n\n" . $heading;
        }

        $questions = [
            'Tell us about your most relevant experience for this role.',
            'Which tools, technologies, or workflows from the job requirements have you used recently?',
            'Describe a project where you solved a difficult problem with limited guidance.',
            'What quality checks do you use before calling your work complete?',
            'What is your expected notice period and availability for interviews?',
        ];

        foreach (array_slice($skills, 0, 5) as $skill) {
            $questions[] = 'Rate your hands-on experience with ' . $skill . ' and share one example.';
        }

        $lines = [$heading, ''];
        foreach ($questions as $index => $questionText) {
            $lines[] = ($index + 1) . '. ' . $questionText;
        }

        $lines[] = '';
        $lines[] = 'Suggested scoring: 0-2 weak, 3 acceptable, 4 strong, 5 excellent. Use the same rubric for every applicant.';
        $questionnaireRows = array_map(static fn (string $questionText): array => [
            'label' => $questionText,
            'type' => 'textarea',
            'placeholder' => 'Candidate answer',
            'required' => true,
        ], $questions);

        return [
            'answer' => implode("\n", $lines),
            'actions' => [[
                'type' => 'screening_questions',
                'job_id' => (int) ($job['id'] ?? 0),
                'job_title' => $title,
                'title' => 'Screening questions',
                'meta' => $title,
                'detail' => implode("\n", array_slice($lines, 2)),
                'buttons' => array_values(array_filter([
                    !empty($job['id']) ? [
                        'label' => 'Save to Job',
                        'kind' => 'prefill_questions',
                        'url' => base_url('recruiter/jobs/edit/' . (int) $job['id']),
                        'screening_prefill' => $questionnaireRows,
                    ] : null,
                    [
                        'label' => 'Copy Questions',
                        'kind' => 'copy',
                        'copy_text' => implode("\n", $lines),
                    ],
                    [
                        'label' => 'Regenerate',
                        'kind' => 'primary',
                        'silent' => true,
                        'command' => !empty($job['id']) ? 'create screening questions for job #' . (int) $job['id'] : 'create screening questions for ' . $title,
                    ],
                ])),
            ]],
        ];
    }

    private function resolveJobForScreening(int $recruiterId, string $question, array $chatContext = []): array
    {
        $db = \Config\Database::connect();
        $jobId = $this->extractNumberAfter($question, 'job');
        if ($jobId > 0) {
            $job = $db->table('jobs')
                ->where('id', $jobId)
                ->where('recruiter_id', $recruiterId)
                ->get()
                ->getRowArray();
            if ($job) {
                return ['status' => 'resolved', 'job' => $job];
            }
        }

        if (preg_match('/\bposted\s+yesterday\b/i', $question)) {
            $jobs = $db->table('jobs')
                ->where('recruiter_id', $recruiterId)
                ->where('created_at >=', date('Y-m-d 00:00:00', strtotime('-1 day')))
                ->where('created_at <=', date('Y-m-d 23:59:59', strtotime('-1 day')))
                ->orderBy('created_at', 'DESC')
                ->get()
                ->getResultArray();

            if (count($jobs) === 1) {
                return ['status' => 'resolved', 'job' => $jobs[0], 'reason' => 'posted_yesterday'];
            }
            if (count($jobs) > 1) {
                return ['status' => 'ambiguous', 'jobs' => $jobs];
            }
        }

        if (preg_match('/\b(that|this|last|previous)\s+job\b/i', $question)
            && !empty($chatContext['last_job']['job_id'])) {
            $job = $db->table('jobs')
                ->where('id', (int) $chatContext['last_job']['job_id'])
                ->where('recruiter_id', $recruiterId)
                ->get()
                ->getRowArray();
            if ($job) {
                return ['status' => 'resolved', 'job' => $job];
            }
        }

        $title = $this->extractRoleTitle($question);
        if ($title === '') {
            $job = $this->resolveJobFromQuestion($recruiterId, $question);
            return $job ? ['status' => 'resolved', 'job' => $job] : ['status' => 'unresolved'];
        }

        $exact = $db->table('jobs')
            ->where('recruiter_id', $recruiterId)
            ->where('LOWER(title)', strtolower($title))
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
        if (count($exact) === 1) {
            return ['status' => 'resolved', 'job' => $exact[0]];
        }
        if (count($exact) > 1) {
            return ['status' => 'ambiguous', 'jobs' => $exact];
        }

        $matches = $db->table('jobs')
            ->where('recruiter_id', $recruiterId)
            ->whereIn('status', ['active', 'open', 'published'])
            ->like('title', $title)
            ->orderBy('created_at', 'DESC')
            ->limit(6)
            ->get()
            ->getResultArray();

        if (count($matches) === 1) {
            return ['status' => 'resolved', 'job' => $matches[0]];
        }
        if (count($matches) > 1) {
            return ['status' => 'ambiguous', 'jobs' => $matches];
        }

        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        return $job ? ['status' => 'resolved', 'job' => $job] : ['status' => 'unresolved'];
    }

    private function buildJobChoiceActions(array $jobs, string $commandPrefix): array
    {
        $buttons = [];
        foreach (array_slice($jobs, 0, 6) as $job) {
            $jobId = (int) ($job['id'] ?? 0);
            if ($jobId <= 0) {
                continue;
            }

            $labelParts = [(string) ($job['title'] ?? ('Job #' . $jobId))];
            if (!empty($job['location'])) {
                $labelParts[] = (string) $job['location'];
            }

            $buttons[] = [
                'label' => implode(' - ', $labelParts),
                'kind' => 'primary',
                'silent' => true,
                'command' => $commandPrefix . ' for job #' . $jobId,
            ];
        }

        if (empty($buttons)) {
            return [];
        }

        return [[
            'type' => 'job_choices',
            'title' => 'Choose job',
            'buttons' => $buttons,
        ]];
    }

    private function suggestCandidates(int $recruiterId, string $question): array
    {
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        $jobId = (int) ($job['id'] ?? $this->extractNumberAfter($question, 'job'));
        $minScore = $this->extractScoreThreshold($question);
        $skillFilter = $this->extractSkillsText($question);
        $candidates = $this->fetchCandidateMatches($recruiterId, $jobId, $skillFilter, $minScore);

        if (empty($candidates)) {
            $filters = [];
            if ($jobId > 0) {
                $filters[] = 'job #' . $jobId . (!empty($job['title']) ? ' (' . $job['title'] . ')' : '');
            }
            if ($minScore > 0) {
                $filters[] = 'ATS/match above ' . $minScore;
            }
            if ($skillFilter !== '') {
                $filters[] = 'skills: ' . $skillFilter;
            }

            return [
                'answer' => 'I could not find matching candidates' . (!empty($filters) ? ' for ' . implode(', ', $filters) : ' with those filters') . '. Try lowering the ATS threshold, removing the skill filter, or checking the job pipeline for applications.',
                'actions' => [],
            ];
        }

        $visibleCandidates = array_slice($candidates, 0, 5);
        $lines = ['Here are the strongest candidates I found' . ($jobId > 0 ? ' for ' . (!empty($job['title']) ? (string) $job['title'] : 'that job') : '') . ':'];
        foreach ($visibleCandidates as $row) {
            $lines[] = sprintf(
                '- %s | %s | Status: %s | Match: %d%% | Skills: %s',
                $row['candidate_name'],
                $row['job_title'],
                $row['status'],
                (int) $row['match_score'],
                $row['candidate_skills'] !== '' ? $row['candidate_skills'] : 'N/A'
            );
        }
        $lines[] = '';
        $lines[] = 'I did not change any statuses. Use the action cards below to review, message, schedule, shortlist, or reject.';

        return [
            'answer' => implode("\n", $lines),
            'actions' => $this->buildCandidateActionCards($visibleCandidates),
        ];
    }

    private function suggestInterviewSlots(int $recruiterId, string $question): array
    {
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        $jobId = (int) ($job['id'] ?? $this->extractNumberAfter($question, 'job'));
        $rows = $this->getUpcomingInterviewSlots($recruiterId, $jobId, 8);
        if (empty($rows)) {
            return [
                'answer' => 'No available upcoming slots' . ($job ? ' for ' . (string) ($job['title'] ?? 'that job') : '') . '.',
                'actions' => [[
                    'type' => 'slot_action',
                    'title' => 'Create interview slots',
                    'meta' => $job ? (string) ($job['title'] ?? 'Selected job') : 'Recruiter calendar',
                    'buttons' => [[
                        'label' => 'Create Slots',
                        'kind' => 'link',
                        'url' => base_url('recruiter/slots/create' . ($jobId > 0 ? '?job_id=' . $jobId : '')),
                    ]],
                ]],
            ];
        }

        $lines = ['Available interview slots' . ($job ? ' for ' . (string) ($job['title'] ?? 'that job') : '') . ':'];
        $slotText = [];
        foreach ($rows as $slot) {
            $label = sprintf(
                '%s | %s %s | %d/%d booked',
                $slot['job_title'] ?: 'Interview',
                $slot['slot_date'],
                $slot['slot_time'],
                (int) $slot['booked_count'],
                (int) $slot['capacity']
            );
            $lines[] = '- ' . $label;
            $slotText[] = $label;
        }

        return [
            'answer' => implode("\n", $lines),
            'actions' => [[
                'type' => 'slot_action',
                'title' => 'Use these slots',
                'meta' => $job ? (string) ($job['title'] ?? 'Selected job') : 'Recruiter calendar',
                'detail' => implode("\n", array_slice($slotText, 0, 3)),
                'buttons' => [
                    [
                        'label' => 'Send Slots',
                        'kind' => 'draft',
                        'command' => 'draft interview invite for ' . ($job ? (string) ($job['title'] ?? 'this job') : 'this role'),
                        'draft_text' => "Available interview slots:\n- " . implode("\n- ", array_slice($slotText, 0, 3)),
                    ],
                    [
                        'label' => 'Manage Slots',
                        'kind' => 'link',
                        'url' => base_url('recruiter/slots' . ($jobId > 0 ? '?job_id=' . $jobId : '')),
                    ],
                ],
            ]],
        ];
    }

    private function getUpcomingInterviewSlots(int $recruiterId, int $jobId = 0, int $limit = 8): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('interview_slots')) {
            return [];
        }

        $builder = $db->table('interview_slots s')
            ->select('s.id, s.job_id, s.slot_date, s.slot_time, s.capacity, s.booked_count, j.title as job_title')
            ->join('jobs j', 'j.id = s.job_id', 'left')
            ->where('s.created_by', $recruiterId)
            ->where('s.is_available', 1)
            ->where('s.slot_datetime >', date('Y-m-d H:i:s'))
            ->where('s.booked_count < s.capacity')
            ->orderBy('s.slot_datetime', 'ASC')
            ->limit($limit);

        if ($jobId > 0) {
            $builder->where('s.job_id', $jobId);
        }

        return $builder->get()->getResultArray();
    }

    private function getUpcomingInterviewSlotOptions(int $recruiterId, int $jobId = 0, int $limit = 3): array
    {
        $options = [];
        foreach ($this->getUpcomingInterviewSlots($recruiterId, $jobId, $limit) as $slot) {
            $date = trim((string) ($slot['slot_date'] ?? ''));
            $time = trim((string) ($slot['slot_time'] ?? ''));
            if ($date === '' && $time === '') {
                continue;
            }
            $options[] = trim($date . ' at ' . $time);
        }

        return $options;
    }

    private function draftRecruiterMessage(int $recruiterId, string $question, array $chatContext = []): array
    {
        $candidateContext = $this->resolveCandidateContextForAction($recruiterId, $question, $chatContext);
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        if (!$job) {
            $job = $this->resolveJobFromChatContext($recruiterId, $chatContext);
        }
        if (!$job && $candidateContext && !empty($candidateContext['job_id'])) {
            $job = \Config\Database::connect()->table('jobs')
                ->where('id', (int) $candidateContext['job_id'])
                ->where('recruiter_id', $recruiterId)
                ->get()
                ->getRowArray();
        }

        $title = (string) ($job['title'] ?? 'the role');
        $company = (string) ($job['company'] ?? $this->getRecruiterCompany($recruiterId));
        $lower = strtolower($question);
        $type = str_contains($lower, 'reject') || str_contains($lower, 'rejection')
            ? 'rejection'
            : (str_contains($lower, 'shortlist') ? 'shortlist' : 'outreach');

        if ($type === 'rejection') {
            $body = "Hi {candidate_name},\n\nThank you for your interest in {$title}" . ($company !== '' ? " at {$company}" : '') . ". After reviewing your application, we will not be moving forward for this position.\n\nWe appreciate the time you invested and encourage you to apply for future roles that match your experience.\n\nBest regards,\n{recruiter_name}";
        } elseif ($type === 'shortlist') {
            $body = "Hi {candidate_name},\n\nGood news - your profile has been shortlisted for {$title}" . ($company !== '' ? " at {$company}" : '') . ". We would like to move you to the next step.\n\nPlease share your availability for an interview, or use the interview booking link in your candidate dashboard if available.\n\nBest regards,\n{recruiter_name}";
        } else {
            $body = "Hi {candidate_name},\n\nI came across your profile and thought your background could be a strong fit for {$title}" . ($company !== '' ? " at {$company}" : '') . ". If you are open to exploring this opportunity, I would be happy to share more details.\n\nBest regards,\n{recruiter_name}";
        }

        $subject = $type === 'outreach'
            ? 'Following up on ' . $title
            : 'Update on your application - ' . $title;
        $personalizedBody = $candidateContext
            ? $this->personalizeRecruiterMessage($body, $candidateContext, $recruiterId)
            : $this->buildBulkMessagePreview($body, $recruiterId);

        $answer = $candidateContext
            ? "Draft ready:\n\nSubject: {$subject}\n\n{$personalizedBody}"
            : "Reusable draft for this role:\n\nSubject: {$subject}\n\n{$personalizedBody}";
        $actions = [];

        if ($candidateContext && !empty($candidateContext['candidate_id'])) {
            $candidateId = (int) $candidateContext['candidate_id'];
            $actions[] = $this->buildMessageDraftAction($candidateContext, $subject, $personalizedBody, 'Use this draft for ' . ($candidateContext['candidate_name'] ?? ('candidate #' . $candidateId)));
        } else {
            $actions = $this->buildBulkDraftSelectionActions($recruiterId, $job, $subject, $body, $type);
        }

        return [
            'answer' => $answer,
            'actions' => $actions,
        ];
    }

    private function buildBulkMessagePreview(string $body, int $recruiterId): string
    {
        return strtr($body, [
            '{candidate_name}' => '[Candidate Name]',
            '{{candidate_name}}' => '[Candidate Name]',
            '{recruiter_name}' => trim((string) (session()->get('user_name') ?? 'Recruiter')),
            '{{recruiter_name}}' => trim((string) (session()->get('user_name') ?? 'Recruiter')),
        ]);
    }

    private function buildBulkDraftSelectionActions(int $recruiterId, ?array $job, string $subject, string $body, string $type): array
    {
        $jobId = (int) ($job['id'] ?? 0);
        $title = (string) ($job['title'] ?? 'this role');
        $candidates = $jobId > 0 ? $this->fetchCandidateMatches($recruiterId, $jobId, '', 0) : [];

        if ($type === 'rejection') {
            usort($candidates, static fn (array $a, array $b): int => ((int) ($a['match_score'] ?? 0)) <=> ((int) ($b['match_score'] ?? 0)));
        }

        $candidateItems = [];
        foreach (array_slice($candidates, 0, 8) as $row) {
            $candidateId = (int) ($row['candidate_id'] ?? 0);
            if ($candidateId <= 0) {
                continue;
            }
            $score = (int) ($row['match_score'] ?? 0);
            $candidateItems[] = [
                'candidate_id' => $candidateId,
                'label' => (string) ($row['candidate_name'] ?? ('Candidate #' . $candidateId)),
                'meta' => 'Match ' . $score . '%',
                'match_score' => $score,
            ];
        }

        $lowScoreIds = array_values(array_map(
            static fn (array $row): int => (int) ($row['candidate_id'] ?? 0),
            array_filter($candidates, static fn (array $row): bool => (int) ($row['match_score'] ?? 0) < 40)
        ));
        $lowScoreIds = array_values(array_filter(array_unique($lowScoreIds), static fn (int $id): bool => $id > 0));

        $draftCard = [
            'type' => 'message_draft',
            'title' => 'Reusable draft for ' . $title,
            'meta' => 'Subject: ' . $subject,
            'detail' => $this->buildBulkMessagePreview($body, $recruiterId),
            'job_id' => $jobId,
            'job_title' => $title,
            'subject' => $subject,
            'message_body' => $body,
            'draft_text' => 'Subject: ' . $subject . "\n\n" . $this->buildBulkMessagePreview($body, $recruiterId),
            'buttons' => [[
                'label' => 'Edit Draft',
                'kind' => 'draft',
                'draft_text' => 'Subject: ' . $subject . "\n\n" . $this->buildBulkMessagePreview($body, $recruiterId),
                'command_prefix' => '',
                'command' => 'draft rejection email for ' . $title,
            ]],
        ];

        if (!empty($candidateItems)) {
            $buttons = [[
                'label' => 'Edit Draft',
                'kind' => 'draft',
                'draft_text' => 'Subject: ' . $subject . "\n\n" . $this->buildBulkMessagePreview($body, $recruiterId),
                'command_prefix' => '',
                'command' => 'draft rejection email for ' . $title,
            ], [
                'label' => 'Select All',
                'kind' => 'secondary',
                'select_candidate_ids' => array_values(array_map(static fn (array $item): int => (int) $item['candidate_id'], $candidateItems)),
            ], [
                'label' => 'Send to Selected',
                'kind' => 'primary',
                'silent' => true,
                'requires_selection' => true,
                'command_prefix' => 'confirm send previous draft to candidates ',
            ]];
            if (!empty($lowScoreIds)) {
                $buttons[] = [
                    'label' => 'Select All Below 40%',
                    'kind' => 'secondary',
                    'select_candidate_ids' => $lowScoreIds,
                ];
            }

            $draftCard['items_title'] = 'Choose candidates to send to';
            $draftCard['items'] = $candidateItems;
            $draftCard['buttons'] = $buttons;
            return [$draftCard];
        }

        $draftCard['meta'] .= ' | No candidates currently match for ' . $title . '.';
        return [$draftCard];
    }

    private function resolveJobFromChatContext(int $recruiterId, array $chatContext): ?array
    {
        $db = \Config\Database::connect();
        $jobId = (int) ($chatContext['last_job']['job_id'] ?? $chatContext['last_draft']['job_id'] ?? $chatContext['last_candidate']['job_id'] ?? 0);
        if ($jobId > 0) {
            $row = $db->table('jobs')
                ->where('id', $jobId)
                ->where('recruiter_id', $recruiterId)
                ->get()
                ->getRowArray();
            if ($row) {
                return $row;
            }
        }

        $jobTitle = trim((string) ($chatContext['last_job']['job_title'] ?? $chatContext['last_draft']['job_title'] ?? $chatContext['last_candidate']['job_title'] ?? ''));
        if ($jobTitle !== '') {
            $row = $db->table('jobs')
                ->where('recruiter_id', $recruiterId)
                ->like('title', $jobTitle)
                ->orderBy('created_at', 'DESC')
                ->get(1)
                ->getRowArray();
            if ($row) {
                return $row;
            }
        }

        return null;
    }

    private function sendCandidateMessageFromPrompt(int $recruiterId, string $question): string
    {
        if (!preg_match('/candidate\s*#?\s*(\d+)\s*:?\s*(.+)$/is', $question, $matches)) {
            return 'Pick a candidate from an action card, then use Message or Send so I can attach the message to the right person.';
        }

        $candidateId = (int) $matches[1];
        $message = trim((string) $matches[2]);
        $message = preg_replace('/^(?:confirm\s+)?(?:send|message)\b.*?\b(candidate\s*#?\s*\d+)\s*:?\s*/i', '', $message) ?? $message;
        $message = trim($message);

        $context = $this->findRecruiterCandidateContext($recruiterId, $candidateId);
        if (!$context) {
            return 'Message was not sent because that candidate is not linked to one of your applications.';
        }

        $message = $this->personalizeRecruiterMessage($message, $context, $recruiterId);
        $delivery = $this->prepareMessageForDelivery($message, $context, $recruiterId);

        return $this->sendMessageToCandidate($recruiterId, $candidateId, $delivery['subject'], $delivery['body']);
    }

    private function buildBatchMessageByScoreConfirmation(int $recruiterId, string $question, array $chatContext): array
    {
        $draft = $this->getDraftDeliveryFromChatContext($chatContext);
        if (!$draft) {
            return $this->buildMissingBatchMessageDraftPrompt($recruiterId, $question, $chatContext);
        }

        $threshold = $this->extractScoreThreshold($question);
        $below = preg_match('/\b(below|under|less than)\b/i', $question) === 1;
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        $jobId = (int) ($job['id'] ?? ($chatContext['last_candidate']['job_id'] ?? 0));
        $candidates = $this->fetchCandidateMatches($recruiterId, $jobId, '', 0);
        $matches = array_values(array_filter($candidates, static function (array $row) use ($threshold, $below): bool {
            $score = (int) ($row['match_score'] ?? 0);
            return $below ? $score < $threshold : $score >= $threshold;
        }));

        if (empty($matches)) {
            $direction = $below ? 'below' : 'at or above';
            return $this->actionResult('batch_message_by_score', 'No candidates found ' . $direction . ' ' . $threshold . '% ATS match' . ($job ? ' for ' . (string) ($job['title'] ?? 'that job') : '') . '.', []);
        }

        $candidateIds = array_values(array_unique(array_map(static fn (array $row): int => (int) ($row['candidate_id'] ?? 0), $matches)));
        $candidateIds = array_values(array_filter($candidateIds, static fn (int $id): bool => $id > 0));
        $preview = [];
        foreach (array_slice($matches, 0, 5) as $row) {
            $preview[] = sprintf('%s (%d%%)', (string) ($row['candidate_name'] ?? 'Candidate'), (int) ($row['match_score'] ?? 0));
        }

        $count = count($candidateIds);
        $direction = $below ? 'below' : 'at or above';
        $answer = $count . ' candidate' . ($count === 1 ? '' : 's') . ' ' . $direction . ' ' . $threshold . '% ATS match: ' . implode(', ', $preview) . ($count > 5 ? ', +' . ($count - 5) . ' more' : '') . '. Send the draft?';

        return $this->actionResult('batch_message_by_score', $answer, [[
            'type' => 'confirmation',
            'title' => 'Send draft to ' . $count . ' candidate' . ($count === 1 ? '' : 's'),
            'meta' => 'Subject: ' . $draft['subject'],
            'detail' => implode("\n", array_slice($preview, 0, 5)),
            'buttons' => [
                [
                    'label' => 'Confirm Send',
                    'kind' => 'primary',
                    'silent' => true,
                    'command' => 'confirm send previous draft to candidates ' . implode(' ', array_map(static fn (int $id): string => 'candidate #' . $id, $candidateIds)),
                ],
                [
                    'label' => 'Review List',
                    'kind' => 'link',
                    'url' => $jobId > 0 ? base_url('recruiter/jobs/view/' . $jobId) : base_url('recruiter/jobs'),
                ],
            ],
        ]]);
    }

    private function buildMissingBatchMessageDraftPrompt(int $recruiterId, string $question, array $chatContext): array
    {
        $threshold = $this->extractScoreThreshold($question);
        $below = preg_match('/\b(below|under|less than)\b/i', $question) === 1;
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        $jobId = (int) ($job['id'] ?? ($chatContext['last_candidate']['job_id'] ?? 0));
        if (!$job && $jobId > 0) {
            $job = \Config\Database::connect()->table('jobs')
                ->where('id', $jobId)
                ->where('recruiter_id', $recruiterId)
                ->get()
                ->getRowArray();
        }

        $title = (string) ($job['title'] ?? $this->extractRoleTitle($question) ?: 'this role');
        $direction = $below ? 'below' : 'at or above';
        $draftCommand = 'draft rejection email for ' . $title;
        $answer = 'No email draft is ready yet. I can draft one for ' . $title . ', then send it to candidates ' . $direction . ' ' . $threshold . '% ATS after you confirm.';

        return $this->actionResult('batch_message_needs_draft', $answer, [[
            'type' => 'next_step',
            'title' => 'Draft email first',
            'meta' => $title . ' | ATS ' . $direction . ' ' . $threshold . '%',
            'buttons' => array_values(array_filter([
                [
                    'label' => 'Draft Email',
                    'kind' => 'primary',
                    'command' => $draftCommand,
                ],
                $jobId > 0 ? [
                    'label' => 'View Candidates',
                    'kind' => 'link',
                    'url' => base_url('recruiter/applications/job/' . $jobId),
                ] : null,
            ])),
        ]]);
    }

    private function sendBatchCandidateMessageFromPrompt(int $recruiterId, string $question, array $chatContext): string
    {
        $draft = $this->getDraftDeliveryFromChatContext($chatContext);
        if (!$draft) {
            return 'Message was not sent because I could not find the previous draft.';
        }

        preg_match_all('/candidate\s*#?\s*(\d+)/i', $question, $matches);
        $candidateIds = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
        if (empty($candidateIds)) {
            return 'Message was not sent because no matching candidates were selected.';
        }

        $sent = [];
        $skipped = [];
        foreach ($candidateIds as $candidateId) {
            $context = $this->findRecruiterCandidateContext($recruiterId, $candidateId);
            if (!$context) {
                $skipped[] = 'candidate not linked to your applications';
                continue;
            }

            $subject = $this->personalizeRecruiterMessage($draft['subject'], $context, $recruiterId);
            $body = $this->personalizeRecruiterMessage($draft['body'], $context, $recruiterId);
            $result = $this->sendMessageToCandidate($recruiterId, $candidateId, $subject, $body);
            if (str_starts_with($result, 'Message sent')) {
                $sent[] = (string) ($context['candidate_name'] ?? 'Candidate');
            } else {
                $skipped[] = (string) ($context['candidate_name'] ?? 'Candidate');
            }
        }

        $lines = [];
        $lines[] = 'Message sent to ' . count($sent) . ' candidate' . (count($sent) === 1 ? '' : 's') . (!empty($sent) ? ': ' . implode(', ', array_slice($sent, 0, 5)) . (count($sent) > 5 ? ', +' . (count($sent) - 5) . ' more' : '') : '') . '.';
        if (!empty($skipped)) {
            $lines[] = count($skipped) . ' skipped.';
        }

        return implode("\n", $lines);
    }

    private function getDraftDeliveryFromChatContext(array $chatContext): ?array
    {
        $lastDraft = is_array($chatContext['last_draft'] ?? null) ? $chatContext['last_draft'] : [];
        if (empty($lastDraft)) {
            return null;
        }

        $subject = trim((string) ($lastDraft['subject'] ?? ''));
        $body = trim((string) ($lastDraft['message_body'] ?? ''));
        if ($body === '' && !empty($lastDraft['command'])) {
            $commandBody = preg_replace('/^send\s+message\s+to\s+candidate\s*#?\s*\d+\s*:?\s*/i', '', (string) $lastDraft['command']) ?? '';
            $delivery = $this->prepareMessageForDelivery($commandBody, [], 0);
            $subject = $subject !== '' ? $subject : $delivery['subject'];
            $body = $delivery['body'];
        }

        if ($subject === '' && $body !== '') {
            $delivery = $this->prepareMessageForDelivery($body, [], 0);
            $subject = $delivery['subject'];
            $body = $delivery['body'];
        }

        if ($body === '') {
            return null;
        }

        return [
            'subject' => $subject !== '' ? $subject : 'Regarding your application',
            'body' => $body,
        ];
    }

    private function sendMessageToCandidate(int $recruiterId, int $candidateId, string $subject, string $body): string
    {
        $context = $this->findRecruiterCandidateContext($recruiterId, $candidateId);
        if (!$context) {
            return 'Message was not sent because that candidate is not linked to one of your applications.';
        }

        $message = trim($body);
        if (!$this->hasUsableMessageText($message) || mb_strlen($message) > 1000) {
            return 'Message was not sent. Please keep the message between 1 and 1000 characters.';
        }

        $db = \Config\Database::connect();
        $db->table('recruiter_candidate_messages')->insert([
            'candidate_id' => $candidateId,
            'recruiter_id' => $recruiterId,
            'application_id' => (int) ($context['application_id'] ?? 0) ?: null,
            'job_id' => (int) ($context['job_id'] ?? 0) ?: null,
            'sender_id' => $recruiterId,
            'sender_role' => 'recruiter',
            'message' => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        $this->sendMailboxMessageIfConnected($recruiterId, $context, $subject, $message);

        if ($db->tableExists('notifications')) {
            (new \App\Models\NotificationModel())->createNotification(
                $candidateId,
                (int) ($context['application_id'] ?? 0) ?: null,
                'recruiter_message',
                'You received a message from a recruiter.',
                base_url('candidate/messages/' . $recruiterId)
            );
        }

        return 'Message sent to ' . ($context['candidate_name'] ?? 'the candidate') . '.';
    }

    private function buildExportAnswer(int $recruiterId, string $question): array
    {
        $type = 'overview';

        if (preg_match('/\b(candidates?|applications?|applicants?|detailed)\b/i', $question)) {
            $type = 'detailed';
        } elseif (preg_match('/\b(leaderboard|rank|ranking|ats)\b/i', $question)) {
            $type = 'leaderboard';
        } elseif (preg_match('/\b(funnel|pipeline|stage)\b/i', $question)) {
            $type = 'funnel';
        }

        $url = base_url('recruiter/dashboard/export-excel?type=' . $type);
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        if ($job && in_array($type, ['detailed', 'leaderboard', 'funnel'], true)) {
            $url .= '&job_id=' . (int) $job['id'];
        }

        $count = $this->countExportRows($recruiterId, $type, (int) ($job['id'] ?? 0));
        $noun = $type === 'detailed' ? 'candidate' : 'row';
        $answer = "Here's your export - {$count} {$noun}" . ($count === 1 ? '' : 's') . '.';

        return [
            'answer' => $answer,
            'actions' => [[
                'type' => 'download',
                'title' => 'Excel export',
                'meta' => $job ? (string) ($job['title'] ?? 'Selected job') : 'All recruiter jobs',
                'download_url' => $url,
                'auto_download' => true,
                'buttons' => [[
                    'label' => 'Download Excel',
                    'kind' => 'link',
                    'url' => $url,
                ]],
            ]],
        ];
    }

    private function countExportRows(int $recruiterId, string $type, int $jobId = 0): int
    {
        $db = \Config\Database::connect();
        if ($type === 'overview') {
            return (int) $db->table('jobs')->where('recruiter_id', $recruiterId)->countAllResults();
        }

        $builder = $db->table('applications a')
            ->join('jobs j', 'j.id = a.job_id', 'inner')
            ->where('j.recruiter_id', $recruiterId);

        if ($jobId > 0) {
            $builder->where('a.job_id', $jobId);
        }

        return (int) $builder->countAllResults();
    }

    private function buildBatchStatusByScoreConfirmation(int $recruiterId, string $question): array
    {
        $status = preg_match('/\breject\b/i', $question) ? 'rejected' : 'shortlisted';
        $verb = $status === 'rejected' ? 'reject' : 'shortlist';
        $threshold = $this->extractScoreThreshold($question);
        $below = preg_match('/\b(below|under|less than)\b/i', $question) === 1;
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        $jobId = (int) ($job['id'] ?? 0);
        $candidates = $this->fetchCandidateMatches($recruiterId, $jobId, '', 0);
        $matches = array_values(array_filter($candidates, static function (array $row) use ($threshold, $below): bool {
            $score = (int) ($row['match_score'] ?? 0);
            return $below ? $score < $threshold : $score >= $threshold;
        }));

        if (empty($matches)) {
            $direction = $below ? 'below' : 'at or above';
            return $this->actionResult('batch_status_by_score', 'No candidates found ' . $direction . ' ' . $threshold . '% match' . ($job ? ' for ' . (string) ($job['title'] ?? 'that job') : '') . '.', []);
        }

        $ids = array_map(static fn (array $row): string => 'application #' . (int) $row['application_id'], $matches);
        $command = 'confirm ' . $verb . ' ' . implode(' ', $ids);
        $count = count($matches);
        $direction = $below ? 'below' : 'at or above';
        $answer = sprintf(
            '%d candidate%s %s %d%% match%s. %s all %d?',
            $count,
            $count === 1 ? '' : 's',
            $direction,
            $threshold,
            $job ? ' for ' . (string) ($job['title'] ?? 'that job') : '',
            ucfirst($verb),
            $count
        );

        $actions = [[
            'type' => 'confirmation',
            'title' => ucfirst($verb) . ' ' . $count . ' candidate' . ($count === 1 ? '' : 's'),
            'meta' => ($job ? (string) ($job['title'] ?? 'Selected job') . ' | ' : '') . 'Match ' . $direction . ' ' . $threshold . '%',
            'buttons' => [
                [
                    'label' => 'Confirm',
                    'kind' => $status === 'rejected' ? 'danger' : 'primary',
                    'silent' => true,
                    'command' => $command,
                ],
                [
                    'label' => 'Review First',
                    'kind' => 'link',
                    'url' => $jobId > 0 ? base_url('recruiter/jobs/view/' . $jobId) : base_url('recruiter/jobs'),
                ],
            ],
        ]];

        return $this->actionResult('batch_status_by_score', $answer, $actions);
    }

    private function buildApplicationStatusConfirmation(int $recruiterId, string $question): array
    {
        $status = preg_match('/\breject\b/i', $question) ? 'rejected' : 'shortlisted';
        preg_match_all('/applications?\s*#?\s*(\d+)/i', $question, $matches);
        $ids = array_values(array_unique(array_map('intval', $matches[1] ?? [])));

        if (empty($ids)) {
            return $this->actionResult('confirm_application_status', 'Choose a candidate from the action cards, then click Shortlist or Reject.');
        }

        $items = [];
        $skipped = [];
        foreach ($ids as $applicationId) {
            $application = $this->findRecruiterApplicationContext($recruiterId, $applicationId);
            if (!$application) {
                $skipped[] = '#' . $applicationId . ' not found or not yours';
                continue;
            }
            $items[] = $application;
        }

        if (empty($items)) {
            return $this->actionResult('confirm_application_status', 'I could not find any matching applications to ' . $status . '. Skipped: ' . implode(', ', $skipped));
        }

        $verb = $status === 'rejected' ? 'reject' : 'shortlist';
        $lines = [];
        foreach ($items as $item) {
            $lines[] = sprintf(
                'I found %s for %s. Confirm %s?',
                $item['candidate_name'] ?? 'candidate',
                $item['job_title'] ?? 'job',
                $verb
            );
        }
        if (!empty($skipped)) {
            $lines[] = 'Skipped: ' . implode(', ', $skipped);
        }

        $cards = [];
        foreach ($items as $item) {
            $applicationId = (int) $item['id'];
            $candidateId = (int) $item['candidate_id'];
            $jobId = (int) $item['job_id'];
            $cards[] = [
                'type' => 'confirmation',
                'title' => ucfirst($verb) . ' candidate',
                'meta' => trim(($item['candidate_name'] ?? 'Candidate') . ' | ' . ($item['job_title'] ?? 'Job')),
                'buttons' => [
                    [
                        'label' => 'Confirm ' . ucfirst($verb),
                        'kind' => $status === 'rejected' ? 'danger' : 'primary',
                        'silent' => true,
                        'command' => 'confirm ' . $verb . ' application #' . $applicationId,
                    ],
                    [
                        'label' => 'View Profile',
                        'kind' => 'link',
                        'url' => base_url('recruiter/candidate/' . $candidateId . '?application_id=' . $applicationId . '&job_id=' . $jobId),
                    ],
                ],
            ];
        }

        return $this->actionResult('confirm_application_status', implode("\n", $lines), $cards);
    }

    private function buildCandidateMessageConfirmation(int $recruiterId, string $question): array
    {
        if (!preg_match('/candidate\s*#?\s*(\d+)\s*:?\s*(.+)$/is', $question, $matches)) {
            return $this->actionResult('confirm_candidate_message', 'Pick a candidate from an action card, then use Message or Send so I know who should receive it.');
        }

        $candidateId = (int) $matches[1];
        $message = trim((string) $matches[2]);
        $message = preg_replace('/^(send|message)\b.*?\b(candidate\s*#?\s*\d+)\s*:?\s*/i', '', $message) ?? $message;
        $message = trim($message);

        $context = $this->findRecruiterCandidateContext($recruiterId, $candidateId);
        if (!$context) {
            return $this->actionResult('confirm_candidate_message', 'Message was not prepared because that candidate is not linked to one of your applications.');
        }

        $message = $this->personalizeRecruiterMessage($message, $context, $recruiterId);
        $delivery = $this->prepareMessageForDelivery($message, $context, $recruiterId);
        $message = $delivery['body'];

        if (!$this->hasUsableMessageText($message) || mb_strlen($message) > 1000) {
            return $this->actionResult('confirm_candidate_message', 'Message was not prepared. Please keep the message between 1 and 1000 characters.');
        }

        $applicationId = (int) ($context['application_id'] ?? 0);
        $jobId = (int) ($context['job_id'] ?? 0);
        $candidateName = $context['candidate_name'] ?? ('candidate #' . $candidateId);

        return $this->actionResult('confirm_candidate_message', 'I found ' . $candidateName . ". Confirm sending this message?\n\nSubject: " . $delivery['subject'] . "\n\nMessage: " . $message, [[
            'type' => 'confirmation',
            'title' => 'Send message to ' . $candidateName,
            'meta' => 'Subject: ' . $delivery['subject'],
            'detail' => $message,
            'buttons' => [
                [
                    'label' => 'Confirm Send',
                    'kind' => 'primary',
                    'silent' => true,
                    'command' => 'confirm send message to candidate #' . $candidateId . ': Subject: ' . $delivery['subject'] . "\n\n" . $message,
                ],
                [
                    'label' => 'View Profile',
                    'kind' => 'link',
                    'url' => base_url('recruiter/candidate/' . $candidateId . '?application_id=' . $applicationId . '&job_id=' . $jobId),
                ],
            ],
        ]]);
    }

    private function buildDefaultMessageProposal(int $recruiterId, array $context): array
    {
        $candidateName = (string) ($context['candidate_name'] ?? 'Candidate');
        $jobTitle = (string) ($context['job_title'] ?? 'the role');
        $company = (string) ($context['company'] ?? $this->getRecruiterCompany($recruiterId));
        $subject = 'Follow-up on your ' . $jobTitle . ' application';
        $body = "Hi {candidate_name},\n\nThank you for applying for the {$jobTitle}"
            . ($company !== '' ? " role at {$company}" : ' role')
            . ". We are reviewing applications and will be in touch soon with next steps.\n\nBest regards,\n{recruiter_name}";
        $body = $this->personalizeRecruiterMessage($body, $context, $recruiterId);

        $answer = "Here is a ready-to-send follow-up for {$candidateName}. Want me to send it as is, or tweak first?\n\n"
            . "Subject: {$subject}\n\n{$body}";

        return $this->actionResult('propose_candidate_message', $answer, [
            $this->buildMessageDraftAction($context, $subject, $body, 'Follow-up to ' . $candidateName),
        ]);
    }

    private function buildMissingMessagePrompt(array $context): array
    {
        $candidateId = (int) ($context['candidate_id'] ?? 0);
        $candidateName = (string) ($context['candidate_name'] ?? ('candidate #' . $candidateId));
        $applicationId = (int) ($context['application_id'] ?? 0);
        $jobId = (int) ($context['job_id'] ?? 0);

        return $this->actionResult('message_needs_body', 'Sure. What message should I send to ' . $candidateName . '?', [[
            'type' => 'confirmation',
            'title' => 'Message ' . $candidateName,
            'meta' => !empty($context['job_title']) ? 'For ' . (string) $context['job_title'] : 'Candidate message',
            'detail' => 'Type your message, or click below to start a draft.',
            'buttons' => [
                [
                    'label' => 'Write Message',
                    'kind' => 'draft',
                    'command' => 'send message to candidate #' . $candidateId . ': ',
                    'command_prefix' => 'send message to candidate #' . $candidateId . ': ',
                    'draft_text' => '',
                ],
                [
                    'label' => 'View Profile',
                    'kind' => 'link',
                    'url' => base_url('recruiter/candidate/' . $candidateId . '?application_id=' . $applicationId . '&job_id=' . $jobId),
                ],
            ],
        ]]);
    }

    private function hasUsableMessageText(string $message): bool
    {
        return trim($message) !== '' && preg_match('/[[:alnum:]]/u', $message) === 1;
    }

    private function personalizeRecruiterMessage(string $message, array $context, int $recruiterId): string
    {
        $user = model('UserModel')->find($recruiterId) ?: [];
        $candidateName = trim((string) ($context['candidate_name'] ?? 'Candidate'));
        $recruiterName = trim((string) ($user['name'] ?? 'Recruiter'));
        $companyName = trim((string) (($context['company'] ?? '') ?: ($user['company_name'] ?? '')));
        $jobTitle = trim((string) ($context['job_title'] ?? 'the role'));

        $replacements = [
            '{candidate_name}' => $candidateName,
            '{{candidate_name}}' => $candidateName,
            '[candidate name]' => $candidateName,
            '[candidate_name]' => $candidateName,
            '[candidate\'s name]' => $candidateName,
            '[Candidate Name]' => $candidateName,
            '[Candidate\'s Name]' => $candidateName,
            '{recruiter_name}' => $recruiterName,
            '{{recruiter_name}}' => $recruiterName,
            '[recruiter name]' => $recruiterName,
            '[Recruiter Name]' => $recruiterName,
            '{job_title}' => $jobTitle,
            '{{job_title}}' => $jobTitle,
            '[job title]' => $jobTitle,
            '[Job Title]' => $jobTitle,
            '{company_name}' => $companyName,
            '{{company_name}}' => $companyName,
            '[company name]' => $companyName,
            '[Company Name]' => $companyName,
        ];

        $message = strtr($message, $replacements);
        $message = preg_replace('/\*\*(Subject|Message|Body):\*\*/i', '$1:', $message) ?? $message;

        return trim($message);
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function prepareMessageForDelivery(string $message, array $context, int $recruiterId): array
    {
        $body = trim($message);
        $subject = '';

        if (preg_match('/^\s*(?:\*\*)?Subject(?:\*\*)?\s*:\s*(.+?)(?:\r?\n|$)(.*)$/is', $body, $matches)) {
            $subject = trim(strip_tags((string) $matches[1]));
            $body = trim((string) $matches[2]);
        }

        $subject = preg_replace('/\*\*/', '', $subject) ?? $subject;
        $subject = trim($subject);
        if ($subject === '') {
            $jobTitle = trim((string) ($context['job_title'] ?? 'the role'));
            $subject = 'Regarding your application for ' . $jobTitle;
        }

        return [
            'subject' => mb_substr($subject, 0, 160),
            'body' => trim($body),
        ];
    }

    private function sendMailboxMessageIfConnected(int $recruiterId, array $context, string $subject, string $message): void
    {
        if (empty($context['candidate_email']) || !\Config\Database::connect()->tableExists('recruiter_mailbox_connections')) {
            return;
        }

        try {
            $mailbox = (new \App\Models\RecruiterMailboxConnectionModel())->getConnectedForRecruiter($recruiterId);
            if (!$mailbox) {
                return;
            }

            (new RecruiterMailboxService())->sendForRecruiter(
                $recruiterId,
                (string) $context['candidate_email'],
                $subject,
                '<p>' . nl2br(esc($message)) . '</p>',
                [
                    'candidate_id' => (int) ($context['candidate_id'] ?? 0),
                    'application_id' => (int) ($context['application_id'] ?? 0) ?: null,
                    'job_id' => (int) ($context['job_id'] ?? 0) ?: null,
                ]
            );
        } catch (\Throwable $e) {
            log_message('error', 'Recruiter chatbot mailbox send failed: ' . $e->getMessage());
        }
    }

    private function updateApplicationsFromPrompt(int $recruiterId, string $question): string
    {
        $status = preg_match('/\breject\b/i', $question) ? 'rejected' : 'shortlisted';
        preg_match_all('/applications?\s*#?\s*(\d+)/i', $question, $matches);
        $ids = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
        if (empty($ids)) {
            return 'Choose a candidate from the action cards, then click Shortlist or Reject.';
        }

        $db = \Config\Database::connect();
        $updated = [];
        $skipped = [];

        foreach ($ids as $applicationId) {
            $application = $db->table('applications a')
                ->select('a.*, j.recruiter_id, j.title as job_title, u.name as candidate_name')
                ->join('jobs j', 'j.id = a.job_id', 'inner')
                ->join('users u', 'u.id = a.candidate_id', 'left')
                ->where('a.id', $applicationId)
                ->get()
                ->getRowArray();

            if (!$application || (int) ($application['recruiter_id'] ?? 0) !== $recruiterId) {
                $skipped[] = '#' . $applicationId . ' not found or not yours';
                continue;
            }

            $db->table('applications')->where('id', $applicationId)->update(['status' => $status]);
            $application['status'] = $status;
            if ($db->tableExists('notifications')) {
                (new \App\Models\NotificationModel())->triggerApplicationNotifications((int) $application['candidate_id'], $application);
            }

            $updated[] = sprintf(
                '#%d %s for %s',
                $applicationId,
                $application['candidate_name'] ?? 'candidate',
                $application['job_title'] ?? 'job'
            );
        }

        $lines = [];
        if (!empty($updated)) {
            $lines[] = ucfirst($status) . ' applications:';
            foreach ($updated as $item) {
                $lines[] = '- ' . $item;
            }
        }
        if (!empty($skipped)) {
            $lines[] = 'Skipped:';
            foreach ($skipped as $item) {
                $lines[] = '- ' . $item;
            }
        }

        return implode("\n", $lines);
    }

    private function buildCandidateActionCards(array $candidates): array
    {
        $cards = [];
        foreach ($candidates as $row) {
            $applicationId = (int) ($row['application_id'] ?? 0);
            $candidateId = (int) ($row['candidate_id'] ?? 0);
            $jobId = (int) ($row['job_id'] ?? 0);
            if ($applicationId <= 0 || $candidateId <= 0) {
                continue;
            }

            $metaParts = [
                (string) ($row['job_title'] ?? 'Job'),
            ];
            if (array_key_exists('match_score', $row)) {
                $metaParts[] = 'Match ' . (int) $row['match_score'] . '%';
            }

            $cards[] = [
                'type' => 'candidate',
                'candidate_id' => $candidateId,
                'application_id' => $applicationId,
                'job_id' => $jobId,
                'candidate_name' => (string) ($row['candidate_name'] ?? ('Candidate #' . $candidateId)),
                'job_title' => (string) ($row['job_title'] ?? 'Job'),
                'title' => (string) ($row['candidate_name'] ?? ('Candidate #' . $candidateId)),
                'meta' => implode(' | ', $metaParts),
                'detail' => (string) (($row['candidate_skills'] ?? '') !== '' ? $row['candidate_skills'] : 'Skills not listed'),
                'buttons' => [
                    [
                        'label' => 'View Profile',
                        'kind' => 'link',
                        'url' => base_url('recruiter/candidate/' . $candidateId . '?application_id=' . $applicationId . '&job_id=' . $jobId),
                    ],
                    [
                        'label' => 'Shortlist',
                        'kind' => 'primary',
                        'silent' => true,
                        'command' => 'shortlist application #' . $applicationId,
                    ],
                    [
                        'label' => 'Message',
                        'kind' => 'draft',
                        'command' => 'send message to candidate #' . $candidateId . ': ',
                        'command_prefix' => 'send message to candidate #' . $candidateId . ': ',
                        'draft_text' => '',
                    ],
                    [
                        'label' => 'Schedule Interview',
                        'kind' => 'link',
                        'url' => base_url('recruiter/slots/create?job_id=' . $jobId),
                    ],
                    [
                        'label' => 'Reject',
                        'kind' => 'danger',
                        'silent' => true,
                        'command' => 'reject application #' . $applicationId,
                    ],
                ],
            ];
        }

        return $cards;
    }

    private function buildCandidateActionCardsFromCandidateRows(int $recruiterId, array $candidateRows): array
    {
        $rows = [];
        foreach (array_slice($candidateRows, 0, 5) as $candidate) {
            $context = $this->findRecruiterCandidateContext($recruiterId, (int) ($candidate['id'] ?? 0));
            if (!$context) {
                continue;
            }

            $rows[] = [
                'application_id' => (int) ($context['application_id'] ?? 0),
                'candidate_id' => (int) ($context['candidate_id'] ?? 0),
                'job_id' => (int) ($context['job_id'] ?? 0),
                'candidate_name' => (string) ($context['candidate_name'] ?? ($candidate['name'] ?? 'Candidate')),
                'job_title' => (string) ($context['job_title'] ?? 'Job'),
                'candidate_skills' => (string) ($candidate['skills'] ?? ''),
            ];
        }

        return $this->buildCandidateActionCards($rows);
    }

    private function findRecruiterApplicationContext(int $recruiterId, int $applicationId): ?array
    {
        $row = \Config\Database::connect()->table('applications a')
            ->select('a.*, j.recruiter_id, j.title as job_title, u.name as candidate_name')
            ->join('jobs j', 'j.id = a.job_id', 'inner')
            ->join('users u', 'u.id = a.candidate_id', 'left')
            ->where('a.id', $applicationId)
            ->get()
            ->getRowArray();

        if (!$row || (int) ($row['recruiter_id'] ?? 0) !== $recruiterId) {
            return null;
        }

        return $row;
    }

    private function resolveCandidateContextFromChat(int $recruiterId, string $question, array $chatContext): ?array
    {
        $mentionsPreviousCandidate = preg_match('/\b(above|previous|last|this|that)\s+(candidate|applicant|person)\b/i', $question)
            || preg_match('/\b(to|for)\s+(him|her|them)\b/i', $question);

        $candidateId = 0;
        if (preg_match('/\bcandidate\s*#?\s*(\d+)\b/i', $question, $matches)) {
            $candidateId = (int) $matches[1];
        } elseif ($mentionsPreviousCandidate && isset($chatContext['last_candidate']['candidate_id'])) {
            $candidateId = (int) $chatContext['last_candidate']['candidate_id'];
        }

        if ($candidateId <= 0 && $mentionsPreviousCandidate) {
            $candidateRows = $this->fetchCandidates($recruiterId, '');
            if (count($candidateRows) === 1) {
                $candidateId = (int) ($candidateRows[0]['id'] ?? 0);
            }
        }

        if ($candidateId <= 0) {
            return null;
        }

        $context = $this->findRecruiterCandidateContext($recruiterId, $candidateId);
        if (!$context) {
            return null;
        }

        return $context;
    }

    private function resolveCandidateContextForAction(int $recruiterId, string $question, array $chatContext): ?array
    {
        $context = $this->resolveCandidateContextFromChat($recruiterId, $question, $chatContext);
        if ($context) {
            return $context;
        }

        $name = $this->extractCandidateNameFromQuestion($question);
        if ($name === '') {
            return null;
        }

        return $this->findRecruiterCandidateContextByName($recruiterId, $name);
    }

    private function extractCandidateNameFromQuestion(string $question): string
    {
        if (!preg_match('/\b(?:to|for|with)\s+([a-z][a-z .\'-]{1,80})(?:\s+(?:about|regarding|for|on|as|at|tomorrow|today|next|this)\b|$)/i', $question, $matches)) {
            return '';
        }

        $name = trim((string) $matches[1]);
        $name = preg_replace('/\b(candidate|applicant|person|above|previous|last|this|that|him|her|them)\b/i', '', $name) ?? $name;
        $name = preg_replace('/\b(interview|invite|invitation|message|email|followup|follow-up)\b.*$/i', '', $name) ?? $name;
        $name = trim($name, " \t\n\r\0\x0B.,");

        return preg_match('/[a-z]/i', $name) === 1 ? $name : '';
    }

    private function findRecruiterCandidateContextByName(int $recruiterId, string $name): ?array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('applications a')
            ->select('a.id as application_id, a.candidate_id, a.job_id, u.name as candidate_name, u.email as candidate_email, j.title as job_title, j.company')
            ->join('jobs j', 'j.id = a.job_id', 'inner')
            ->join('users u', 'u.id = a.candidate_id', 'inner')
            ->where('j.recruiter_id', $recruiterId)
            ->like('u.name', $name)
            ->orderBy('a.applied_at', 'DESC');

        $row = $builder->get(1)->getRowArray();
        if ($row) {
            return $row;
        }

        $tokens = $this->normalizeSearchTokens($name);
        if (empty($tokens)) {
            return null;
        }

        $builder = $db->table('applications a')
            ->select('a.id as application_id, a.candidate_id, a.job_id, u.name as candidate_name, u.email as candidate_email, j.title as job_title, j.company')
            ->join('jobs j', 'j.id = a.job_id', 'inner')
            ->join('users u', 'u.id = a.candidate_id', 'inner')
            ->where('j.recruiter_id', $recruiterId)
            ->orderBy('a.applied_at', 'DESC');

        foreach ($tokens as $token) {
            $builder->like('u.name', $token);
        }

        return $builder->get(1)->getRowArray() ?: null;
    }

    private function classifyIntent(string $question): string
    {
        $q = strtolower($question);

        // Job-related
        if (preg_match('/\b(jobs?|postings?|openings?|vacancies?)\b/', $q) && !preg_match('/\b(candidates?|applicants?)\b/', $q)) {
            return 'jobs';
        }

        // Applications
        if (preg_match('/\b(applications?|applied|applicants?)\b/', $q) && !preg_match('/\b(slots?|interviews?|bookings?)\b/', $q)) {
            return 'applications';
        }

        // Candidates
        if (preg_match('/\b(candidates?|applicants?|people|hires?)\b/', $q)) {
            return 'candidates';
        }

        // Interview slots / bookings
        if (preg_match('/\b(slots?|interviews?|bookings?|scheduled?|calendar)\b/', $q)) {
            return 'interviews';
        }

        // Stats / summary
        if (preg_match('/\b(summary|overview|stats?|statistics|dashboard|how many|total|count)\b/', $q)) {
            return 'summary';
        }

        // Fall back to general (fetch broad data)
        return 'general';
    }

    // ─── Data retrieval ────────────────────────────────────────

    private function retrieveData(int $recruiterId, string $intent, string $question): array
    {
        $db = \Config\Database::connect();
        $data = [];
        $q = strtolower($question);

        switch ($intent) {
            case 'jobs':
                $data['jobs'] = $this->fetchJobs($recruiterId, $q);
                break;

            case 'applications':
                $data['applications_summary'] = $this->fetchApplicationsSummary($recruiterId);
                $data['recent_applications'] = $this->fetchRecentApplications($recruiterId);
                break;

            case 'candidates':
                $data['candidates'] = $this->fetchCandidates($recruiterId, $q);
                $data['applications_summary'] = $this->fetchApplicationsSummary($recruiterId);
                break;

            case 'interviews':
                $data['slots'] = $this->fetchSlots($recruiterId);
                $data['bookings'] = $this->fetchBookings($recruiterId, $q);
                break;

            case 'summary':
                $data['jobs'] = $this->fetchJobs($recruiterId, '');
                $data['applications_summary'] = $this->fetchApplicationsSummary($recruiterId);
                $data['slots'] = $this->fetchSlots($recruiterId);
                $data['bookings'] = $this->fetchBookings($recruiterId, '');
                $data['candidates'] = $this->fetchCandidates($recruiterId, '');
                break;

            default:
                // General — fetch a bit of everything
                $data['jobs'] = array_slice($this->fetchJobs($recruiterId, ''), 0, 5);
                $data['applications_summary'] = $this->fetchApplicationsSummary($recruiterId);
                $data['recent_bookings'] = array_slice($this->fetchBookings($recruiterId, ''), 0, 3);
                break;
        }

        return $data;
    }

    private function fetchJobs(int $recruiterId, string $keyword = ''): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('jobs j')
            ->select('j.id, j.title, j.category, j.location, j.status, j.openings, j.created_at, j.experience_level, j.employment_type, j.company')
            ->where('j.recruiter_id', $recruiterId)
            ->orderBy('j.created_at', 'DESC')
            ->limit(30);

        if ($keyword !== '' && preg_match('/\b(open|active)\b/', $keyword)) {
            $builder->where('j.status', 'open');
        } elseif ($keyword !== '' && preg_match('/\b(closed?|filled?|inactive)\b/', $keyword)) {
            $builder->where('j.status', 'closed');
        }

        // Add application counts
        $result = $builder->get();
        $rows = ($result !== false) ? $result->getResultArray() : [];
        foreach ($rows as &$row) {
            $count = $db->table('applications')
                ->where('job_id', (int) $row['id'])
                ->countAllResults();
            $row['application_count'] = $count;
        }
        unset($row);

        return $rows;
    }

    private function fetchApplicationsSummary(int $recruiterId): array
    {
        $db = \Config\Database::connect();

        $totalResult = $db->query("
            SELECT COUNT(*) as total
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            WHERE j.recruiter_id = ?
        ", [$recruiterId]);
        $total = ($totalResult !== false) ? $totalResult->getRowArray() : null;

        $byStatusResult = $db->query("
            SELECT a.status, COUNT(*) as count
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            WHERE j.recruiter_id = ?
            GROUP BY a.status
        ", [$recruiterId]);
        $byStatus = ($byStatusResult !== false) ? $byStatusResult->getResultArray() : [];

        return [
            'total' => (int) ($total['total'] ?? 0),
            'by_status' => $byStatus,
        ];
    }

    private function fetchRecentApplications(int $recruiterId, int $limit = 10): array
    {
        $db = \Config\Database::connect();
        $result = $db->query("
            SELECT a.id, a.status, a.applied_at, a.candidate_id,
                   j.title as job_title, j.id as job_id,
                   u.name as candidate_name, u.email as candidate_email
            FROM applications a
            JOIN jobs j ON a.job_id = j.id
            JOIN users u ON a.candidate_id = u.id
            WHERE j.recruiter_id = ?
            ORDER BY a.applied_at DESC
            LIMIT ?
        ", [$recruiterId, $limit]);
        return ($result !== false) ? $result->getResultArray() : [];
    }

    private function fetchCandidates(int $recruiterId, string $keyword = ''): array
    {
        $db = \Config\Database::connect();
        $result = $db->query("
            SELECT u.id, u.name, u.email, '' AS location, u.created_at
            FROM users u
            WHERE u.id IN (
                SELECT DISTINCT a.candidate_id
                FROM applications a
                JOIN jobs j ON a.job_id = j.id
                WHERE j.recruiter_id = ?
            )
            ORDER BY u.created_at DESC
            LIMIT 50
        ", [$recruiterId]);

        $rows = ($result !== false) ? $result->getResultArray() : [];

        // Enrich with skill info
        foreach ($rows as &$row) {
            $skillsResult = $db->table('candidate_skills')
                ->select('skill_name')
                ->where('candidate_id', (int) $row['id'])
                ->get();
            $skillsRow = ($skillsResult !== false) ? $skillsResult->getRowArray() : null;
            $row['skills'] = $skillsRow['skill_name'] ?? '';

            $appCount = $db->table('applications')
                ->where('candidate_id', (int) $row['id'])
                ->whereIn('job_id', function ($b) use ($recruiterId) {
                    $b->select('id')->from('jobs')->where('recruiter_id', $recruiterId);
                })
                ->countAllResults();
            $row['application_count'] = $appCount;
        }
        unset($row);

        return $rows;
    }

    private function fetchSlots(int $recruiterId): array
    {
        $db = \Config\Database::connect();
        $result = $db->table('interview_slots')
            ->select('id, job_id, slot_date, slot_time, slot_datetime, is_available, created_at')
            ->where('created_by', $recruiterId)
            ->orderBy('slot_date', 'DESC')
            ->limit(20)
            ->get();

        $rows = ($result !== false) ? $result->getResultArray() : [];

        $available = 0;
        $booked = 0;
        foreach ($rows as $row) {
            if ((int) ($row['is_available'] ?? 0) === 1) {
                $available++;
            } else {
                $booked++;
            }
        }

        return [
            'total' => count($rows),
            'available' => $available,
            'booked' => $booked,
            'upcoming' => array_values(array_filter($rows, function ($r) {
                return ($r['slot_date'] ?? '') >= date('Y-m-d');
            })),
        ];
    }

    private function fetchBookings(int $recruiterId, string $keyword = ''): array
    {
        $db = \Config\Database::connect();
        $result = $db->query("
            SELECT ib.id, ib.slot_id, ib.user_id,
                   ib.booking_status AS status, ib.booked_at AS booked_at,
                   s.slot_date, s.slot_time, s.slot_datetime, s.is_available AS slot_is_available,
                   u.name AS candidate_name, u.email AS candidate_email
            FROM interview_bookings ib
            JOIN interview_slots s ON ib.slot_id = s.id
            JOIN users u ON ib.user_id = u.id
            WHERE s.created_by = ?
            ORDER BY s.slot_date DESC
            LIMIT 30
        ", [$recruiterId]);

        $rows = ($result !== false) ? $result->getResultArray() : [];

        // Apply keyword filters in PHP since the query uses raw SQL
        if ($keyword !== '') {
            if (preg_match('/\b(upcoming|today|future)\b/', $keyword)) {
                $rows = array_values(array_filter($rows, function ($r) {
                    return ($r['slot_date'] ?? '') >= date('Y-m-d');
                }));
            }
            if (preg_match('/\b(past|previous|completed)\b/', $keyword)) {
                $rows = array_values(array_filter($rows, function ($r) {
                    return ($r['slot_date'] ?? '') < date('Y-m-d');
                }));
            }
        }

        return $rows;
    }

    // ─── Prompt building ───────────────────────────────────────

    private function buildSystemPrompt(int $recruiterId): string
    {
        $user = model('UserModel')->find($recruiterId);
        $recruiterName = $user['name'] ?? 'Recruiter';
        $companyName = $user['company_name'] ?? '';

        return "You are HireMate, an AI recruitment assistant embedded in the HireMatrix recruiter dashboard. "
            . "You help recruiters manage jobs, candidates, screening, communication, scheduling, and reporting with minimum effort.\n\n"
            . "Recruiter: {$recruiterName}\n"
            . "Company: {$companyName}\n\n"
            . "You have access to the recruiter's data through RAG retrieval. "
            . "The data provided in each query is real-time and specific to this recruiter.\n\n"
            . "Guidelines:\n"
            . "- Lead with the direct answer or result. Keep prose minimal.\n"
            . "- Never expose or ask for internal IDs. Refer to jobs by title and candidates by name.\n"
            . "- If a job or candidate reference is ambiguous, ask the recruiter to choose from short options.\n"
            . "- Every action-oriented response should end with a useful button, link, copy action, or confirmation control.\n"
            . "- Do not ask what you can infer from recruiter, job, candidate, or recent chat context.\n"
            . "- If a bulk email/message needs recipient selection, show the draft and candidate selection in the same turn. Do not draft first and then ask the recruiter to choose candidates manually.\n"
            . "- Bulk or destructive actions must show a count and require explicit confirmation.\n"
            . "- Answer only based on the data provided. Do not invent numbers.\n"
            . "- If the data is insufficient, say 'I don't have enough data to answer that yet.'\n"
            . "- Format numbers with commas for readability.\n"
            . "- Never reveal raw SQL or database details.\n"
            . "- Keep responses under 200 words unless the question requires more detail.";
    }

    private function formatRetrievedData(array $data): string
    {
        $lines = [];

        if (isset($data['jobs']) && !empty($data['jobs'])) {
            $lines[] = '--- JOBS ---';
            foreach ($data['jobs'] as $job) {
                $lines[] = sprintf(
                    '- Job #%d: "%s" | %s | %s | %s | %d openings | %d applications',
                    $job['id'],
                    $job['title'],
                    $job['status'],
                    $job['category'],
                    $job['location'],
                    $job['openings'],
                    $job['application_count'] ?? 0
                );
            }
        }

        if (isset($data['applications_summary'])) {
            $s = $data['applications_summary'];
            $lines[] = '--- APPLICATIONS SUMMARY ---';
            $lines[] = sprintf('- Total applications: %d', $s['total']);
            foreach ($s['by_status'] as $row) {
                $lines[] = sprintf('- %s: %d', $row['status'], $row['count']);
            }
        }

        if (isset($data['recent_applications']) && !empty($data['recent_applications'])) {
            $lines[] = '--- RECENT APPLICATIONS ---';
            foreach ($data['recent_applications'] as $app) {
                $lines[] = sprintf(
                    '- App #%d: %s applied to "%s" | Status: %s | Date: %s',
                    $app['id'],
                    $app['candidate_name'],
                    $app['job_title'],
                    $app['status'],
                    $app['applied_at']
                );
            }
        }

        if (isset($data['candidates']) && !empty($data['candidates'])) {
            $lines[] = '--- CANDIDATES ---';
            foreach ($data['candidates'] as $cand) {
                $lines[] = sprintf(
                    '- Candidate #%d: %s | %s | Skills: %s | Applied to %d of your jobs',
                    $cand['id'],
                    $cand['name'],
                    $cand['email'],
                    $cand['skills'] ?: 'N/A',
                    $cand['application_count'] ?? 0
                );
            }
        }

        if (isset($data['slots'])) {
            $s = $data['slots'];
            $lines[] = '--- INTERVIEW SLOTS ---';
            $lines[] = sprintf('- Total: %d | Available: %d | Booked: %d', $s['total'], $s['available'], $s['booked']);
            foreach ($s['upcoming'] as $slot) {
                $lines[] = sprintf(
                    '- Slot #%d | %s %s | %s',
                    $slot['id'],
                    $slot['slot_date'] ?? '',
                    $slot['slot_time'] ?? '',
                    (int) ($slot['is_available'] ?? 0) === 1 ? 'Available' : 'Booked'
                );
            }
        }

        if (isset($data['bookings']) && !empty($data['bookings'])) {
            $lines[] = '--- BOOKINGS ---';
            foreach ($data['bookings'] as $b) {
                $lines[] = sprintf(
                    '- Booking #%d: %s on %s at %s | Status: %s',
                    $b['id'],
                    $b['candidate_name'],
                    $b['slot_date'],
                    $b['slot_time'],
                    $b['status']
                );
            }
        }

        if (empty($lines)) {
            return 'No relevant data found for this recruiter.';
        }

        return implode("\n", $lines);
    }

    // ─── OpenAI call ───────────────────────────────────────────

    private function resolveJobFromQuestion(int $recruiterId, string $question): ?array
    {
        $db = \Config\Database::connect();
        $jobId = $this->extractNumberAfter($question, 'job');
        if ($jobId > 0) {
            $row = $db->table('jobs')
                ->where('id', $jobId)
                ->where('recruiter_id', $recruiterId)
                ->get()
                ->getRowArray();
            if ($row) {
                return $row;
            }
        }

        $title = $this->extractRoleTitle($question);
        if ($title !== '') {
            $row = $db->table('jobs')
                ->where('recruiter_id', $recruiterId)
                ->like('title', $title)
                ->orderBy('created_at', 'DESC')
                ->get(1)
                ->getRowArray();
            if ($row) {
                return $row;
            }

            $tokens = $this->normalizeSearchTokens($title);
            if (!empty($tokens)) {
                $builder = $db->table('jobs')
                    ->where('recruiter_id', $recruiterId)
                    ->orderBy('created_at', 'DESC');

                foreach ($tokens as $token) {
                    $builder->like('title', $token);
                }

                $row = $builder->get(1)->getRowArray();
                if ($row) {
                    return $row;
                }
            }
        }

        return null;
    }

    private function extractNumberAfter(string $question, string $label): int
    {
        if (preg_match('/\b' . preg_quote($label, '/') . '\s*#?\s*(\d+)\b/i', $question, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function extractRoleTitle(string $question): string
    {
        if (preg_match('/\b(?:for|as|role|position)\s+([a-z0-9+#.,\/ -]{3,80})/i', $question, $matches)) {
            $title = trim((string) $matches[1]);
            $title = preg_replace('/\b(with|using|and skills?|questions?|message|email|job|ats|score|match|above|over|under|below)\b.*$/i', '', $title) ?? $title;
            return trim($title, " \t\n\r\0\x0B.,");
        }

        return '';
    }

    private function normalizeSearchTokens(string $value): array
    {
        $parts = preg_split('/[^a-z0-9+#.]+/i', strtolower($value)) ?: [];
        $stopWords = ['a', 'an', 'the', 'for', 'job', 'role', 'position', 'developer', 'engineer'];
        $tokens = [];
        foreach ($parts as $part) {
            $token = trim($part);
            if ($token !== '' && !in_array($token, $stopWords, true)) {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
    }

    private function extractSkillsText(string $question): string
    {
        if (preg_match('/\b(?:skills?|with|using)\s+([a-z0-9+#.,\/ -]{2,120})/i', $question, $matches)) {
            $value = trim((string) $matches[1]);
            $value = preg_replace('/\b(?:for|job|candidate|applicant|ats|score|above|over|under)\b.*$/i', '', $value) ?? $value;
            return trim($value, " \t\n\r\0\x0B.,");
        }

        return '';
    }

    private function extractScoreThreshold(string $question): int
    {
        if (preg_match('/\b(?:ats|score|match)\D{0,12}(\d{1,3})\b/i', $question, $matches)) {
            return max(0, min(100, (int) $matches[1]));
        }

        if (preg_match('/\b(?:below|under|less than|above|over|at least)\s+(\d{1,3})\s*%?\s*(?:ats|score|match)?\b/i', $question, $matches)) {
            return max(0, min(100, (int) $matches[1]));
        }

        return 0;
    }

    private function normalizeList(string $value): array
    {
        $parts = preg_split('/[,|;\/\n\r]+/', $value) ?: [];
        $items = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $items[] = $part;
            }
        }

        return array_values(array_unique($items));
    }

    private function getRecruiterCompany(int $recruiterId): string
    {
        $user = model('UserModel')->find($recruiterId);
        return (string) ($user['company_name'] ?? '');
    }

    private function fetchCandidateMatches(int $recruiterId, int $jobId = 0, string $skillFilter = '', int $minScore = 0): array
    {
        $db = \Config\Database::connect();
        $hasInterviewSessions = $db->tableExists('interview_sessions');
        $ratingSelect = $hasInterviewSessions
            ? 'MAX(COALESCE(s.overall_rating, 0)) as overall_rating'
            : '0 as overall_rating';
        $experienceSubQuery = '(SELECT user_id, SUM(TIMESTAMPDIFF(MONTH, start_date, COALESCE(NULLIF(end_date, \'\'), CURDATE()))) AS total_experience_months FROM work_experiences GROUP BY user_id) candidate_experience';

        $builder = $db->table('applications a')
            ->select('a.id as application_id, a.status, a.candidate_id, a.job_id, a.applied_at, u.name as candidate_name, u.email as candidate_email, j.title as job_title, j.required_skills, j.experience_level, COALESCE(cp.resume_path, "") as resume_path, COALESCE(candidate_experience.total_experience_months, 0) as total_experience_months, COALESCE(GROUP_CONCAT(DISTINCT cs.skill_name SEPARATOR ", "), "") as candidate_skills, ' . $ratingSelect)
            ->join('jobs j', 'j.id = a.job_id', 'inner')
            ->join('users u', 'u.id = a.candidate_id', 'inner')
            ->join('candidate_profiles cp', 'cp.user_id = a.candidate_id', 'left')
            ->join('candidate_skills cs', 'cs.candidate_id = a.candidate_id', 'left')
            ->join($experienceSubQuery, 'candidate_experience.user_id = a.candidate_id', 'left', false)
            ->where('j.recruiter_id', $recruiterId)
            ->groupBy('a.id, a.status, a.candidate_id, a.job_id, a.applied_at, u.name, u.email, j.title, j.required_skills, j.experience_level, cp.resume_path, candidate_experience.total_experience_months')
            ->orderBy('a.applied_at', 'DESC')
            ->limit(80);

        if ($hasInterviewSessions) {
            $builder->join('interview_sessions s', 's.application_id = a.id', 'left');
        }

        if ($jobId > 0) {
            $builder->where('a.job_id', $jobId);
        }

        $skillTokens = $this->normalizeSkillTokens($skillFilter);
        $rows = $builder->get()->getResultArray();
        foreach ($rows as &$row) {
            $candidateTokens = $this->normalizeSkillTokens((string) ($row['candidate_skills'] ?? ''));
            $requiredTokens = $this->normalizeSkillTokens((string) ($row['required_skills'] ?? ''));
            $comparisonTokens = !empty($skillTokens) ? $skillTokens : $requiredTokens;
            $row['match_score'] = !empty($skillTokens)
                ? $this->calculateMatchScore($candidateTokens, $comparisonTokens)
                : $this->calculateCandidateAtsScore($row, $candidateTokens, $requiredTokens);
        }
        unset($row);

        $rows = array_values(array_filter($rows, static function (array $row) use ($minScore): bool {
            return (int) ($row['match_score'] ?? 0) >= $minScore;
        }));

        usort($rows, static fn (array $a, array $b): int => ((int) ($b['match_score'] ?? 0)) <=> ((int) ($a['match_score'] ?? 0)));

        return $rows;
    }

    private function normalizeSkillTokens(string $value): array
    {
        $parts = preg_split('/[,|;\/\n\r]+/', strtolower($value)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '') {
                $tokens[] = $part;
            }
        }

        return array_values(array_unique($tokens));
    }

    private function buildSkillMatchSummary(array $row): string
    {
        $candidateTokens = $this->normalizeSkillTokens((string) ($row['candidate_skills'] ?? ''));
        $requiredTokens = $this->normalizeSkillTokens((string) ($row['required_skills'] ?? ''));
        if (empty($candidateTokens)) {
            return '';
        }

        $matches = [];
        foreach ($requiredTokens as $required) {
            foreach ($candidateTokens as $candidate) {
                if ($required === $candidate || str_contains($candidate, $required) || str_contains($required, $candidate)) {
                    $matches[] = $candidate;
                    break;
                }
            }
        }

        if (empty($matches)) {
            $matches = $candidateTokens;
        }

        $labels = array_map(static fn (string $value): string => strtoupper($value) === 'PHP' ? 'PHP' : ucwords($value), array_slice(array_values(array_unique($matches)), 0, 4));
        return implode(', ', $labels) . ' match';
    }

    private function calculateMatchScore(array $candidateTokens, array $requiredTokens): int
    {
        if (empty($candidateTokens) && empty($requiredTokens)) {
            return 50;
        }
        if (empty($requiredTokens)) {
            return 40;
        }
        if (empty($candidateTokens)) {
            return 0;
        }

        $matches = 0;
        foreach ($requiredTokens as $required) {
            foreach ($candidateTokens as $candidate) {
                if ($required === $candidate || str_contains($candidate, $required) || str_contains($required, $candidate)) {
                    $matches++;
                    break;
                }
            }
        }

        return (int) round(($matches / max(1, count($requiredTokens))) * 100);
    }

    private function calculateCandidateAtsScore(array $row, array $candidateTokens, array $requiredTokens): int
    {
        $requiredMonths = $this->extractRequiredExperienceMonths((string) ($row['experience_level'] ?? ''));
        if (empty($requiredTokens) && ($requiredMonths === null || $requiredMonths <= 0)) {
            return 0;
        }

        if (empty($requiredTokens)) {
            $skillScore = 60;
        } else {
            $skillScore = (int) round(($this->countSkillMatches($candidateTokens, $requiredTokens) / max(1, count($requiredTokens))) * 60);
        }

        $candidateMonths = max(0, (int) ($row['total_experience_months'] ?? 0));
        $experienceScore = ($requiredMonths === null || $requiredMonths <= 0)
            ? 20
            : (int) round(min(1, $candidateMonths / $requiredMonths) * 20);
        $rating = (float) ($row['overall_rating'] ?? 0);
        $aiScore = $rating > 0 ? (int) round(min(10, max(0, $rating)) / 10 * 15) : 0;
        $profileScore = !empty($row['resume_path']) ? 5 : 0;

        return max(0, min(100, $skillScore + $experienceScore + $aiScore + $profileScore));
    }

    private function countSkillMatches(array $candidateTokens, array $requiredTokens): int
    {
        $matches = 0;
        foreach ($requiredTokens as $required) {
            foreach ($candidateTokens as $candidate) {
                if ($required === $candidate || str_contains($candidate, $required) || str_contains($required, $candidate)) {
                    $matches++;
                    break;
                }
            }
        }

        return $matches;
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

    private function findRecruiterCandidateContext(int $recruiterId, int $candidateId): ?array
    {
        $row = \Config\Database::connect()->table('applications a')
            ->select('a.id as application_id, a.candidate_id, a.job_id, u.name as candidate_name, u.email as candidate_email, j.title as job_title, j.company')
            ->join('jobs j', 'j.id = a.job_id', 'inner')
            ->join('users u', 'u.id = a.candidate_id', 'inner')
            ->where('j.recruiter_id', $recruiterId)
            ->where('a.candidate_id', $candidateId)
            ->orderBy('a.applied_at', 'DESC')
            ->get(1)
            ->getRowArray();

        return $row ?: null;
    }

    private function callOpenAi(string $systemPrompt, string $userMessage): ?string
    {
        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => 0.3,
            'max_tokens' => 800,
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim($this->apiKey),
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 45,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            log_message('error', 'RecruiterChatbot cURL error: ' . $curlError);
            return null;
        }

        if ($httpCode !== 200) {
            log_message('error', 'RecruiterChatbot HTTP error: ' . $httpCode . ' body: ' . substr((string) $response, 0, 400));
            return null;
        }

        $decoded = json_decode((string) $response, true);
        if (is_array($decoded)) {
            (new UsageAnalyticsService())->logOpenAiUsage($decoded, '/v1/chat/completions', 'gpt-4o-mini');
        }

        return $decoded['choices'][0]['message']['content'] ?? null;
    }

    public function buildMorningBrief(int $recruiterId): array
    {
        $db = \Config\Database::connect();
        $user = model('UserModel')->find($recruiterId) ?: [];
        $name = trim((string) ($user['name'] ?? ''));
        $firstName = $name !== '' ? preg_split('/\s+/', $name)[0] : 'there';
        $since = date('Y-m-d H:i:s', strtotime('-1 day'));
        $fiveDaysAgo = date('Y-m-d H:i:s', strtotime('-5 days'));
        $threeDaysAgo = date('Y-m-d H:i:s', strtotime('-3 days'));

        $newApplications = (int) $db->table('applications a')
            ->join('jobs j', 'j.id = a.job_id', 'inner')
            ->where('j.recruiter_id', $recruiterId)
            ->where('a.applied_at >=', $since)
            ->countAllResults();

        $recentStrong = [];
        foreach ($this->fetchCandidateMatches($recruiterId, 0, '', 70) as $row) {
            if (strtotime((string) ($row['applied_at'] ?? '')) >= strtotime($since)) {
                $recentStrong[] = $row;
            }
        }
        $strongJobs = array_values(array_unique(array_filter(array_map(static fn (array $row): string => (string) ($row['job_title'] ?? ''), $recentStrong))));

        $noResponseCount = 0;
        if ($db->tableExists('recruiter_candidate_messages')) {
            $sentRows = $db->table('recruiter_candidate_messages')
                ->select('candidate_id, MAX(created_at) as last_sent_at')
                ->where('recruiter_id', $recruiterId)
                ->where('sender_role', 'recruiter')
                ->where('created_at <=', $threeDaysAgo)
                ->groupBy('candidate_id')
                ->get()
                ->getResultArray();

            foreach ($sentRows as $sent) {
                $reply = $db->table('recruiter_candidate_messages')
                    ->where('recruiter_id', $recruiterId)
                    ->where('candidate_id', (int) ($sent['candidate_id'] ?? 0))
                    ->where('sender_role', 'candidate')
                    ->where('created_at >', (string) ($sent['last_sent_at'] ?? ''))
                    ->countAllResults();
                if ($reply === 0) {
                    $noResponseCount++;
                }
            }
        }

        $staleJob = $db->table('jobs j')
            ->select('j.id, j.title, COUNT(a.id) as application_count')
            ->join('applications a', 'a.job_id = j.id', 'left')
            ->where('j.recruiter_id', $recruiterId)
            ->where('j.created_at <=', $fiveDaysAgo)
            ->groupBy('j.id, j.title')
            ->having('application_count', 0)
            ->orderBy('j.created_at', 'DESC')
            ->get(1)
            ->getRowArray();

        $lines = ['Good morning, ' . $firstName . '. Since yesterday:'];
        $lines[] = '- ' . $newApplications . ' new application' . ($newApplications === 1 ? '' : 's')
            . (!empty($recentStrong) ? ' (' . count($recentStrong) . ' strong match' . (count($recentStrong) === 1 ? '' : 'es') . ' - ' . implode(', ', array_slice($strongJobs, 0, 2)) . ')' : '');
        $lines[] = '- ' . $noResponseCount . ' candidate' . ($noResponseCount === 1 ? '' : 's') . ' without a reply after 3 days';
        $lines[] = $staleJob
            ? '- Job "' . (string) ($staleJob['title'] ?? 'Untitled') . '" has 0 applications in 5 days - consider reposting'
            : '- No stalled zero-application jobs found';

        $actions = [];
        if ($newApplications > 0) {
            $actions[] = [
                'type' => 'brief_action',
                'title' => 'Review new candidates',
                'buttons' => [[
                    'label' => 'Review new candidates',
                    'kind' => 'link',
                    'url' => base_url('recruiter/jobs'),
                ]],
            ];
        }
        if ($noResponseCount > 0) {
            $actions[] = [
                'type' => 'brief_action',
                'title' => 'Nudge no-response',
                'buttons' => [[
                    'label' => 'Nudge no-response',
                    'kind' => 'draft',
                    'command' => 'draft follow-up message for candidates who have not responded',
                ]],
            ];
        }
        if ($staleJob) {
            $actions[] = [
                'type' => 'brief_action',
                'title' => 'Repost job',
                'meta' => (string) ($staleJob['title'] ?? 'Job'),
                'buttons' => [[
                    'label' => 'Repost job',
                    'kind' => 'link',
                    'url' => base_url('recruiter/jobs/view/' . (int) ($staleJob['id'] ?? 0)),
                ]],
            ];
        }

        return [
            'answer' => implode("\n", $lines),
            'data_summary' => ['action' => 'morning_brief'],
            'actions' => $actions,
        ];
    }
}
