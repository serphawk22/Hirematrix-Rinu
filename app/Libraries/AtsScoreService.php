<?php

namespace App\Libraries;

use App\Models\CandidateResumeVersionModel;
use App\Models\CandidateSkillsModel;
use App\Models\JobModel;
use App\Models\UserModel;

class AtsScoreService
{
    private int $cacheTtl = 21600;

    public function analyzeCandidateJob(int $candidateId, array $job, int $resumeVersionId = 0): array
    {
        $jobId = (int) ($job['id'] ?? 0);
        if ($candidateId <= 0 || $jobId <= 0) {
            return $this->emptyResult();
        }

        $user = (new UserModel())->findCandidateWithProfile($candidateId) ?? [];
        if (empty($user)) {
            return $this->emptyResult();
        }

        $resumeVersionModel = new CandidateResumeVersionModel();
        $resumeVersion = $resumeVersionId > 0
            ? $resumeVersionModel->where('id', $resumeVersionId)->where('candidate_id', $candidateId)->first()
            : $resumeVersionModel->getPreferredVersionForJob($candidateId, $jobId);

        $cacheKey = 'ats_service_' . sha1(json_encode([
            'candidate_id' => $candidateId,
            'job_id' => $jobId,
            'resume_version_id' => (int) ($resumeVersion['id'] ?? 0),
            'job_title' => (string) ($job['title'] ?? ''),
            'job_category' => (string) ($job['category'] ?? ''),
            'job_required_skills' => (string) ($job['required_skills'] ?? ''),
            'job_experience_level' => (string) ($job['experience_level'] ?? ''),
            'job_description_hash' => sha1((string) ($job['description'] ?? '')),
            'job_updated_at' => (string) ($job['updated_at'] ?? $job['created_at'] ?? ''),
            'resume_updated_at' => (string) ($resumeVersion['updated_at'] ?? ''),
            'resume_path' => (string) ($user['resume_path'] ?? ''),
            'headline' => (string) ($user['resume_headline'] ?? ''),
            'bio' => (string) ($user['bio'] ?? ''),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $cache = cache();
        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $skillsRow = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->first() ?? [];
        $candidateSkills = $this->tokenizeSkills((string) ($skillsRow['skill_name'] ?? ''));
        $resumeSkills = $this->tokenizeSkills((string) ($resumeVersion['highlight_skills'] ?? ''));
        $jobSkills = $this->tokenizeSkills((string) ($job['required_skills'] ?? ''));
        $jobKeywords = $this->extractJobKeywords(
            (string) ($job['title'] ?? ''),
            (string) ($job['category'] ?? ''),
            (string) ($job['description'] ?? ''),
            $jobSkills
        );

        $resumeText = $this->buildResumeText($user, $resumeVersion);
        $candidateKeywordPool = $this->buildCandidateKeywordPool($user, $candidateSkills, $resumeSkills, $resumeText, $resumeVersion);

        $matchedSkills = $this->findMatches($jobSkills, $candidateKeywordPool);
        $missingSkills = $this->findMissing($jobSkills, $candidateKeywordPool);
        $matchedKeywords = $this->findMatches($jobKeywords, $candidateKeywordPool);
        $missingKeywords = $this->findMissing($jobKeywords, $candidateKeywordPool);

        $skillScore = empty($jobSkills)
            ? 30
            : (int) round((count($matchedSkills) / max(1, count($jobSkills))) * 55);

        $keywordScore = empty($jobKeywords)
            ? 10
            : (int) round((count($matchedKeywords) / max(1, count($jobKeywords))) * 15);

        $experienceMonths = $this->getCandidateExperienceMonths($candidateId);
        $requiredMonths = $this->extractRequiredExperienceMonths((string) ($job['experience_level'] ?? ''));
        if ($requiredMonths === null || $requiredMonths <= 0) {
            $experienceScore = 12;
        } else {
            $experienceScore = (int) round(min(15, ($experienceMonths / max(1, $requiredMonths)) * 15));
        }

        $resumeCompleteness = 0;
        if (!empty($user['resume_path']) || !empty($resumeVersion['content'])) {
            $resumeCompleteness += 6;
        }
        if (!empty($resumeVersion['summary']) || !empty($user['bio'])) {
            $resumeCompleteness += 4;
        }
        if (!empty($resumeSkills) || !empty($candidateSkills)) {
            $resumeCompleteness += 3;
        }
        if (!empty($user['resume_headline'])) {
            $resumeCompleteness += 2;
        }

        $score = max(0, min(100, $skillScore + $keywordScore + $experienceScore + $resumeCompleteness));

        $criticalGap = 'No major gaps found.';
        if (!empty($jobSkills) && count($matchedSkills) === 0) {
            $criticalGap = 'Core required skills are not yet visible in the resume or profile.';
        } elseif ($requiredMonths !== null && $requiredMonths > 0 && $experienceMonths < (int) floor($requiredMonths * 0.5)) {
            $criticalGap = 'Visible experience is well below the experience level requested for this role.';
        } elseif (!empty($missingSkills)) {
            $criticalGap = 'Some important job keywords are still missing from the resume narrative.';
        }

        $suggestions = [];
        if (!empty($missingSkills)) {
            $suggestions[] = 'Add proof points for ' . implode(', ', array_slice($missingSkills, 0, 4)) . ' if you have real experience with them.';
        }
        if (empty($resumeVersion['summary']) && empty($user['bio'])) {
            $suggestions[] = 'Add a focused summary so recruiters and ATS can quickly understand your target fit.';
        }
        if ($experienceMonths < 12) {
            $suggestions[] = 'Use projects, internships, or measurable work samples to strengthen role relevance despite lighter experience.';
        }
        if (empty($suggestions)) {
            $suggestions[] = 'Resume alignment looks solid. Focus on sharpening measurable outcomes and role-specific keywords.';
        }

        $result = [
            'score' => $score,
            'matched_skills' => array_values(array_slice($matchedSkills, 0, 8)),
            'missing_keywords' => array_values(array_slice(array_unique(array_merge($missingSkills, $missingKeywords)), 0, 8)),
            'suggestions' => array_values(array_slice($suggestions, 0, 5)),
            'critical_gap' => $criticalGap,
            'match_reason' => $this->buildMatchReason($score, $matchedSkills, $experienceMonths),
            'resume_version' => $resumeVersion,
            'experience_months' => $experienceMonths,
            'has_resume_source' => trim($resumeText) !== '' || !empty($user['resume_path']),
        ];

        $cache->save($cacheKey, $result, $this->cacheTtl);

        return $result;
    }

    private function emptyResult(): array
    {
        return [
            'score' => 0,
            'matched_skills' => [],
            'missing_keywords' => [],
            'suggestions' => ['Add a stronger role-specific resume and profile summary to improve ATS alignment.'],
            'critical_gap' => 'No analyzable candidate or job context was found.',
            'match_reason' => 'Insufficient profile signals for ATS analysis.',
            'resume_version' => null,
            'experience_months' => 0,
            'has_resume_source' => false,
        ];
    }

    private function buildResumeText(array $user, ?array $resumeVersion): string
    {
        $segments = [
            (string) ($user['resume_headline'] ?? ''),
            (string) ($user['bio'] ?? ''),
            (string) ($resumeVersion['summary'] ?? ''),
            (string) ($resumeVersion['highlight_skills'] ?? ''),
            (string) ($resumeVersion['content'] ?? ''),
        ];

        if (trim((string) ($resumeVersion['content'] ?? '')) === '' && !empty($user['resume_path'])) {
            $filePath = rtrim(WRITEPATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim((string) $user['resume_path'], DIRECTORY_SEPARATOR);
            if (is_file($filePath)) {
                try {
                    $parsed = (new ResumeParser())->parse($filePath);
                    $segments[] = (string) ($parsed['full_text'] ?? '');
                } catch (\Throwable $e) {
                    log_message('warning', 'ATS score resume parse failed: ' . $e->getMessage());
                }
            }
        }

        return trim(implode("\n", array_filter(array_map('trim', $segments))));
    }

    private function buildCandidateKeywordPool(array $user, array $candidateSkills, array $resumeSkills, string $resumeText, ?array $resumeVersion): array
    {
        $textTokens = $this->tokenizeText(implode(' ', [
            (string) ($user['resume_headline'] ?? ''),
            (string) ($user['bio'] ?? ''),
            (string) ($resumeVersion['summary'] ?? ''),
            (string) ($resumeVersion['target_role'] ?? ''),
            $resumeText,
        ]));

        return array_values(array_unique(array_filter(array_merge(
            $candidateSkills,
            $resumeSkills,
            $textTokens
        ))));
    }

    private function extractJobKeywords(string $title, string $category, string $description, array $jobSkills): array
    {
        $textTokens = $this->tokenizeText($title . ' ' . $category . ' ' . $description);
        return array_values(array_unique(array_filter(array_merge($jobSkills, $textTokens))));
    }

    private function tokenizeSkills(string $value): array
    {
        $parts = preg_split('/[,|\/]+/', strtolower($value)) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if ($trimmed !== '') {
                $tokens[] = $trimmed;
            }
        }
        return array_values(array_unique($tokens));
    }

    private function tokenizeText(string $text): array
    {
        $parts = preg_split('/[^a-z0-9+#.]+/i', strtolower($text)) ?: [];
        $stopWords = array_fill_keys([
            'the', 'and', 'for', 'with', 'from', 'this', 'that', 'your', 'role', 'job',
            'developer', 'engineer', 'senior', 'junior', 'experience', 'skills', 'work',
            'good', 'strong', 'using', 'have', 'has', 'into', 'our', 'team', 'will',
        ], true);

        $tokens = [];
        foreach ($parts as $part) {
            $token = trim($part);
            if ($token === '' || strlen($token) < 2 || isset($stopWords[$token])) {
                continue;
            }
            $tokens[] = $token;
        }

        return array_values(array_unique($tokens));
    }

    private function findMatches(array $required, array $candidatePool): array
    {
        $matches = [];
        foreach ($required as $requiredItem) {
            foreach ($candidatePool as $candidateItem) {
                if ($this->tokensMatch($requiredItem, $candidateItem)) {
                    $matches[] = $requiredItem;
                    break;
                }
            }
        }
        return array_values(array_unique($matches));
    }

    private function findMissing(array $required, array $candidatePool): array
    {
        $missing = [];
        foreach ($required as $requiredItem) {
            $found = false;
            foreach ($candidatePool as $candidateItem) {
                if ($this->tokensMatch($requiredItem, $candidateItem)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing[] = $requiredItem;
            }
        }
        return array_values(array_unique($missing));
    }

    private function tokensMatch(string $left, string $right): bool
    {
        $left = strtolower(trim($left));
        $right = strtolower(trim($right));
        if ($left === '' || $right === '') {
            return false;
        }
        if ($left === $right) {
            return true;
        }
        if (strlen($left) >= 4 && str_contains($right, $left)) {
            return true;
        }
        if (strlen($right) >= 4 && str_contains($left, $right)) {
            return true;
        }
        return false;
    }

    private function getCandidateExperienceMonths(int $candidateId): int
    {
        $row = \Config\Database::connect()
            ->table('work_experiences')
            ->select('SUM(TIMESTAMPDIFF(MONTH, start_date, COALESCE(NULLIF(end_date, \'\'), CURDATE()))) AS total_experience_months', false)
            ->where('user_id', $candidateId)
            ->get()
            ->getRowArray();

        return max(0, (int) ($row['total_experience_months'] ?? 0));
    }

    private function extractRequiredExperienceMonths(string $experienceLevel): ?int
    {
        $value = strtolower(trim($experienceLevel));
        if ($value === '') {
            return null;
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*[-to]+\s*(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (int) round(((float) $matches[1]) * 12);
        }

        if (preg_match('/(\d+(?:\.\d+)?)\+/', $value, $matches)) {
            return (int) round(((float) $matches[1]) * 12);
        }

        if (str_contains($value, 'fresher') || str_contains($value, 'entry')) {
            return 0;
        }

        if (preg_match('/(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (int) round(((float) $matches[1]) * 12);
        }

        return null;
    }

    private function buildMatchReason(int $score, array $matchedSkills, int $experienceMonths): string
    {
        if ($score >= 85) {
            return 'Strong ATS alignment with visible skill overlap and relevant experience.';
        }
        if ($score >= 70) {
            return 'Good ATS fit with several matching keywords for the role.';
        }
        if ($score >= 55) {
            return 'Moderate ATS fit with partial keyword coverage.';
        }
        if (!empty($matchedSkills)) {
            return 'Some role keywords match, but the resume needs stronger alignment.';
        }
        if ($experienceMonths > 24) {
            return 'Experience is present, but the resume is missing role-specific keywords.';
        }
        return 'Current ATS alignment is limited based on the visible profile and resume signals.';
    }
}
            