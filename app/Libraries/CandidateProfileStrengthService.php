<?php

namespace App\Libraries;

/**
 * Builds an explainable candidate profile score and actionable checklist.
 */
class CandidateProfileStrengthService
{
    /**
     * @return array{percentage:int,completed_count:int,total_count:int,missing_count:int,items:array<int,array<string,mixed>>}
     */
    public function evaluate(
        array $user,
        ?array $skills,
        array $workExperiences,
        array $education,
        array $projects
    ): array {
        $skillNames = array_values(array_filter(array_map(
            'trim',
            explode(',', (string) ($skills['skill_name'] ?? ''))
        )));
        $isFresher = (int) ($user['is_fresher_candidate'] ?? 0) === 1;
        $projectOutcomeCount = array_sum(array_map(static function (array $project): int {
            $outcome = trim((string) ($project['impact_metrics'] ?? ''));
            if ($outcome === '') {
                return 0;
            }
            preg_match_all('/(?:\d+(?:[.,]\d+)?%?|₹|\\$|increased|reduced|improved|saved|grew)/i', $outcome, $matches);
            return min(2, count($matches[0] ?? []));
        }, $projects));

        $items = [
            $this->item(
                'personal',
                10,
                $this->has($user, 'name') && $this->has($user, 'phone') && $this->has($user, 'location'),
                'Complete your contact details',
                'Add your full name, phone number, and current location so recruiters can contact and filter you.',
                '#personal',
                'fas fa-address-card'
            ),
            $this->item(
                'headline',
                10,
                mb_strlen(trim((string) ($user['resume_headline'] ?? ''))) >= 30,
                'Write a specific professional headline',
                'Describe your target role, strongest specialty, and experience level in one clear line.',
                '#career-details',
                'fas fa-heading'
            ),
            $this->item(
                'resume',
                15,
                $this->has($user, 'resume_path'),
                'Upload your latest resume',
                'Use a current PDF or DOCX resume so recruiters can review your full background.',
                '#resume',
                'fas fa-file-alt'
            ),
            $this->item(
                'skills',
                15,
                count($skillNames) >= 5,
                'Add at least five relevant skills',
                'Include role-specific tools and technologies recruiters are likely to search for.',
                '#skills',
                'fas fa-wrench'
            ),
            $this->item(
                'experience',
                10,
                $isFresher || !empty($workExperiences),
                'Add your work experience',
                'Add your latest role with responsibilities and outcomes, or mark yourself as a fresher.',
                '#experience',
                'fas fa-briefcase'
            ),
            $this->item(
                'education',
                10,
                !empty($education),
                'Add your education',
                'Include your highest qualification, institution, specialization, and completion year.',
                '#education',
                'fas fa-graduation-cap'
            ),
            $this->item(
                'preferences',
                10,
                $this->has($user, 'preferred_job_titles')
                    && $this->has($user, 'preferred_locations')
                    && $this->has($user, 'preferred_employment_type'),
                'Set your job preferences',
                'Choose target job titles, preferred locations, and employment type for better matches.',
                '#preferences',
                'fas fa-bullseye'
            ),
            $this->item(
                'projects',
                5,
                !empty($projects),
                'Add a relevant project',
                'Show what you built, your role, the technology used, and a project link when available.',
                '#projects',
                'fas fa-code'
            ),
            $this->item(
                'outcomes',
                10,
                $projectOutcomeCount >= 2,
                'Add two measurable project outcomes',
                'Quantify results such as performance gains, users served, revenue, time saved, or defects reduced.',
                '#projects',
                'fas fa-chart-line'
            ),
            $this->item(
                'photo',
                5,
                $this->has($user, 'profile_photo'),
                'Add a professional profile photo',
                'Use a clear, recent headshot to make your recruiter-facing profile easier to recognize.',
                '#personal',
                'fas fa-camera'
            ),
        ];

        usort($items, static function (array $left, array $right): int {
            if ($left['completed'] !== $right['completed']) {
                return $left['completed'] ? 1 : -1;
            }
            return $right['weight'] <=> $left['weight'];
        });

        $earned = array_sum(array_map(static fn (array $item): int => $item['completed'] ? $item['weight'] : 0, $items));
        $total = array_sum(array_column($items, 'weight'));
        $completedCount = count(array_filter($items, static fn (array $item): bool => $item['completed']));

        return [
            'percentage' => $total > 0 ? (int) round(($earned / $total) * 100) : 0,
            'completed_count' => $completedCount,
            'total_count' => count($items),
            'missing_count' => count($items) - $completedCount,
            'items' => $items,
        ];
    }

    private function has(array $data, string $field): bool
    {
        return trim((string) ($data[$field] ?? '')) !== '';
    }

    private function item(
        string $key,
        int $weight,
        bool $completed,
        string $title,
        string $detail,
        string $actionUrl,
        string $icon
    ): array {
        return compact('key', 'weight', 'completed', 'title', 'detail', 'actionUrl', 'icon');
    }
}
