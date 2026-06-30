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
     * @return array{answer: string, data_summary: array}
     */
    public function answer(int $recruiterId, string $question): array
    {
        $question = trim($question);
        if ($question === '') {
            return [
                'answer' => 'Please type a question so I can help you.',
                'data_summary' => [],
            ];
        }

        $actionResult = $this->handleActionRequest($recruiterId, $question);
        if ($actionResult !== null) {
            return $actionResult;
        }

        if ($this->apiKey === '') {
            return [
                'answer' => 'The AI answer mode is not available because the OpenAI API key is not configured. I can still help with action requests like "draft job description for PHP Developer", "screening questions for job 12", "shortlist candidates for job 12", "suggest slots for job 12", or "export candidates".',
                'data_summary' => [],
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

        if ($answer === null) {
            $answer = 'I ran into a problem fetching the answer. Please try again in a moment.';
        }

        return [
            'answer' => $answer,
            'data_summary' => $retrievedData,
        ];
    }

    // ─── Intent classification ────────────────────────────────

    private function handleActionRequest(int $recruiterId, string $question): ?array
    {
        if (preg_match('/\b(shortlist|reject)\b.*\bapplications?\s*#?\s*\d+/i', $question)) {
            return $this->actionResult('update_application_status', $this->updateApplicationsFromPrompt($recruiterId, $question));
        }

        if (preg_match('/\b(send|message)\b.*\bcandidate\s*#?\s*\d+\b/i', $question)) {
            return $this->actionResult('send_candidate_message', $this->sendCandidateMessageFromPrompt($recruiterId, $question));
        }

        if (preg_match('/\b(reject|rejection|shortlist|shortlisted|outreach|follow[- ]?up|invite)\b.*\b(email|message|template|draft)\b/i', $question)
            || preg_match('/\b(draft|write|generate)\b.*\b(email|message)\b/i', $question)) {
            return $this->actionResult('draft_message', $this->draftRecruiterMessage($recruiterId, $question));
        }

        if (preg_match('/\b(screening|screen|interview)\b.*\b(questions?|questionnaire)\b/i', $question)) {
            return $this->actionResult('screening_questions', $this->generateScreeningQuestions($recruiterId, $question));
        }

        if (preg_match('/\b(draft|write|create|generate|improve|post)\b.*\b(job description|jd|job post|job posting|job)\b/i', $question)
            || preg_match('/\bjob description\b/i', $question)
            || preg_match('/\bpost\s+(?:a\s+)?job\s+(?:for|as|role|position)\b/i', $question)) {
            return $this->actionResult('draft_job_description', $this->draftJobDescription($recruiterId, $question));
        }

        if (preg_match('/\b(shortlist|filter|find|show|rank)\b.*\b(candidates?|applicants?)\b/i', $question)) {
            return $this->actionResult('filter_candidates', $this->suggestCandidates($recruiterId, $question));
        }

        if (preg_match('/\b(suggest|show|find|available)\b.*\b(slots?|interviews?|schedule)\b/i', $question)) {
            return $this->actionResult('suggest_interview_slots', $this->suggestInterviewSlots($recruiterId, $question));
        }

        if (preg_match('/\b(export|download)\b.*\b(candidates?|applications?|jobs?|data|excel|csv|leaderboard|overview|funnel)\b/i', $question)) {
            return $this->actionResult('export_data', $this->buildExportAnswer($recruiterId, $question));
        }

        return null;
    }

    private function actionResult(string $action, string $answer): array
    {
        return [
            'answer' => $answer,
            'data_summary' => ['action' => $action],
        ];
    }

    private function draftJobDescription(int $recruiterId, string $question): string
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
            'Here is a recruiter-ready job description draft:',
            '',
            $title,
            ($company !== '' ? $company . ' | ' : '') . $location . ' | ' . $employmentType,
            '',
            'About the role',
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
        $lines[] = '';
        $lines[] = 'Next step: open Post a Job, paste this draft, then add the required category, location, openings, salary, deadline, and must-have experience before publishing.';

        return implode("\n", $lines);
    }

    private function generateScreeningQuestions(int $recruiterId, string $question): string
    {
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        $title = (string) ($job['title'] ?? $this->extractRoleTitle($question) ?: 'this role');
        $skills = $this->normalizeList((string) ($job['required_skills'] ?? $this->extractSkillsText($question)));

        $lines = [
            'Screening questions for ' . $title . ':',
            '',
            '1. Tell us about your most relevant experience for this role.',
            '2. Which tools, technologies, or workflows from the job requirements have you used recently?',
            '3. Describe a project where you solved a difficult problem with limited guidance.',
            '4. What quality checks do you use before calling your work complete?',
            '5. What is your expected notice period and availability for interviews?',
        ];

        $index = 6;
        foreach (array_slice($skills, 0, 5) as $skill) {
            $lines[] = $index . '. Rate your hands-on experience with ' . $skill . ' and share one example.';
            $index++;
        }

        $lines[] = '';
        $lines[] = 'Suggested scoring: 0-2 weak, 3 acceptable, 4 strong, 5 excellent. Use the same rubric for every applicant.';

        return implode("\n", $lines);
    }

    private function suggestCandidates(int $recruiterId, string $question): string
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

            return 'I could not find matching candidates' . (!empty($filters) ? ' for ' . implode(', ', $filters) : ' with those filters') . '. Try lowering the ATS threshold, removing the skill filter, or checking the job pipeline for applications.';
        }

        $lines = ['Here are the strongest candidates I found' . ($jobId > 0 ? ' for job #' . $jobId . (!empty($job['title']) ? ' (' . $job['title'] . ')' : '') : '') . ':'];
        foreach (array_slice($candidates, 0, 8) as $row) {
            $lines[] = sprintf(
                '- App #%d | Candidate #%d: %s | %s | Status: %s | Match: %d%% | Skills: %s',
                (int) $row['application_id'],
                (int) $row['candidate_id'],
                $row['candidate_name'],
                $row['job_title'],
                $row['status'],
                (int) $row['match_score'],
                $row['candidate_skills'] !== '' ? $row['candidate_skills'] : 'N/A'
            );
        }
        $lines[] = '';
        $lines[] = 'I did not change any statuses. Use the application IDs above for manual review in the job pipeline.';

        return implode("\n", $lines);
    }

    private function suggestInterviewSlots(int $recruiterId, string $question): string
    {
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
        $jobId = (int) ($job['id'] ?? $this->extractNumberAfter($question, 'job'));
        $db = \Config\Database::connect();

        $builder = $db->table('interview_slots s')
            ->select('s.id, s.job_id, s.slot_date, s.slot_time, s.capacity, s.booked_count, j.title as job_title')
            ->join('jobs j', 'j.id = s.job_id', 'left')
            ->where('s.created_by', $recruiterId)
            ->where('s.is_available', 1)
            ->where('s.slot_datetime >', date('Y-m-d H:i:s'))
            ->where('s.booked_count < s.capacity')
            ->orderBy('s.slot_datetime', 'ASC')
            ->limit(8);

        if ($jobId > 0) {
            $builder->where('s.job_id', $jobId);
        }

        $rows = $builder->get()->getResultArray();
        if (empty($rows)) {
            return 'I found no available upcoming slots' . ($jobId > 0 ? ' for job #' . $jobId : '') . '. Create more slots from Interview Slots > Create Slot.';
        }

        $lines = ['Available interview slot suggestions:'];
        foreach ($rows as $slot) {
            $lines[] = sprintf(
                '- Slot #%d | %s | %s %s | %d/%d booked',
                (int) $slot['id'],
                $slot['job_title'] ?: ('Job #' . (int) $slot['job_id']),
                $slot['slot_date'],
                $slot['slot_time'],
                (int) $slot['booked_count'],
                (int) $slot['capacity']
            );
        }
        $lines[] = '';
        $lines[] = 'I only suggested slots. Booking or rescheduling still needs recruiter confirmation in the slot workflow.';

        return implode("\n", $lines);
    }

    private function draftRecruiterMessage(int $recruiterId, string $question): string
    {
        $job = $this->resolveJobFromQuestion($recruiterId, $question);
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

        return "Here is a draft {$type} message:\n\nSubject: Update on {$title}\n\n{$body}\n\nTo send from chat, use: send message to candidate #ID: your message";
    }

    private function sendCandidateMessageFromPrompt(int $recruiterId, string $question): string
    {
        if (!preg_match('/candidate\s*#?\s*(\d+)\s*:?\s*(.+)$/is', $question, $matches)) {
            return 'To send a message, use this exact format: send message to candidate #123: Your message text';
        }

        $candidateId = (int) $matches[1];
        $message = trim((string) $matches[2]);
        $message = preg_replace('/^(send|message)\b.*?\b(candidate\s*#?\s*\d+)\s*:?\s*/i', '', $message) ?? $message;
        $message = trim($message);

        if ($message === '' || mb_strlen($message) > 1000) {
            return 'Message was not sent. Please provide 1 to 1000 characters after the candidate ID.';
        }

        $context = $this->findRecruiterCandidateContext($recruiterId, $candidateId);
        if (!$context) {
            return 'Message was not sent because candidate #' . $candidateId . ' is not linked to one of your applications.';
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

        if ($db->tableExists('notifications')) {
            (new \App\Models\NotificationModel())->createNotification(
                $candidateId,
                (int) ($context['application_id'] ?? 0) ?: null,
                'recruiter_message',
                'You received a message from a recruiter.',
                base_url('candidate/messages/' . $recruiterId)
            );
        }

        return 'Message sent to ' . ($context['candidate_name'] ?? ('candidate #' . $candidateId)) . '.';
    }

    private function buildExportAnswer(int $recruiterId, string $question): string
    {
        $lower = strtolower($question);
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

        $labels = [
            'overview' => 'overview report',
            'detailed' => 'candidate/application report',
            'leaderboard' => 'candidate leaderboard report',
            'funnel' => 'recruitment funnel report',
        ];

        $lines = [
            'Your ' . ($labels[$type] ?? 'export') . ' is ready here:',
            $url,
        ];

        if (!$job && $type === 'detailed') {
            $lines[] = '';
            $lines[] = 'This exports candidate/application data across your jobs. For one job only, ask like: export candidates for job 12.';
        }

        return implode("\n", $lines);
    }

    private function updateApplicationsFromPrompt(int $recruiterId, string $question): string
    {
        $status = preg_match('/\breject\b/i', $question) ? 'rejected' : 'shortlisted';
        preg_match_all('/applications?\s*#?\s*(\d+)/i', $question, $matches);
        $ids = array_values(array_unique(array_map('intval', $matches[1] ?? [])));
        if (empty($ids)) {
            return 'No application ID found. Use: shortlist application #123 or reject application #123.';
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
            . "You help recruiters understand their hiring data.\n\n"
            . "Recruiter: {$recruiterName}\n"
            . "Company: {$companyName}\n\n"
            . "You have access to the recruiter's data through RAG retrieval. "
            . "The data provided in each query is real-time and specific to this recruiter.\n\n"
            . "Guidelines:\n"
            . "- Be concise and conversational. Use natural language.\n"
            . "- Answer only based on the data provided. Do not invent numbers.\n"
            . "- If the data is insufficient, say 'I don't have enough data to answer that yet.'\n"
            . "- Use bullet points or short paragraphs for readability.\n"
            . "- If the question is about taking action (e.g. 'post a job', 'send message'), "
            . "explain that you can only provide information and suggest they use the dashboard.\n"
            . "- Format numbers with commas for readability.\n"
            . "- Use emojis sparingly to make responses friendly.\n"
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
            ->select('a.id as application_id, a.job_id, u.name as candidate_name')
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
}
