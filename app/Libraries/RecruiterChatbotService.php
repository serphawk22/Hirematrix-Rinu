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
        if ($this->apiKey === '') {
            return [
                'answer' => 'The chatbot is not available right now — OpenAI API key is not configured. Please ask your system administrator to set it up.',
                'data_summary' => [],
            ];
        }

        $question = trim($question);
        if ($question === '') {
            return [
                'answer' => 'Please type a question so I can help you.',
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
