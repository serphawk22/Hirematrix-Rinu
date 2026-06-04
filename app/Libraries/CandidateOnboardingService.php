<?php

namespace App\Libraries;

use App\Models\CandidateInterestsModel;
use App\Models\CandidateSkillsModel;
use App\Models\EducationModel;
use App\Models\UserModel;
use App\Models\WorkExperienceModel;

class CandidateOnboardingService
{
    public const STEPS = [
        'personal',
        'skills',
        'education',
        'experience',
        'review',
    ];

    public function isComplete(int $candidateId): bool
    {
        $user = (new UserModel())->find($candidateId);
        if (!$user || (string) ($user['role'] ?? '') !== 'candidate') {
            return false;
        }

        return (int) ($user['onboarding_completed'] ?? 0) === 1;
    }

    public function getCompletionMap(int $candidateId): array
    {
        $userModel = new UserModel();
        $user = $userModel->findCandidateWithProfile($candidateId) ?? $userModel->find($candidateId) ?? [];
        $skills = (new CandidateSkillsModel())->where('candidate_id', $candidateId)->first();
        $education = (new EducationModel())->where('user_id', $candidateId)->first();
        $experience = (new WorkExperienceModel())->where('user_id', $candidateId)->first();

        return [
            'personal' => !empty($user['name']) && !empty($user['email']) && !empty($user['phone']) && !empty($user['location']) && !empty($user['bio']) && !empty($user['gender']) && !empty($user['date_of_birth']),
            'skills' => !empty($skills['skill_name']),
            'education' => !empty($education),
            'experience' => !empty($experience) || (int) ($user['is_fresher_candidate'] ?? 0) === 1,
            'review' => (int) ($user['onboarding_completed'] ?? 0) === 1,
        ];
    }

    public function getNextStep(int $candidateId): string
    {
        $completion = $this->getCompletionMap($candidateId);
        foreach (self::STEPS as $step) {
            if (empty($completion[$step])) {
                return $step;
            }
        }

        return 'review';
    }

    public function getAccessibleSteps(int $candidateId): array
    {
        $completion = $this->getCompletionMap($candidateId);
        $accessible = [];

        foreach (self::STEPS as $index => $step) {
            $accessible[] = $step;
            if (empty($completion[$step])) {
                break;
            }
        }

        return array_values(array_unique($accessible));
    }

    public function getProgressPercent(int $candidateId): int
    {
        $completion = $this->getCompletionMap($candidateId);
        $completedCount = 0;
        foreach (self::STEPS as $step) {
            if (!empty($completion[$step])) {
                $completedCount++;
            }
        }

        return (int) round(($completedCount / count(self::STEPS)) * 100);
    }

    public function updateStepState(int $candidateId, string $step, bool $completed = false): void
    {
        $payload = [
            'onboarding_step' => $step,
        ];

        if ($completed) {
            $payload['onboarding_completed'] = 1;
            $payload['onboarding_completed_at'] = date('Y-m-d H:i:s');
            $payload['onboarding_step'] = 'review';
        }

        (new UserModel())->update($candidateId, $payload);
    }
}
