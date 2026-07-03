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

        $actionResult = $this->handleActionRequest($candidateId, $question);
        if ($actionResult !== null) {
            return $actionResult;
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

    private function handleActionRequest(int $candidateId, string $question): ?array
    {
        $jobIdPattern = '\bjobs?\s*(?:#|id|number|no\.?|to)?\s*\d+\b';

        if (preg_match('/\b(compare|difference|versus|vs)\b.*\bjobs?\b/i', $question) || preg_match('/' . $jobIdPattern . '.*\b(vs|versus|and)\b.*' . $jobIdPattern . '/i', $question)) {
            return $this->actionResult('compare_jobs', $this->compareJobs($candidateId, $question));
        }

        if (preg_match('/\b(save|bookmark)\b.*' . $jobIdPattern . '/i', $question)
            || preg_match('/\b(save|bookmark)\b.*\bjobs?\s*#\s*[a-z0-9+#.][a-z0-9+#. -]{1,80}\b/i', $question)) {
            return $this->actionResult('save_job', $this->saveJobFromPrompt($candidateId, $question));
        }

        if (preg_match('/\b(save|bookmark)\b.*\b(jobs?|roles?|openings?|opportunities?)\b/i', $question)) {
            return $this->actionResult('find_jobs_to_save', $this->findJobsForCandidate($candidateId, $question, true));
        }

        if (preg_match('/\b(apply|submit application)\b.*' . $jobIdPattern . '/i', $question)
            || preg_match('/\b(apply|submit application)\b.*\bjobs?\s*#?\s*[a-z0-9+#.][a-z0-9+#. -]{1,80}\b/i', $question)) {
            return $this->actionResult('apply_job', $this->applyToJobFromPrompt($candidateId, $question));
        }

        if (preg_match('/\b(why|explain|match|fit|suitable|good fit|bad fit)\b.*' . $jobIdPattern . '/i', $question)
            || preg_match('/\b(why|explain|match|fit|suitable|good fit|bad fit)\b.*\bjobs?\s*#?\s*[a-z0-9+#.][a-z0-9+#. -]{1,80}\b/i', $question)) {
            return $this->actionResult('explain_job_match', $this->explainJobMatch($candidateId, $question));
        }

        if (preg_match('/\b(jobs?|roles?|openings?|opportunities?)\s+(?:opening\s+)?(details?|info|information|description)\b/i', $question)
            || preg_match('/\b(details?|info|information|description)\b.*\b(job|role|opening|opportunity)\b/i', $question)) {
            return $this->actionResult('job_details', $this->showJobDetails($candidateId, $question));
        }

        if (preg_match('/' . $jobIdPattern . '/i', $question)) {
            return $this->actionResult('job_details', $this->showJobDetails($candidateId, $question));
        }

        if (preg_match('/\b(find|show|search|recommend(?:ations?)?|matching|matched|suggest(?:ions?)?)\b.*\b(jobs?|roles?|openings?|opportunities?)\b/i', $question)
            || preg_match('/\b(jobs?|roles?|openings?|opportunities?)\b.*\b(suggest(?:ions?)?|recommend(?:ations?)?)\b/i', $question)
            || preg_match('/\b(remote|hybrid|onsite|salary|skills?|location)\b.*\b(jobs?|roles?|openings?)\b/i', $question)) {
            return $this->actionResult('find_jobs', $this->findJobsForCandidate($candidateId, $question));
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

    private function findJobsForCandidate(int $candidateId, string $question, bool $forSaveRequest = false): string
    {
        $filters = $this->extractJobFilters($question);
        $jobs = $this->fetchRecommendedJobs($candidateId, $filters, 10);

        if (empty($jobs)) {
            return 'I could not find open jobs matching those filters. Try a broader search like "find matching jobs" or remove location, salary, or skill filters.';
        }

        $lines = [$forSaveRequest ? 'I found these matching jobs. Choose one to save by job ID:' : 'Here are matching open jobs I found:'];
        foreach (array_slice($jobs, 0, 8) as $job) {
            $score = (int) round((float) ($job['match_score'] ?? 0));
            $parts = [
                'Job #' . (int) $job['id'],
                (string) ($job['title'] ?? 'Untitled role'),
                (string) ($job['company'] ?? 'Company not listed'),
                (string) ($job['location'] ?? 'Location not listed'),
            ];
            if (!empty($job['employment_type'])) {
                $parts[] = (string) $job['employment_type'];
            }
            if (!empty($job['salary_range'])) {
                $parts[] = 'Salary: ' . (string) $job['salary_range'];
            }
            if ($score > 0) {
                $parts[] = 'Match: ' . $score . '%';
            }
            $lines[] = '- ' . implode(' | ', $parts);
        }

        $lines[] = '';
        $lines[] = $forSaveRequest
            ? 'To save one, ask: save job #ID or #Name. I will not save multiple jobs automatically without the exact IDs.'
            : 'You can ask: "save job #ID or #Name", "apply to job #ID or #Name", "compare job #ID or #Name and job #ID or #Name", or "explain why job #ID or #Name matches me".';

        return implode("\n", $lines);
    }

    private function showJobDetails(int $candidateId, string $question): string
    {
        $jobId = $this->extractFirstJobId($question);
        if ($jobId > 0) {
            $job = $this->findOpenJob($jobId);
            if (!$job) {
                return 'I could not find an open job with ID #' . $jobId . '.';
            }

            return $this->formatJobDetails($candidateId, $job);
        }

        $filters = $this->extractJobFilters($question);
        $exactTitleJobs = $this->findJobsByTitle($candidateId, (string) ($filters['title_query'] ?: $filters['keyword']), 3);
        if (!empty($exactTitleJobs)) {
            if (count($exactTitleJobs) === 1) {
                return $this->formatJobDetails($candidateId, $exactTitleJobs[0]);
            }

            $lines = ['I found multiple matching portal jobs. Ask for details with the job ID:'];
            foreach ($exactTitleJobs as $job) {
                $lines[] = sprintf(
                    '- Job #%d | %s | %s | %s | Match: %d%%',
                    (int) $job['id'],
                    $job['title'] ?? 'Untitled role',
                    $job['company'] ?? 'Company not listed',
                    $job['location'] ?? 'Location not listed',
                    (int) round((float) ($job['match_score'] ?? 0))
                );
            }

            return implode("\n", $lines);
        }

        $jobs = $this->fetchRecommendedJobs($candidateId, $filters, 3);
        if (empty($jobs)) {
            return 'I could not find an open job matching that title. Try asking "find PHP Developer jobs" or include a job ID like "job #12 details".';
        }

        if (count($jobs) === 1) {
            return $this->formatJobDetails($candidateId, $jobs[0]);
        }

        $lines = ['I found multiple matching jobs. Ask for details with the job ID:'];
        foreach ($jobs as $job) {
            $lines[] = sprintf(
                '- Job #%d | %s | %s | %s | Match: %d%%',
                (int) $job['id'],
                $job['title'] ?? 'Untitled role',
                $job['company'] ?? 'Company not listed',
                $job['location'] ?? 'Location not listed',
                (int) round((float) ($job['match_score'] ?? 0))
            );
        }

        return implode("\n", $lines);
    }

    private function formatJobDetails(int $candidateId, array $job): string
    {
        $match = $this->buildJobMatchBreakdown($candidateId, $job);
        $description = trim(strip_tags((string) ($job['description'] ?? '')));
        if (strlen($description) > 220) {
            $description = substr($description, 0, 220) . '...';
        }

        $lines = [
            'Job #' . (int) $job['id'] . ' - ' . ($job['title'] ?? 'Untitled role'),
            'Company: ' . (($job['company'] ?? '') !== '' ? $job['company'] : 'Not listed'),
            'Location: ' . (($job['location'] ?? '') !== '' ? $job['location'] : 'Not listed'),
            'Type: ' . (($job['employment_type'] ?? '') !== '' ? $job['employment_type'] : 'Not listed'),
            'Experience: ' . (($job['experience_level'] ?? '') !== '' ? $job['experience_level'] : 'Not listed'),
        ];

        if (!empty($job['salary_range'])) {
            $lines[] = 'Salary: ' . $job['salary_range'];
        }

        $lines[] = 'Match: ' . (int) $match['score'] . '%';
        $lines[] = 'Required skills: ' . (!empty($job['required_skills']) ? $job['required_skills'] : 'Not listed');

        if ($description !== '') {
            $lines[] = '';
            $lines[] = 'Summary: ' . $description;
        }

        $lines[] = '';
        $lines[] = 'Next actions: save job #' . (int) $job['id'] . ', apply to job #' . (int) $job['id'] . ', or explain why job #' . (int) $job['id'] . ' matches me.';

        return implode("\n", $lines);
    }

    private function saveJobFromPrompt(int $candidateId, string $question): string
    {
        $jobId = $this->extractFirstJobId($question);
        if ($jobId <= 0) {
            $resolved = $this->resolveSingleOpenJobFromPrompt($candidateId, $question);
            if (!empty($resolved['message'])) {
                return (string) $resolved['message'];
            }
            $jobId = (int) ($resolved['job']['id'] ?? 0);
        }

        $job = $this->findOpenJob($jobId);
        if (!$job) {
            return 'I could not find an open job with ID #' . $jobId . '.';
        }

        $savedJobModel = new \App\Models\SavedJobModel();
        $existing = $savedJobModel
            ->where('candidate_id', $candidateId)
            ->where('job_id', $jobId)
            ->first();

        if (!$existing) {
            $savedJobModel->insert([
                'candidate_id' => $candidateId,
                'job_id' => $jobId,
            ]);
        }

        return ($existing ? 'This job was already saved: ' : 'Saved this job: ')
            . '#' . $jobId . ' ' . ($job['title'] ?? 'Untitled role') . '.';
    }

    private function applyToJobFromPrompt(int $candidateId, string $question): string
    {
        $jobId = $this->extractFirstJobId($question);
        if ($jobId <= 0) {
            $resolved = $this->resolveSingleOpenJobFromPrompt($candidateId, $question);
            if (!empty($resolved['message'])) {
                return (string) $resolved['message'];
            }
            $jobId = (int) ($resolved['job']['id'] ?? 0);
        }

        $job = $this->findOpenJob($jobId);
        if (!$job) {
            return 'I could not find an open job with ID #' . $jobId . '.';
        }

        if (!empty($job['application_deadline']) && strtotime($job['application_deadline'] . ' 23:59:59') < time()) {
            return 'The application deadline for job #' . $jobId . ' has passed.';
        }

        if (\App\Models\JobModel::isExternalJob($job)) {
            $url = trim((string) ($job['external_apply_url'] ?? ''));
            return $url !== ''
                ? 'This job uses an external application flow. Apply here: ' . $url
                : 'This job uses an external application flow, but no valid apply link is available.';
        }

        $userModel = model('UserModel');
        $user = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId);
        if (empty($user['resume_path'])) {
            return 'Please upload your resume before applying. Open your profile here: ' . base_url('candidate/profile');
        }

        $applicationModel = new \App\Models\ApplicationModel();
        $alreadyApplied = $applicationModel
            ->where('job_id', $jobId)
            ->where('candidate_id', $candidateId)
            ->where('status !=', 'withdrawn')
            ->first();
        if ($alreadyApplied) {
            return 'You have already applied to job #' . $jobId . '. Current status: ' . ucwords(str_replace('_', ' ', (string) ($alreadyApplied['status'] ?? 'applied'))) . '.';
        }

        $questionnaire = trim((string) ($job['application_questionnaire'] ?? ''));
        if ($questionnaire !== '' && $questionnaire !== '[]' && $questionnaire !== '{}') {
            return 'This job has screening questions. Please apply from the job page so you can answer them: ' . base_url('job/' . $jobId);
        }

        $db = \Config\Database::connect();
        $payload = [
            'job_id' => $jobId,
            'candidate_id' => $candidateId,
            'status' => 'applied',
            'applied_at' => date('Y-m-d H:i:s'),
        ];

        if ($db->fieldExists('resume_version_id', 'applications') && $db->tableExists('candidate_resume_versions')) {
            $resumeVersion = (new \App\Models\CandidateResumeVersionModel())->getPreferredVersionForJob($candidateId, $jobId);
            $payload['resume_version_id'] = (int) ($resumeVersion['id'] ?? 0) > 0 ? (int) $resumeVersion['id'] : null;
        }

        if ($db->fieldExists('questionnaire_responses', 'applications')) {
            $payload['questionnaire_responses'] = null;
        }

        $applicationModel->insert($payload);
        $applicationId = (int) $applicationModel->getInsertID();
        if ($applicationId > 0 && $db->tableExists('stage_history')) {
            try {
                model('StageHistoryModel')->moveToStage($applicationId, 'Applied');
            } catch (\Throwable $e) {
                log_message('warning', 'Candidate chatbot stage history update failed: ' . $e->getMessage());
            }
        }

        return 'Application submitted for job #' . $jobId . ' ' . ($job['title'] ?? 'Untitled role') . '. Status: Applied.';
    }

    private function resolveSingleOpenJobFromPrompt(int $candidateId, string $question): array
    {
        $title = $this->extractJobReferenceText($question);
        if ($title === '') {
            return [
                'job' => null,
                'message' => 'Please include a job ID or title, for example: apply to job #12 or apply to job #PHP Developer.',
            ];
        }

        $jobs = $this->findJobsByTitle($candidateId, $title, 5);
        if (empty($jobs)) {
            return [
                'job' => null,
                'message' => 'I could not find an open job matching "' . $title . '". Try the exact job title or use the job ID.',
            ];
        }

        if (count($jobs) === 1) {
            return [
                'job' => $jobs[0],
                'message' => '',
            ];
        }

        $exactMatches = array_values(array_filter($jobs, static function (array $job) use ($title): bool {
            return strtolower(trim((string) ($job['title'] ?? ''))) === strtolower(trim($title));
        }));

        if (count($exactMatches) === 1) {
            return [
                'job' => $exactMatches[0],
                'message' => '',
            ];
        }

        $lines = ['I found multiple matching jobs. Please choose the exact job ID:'];
        foreach ($jobs as $job) {
            $lines[] = sprintf(
                '- Job #%d | %s | %s | %s',
                (int) $job['id'],
                $job['title'] ?? 'Untitled role',
                $job['company'] ?? 'Company not listed',
                $job['location'] ?? 'Location not listed'
            );
        }

        return [
            'job' => null,
            'message' => implode("\n", $lines),
        ];
    }

    private function resolveSingleOpenJobByTitle(int $candidateId, string $title): array
    {
        $title = $this->cleanJobReferenceText($title);
        if ($title === '') {
            return [
                'job' => null,
                'message' => 'Please include a job title or ID.',
            ];
        }

        $jobs = $this->findJobsByTitle($candidateId, $title, 5);
        if (empty($jobs)) {
            return [
                'job' => null,
                'message' => 'I could not find an open job matching "' . $title . '". Try the exact job title from the listing.',
            ];
        }

        if (count($jobs) === 1) {
            return [
                'job' => $jobs[0],
                'message' => '',
            ];
        }

        $exactMatches = array_values(array_filter($jobs, static function (array $job) use ($title): bool {
            return strtolower(trim((string) ($job['title'] ?? ''))) === strtolower(trim($title));
        }));

        if (count($exactMatches) === 1) {
            return [
                'job' => $exactMatches[0],
                'message' => '',
            ];
        }

        $lines = ['I found multiple jobs matching "' . $title . '". Please choose one by title or job ID:'];
        foreach ($jobs as $job) {
            $lines[] = sprintf(
                '- Job #%d | %s | %s | %s',
                (int) $job['id'],
                $job['title'] ?? 'Untitled role',
                $job['company'] ?? 'Company not listed',
                $job['location'] ?? 'Location not listed'
            );
        }

        return [
            'job' => null,
            'message' => implode("\n", $lines),
        ];
    }

    private function extractJobReferenceText(string $question): string
    {
        if (preg_match('/\bjobs?\s*#\s*([a-z0-9+#.][a-z0-9+#. -]{1,80})/i', $question, $matches)) {
            return $this->cleanJobReferenceText((string) $matches[1]);
        }

        if (preg_match('/\b(?:apply|save|bookmark|submit application)\b.*\bjobs?\s+(?:named|called|titled)?\s*["\']?([a-z0-9+#.][a-z0-9+#. -]{1,80})/i', $question, $matches)) {
            return $this->cleanJobReferenceText((string) $matches[1]);
        }

        return '';
    }

    private function cleanJobReferenceText(string $value): string
    {
        $value = trim($value, " \t\n\r\0\x0B.,:;!?\"'");
        $value = preg_replace('/\s+\b(?:please|now|today|for me|from chatbot)\b.*$/i', '', $value) ?? $value;
        $value = preg_replace('/\s+\b(?:job|role|opening|application)\b\s*$/i', '', $value) ?? $value;

        return trim($value, " \t\n\r\0\x0B.,:;!?\"'");
    }

    private function compareJobs(int $candidateId, string $question): string
    {
        $resolved = $this->resolveJobsForComparison($candidateId, $question);
        if (!empty($resolved['message'])) {
            return (string) $resolved['message'];
        }

        $jobs = array_slice((array) ($resolved['jobs'] ?? []), 0, 2);
        if (count($jobs) < 2) {
            return 'Please include two job titles or IDs, for example: compare job PHP Developer and job Data Analyst.';
        }

        foreach ($jobs as &$job) {
            $job['_match'] = $this->buildJobMatchBreakdown($candidateId, $job);
        }
        unset($job);

        $lines = ['Here is a quick comparison:'];
        foreach ($jobs as $job) {
            $match = $job['_match'];
            $lines[] = sprintf(
                '- Job #%d | %s | %s | %s | Match: %d%% | Matched skills: %s | Missing: %s',
                (int) $job['id'],
                $job['title'] ?? 'Untitled role',
                $job['company'] ?? 'Company not listed',
                $job['location'] ?? 'Location not listed',
                (int) $match['score'],
                !empty($match['matched_skills']) ? implode(', ', $match['matched_skills']) : 'None found',
                !empty($match['missing_skills']) ? implode(', ', array_slice($match['missing_skills'], 0, 5)) : 'None obvious'
            );
        }

        $winner = ((int) $jobs[0]['_match']['score'] >= (int) $jobs[1]['_match']['score']) ? $jobs[0] : $jobs[1];
        $lines[] = '';
        $lines[] = 'Best fit based on your profile right now: Job #' . (int) $winner['id'] . ' ' . ($winner['title'] ?? 'Untitled role') . '.';

        return implode("\n", $lines);
    }

    private function resolveJobsForComparison(int $candidateId, string $question): array
    {
        $ids = $this->extractJobIds($question);
        if (count($ids) >= 2) {
            $jobs = [];
            foreach (array_slice($ids, 0, 2) as $jobId) {
                $job = $this->findOpenJob($jobId);
                if ($job) {
                    $jobs[] = $job;
                }
            }

            if (count($jobs) < 2) {
                return [
                    'jobs' => [],
                    'message' => 'I could not find both open jobs. Please check the job IDs and try again.',
                ];
            }

            return ['jobs' => $jobs, 'message' => ''];
        }

        $titles = $this->extractComparisonJobTitles($question);
        if (count($titles) < 2) {
            return [
                'jobs' => [],
                'message' => 'Please include two job titles or IDs, for example: compare job PHP Developer and job Data Analyst.',
            ];
        }

        $jobs = [];
        foreach (array_slice($titles, 0, 2) as $title) {
            $resolved = $this->resolveSingleOpenJobByTitle($candidateId, $title);
            if (!empty($resolved['message'])) {
                return [
                    'jobs' => [],
                    'message' => (string) $resolved['message'],
                ];
            }
            $jobs[] = $resolved['job'];
        }

        return ['jobs' => $jobs, 'message' => ''];
    }

    private function extractComparisonJobTitles(string $question): array
    {
        $normalized = trim(preg_replace('/\s+/', ' ', $question) ?? $question);
        $normalized = preg_replace('/^\s*(?:compare|difference between|what(?:\'s| is)? the difference between)\s+/i', '', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bjob\s+data\b/i', 'job Data', $normalized) ?? $normalized;
        $normalized = preg_replace('/\bjobs?\s*#\s*/i', 'job ', $normalized) ?? $normalized;

        $parts = preg_split('/\s+(?:and|vs|versus)\s+/i', $normalized) ?: [];
        $titles = [];
        foreach ($parts as $part) {
            $part = preg_replace('/^\s*(?:jobs?|roles?|openings?)\s+/i', '', (string) $part) ?? (string) $part;
            $part = $this->cleanJobReferenceText($part);
            if ($part !== '' && !preg_match('/^\d+$/', $part)) {
                $titles[] = $part;
            }
        }

        return array_values(array_unique($titles));
    }

    private function explainJobMatch(int $candidateId, string $question): string
    {
        $jobId = $this->extractFirstJobId($question);
        if ($jobId <= 0) {
            $resolved = $this->resolveSingleOpenJobFromPrompt($candidateId, $question);
            if (!empty($resolved['message'])) {
                return (string) $resolved['message'];
            }
            $jobId = (int) ($resolved['job']['id'] ?? 0);
        }

        $job = $this->findOpenJob($jobId);
        if (!$job) {
            return 'I could not find an open job with ID #' . $jobId . '.';
        }

        $match = $this->buildJobMatchBreakdown($candidateId, $job);
        $lines = [
            'Job #' . $jobId . ' - ' . ($job['title'] ?? 'Untitled role'),
            'Overall match: ' . (int) $match['score'] . '%',
            '',
            'Why it matches',
        ];

        $lines[] = !empty($match['matched_skills'])
            ? '- Your profile has: ' . implode(', ', $match['matched_skills'])
            : '- I could not find direct skill overlap in your saved profile skills.';

        if ((int) $match['experience_score'] > 0) {
            $lines[] = '- Your experience appears relevant for the listed experience level.';
        }

        if ((int) $match['location_score'] > 0) {
            $lines[] = '- The location/remote preference looks compatible.';
        }

        if (!empty($match['missing_skills'])) {
            $lines[] = '';
            $lines[] = 'Possible gaps';
            $lines[] = '- Missing or not listed: ' . implode(', ', array_slice($match['missing_skills'], 0, 6));
        }

        $lines[] = '';
        $lines[] = 'Next actions: save job #' . $jobId . ' or apply to job #' . $jobId . '.';

        return implode("\n", $lines);
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

    private function extractJobFilters(string $question): array
    {
        $lower = strtolower($question);
        $filters = [
            'location' => '',
            'skills' => [],
            'work_mode' => '',
            'salary_min' => 0,
            'keyword' => '',
            'title_query' => '',
        ];

        if (preg_match('/\b(?:in|at|near)\s+([a-z ,.-]{2,40})/i', $question, $matches)) {
            $location = preg_replace('/\b(?:with|for|salary|skills?|remote|hybrid|onsite|jobs?|roles?)\b.*$/i', '', trim((string) $matches[1])) ?? '';
            $filters['location'] = trim($location, " \t\n\r\0\x0B.,");
        }

        if (preg_match('/\b(?:skills?|with|using)\s+([a-z0-9+#.,\/ -]{2,100})/i', $question, $matches)) {
            $value = preg_replace('/\b(?:in|at|near|salary|remote|hybrid|onsite|jobs?|roles?)\b.*$/i', '', trim((string) $matches[1])) ?? '';
            $filters['skills'] = $this->normalizeSkillTokens($value);
        }

        if (preg_match('/\b(remote|work from home|wfh)\b/i', $question)) {
            $filters['work_mode'] = 'remote';
        } elseif (preg_match('/\bhybrid\b/i', $question)) {
            $filters['work_mode'] = 'hybrid';
        } elseif (preg_match('/\b(onsite|on-site|office)\b/i', $question)) {
            $filters['work_mode'] = 'onsite';
        }

        if (preg_match('/\b(?:salary|pay|ctc)\D{0,12}(\d+(?:\.\d+)?)\s*(lpa|lakhs?|k|000)?/i', $question, $matches)) {
            $amount = (float) $matches[1];
            $unit = strtolower((string) ($matches[2] ?? ''));
            $filters['salary_min'] = str_contains($unit, 'k') ? (int) round($amount / 100) : (int) round($amount);
        }

        if (preg_match('/\b(?:for|as)\s+([a-z0-9+#. -]{3,60})/i', $question, $matches)) {
            $keyword = preg_replace('/\b(?:in|at|near|with|using|salary|remote|hybrid|onsite)\b.*$/i', '', trim((string) $matches[1])) ?? '';
            $filters['keyword'] = trim($keyword, " \t\n\r\0\x0B.,");
        }

        if (preg_match('/\b(?:save|bookmark)\b.*\b(?:for|as)\s+([a-z0-9+#. -]{3,70})/i', $question, $matches)) {
            $filters['keyword'] = trim((string) $matches[1], " \t\n\r\0\x0B.,");
            $filters['title_query'] = $filters['keyword'];
        }

        if (preg_match('/\b(?:suggest(?:ions?)?|recommend(?:ations?)?)\s+for\s+([a-z0-9+#. -]{3,70})\s+(?:jobs?|roles?|openings?|opportunities?)\b/i', $question, $matches)) {
            $filters['keyword'] = trim((string) $matches[1], " \t\n\r\0\x0B.,");
            $filters['title_query'] = $filters['keyword'];
        }

        if ($filters['title_query'] === '' && preg_match('/(?:give\s+me\s+|show\s+me\s+|find\s+|search\s+)?([a-z0-9+#. -]{3,70})\s+(?:jobs?|roles?|openings?|opportunities?)\s*(?:suggest(?:ions?)?|recommend(?:ations?)?)?\b/i', $question, $matches)) {
            $candidateTitle = trim((string) $matches[1], " \t\n\r\0\x0B.,");
            $candidateTitle = preg_replace('/\b(?:remote|hybrid|onsite|salary|location|matching|matched|available|open|latest|new)\b/i', '', $candidateTitle) ?? $candidateTitle;
            $candidateTitle = trim($candidateTitle, " \t\n\r\0\x0B.,");
            if ($candidateTitle !== '' && !preg_match('/\b(?:me|my|matching|available|open|saved)\b/i', $candidateTitle)) {
                $filters['keyword'] = $candidateTitle;
                $filters['title_query'] = $candidateTitle;
            }
        }

        if ($filters['keyword'] === '' && preg_match('/(?:give\s+me\s+|show\s+me\s+|find\s+)?([a-z0-9+#. -]{3,70})\s+(?:jobs?|roles?|openings?|opportunities?)\s+(?:opening\s+)?(?:details?|info|information|description)\b/i', $question, $matches)) {
            $filters['keyword'] = trim((string) $matches[1], " \t\n\r\0\x0B.,");
            $filters['title_query'] = $filters['keyword'];
        }

        if ($filters['keyword'] === '' && preg_match('/\b(php|python|java|react|frontend|front end|backend|full stack|designer|developer|tester|qa|devops|data analyst)\b/i', $lower, $matches)) {
            $filters['keyword'] = trim((string) $matches[1]);
        }

        return $filters;
    }

    private function fetchRecommendedJobs(int $candidateId, array $filters, int $limit = 10): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('jobs')) {
            return [];
        }

        $jobModel = new \App\Models\JobModel();
        $recommended = [];
        try {
            $recommended = $jobModel->getSuggestedJobsBasic($candidateId, 80);
        } catch (\Throwable $e) {
            log_message('warning', 'Candidate chatbot suggested jobs failed: ' . $e->getMessage());
        }

        $byId = [];
        foreach ($recommended as $job) {
            $byId[(int) $job['id']] = $job;
        }

        $builder = $db->table('jobs')
            ->select('id, title, company, location, category, description, required_skills, experience_level, employment_type, salary_range, status, application_deadline, created_at, is_external')
            ->where('status', 'open')
            ->orderBy('created_at', 'DESC')
            ->limit(120);
        \App\Models\JobModel::applyApplicationDeadlineFilter($builder);

        $rows = $builder->get()->getResultArray();
        $candidateContext = $this->getCandidateMatchContext($candidateId);
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $row['match_score'] = (float) ($byId[$id]['match_score'] ?? $this->buildJobMatchBreakdown($candidateId, $row, $candidateContext)['score']);
            if ($this->jobPassesFilters($row, $filters)) {
                $byId[$id] = array_merge($row, $byId[$id] ?? []);
                $byId[$id]['match_score'] = (float) $row['match_score'];
            } elseif (isset($byId[$id])) {
                unset($byId[$id]);
            }
        }

        $jobs = array_values($byId);
        usort($jobs, static function (array $a, array $b): int {
            $scoreCompare = ((float) ($b['match_score'] ?? 0)) <=> ((float) ($a['match_score'] ?? 0));
            if ($scoreCompare !== 0) {
                return $scoreCompare;
            }

            return ((int) ($a['is_external'] ?? 0)) <=> ((int) ($b['is_external'] ?? 0));
        });

        return array_slice($jobs, 0, $limit);
    }

    private function findJobsByTitle(int $candidateId, string $title, int $limit = 3): array
    {
        $title = strtolower(trim($title));
        if ($title === '') {
            return [];
        }

        $db = \Config\Database::connect();
        if (!$db->tableExists('jobs')) {
            return [];
        }

        $builder = $db->table('jobs')
            ->select('id, title, company, location, category, description, required_skills, experience_level, employment_type, salary_range, status, application_deadline, created_at, is_external')
            ->where('status', 'open')
            ->groupStart()
                ->where('LOWER(title)', $title)
                ->orLike('LOWER(title)', $title)
            ->groupEnd()
            ->orderBy('is_external', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->limit(20);
        \App\Models\JobModel::applyApplicationDeadlineFilter($builder);

        $rows = $builder->get()->getResultArray();
        if (empty($rows)) {
            return [];
        }

        $context = $this->getCandidateMatchContext($candidateId);
        foreach ($rows as &$row) {
            $row['match_score'] = $this->buildJobMatchBreakdown($candidateId, $row, $context)['score'];
            $row['_title_rank'] = $this->calculateTitleRank((string) ($row['title'] ?? ''), $title);
        }
        unset($row);

        usort($rows, static function (array $a, array $b): int {
            $externalCompare = ((int) ($a['is_external'] ?? 0)) <=> ((int) ($b['is_external'] ?? 0));
            if ($externalCompare !== 0) {
                return $externalCompare;
            }

            $titleCompare = ((int) ($b['_title_rank'] ?? 0)) <=> ((int) ($a['_title_rank'] ?? 0));
            if ($titleCompare !== 0) {
                return $titleCompare;
            }

            return ((float) ($b['match_score'] ?? 0)) <=> ((float) ($a['match_score'] ?? 0));
        });

        return array_slice($rows, 0, $limit);
    }

    private function calculateTitleRank(string $jobTitle, string $query): int
    {
        $jobTitle = strtolower(trim($jobTitle));
        $query = strtolower(trim($query));
        if ($jobTitle === $query) {
            return 100;
        }
        if (str_contains($jobTitle, $query)) {
            return 80;
        }

        $queryTokens = array_values(array_filter(preg_split('/[^a-z0-9+#.]+/', $query) ?: []));
        if (empty($queryTokens)) {
            return 0;
        }

        $matches = 0;
        foreach ($queryTokens as $token) {
            if ($token !== '' && str_contains($jobTitle, $token)) {
                $matches++;
            }
        }

        return (int) round(($matches / count($queryTokens)) * 60);
    }

    private function jobPassesFilters(array $job, array $filters): bool
    {
        $haystack = strtolower(trim((string) ($job['title'] ?? '') . ' ' . (string) ($job['category'] ?? '') . ' ' . (string) ($job['description'] ?? '') . ' ' . (string) ($job['required_skills'] ?? '')));

        $keyword = strtolower(trim((string) ($filters['keyword'] ?? '')));
        $titleQuery = strtolower(trim((string) ($filters['title_query'] ?? '')));
        if ($titleQuery !== '' && $this->calculateTitleRank((string) ($job['title'] ?? ''), $titleQuery) < 60) {
            return false;
        }

        if ($keyword !== '' && !str_contains($haystack, $keyword)) {
            return false;
        }

        $location = strtolower(trim((string) ($filters['location'] ?? '')));
        if ($location !== '' && !str_contains(strtolower((string) ($job['location'] ?? '')), $location)) {
            return false;
        }

        $workMode = strtolower(trim((string) ($filters['work_mode'] ?? '')));
        $jobLocation = strtolower((string) ($job['location'] ?? ''));
        if ($workMode === 'remote' && !preg_match('/\b(remote|wfh|work from home|anywhere)\b/i', $jobLocation)) {
            return false;
        }
        if ($workMode === 'hybrid' && !str_contains($jobLocation, 'hybrid')) {
            return false;
        }
        if ($workMode === 'onsite' && preg_match('/\b(remote|wfh|work from home|hybrid)\b/i', $jobLocation)) {
            return false;
        }

        $skills = (array) ($filters['skills'] ?? []);
        foreach ($skills as $skill) {
            if ($skill !== '' && !str_contains($haystack, strtolower((string) $skill))) {
                return false;
            }
        }

        $salaryMin = (int) ($filters['salary_min'] ?? 0);
        if ($salaryMin > 0 && !$this->salaryRangeMeetsMinimum((string) ($job['salary_range'] ?? ''), $salaryMin)) {
            return false;
        }

        return true;
    }

    private function salaryRangeMeetsMinimum(string $salaryRange, int $minimumLpa): bool
    {
        if ($salaryRange === '') {
            return true;
        }

        preg_match_all('/\d+(?:\.\d+)?/', $salaryRange, $matches);
        $numbers = array_map('floatval', $matches[0] ?? []);
        if (empty($numbers)) {
            return true;
        }

        $max = max($numbers);
        if ($max > 1000) {
            $max = $max / 100000;
        }

        return $max >= $minimumLpa;
    }

    private function findOpenJob(int $jobId): ?array
    {
        if ($jobId <= 0) {
            return null;
        }

        $job = (new \App\Models\JobModel())
            ->where('id', $jobId)
            ->where('status', 'open')
            ->first();

        if (!$job) {
            return null;
        }

        if (!empty($job['application_deadline']) && strtotime($job['application_deadline'] . ' 23:59:59') < time()) {
            return null;
        }

        return $job;
    }

    private function buildJobMatchBreakdown(int $candidateId, array $job, ?array $context = null): array
    {
        $context = $context ?? $this->getCandidateMatchContext($candidateId);
        $candidateSkills = $context['skills'];
        $requiredSkills = $this->normalizeSkillTokens((string) ($job['required_skills'] ?? ''));

        $matched = [];
        $missing = [];
        foreach ($requiredSkills as $required) {
            $found = false;
            foreach ($candidateSkills as $candidateSkill) {
                if ($required === $candidateSkill || str_contains($candidateSkill, $required) || str_contains($required, $candidateSkill)) {
                    $matched[] = $required;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing[] = $required;
            }
        }

        $skillScore = empty($requiredSkills) ? 25 : (int) round((count($matched) / max(1, count($requiredSkills))) * 55);
        $requiredMonths = $this->extractRequiredExperienceMonths((string) ($job['experience_level'] ?? ''));
        $experienceScore = ($requiredMonths === null || $requiredMonths <= 0)
            ? 20
            : (int) round(min(1, $context['experience_months'] / $requiredMonths) * 20);
        $locationScore = $this->locationMatchesCandidate($job, $context) ? 15 : 0;
        $profileScore = !empty($context['resume_path']) ? 10 : 0;

        return [
            'score' => max(0, min(100, $skillScore + $experienceScore + $locationScore + $profileScore)),
            'matched_skills' => array_values(array_unique($matched)),
            'missing_skills' => array_values(array_unique($missing)),
            'experience_score' => $experienceScore,
            'location_score' => $locationScore,
        ];
    }

    private function getCandidateMatchContext(int $candidateId): array
    {
        $db = \Config\Database::connect();
        $profile = $this->fetchProfileSummary($candidateId);
        $skillRows = $this->fetchSkills($candidateId);
        $skills = [];
        foreach ($skillRows as $row) {
            $skills = array_merge($skills, $this->normalizeSkillTokens((string) ($row['skill_name'] ?? '')));
        }

        $experienceMonths = 0;
        if ($db->tableExists('work_experiences')) {
            $row = $db->query(
                "SELECT SUM(TIMESTAMPDIFF(MONTH, start_date, COALESCE(NULLIF(end_date, ''), CURDATE()))) AS total_experience_months FROM work_experiences WHERE user_id = ?",
                [$candidateId]
            )->getRowArray();
            $experienceMonths = (int) ($row['total_experience_months'] ?? 0);
        }

        $user = model('UserModel')->findCandidateWithProfile($candidateId) ?? [];

        return [
            'skills' => array_values(array_unique($skills)),
            'experience_months' => max(0, $experienceMonths),
            'location' => strtolower((string) ($profile['location'] ?? '')),
            'preferred_locations' => $this->normalizeSkillTokens((string) ($profile['preferred_locations'] ?? '')),
            'preferred_employment_type' => $this->normalizeSkillTokens((string) ($profile['preferred_employment_type'] ?? '')),
            'resume_path' => (string) ($user['resume_path'] ?? ''),
        ];
    }

    private function locationMatchesCandidate(array $job, array $context): bool
    {
        $jobLocation = strtolower((string) ($job['location'] ?? ''));
        if ($jobLocation === '' || preg_match('/\b(remote|wfh|work from home|anywhere)\b/i', $jobLocation)) {
            return true;
        }

        if (!empty($context['location']) && str_contains($jobLocation, (string) $context['location'])) {
            return true;
        }

        foreach ((array) ($context['preferred_locations'] ?? []) as $preferred) {
            if ($preferred !== '' && (str_contains($jobLocation, $preferred) || str_contains($preferred, $jobLocation))) {
                return true;
            }
        }

        return false;
    }

    private function extractJobIds(string $question): array
    {
        preg_match_all('/\bjobs?\s*(?:#|id|number|no\.?|to)?\s*(\d+)\b/i', $question, $matches);
        $ids = array_map('intval', $matches[1] ?? []);

        if (count($ids) < 2) {
            preg_match_all('/#\s*(\d+)\b/', $question, $hashMatches);
            $ids = array_merge($ids, array_map('intval', $hashMatches[1] ?? []));
        }

        return array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));
    }

    private function extractFirstJobId(string $question): int
    {
        $ids = $this->extractJobIds($question);
        return (int) ($ids[0] ?? 0);
    }

    private function normalizeSkillTokens(string $value): array
    {
        $parts = preg_split('/[,|;\/\n\r]+/', strtolower($value)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $token = trim($part);
            if ($token !== '') {
                $tokens[] = $token;
            }
        }

        return array_values(array_unique($tokens));
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
