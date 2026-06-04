<?php
namespace App\Libraries;

use App\Models\JobModel;
use App\Models\CandidateSkillsModel;
use App\Models\CandidateInterestsModel;
use App\Models\JobSuggestionModel;

class AiJobMatcher
{
    private $apiKey;
    private $apiUrl;

    // Cache duration in minutes - don't call AI again if suggestions are fresh
    private $cacheDurationMinutes = 60;

    public function __construct()
    {
        $this->apiKey = getenv('OPENAI_API_KEY');
        $this->apiUrl = 'https://api.openai.com/v1/chat/completions';
    }

    public function generateSuggestions($candidateId, $limit = 15)
    {
        $jobModel        = new JobModel();
        $suggestionModel = new JobSuggestionModel();
        $db              = \Config\Database::connect();

        // ── CHECK CACHE FIRST ──
        // If we already have fresh suggestions (within cache window), just return them.
        // This prevents duplicate inserts on every filter change.
        $freshSuggestions = $db->query("
            SELECT j.*, js.score as match_score, js.reason as match_reason
            FROM job_suggestions js
            JOIN jobs j ON js.job_id = j.id
            WHERE js.candidate_id = ?
              AND j.status = 'open'
              AND (j.application_deadline IS NULL OR j.application_deadline = '' OR j.application_deadline >= CURDATE())
              AND js.score > 0
              AND js.created_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ORDER BY js.score DESC
            LIMIT ?
        ", [$candidateId, $this->cacheDurationMinutes, $limit])->getResultArray();

        if (!empty($freshSuggestions)) {
            // Return cached suggestions — no AI call needed
            return $freshSuggestions;
        }

        // ── NO FRESH CACHE: Call AI ──
        $skillsModel    = new CandidateSkillsModel();
        $interestsModel = new CandidateInterestsModel();
        $userModel      = new \App\Models\UserModel();

        $candidate = $userModel->find($candidateId);

        // Skills: comma-separated single row → flat array
        $skillRow  = $skillsModel->where('candidate_id', $candidateId)->first();
        $skills    = [];
        if ($skillRow && !empty($skillRow['skill_name'])) {
            $skills = array_values(array_filter(array_map('trim', explode(',', $skillRow['skill_name']))));
        }

        // Interests: comma-separated single row → flat array
        $interestRow = $interestsModel->where('candidate_id', $candidateId)->first();
        $interests   = [];
        if ($interestRow && !empty($interestRow['interest'])) {
            $interests = array_values(array_filter(array_map('trim', explode(',', $interestRow['interest']))));
        }
        $behavior  = $jobModel->getCandidateBehaviorProfile($candidateId);

        $openJobsBuilder = $jobModel->where('status', 'open')
            ->whereNotIn('id', function($builder) use ($candidateId) {
                return $builder->select('job_id')->from('applications')->where('candidate_id', $candidateId);
            })
            ->orderBy('created_at', 'DESC')
            ->limit(50);

        JobModel::applyApplicationDeadlineFilter($openJobsBuilder);
        $openJobs = $openJobsBuilder->find();

        if (empty($openJobs)) return [];

        $candidateProfile = [
            'name'      => $candidate['name'] ?? '',
            'location'  => $candidate['location'] ?? '',
            'bio'       => $candidate['bio'] ?? '',
            'skills'    => $skills,
            'interests' => $interests,
            'behavior'  => [
                'preferred_categories'       => array_column($behavior['top_categories'] ?? [], 'category'),
                'preferred_experience'        => array_column($behavior['top_experience_levels'] ?? [], 'experience_level'),
                'preferred_employment'        => array_column($behavior['top_employment_types'] ?? [], 'employment_type'),
                'preferred_locations'         => array_column($behavior['top_locations'] ?? [], 'location'),
                'frequently_applied_skills'   => array_keys($behavior['applied_skill_frequency'] ?? []),
            ],
        ];

        $jobsList = array_map(function($job) {
            return [
                'id'               => $job['id'],
                'title'            => $job['title'],
                'category'         => $job['category'],
                'company'          => $job['company'],
                'location'         => $job['location'],
                'required_skills'  => $job['required_skills'],
                'experience_level' => $job['experience_level'],
                'employment_type'  => $job['employment_type'],
            ];
        }, $openJobs);

        $prompt = "You are a job recommendation engine. Analyze this candidate's profile and rank the best matching jobs.

CANDIDATE PROFILE:
" . json_encode($candidateProfile, JSON_PRETTY_PRINT) . "

AVAILABLE JOBS:
" . json_encode($jobsList, JSON_PRETTY_PRINT) . "

INSTRUCTIONS:
1. Consider skill matches (exact and related skills)
2. Consider the candidate's stated interests
3. Consider their application behavior patterns
4. Consider location compatibility
5. Return the top {$limit} best matching jobs

Return ONLY a valid JSON array, no other text:
[{\"job_id\": 123, \"score\": 92.5, \"reason\": \"Strong skill match in React + preferred category\"}, ...]

Score from 0-100. Reason should be 1 short sentence.";

        $aiResponse = $this->callAiApi($prompt);

        if (!$aiResponse) {
            return $jobModel->getSuggestedJobsBasic($candidateId, $limit);
        }

        $suggestions = json_decode($aiResponse, true);
        if (!is_array($suggestions)) {
            return $jobModel->getSuggestedJobsBasic($candidateId, $limit);
        }

        // ── SAVE SUGGESTIONS SAFELY ──
        // Use INSERT ... ON DUPLICATE KEY UPDATE instead of delete+insert
        // This avoids the unique constraint error completely
        $now = date('Y-m-d H:i:s');

        foreach ($suggestions as $suggestion) {
            if (!isset($suggestion['job_id'], $suggestion['score'])) continue;

            $db->query("
                INSERT INTO job_suggestions (candidate_id, job_id, score, reason, created_at)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    score      = VALUES(score),
                    reason     = VALUES(reason),
                    created_at = VALUES(created_at)
            ", [
                $candidateId,
                (int) $suggestion['job_id'],
                (float) $suggestion['score'],
                $suggestion['reason'] ?? '',
                $now
            ]);
        }

        // Return fresh suggestions — only jobs with a real match score
        return $db->query("
            SELECT j.*, js.score as match_score, js.reason as match_reason
            FROM job_suggestions js
            JOIN jobs j ON js.job_id = j.id
            WHERE js.candidate_id = ?
              AND j.status = 'open'
              AND (j.application_deadline IS NULL OR j.application_deadline = '' OR j.application_deadline >= CURDATE())
              AND js.score > 0
            ORDER BY js.score DESC
            LIMIT ?
        ", [$candidateId, $limit])->getResultArray();
    }

    private function callAiApi($prompt)
    {
        if (empty($this->apiKey)) {
            log_message('warning', 'AI_API_KEY not configured, falling back to basic matching');
            return null;
        }

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode([
                'model'       => 'gpt-4o-mini',
                'messages'    => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.3,
                'max_tokens'  => 2000,
            ]),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            log_message('error', "AI API error: HTTP {$httpCode} - {$response}");
            return null;
        }

        $data = json_decode($response, true);
        if (is_array($data)) {
            (new UsageAnalyticsService())->logOpenAiUsage($data, '/v1/chat/completions', 'gpt-4o-mini');
        }
        return $data['choices'][0]['message']['content'] ?? null;
    }
}
