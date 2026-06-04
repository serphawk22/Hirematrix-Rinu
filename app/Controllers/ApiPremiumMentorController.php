<?php

namespace App\Controllers;

class ApiPremiumMentorController extends PremiumCareerMentorController
{
    public function getMentorData($candidateId)
    {
        $candidateId = (int) $candidateId;
        if ($candidateId <= 0) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Invalid Candidate ID'
            ])->setStatusCode(400);
        }

        $subscription = $this->subscriptionModel->getUserActiveSubscription($candidateId);
        
        if (!$subscription) {
            return $this->response->setJSON([
                'status' => 'no_subscription',
                'message' => 'Premium subscription required for AI Career Mentor'
            ]);
        }

        $usageToday = $this->usageModel->getTodayUsage($candidateId);
        $activeSessions = $this->hydrateSessionProgress($this->sessionModel->getUserActiveSessions($candidateId), $candidateId);
        $history = $this->getConversationHistory($candidateId);
        $features = json_decode($subscription['features'], true) ?: [];

        // Determine default target role suggestions from profile
        $userProfile = $this->getUserCareerProfile($candidateId);
        $suggestedRoles = [];
        if (!empty($userProfile['target_role'])) {
            $suggestedRoles[] = $userProfile['target_role'];
        }
        if (!empty($userProfile['current_role']) && !in_array($userProfile['current_role'], $suggestedRoles, true)) {
            $suggestedRoles[] = $userProfile['current_role'];
        }
        // Fallback defaults
        $suggestedRoles = array_merge($suggestedRoles, ['Software Engineer', 'Product Manager', 'Data Analyst', 'UX Designer']);
        $suggestedRoles = array_values(array_unique($suggestedRoles));

        return $this->response->setJSON([
            'status' => 'success',
            'subscription' => $subscription,
            'usage_today' => $usageToday,
            'active_sessions' => $activeSessions,
            'history' => $history,
            'features' => $features,
            'suggested_roles' => $suggestedRoles,
            'user_profile' => $userProfile
        ]);
    }

    public function chat()
    {
        $userId = (int) ($this->request->getPost('candidate_id') ?: $this->request->getPost('user_id'));
        $message = trim((string) $this->request->getPost('message'));
        $sessionId = trim((string) $this->request->getPost('session_id')) ?: uniqid();

        if ($userId <= 0 || $message === '') {
            return $this->response->setJSON([
                'status' => 'error',
                'error' => 'candidate_id and message are required.'
            ])->setStatusCode(400);
        }

        $canUse = $this->checkUsageLimit($userId);
        if (!$canUse['allowed']) {
            return $this->response->setJSON([
                'status' => 'limit_reached',
                'error' => $canUse['message'],
                'upgrade_required' => true
            ]);
        }

        $response = $this->processPremiumCareerChat($message, $userId, $sessionId);
        $this->usageModel->trackUsage($userId, $sessionId, $response['feature_used']);

        return $this->response->setJSON([
            'status' => 'success',
            'message' => $response['message'],
            'session_id' => $response['session_id'],
            'feature_used' => $response['feature_used'],
            'premium_features' => $response['premium_features'] ?? [],
            'progress_tracking' => $response['progress_tracking'] ?? null,
            'follow_up_chips' => $response['follow_up_chips'] ?? [],
        ]);
    }

    public function createPlan()
    {
        $userId = (int) ($this->request->getPost('candidate_id') ?: $this->request->getPost('user_id'));
        $targetRole = trim((string) $this->request->getPost('target_role'));
        $timeline = trim((string) $this->request->getPost('timeline'));
        $currentRole = trim((string) $this->request->getPost('current_role'));

        if ($userId <= 0 || $targetRole === '' || $timeline === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'candidate_id, target_role, and timeline are required.'
            ])->setStatusCode(400);
        }

        $prompt = "Create a detailed career transition plan:
                   Current Role: {$currentRole}
                   Target Role: {$targetRole}
                   Timeline: {$timeline}
                   
                   Provide:
                   1. Skill gap analysis
                   2. Learning roadmap with specific courses/certifications
                   3. Monthly milestones
                   4. Networking strategies
                   5. Portfolio/project recommendations
                   6. Interview preparation timeline
                   
                   Format as structured JSON with phases, tasks, and deadlines.";

        $aiAnalysis = $this->generateCareerResponse($prompt);
        $initialProgress = [
            'progress_percentage' => self::INITIAL_PLAN_PROGRESS,
            'completed_milestones' => [],
            'next_milestones' => [],
            'last_nudge' => 'Start with the first milestone this week to build momentum.',
            'updated_at' => date('c')
        ];
        
        $sessionData = [
            'user_id' => $userId,
            'session_type' => 'career_strategy',
            'current_role' => $currentRole,
            'target_role' => $targetRole,
            'timeline' => $timeline,
            'ai_analysis' => $aiAnalysis,
            'progress_tracking' => json_encode($initialProgress),
            'status' => 'active'
        ];

        $existingSession = $this->sessionModel->findSimilarActiveSession(
            $userId,
            'career_strategy',
            $currentRole,
            $targetRole,
            $timeline
        );

        if ($existingSession) {
            $sessionId = (int) ($existingSession['id'] ?? 0);
            $this->sessionModel->update($sessionId, [
                'ai_analysis' => $aiAnalysis,
                'progress_tracking' => json_encode($initialProgress),
                'status' => 'active'
            ]);
        } else {
            $sessionId = $this->sessionModel->insert($sessionData, true);
        }

        return $this->response->setJSON([
            'success' => true,
            'session_id' => $sessionId,
            'message' => $existingSession
                ? 'Career plan updated successfully!'
                : 'Career plan created successfully!'
        ]);
    }
}
