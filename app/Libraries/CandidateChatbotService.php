<?php

namespace App\Libraries;

/**
 * CandidateChatbotService — RAG-style assistant for candidates.
 *
 * It retrieves profile, applications, saved jobs, and interview data from the
 * portal so the AI can answer questions in a context-aware way.
 */
class CandidateChatbotService
{
    private string $apiKey;
    private string $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = (string) (getenv('OPENAI_API_KEY') ?: '');
    }

    public function answer(int $candidateId, string $question): array
    {
        $question = trim($question);
        if ($question === '') {
            return [
                'answer' => 'Please type a question so I can help you.',
                'data_summary' => [],
            ];
        }

        if ($this->apiKey === '') {
            $retrievedData = $this->retrieveData($candidateId, $this->classifyIntent($question), $question);
            $answer = $this->buildFallbackAnswer($question, $retrievedData);

            return [
                'answer' => $answer,
                'data_summary' => [],
            ];
        }

        $intent = $this->classifyIntent($question);
        $retrievedData = $this->retrieveData($candidateId, $intent, $question);

        $systemPrompt = $this->buildSystemPrompt();
        $dataContext = $this->formatRetrievedData($retrievedData);
        $userMessage = "Candidate question: {$question}\n\n"
            . "Here is the relevant data from the candidate's account:\n"
            . "{$dataContext}\n\n"
            . "Please answer in a helpful, conversational way and be honest when the portal data is incomplete."
            . "Keep the answer concise and action-oriented.";

        $answer = $this->callOpenAi($systemPrompt, $userMessage);
        if ($answer === null) {
            $answer = $this->buildFallbackAnswer($question, $retrievedData);
        }

        return [
            'answer' => $answer,
            'data_summary' => $retrievedData,
        ];
    }

    private function classifyIntent(string $question): string
    {
        $q = strtolower($question);

        if (preg_match('/\b(jobs?|roles?|openings?|vacancies?|opportunities?)\b/', $q)) {
            return 'jobs';
        }

        if (preg_match('/\b(applications?|applied|status|submitted)\b/', $q)) {
            return 'applications';
        }

        if (preg_match('/\b(saved|bookmark|bookmarked)\b/', $q)) {
            return 'saved';
        }

        if (preg_match('/\b(interview|booking|booked|schedule|scheduled)\b/', $q)) {
            return 'interviews';
        }

        if (preg_match('/\b(profile|resume|skills|experience|strength|career|goal)\b/', $q)) {
            return 'profile';
        }

        if (preg_match('/\b(summary|overview|dashboard|how many|stats?|count)\b/', $q)) {
            return 'summary';
        }

        return 'general';
    }

    private function retrieveData(int $candidateId, string $intent, string $question): array
    {
        $data = [];
        switch ($intent) {
            case 'jobs':
                $data['open_jobs'] = $this->fetchOpenJobs($candidateId, $question);
                $data['saved_jobs'] = $this->fetchSavedJobs($candidateId);
                break;

            case 'applications':
                $data['applications_summary'] = $this->fetchApplicationsSummary($candidateId);
                $data['recent_applications'] = $this->fetchRecentApplications($candidateId);
                break;

            case 'saved':
                $data['saved_jobs'] = $this->fetchSavedJobs($candidateId);
                break;

            case 'interviews':
                $data['upcoming_interviews'] = $this->fetchUpcomingInterviews($candidateId);
                break;

            case 'profile':
                $data['profile'] = $this->fetchProfileSummary($candidateId);
                $data['skills'] = $this->fetchSkills($candidateId);
                break;

            case 'summary':
                $data['profile'] = $this->fetchProfileSummary($candidateId);
                $data['applications_summary'] = $this->fetchApplicationsSummary($candidateId);
                $data['saved_jobs'] = $this->fetchSavedJobs($candidateId);
                $data['upcoming_interviews'] = $this->fetchUpcomingInterviews($candidateId);
                $data['open_jobs'] = $this->fetchOpenJobs($candidateId, '');
                break;

            default:
                $data['profile'] = $this->fetchProfileSummary($candidateId);
                $data['applications_summary'] = $this->fetchApplicationsSummary($candidateId);
                $data['saved_jobs'] = $this->fetchSavedJobs($candidateId);
                $data['upcoming_interviews'] = $this->fetchUpcomingInterviews($candidateId);
                $data['open_jobs'] = $this->fetchOpenJobs($candidateId, '');
                break;
        }

        return $data;
    }

    private function fetchProfileSummary(int $candidateId): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('users') || !$db->tableExists('candidate_profiles')) {
            return [];
        }

        $builder = $db->table('users u')
            ->select('u.id, u.name, u.email, cp.headline, cp.location, cp.preferred_job_titles, cp.preferred_locations, cp.preferred_employment_type')
            ->join('candidate_profiles cp', 'cp.user_id = u.id', 'left')
            ->where('u.id', $candidateId)
            ->limit(1);

        $row = $builder->get()->getRowArray();
        return $row ?: [];
    }

    private function fetchSkills(int $candidateId): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('candidate_skills')) {
            return [];
        }

        $result = $db->table('candidate_skills')
            ->select('skill_name')
            ->where('candidate_id', $candidateId)
            ->orderBy('skill_name', 'ASC')
            ->get();

        return $result !== false ? $result->getResultArray() : [];
    }

    private function fetchApplicationsSummary(int $candidateId): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('applications')) {
            return ['total' => 0, 'by_status' => []];
        }

        $total = $db->table('applications')->where('candidate_id', $candidateId)->countAllResults();
        $byStatus = $db->table('applications')
            ->select('status, COUNT(*) as count')
            ->where('candidate_id', $candidateId)
            ->groupBy('status')
            ->get()
            ->getResultArray();

        return [
            'total' => (int) $total,
            'by_status' => $byStatus,
        ];
    }

    private function fetchRecentApplications(int $candidateId, int $limit = 5): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('applications') || !$db->tableExists('jobs')) {
            return [];
        }

        $result = $db->query(
            "SELECT a.id, a.status, a.applied_at, j.title as job_title, j.company as company_name
             FROM applications a
             JOIN jobs j ON j.id = a.job_id
             WHERE a.candidate_id = ?
             ORDER BY a.applied_at DESC
             LIMIT ?",
            [$candidateId, $limit]
        );

        return $result !== false ? $result->getResultArray() : [];
    }

    private function fetchSavedJobs(int $candidateId): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('saved_jobs') || !$db->tableExists('jobs')) {
            return [];
        }

        $result = $db->query(
            "SELECT sj.id, sj.created_at, j.id as job_id, j.title, j.company, j.location, j.status
             FROM saved_jobs sj
             JOIN jobs j ON j.id = sj.job_id
             WHERE sj.candidate_id = ?
             ORDER BY sj.created_at DESC
             LIMIT 10",
            [$candidateId]
        );

        return $result !== false ? $result->getResultArray() : [];
    }

    private function fetchOpenJobs(int $candidateId, string $keyword = ''): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('jobs')) {
            return [];
        }

        $builder = $db->table('jobs')
            ->select('id, title, company, location, experience_level, employment_type, status, created_at')
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC')
            ->limit(10);

        if ($keyword !== '' && preg_match('/\b(saved|bookmark|bookmarking)\b/', strtolower($keyword))) {
            $builder->where('status', 'open');
        }

        $result = $builder->get();
        return $result !== false ? $result->getResultArray() : [];
    }

    private function fetchUpcomingInterviews(int $candidateId): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('interview_bookings') || !$db->tableExists('jobs')) {
            return [];
        }

        $result = $db->query(
            "SELECT ib.id, ib.slot_datetime, ib.booking_status, j.title as job_title, j.company as company_name
             FROM interview_bookings ib
             JOIN jobs j ON j.id = ib.job_id
             WHERE ib.user_id = ?
             AND ib.booking_status IN ('booked', 'confirmed', 'rescheduled')
             AND ib.slot_datetime >= NOW()
             ORDER BY ib.slot_datetime ASC
             LIMIT 5",
            [$candidateId]
        );

        return $result !== false ? $result->getResultArray() : [];
    }

    private function buildSystemPrompt(): string
    {
        return "You are HireMate, a helpful AI assistant for candidates on a job portal. "
            . "Use the candidate's portal data provided in the prompt to answer questions clearly. "
            . "Prefer concise, practical answers. If the data does not contain enough information, say so plainly."
            . "Do not invent facts or applications that are not in the data.";
    }

    private function formatRetrievedData(array $data): string
    {
        $lines = [];
        if (!empty($data['profile'])) {
            $profile = $data['profile'];
            $lines[] = 'Profile: ' . ($profile['name'] ?? 'Candidate') . ' | Headline: ' . ($profile['headline'] ?? 'Not set') . ' | Location: ' . ($profile['location'] ?? 'Not set');
        }

        if (!empty($data['applications_summary'])) {
            $summary = $data['applications_summary'];
            $lines[] = 'Applications total: ' . ($summary['total'] ?? 0) . '; statuses: ' . $this->formatStatuses($summary['by_status'] ?? []);
        }

        if (!empty($data['saved_jobs'])) {
            $jobs = array_slice($data['saved_jobs'], 0, 5);
            $lines[] = 'Saved jobs: ' . implode(', ', array_map(fn($job) => $job['title'] ?? 'Untitled', $jobs));
        }

        if (!empty($data['upcoming_interviews'])) {
            $bookings = array_slice($data['upcoming_interviews'], 0, 3);
            $lines[] = 'Upcoming interviews: ' . implode(', ', array_map(fn($row) => ($row['job_title'] ?? 'Role') . ' at ' . ($row['slot_datetime'] ?? ''), $bookings));
        }

        if (!empty($data['open_jobs'])) {
            $jobs = array_slice($data['open_jobs'], 0, 5);
            $lines[] = 'Open jobs: ' . implode(', ', array_map(fn($job) => $job['title'] ?? 'Untitled', $jobs));
        }

        if ($lines === []) {
            return 'No candidate portal data is available yet.';
        }

        return implode("\n", $lines);
    }

    private function formatStatuses(array $statuses): string
    {
        if ($statuses === []) {
            return 'none';
        }

        return implode(', ', array_map(fn($row) => ($row['status'] ?? 'unknown') . ':' . ($row['count'] ?? 0), $statuses));
    }

    private function buildFallbackAnswer(string $question, array $data): string
    {
        $questionLower = strtolower($question);

        if (preg_match('/\b(job|jobs|role|roles|opening|vacancies|opportunities)\b/', $questionLower)) {
            $jobs = $data['open_jobs'] ?? [];
            if ($jobs !== []) {
                $sample = array_map(fn($job) => $job['title'] ?? 'Untitled', array_slice($jobs, 0, 3));
                return 'I found a few open opportunities you may want to review: ' . implode(', ', $sample) . '. You can open them from the jobs section to apply.';
            }
            return 'There are no open roles available right now, but you can keep checking the jobs board for new postings.';
        }

        if (preg_match('/\b(application|applications|applied|status)\b/', $questionLower)) {
            $summary = $data['applications_summary'] ?? [];
            $total = (int) ($summary['total'] ?? 0);
            return 'You currently have ' . $total . ' application(s) in your account. I can help you review their status or suggest the next step.';
        }

        if (preg_match('/\b(saved|bookmark|bookmarked)\b/', $questionLower)) {
            $saved = $data['saved_jobs'] ?? [];
            if ($saved !== []) {
                $titles = array_map(fn($job) => $job['title'] ?? 'Untitled', array_slice($saved, 0, 3));
                return 'You have saved jobs such as ' . implode(', ', $titles) . '. You can revisit them from your saved jobs list.';
            }
            return 'You do not currently have any saved jobs. Save roles that interest you to keep them handy.';
        }

        if (preg_match('/\b(interview|booking|booked|schedule)\b/', $questionLower)) {
            $bookings = $data['upcoming_interviews'] ?? [];
            if ($bookings !== []) {
                return 'You have upcoming interview booking(s) in your account. I can help you review them or prepare for the next step.';
            }
            return 'You do not have any upcoming interview bookings right now.';
        }

        if (preg_match('/\b(profile|resume|skill|skills|experience|career)\b/', $questionLower)) {
            return 'Your profile and skills are available in your account. Updating your resume and skills can improve your match rate with relevant jobs.';
        }

        return 'I can help you review your applications, saved jobs, profile, and interview bookings. Try asking about a specific role or your application status.';
    }

    private function callOpenAi(string $systemPrompt, string $userMessage): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $payload = [
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => 0.3,
        ];

        $ch = curl_init($this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            return null;
        }

        if ($httpCode >= 400) {
            return null;
        }

        $decoded = json_decode($response, true);
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        return is_string($content) ? trim($content) : null;
    }
}
