<?php

namespace App\Controllers;

use App\Models\SubscriptionModel;
use App\Models\ChatbotUsageModel;
use App\Models\PremiumCareerSessionModel;
use App\Models\CareerGoalModel;
use App\Models\PremiumMentorMemoryModel;
use App\Models\PremiumMentorMessageModel;
use App\Libraries\CareerTransitionAI;
use Throwable;

class PremiumCareerMentorController extends BaseController
{
    private const MAX_CONVERSATION_MESSAGES = 20;
    private const MAX_RECENT_MEMORY_MESSAGES = 12;
    private const MEMORY_COMPACTION_THRESHOLD = 10;
    private const INITIAL_PLAN_PROGRESS = 5;
    private const STARTED_MILESTONE_PROGRESS_STEP = 10;
    private const COMPLETED_MILESTONE_PROGRESS_STEP = 25;

    protected $subscriptionModel;
    protected $usageModel;
    protected $sessionModel;
    protected $careerGoalModel;
    protected $mentorMemoryModel;
    protected $mentorMessageModel;
    protected $aiLibrary;
    protected $careerGoalsFeatureEnabled = null;
    protected $mentorMemoryFeatureEnabled = null;

    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
        $this->usageModel        = new ChatbotUsageModel();
        $this->sessionModel      = new PremiumCareerSessionModel();
        $this->careerGoalModel   = new CareerGoalModel();
        $this->mentorMemoryModel = new PremiumMentorMemoryModel();
        $this->mentorMessageModel = new PremiumMentorMessageModel();
        $this->aiLibrary         = new CareerTransitionAI();
    }

    public function plans()
    {
        return redirect()->to(base_url('premium/plans?service=mentor'));
    }

    /**
     * Starts a 7-day free trial for the career mentor service.
     */
    public function startTrial()
    {
        $planId = $this->request->getPost('plan_id');
        $userId = session()->get('user_id');

        if (!$userId) {
            return redirect()->to('/login')->with('error', 'Please login to start your free trial.');
        }

        $plan = $this->subscriptionModel->find($planId);
        if (!$plan) {
            return redirect()->back()->with('error', 'Invalid plan selected.');
        }

        // Check if the user has already taken a free trial
        $db = \Config\Database::connect();
        $existingTrial = $db->table('user_subscriptions')
                            ->where('user_id', $userId)
                            ->where('amount_paid', 0.00)
                            ->get()->getRowArray();
        if ($existingTrial) {
            return redirect()->back()->with('error', 'You have already used your free trial option.');
        }

        // Create a trial subscription record
        $subscriptionData = [
            'user_id' => $userId,
            'plan_id' => $planId,
            'start_date' => date('Y-m-d'),
            // Set end date based on the trial constant
            'end_date' => date('Y-m-d', strtotime('+' . SUBSCRIPTION_TRIAL_DAYS . ' days')),
            'amount_paid' => 0.00,
            'payment_id' => 'trial_' . uniqid(),
            'status' => 'active'
        ];

        if ($this->subscriptionModel->saveSubscription($subscriptionData)) {
            return redirect()->to('/candidate/dashboard')->with('success',
                sprintf('Your %d-day free trial has started! You can now use AI Career Mentor in the chatbot.', SUBSCRIPTION_TRIAL_DAYS)
            );
        }

        return redirect()->back()->with('error', 'Failed to activate free trial. Please try again.');
    }

    public function chat()
    {
        $userId = session()->get('user_id');
        $message = $this->request->getPost('message');
        $sessionId = $this->request->getPost('session_id') ?: uniqid();

        // Check subscription and usage limits
        $canUse = $this->checkUsageLimit($userId);
        if (!$canUse['allowed']) {
            return $this->response->setJSON([
                'error' => $canUse['message'],
                'upgrade_required' => true
            ]);
        }

        // Process premium career mentoring
        $response = $this->processPremiumCareerChat($message, $userId, $sessionId);
        
        // Track usage
        $this->usageModel->trackUsage($userId, $sessionId, $response['feature_used']);

        return $this->response->setJSON([
            'message' => $response['message'],
            'session_id' => $response['session_id'],
            'feature_used' => $response['feature_used'],
            'premium_features' => $response['premium_features'] ?? [],
            'progress_tracking' => $response['progress_tracking'] ?? null,
            'follow_up_chips' => $response['follow_up_chips'] ?? [],
            'csrf_hash' => csrf_hash()
        ]);
    }

    protected function processPremiumCareerChat($message, $userId, $sessionId)
    {
        $subscription = $this->subscriptionModel->getUserActiveSubscription($userId);
        $userProfile = $this->getUserCareerProfile($userId);
        $conversationHistory = $this->getConversationHistory($userId);
        $mentorMemory = $this->getPersistentMentorMemory($userId);
        $responseType = $this->analyzeMessageIntent($message);
        $premiumFeatures = $this->getPremiumFeatures($responseType, $subscription);

        // Build full chat context (profile + prior turns + latest user message).
        $messages = $this->buildPremiumMessages($message, $userProfile, $subscription, $conversationHistory, $premiumFeatures, $responseType, $mentorMemory);
        $aiResponse = $this->generateCareerResponse($messages);

        // Extract follow-up chips from the AI response
        $followUpChips = [];
        if (preg_match_all('/\[\[Chip:\s*(.*?)\]\]/i', $aiResponse, $matches)) {
            $followUpChips = array_map('trim', $matches[1]);
            $aiResponse = preg_replace('/\[\[Chip:\s*.*?\]\]/i', '', $aiResponse);
            $aiResponse = trim($aiResponse);
        }

        $this->appendConversationMessage($userId, $sessionId, 'user', $message);
        $this->appendConversationMessage($userId, $sessionId, 'assistant', $aiResponse);
        $this->compactMentorMemoryIfNeeded($userId, $userProfile);

        $smartGoalSaved = $this->syncSmartGoalIfReady($userId, $sessionId, $message);
        $progressUpdate = $this->syncSessionProgressTracking($userId, $sessionId, $message, $aiResponse, $userProfile);
        $aiResponse = $this->decorateResponseWithPremiumFeatures($aiResponse, $premiumFeatures, $userProfile, $progressUpdate);
        
        $response = [
            'message'      => $aiResponse,
            'session_id'   => $sessionId,
            'feature_used' => $responseType,
            'smart_goal_saved' => $smartGoalSaved,
            'progress_tracking' => $progressUpdate,
            'premium_features' => array_keys(array_filter($premiumFeatures)),
            'follow_up_chips' => $followUpChips
        ];

        return $response;
    }

    private function buildPremiumMessages($message, $userProfile, $subscription, $conversationHistory, $premiumFeatures, $responseType, $mentorMemory)
    {
        $planFeatures = json_decode($subscription['features'], true);
        if (!is_array($planFeatures)) {
            $planFeatures = [];
        }

        $activePremiumFeatures = array_keys(array_filter($premiumFeatures));

        $systemPrompt = "You are an expert AI Career Mentor with access to premium features: " .
            implode(', ', $planFeatures) . "\n\n" .
            "User Profile:\n" .
            "- Current Role: " . ($userProfile['current_role'] ?? 'Not specified') . "\n" .
            "- Target Role: " . ($userProfile['target_role'] ?? 'Not specified') . "\n" .
            "- Experience: " . ($userProfile['experience'] ?? 'Not specified') . "\n" .
            "- Skills: " . implode(', ', $userProfile['skills'] ?? []) . "\n\n" .
            "Mentoring approach:\n" .
            "1. Continue from prior conversation context and avoid repeating generic advice.\n" .
            "2. Progress toward SMART goals gradually.\n" .
            "3. Give actionable next steps and offer support, only asking for progress updates when relevant. Avoid generic motivational fluff.\n" .
            "4. Suggest 3 short follow-ups at the end of every response, formatted as [[Chip: Suggestion Text]]. These should be context-aware next steps.\n" .
            "5. Be encouraging, specific, and practical.\n\n" .
            "Current response type: {$responseType}\n" .
            "Active premium execution features: " . (empty($activePremiumFeatures) ? 'none' : implode(', ', $activePremiumFeatures)) . "\n\n" .
            $this->buildPersistentMemoryInstructions($mentorMemory) . "\n\n" .
            $this->buildFeatureExecutionInstructions($premiumFeatures);

        $messages = [[
            'role' => 'system',
            'content' => $systemPrompt
        ]];

        foreach ($conversationHistory as $entry) {
            if (!isset($entry['role'], $entry['content'])) {
                continue;
            }

            $role = $entry['role'] === 'assistant' ? 'assistant' : 'user';
            $content = trim((string) $entry['content']);
            if ($content === '') {
                continue;
            }

            $messages[] = [
                'role' => $role,
                'content' => $content
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];

        return $messages;
    }

    private function analyzeMessageIntent($message)
    {
        $message = strtolower($message);
        
        if (strpos($message, 'goal') !== false || strpos($message, 'target') !== false) {
            return 'goal_setting';
        } elseif (strpos($message, 'skill') !== false || strpos($message, 'learn') !== false) {
            return 'skill_development';
        } elseif (strpos($message, 'interview') !== false) {
            return 'interview_prep';
        } elseif (strpos($message, 'resume') !== false || strpos($message, 'cv') !== false) {
            return 'resume_optimization';
        } elseif (strpos($message, 'salary') !== false || strpos($message, 'negotiate') !== false) {
            return 'salary_negotiation';
        } else {
            return 'general_guidance';
        }
    }

    private function getPremiumFeatures($responseType, $subscription)
    {
        $features = [];
        $planFeatures = json_decode((string) ($subscription['features'] ?? '[]'), true);
        $planFeatureText = strtolower(is_array($planFeatures) ? implode(' ', $planFeatures) : '');
        
        switch ($responseType) {
            case 'goal_setting':
                $features = [
                    'smart_goals_generator' => $this->planSupportsFeature($planFeatureText, ['smart goals', 'career chat', 'career transition support']),
                    'milestone_tracker' => $this->planSupportsFeature($planFeatureText, ['progress reviews', 'career transition support', 'career acceleration']),
                    'progress_analytics' => $this->planSupportsFeature($planFeatureText, ['progress reviews', 'career acceleration', 'executive coaching'])
                ];
                break;
            case 'skill_development':
                $features = [
                    'skill_gap_analysis' => $this->planSupportsFeature($planFeatureText, ['skill gap analysis', 'career transition support', 'learning roadmap']),
                    'learning_roadmap' => $this->planSupportsFeature($planFeatureText, ['learning roadmap', 'career transition support', 'skill gap analysis']),
                    'course_recommendations' => $this->planSupportsFeature($planFeatureText, ['learning roadmap', 'online', 'skill gap analysis'])
                ];
                break;
            case 'interview_prep':
                $features = [
                    'mock_interview_questions' => $this->planSupportsFeature($planFeatureText, ['interview preparation', 'career chat', 'career acceleration']),
                    'answer_templates' => $this->planSupportsFeature($planFeatureText, ['interview preparation', 'career chat', 'career transition support']),
                    'company_insights' => $this->planSupportsFeature($planFeatureText, ['industry insights', 'priority job referrals', 'career acceleration'])
                ];
                break;
            case 'resume_optimization':
                $features = [
                    'resume_scorecard' => true,
                    'rewrite_suggestions' => true,
                    'positioning_tips' => true
                ];
                break;
            case 'salary_negotiation':
                $features = [
                    'market_positioning' => true,
                    'negotiation_script' => true,
                    'risk_flags' => true
                ];
                break;
            default:
                $features = [
                    'personalized_guidance' => true,
                    'next_step_nudge' => true
                ];
        }

        return $features;
    }

    protected function checkUsageLimit($userId)
    {
        $subscription = $this->subscriptionModel->getUserActiveSubscription($userId);
        
        if (!$subscription) {
            return [
                'allowed' => false,
                'message' => 'Premium subscription required for AI Career Mentor'
            ];
        }

        if ($subscription['chat_limit']) {
            $todayUsage = $this->usageModel->getTodayUsage($userId);
            if ($todayUsage >= $subscription['chat_limit']) {
                return [
                    'allowed' => false,
                    'message' => 'Daily chat limit reached. Upgrade for unlimited access.'
                ];
            }
        }

        return ['allowed' => true];
    }

    protected function getUserCareerProfile($userId)
    {
        $db = \Config\Database::connect();

        $profile = $db->table('users')
                     ->select('users.*, candidate_profiles.*')
                     ->join('candidate_profiles', 'candidate_profiles.user_id = users.id', 'left')
                     ->where('users.id', $userId)
                     ->get()
                     ->getRowArray();

        $skillsRow = $db->table('candidate_skills')
                        ->where('candidate_id', $userId)
                        ->get()
                        ->getRowArray();

        $skills = [];
        if (!empty($skillsRow['skill_name'])) {
            $skills = array_map('trim', explode(',', $skillsRow['skill_name']));
        }

        return [
            'current_role' => $profile['current_position'] ?? null,
            'target_role'  => $profile['preferred_job_title'] ?? null,
            'experience'   => $profile['total_experience'] ?? null,
            'skills'       => $skills
        ];
    }

    public function createCareerPlan()
    {
        $userId = session()->get('user_id');
        $targetRole = trim((string) $this->request->getPost('target_role'));
        $timeline = trim((string) $this->request->getPost('timeline'));
        $currentRole = trim((string) $this->request->getPost('current_role'));

        if ($targetRole === '' || $timeline === '') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Target role and timeline are required.'
            ])->setStatusCode(422);
        }

        // Generate comprehensive career plan using AI
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
        
        // Save premium career session
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
            (int) $userId,
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

    public function subscribe()
    {
        $planId = $this->request->getPost('plan_id');
        $userId = session()->get('user_id');

        $plan = $this->subscriptionModel->find($planId);
        
        if (!$plan) {
            return redirect()->back()->with('error', 'Invalid plan selected');
        }

        // In a real implementation, integrate with payment gateway
        // For now, we'll simulate successful payment
        
        $subscriptionData = [
            'user_id' => $userId,
            'plan_id' => $planId,
            'start_date' => date('Y-m-d'),
            'end_date' => date('Y-m-d', strtotime('+' . $plan['duration_days'] . ' days')),
            'amount_paid' => $plan['price'],
            'payment_id' => 'demo_' . uniqid(),
            'status' => 'active'
        ];

        if ($this->subscriptionModel->saveSubscription($subscriptionData)) {
            return redirect()->to('/candidate/dashboard')->with('success', 'Subscription activated successfully! You can now use AI Career Mentor in the chatbot.');
        }

        return redirect()->back()->with('error', 'Failed to activate subscription');
    }

    protected function generateCareerResponse($messages)
    {
        // Use the existing CareerTransitionAI library's OpenAI integration
        $apiKey = getenv('OPENAI_API_KEY');
        if (empty($apiKey)) {
            return 'I apologize, but I need an OpenAI API key to provide personalized career guidance. Please contact support.';
        }

        if (is_string($messages)) {
            $messages = [[
                'role' => 'system',
                'content' => 'You are an expert AI Career Mentor. Provide personalized, actionable career advice in a conversational tone. Be encouraging and specific.'
            ], [
                'role' => 'user',
                'content' => $messages
            ]];
        }
        
        $data = [
            'model' => 'gpt-4o-mini',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . trim($apiKey),
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return 'I apologize, but I\'m having trouble connecting to my AI systems right now. Please try again in a moment.';
        }

        $data = json_decode($response, true);
        return $data['choices'][0]['message']['content'] ?? 'I apologize, but I couldn\'t generate a response. Please try rephrasing your question.';
    }

    protected function getConversationHistory($userId)
    {
        if (!$this->canUseMentorMemory()) {
            return [];
        }

        $history = $this->mentorMessageModel->getRecentByUserId($userId, self::MAX_RECENT_MEMORY_MESSAGES);
        $history = array_reverse($history);

        return array_values(array_filter(array_map(static function ($item) {
            return [
                'role' => $item['role'] ?? 'user',
                'content' => $item['content'] ?? '',
                'session_id' => $item['session_id'] ?? null,
                'created_at' => $item['created_at'] ?? null,
            ];
        }, $history), static function ($item) {
            return trim((string) ($item['content'] ?? '')) !== '';
        }));
    }

    private function appendConversationMessage($userId, $sessionId, $role, $content)
    {
        if (!$this->canUseMentorMemory()) {
            return;
        }

        $this->mentorMessageModel->insert([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'role' => $role === 'assistant' ? 'assistant' : 'user',
            'content' => trim((string) $content),
            'compacted' => 0,
        ], true);
    }

    private function syncSmartGoalIfReady($userId, $sessionId, $latestUserMessage)
    {
        if (!$this->canUseCareerGoals()) {
            return false;
        }

        if (!$this->shouldAttemptSmartGoalExtraction($userId, $latestUserMessage)) {
            return false;
        }

        $history = $this->getConversationHistory($userId);
        $smartGoal = $this->extractSmartGoalsFromHistory($history);
        if ($smartGoal === null) {
            return false;
        }

        return $this->saveOrUpdateSmartGoal($userId, $sessionId, $smartGoal);
    }

    private function shouldAttemptSmartGoalExtraction($userId, $latestUserMessage)
    {
        $history = $this->getConversationHistory($userId);
        $userTurns = 0;
        foreach ($history as $item) {
            if (($item['role'] ?? '') === 'user' && !empty($item['content'])) {
                $userTurns++;
            }
        }

        if ($userTurns < 2) {
            return false;
        }

        $text = strtolower((string) $latestUserMessage);
        $keywords = [
            'goal', 'target', 'plan', 'roadmap', 'timeline', 'become',
            'transition', 'switch', 'career', 'learn', 'skill', 'month', 'week'
        ];

        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function extractSmartGoalsFromHistory($history)
    {
        $lines = [];
        foreach (array_slice($history, -12) as $item) {
            $role = ($item['role'] ?? '') === 'assistant' ? 'Mentor' : 'User';
            $content = trim((string) ($item['content'] ?? ''));
            if ($content === '') {
                continue;
            }
            $lines[] = $role . ': ' . $content;
        }

        if (empty($lines)) {
            return null;
        }

        $conversation = implode("\n", $lines);
        $prompt = "Extract a SMART career goal from this conversation.\n\n" .
            $conversation . "\n\n" .
            "Return ONLY valid JSON object (no markdown) in this exact shape:\n" .
            "{\n" .
            "  \"aspiration\": \"overall aspiration\",\n" .
            "  \"specific\": \"clear specific goal\",\n" .
            "  \"measurable\": [\"metric 1\", \"metric 2\"],\n" .
            "  \"achievable\": [\"step 1\", \"step 2\", \"step 3\"],\n" .
            "  \"realistic\": \"why this is realistic\",\n" .
            "  \"time_bound\": \"deadline or timeline\",\n" .
            "  \"current_skills\": [\"skill1\", \"skill2\"],\n" .
            "  \"target_skills\": [\"skill1\", \"skill2\"],\n" .
            "  \"progress_percentage\": 0\n" .
            "}\n\n" .
            "Rules:\n" .
            "- If data is missing, infer conservative placeholders, do not return null.\n" .
            "- progress_percentage must be an integer 0-100.\n" .
            "- measurable/achievable/current_skills/target_skills must be arrays.";

        $raw = $this->generateCareerResponse($prompt);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = $this->extractJsonObject($raw);
        }

        if (!is_array($decoded)) {
            return null;
        }

        if (empty($decoded['specific']) || empty($decoded['time_bound'])) {
            return null;
        }

        return [
            'aspiration' => trim((string) ($decoded['aspiration'] ?? 'Career growth')),
            'specific' => trim((string) ($decoded['specific'] ?? '')),
            'measurable' => $this->normalizeStringList($decoded['measurable'] ?? []),
            'achievable' => $this->normalizeStringList($decoded['achievable'] ?? []),
            'realistic' => trim((string) ($decoded['realistic'] ?? 'Aligned with current profile and effort.')),
            'time_bound' => trim((string) ($decoded['time_bound'] ?? 'Within 6 months')),
            'current_skills' => $this->normalizeStringList($decoded['current_skills'] ?? []),
            'target_skills' => $this->normalizeStringList($decoded['target_skills'] ?? []),
            'progress_percentage' => max(0, min(100, (int) ($decoded['progress_percentage'] ?? 0)))
        ];
    }

    private function saveOrUpdateSmartGoal($userId, $sessionId, $smartGoal)
    {
        try {
            $goalMap = session()->get('premium_mentor_goal_map') ?? [];
            $goalId = $goalMap[$sessionId] ?? null;
            $existing = null;

            if ($goalId) {
                $existing = $this->careerGoalModel
                    ->where('id', $goalId)
                    ->where('user_id', $userId)
                    ->first();
            }

            $payload = [
                'user_id' => $userId,
                'aspiration' => $smartGoal['aspiration'],
                'specific_goal' => $smartGoal['specific'],
                'measurable_criteria' => json_encode($smartGoal['measurable']),
                'achievable_steps' => json_encode($smartGoal['achievable']),
                'realistic_assessment' => $smartGoal['realistic'],
                'time_bound' => $smartGoal['time_bound'],
                'current_skills' => json_encode($smartGoal['current_skills']),
                'target_skills' => json_encode($smartGoal['target_skills']),
                'progress_percentage' => $smartGoal['progress_percentage'],
                'status' => 'active'
            ];

            if ($existing) {
                $this->careerGoalModel->update($existing['id'], $payload);
                return true;
            }

            $newGoalId = $this->careerGoalModel->insert($payload, true);
            if ($newGoalId) {
                $goalMap[$sessionId] = $newGoalId;
                session()->set('premium_mentor_goal_map', $goalMap);
                return true;
            }
        } catch (Throwable $e) {
            log_message('error', 'SMART goal sync failed: {error}', ['error' => $e->getMessage()]);
        }

        return false;
    }

    private function extractJsonObject($text)
    {
        $text = trim((string) $text);
        if (preg_match('/\{[\s\S]*\}/', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    private function normalizeStringList($value)
    {
        if (!is_array($value)) {
            return [];
        }

        $normalized = [];
        foreach ($value as $item) {
            $text = trim((string) $item);
            if ($text !== '') {
                $normalized[] = $text;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizePlanIdentity(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';
        $value = preg_replace('/\s+/', ' ', $value) ?? '';

        return trim($value);
    }

    private function syncSessionProgressTracking($userId, $chatSessionId, $latestUserMessage, $latestAssistantMessage, $userProfile)
    {
        if (!$this->canUseCareerGoals()) {
            return null;
        }

        if (!$this->shouldAttemptProgressUpdate($userId, $latestUserMessage)) {
            return null;
        }

        $goal = $this->getMappedGoalForChat($userId, $chatSessionId);
        if (!$goal) {
            return null;
        }

        $sessionRecord = $this->getOrCreateProgressSessionRecord($userId, $chatSessionId, $goal, $userProfile);
        if (!$sessionRecord) {
            return null;
        }

        $progressData = $this->buildProgressTrackingPayload($userId, $goal, $sessionRecord, $latestUserMessage, $latestAssistantMessage);
        if (!$progressData) {
            return null;
        }

        $this->sessionModel->updateProgress($sessionRecord['id'], $progressData);
        $this->careerGoalModel->updateProgress($goal['id'], (int) ($progressData['progress_percentage'] ?? 0));

        // Include the target DOM ID for the asynchronous UI update
        $progressData['plan_card_id'] = 'plan-' . $sessionRecord['id'];

        return $progressData;
    }

    private function shouldAttemptProgressUpdate($userId, $latestUserMessage)
    {
        $text = strtolower((string) $latestUserMessage);
        $keywords = [
            'done', 'completed', 'finished', 'started', 'progress',
            'achieved', 'milestone', 'stuck', 'blocked', 'next'
        ];
        foreach ($keywords as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }

        $history = $this->getConversationHistory($userId);
        $userTurns = 0;
        foreach ($history as $item) {
            if (($item['role'] ?? '') === 'user' && !empty($item['content'])) {
                $userTurns++;
            }
        }

        if ($userTurns < 3) {
            return false;
        }

        return ($userTurns % 2) === 0;
    }

    private function getMappedGoalForChat($userId, $chatSessionId)
    {
        if (!$this->canUseCareerGoals()) {
            return null;
        }

        if (preg_match('/^plan-(\d+)$/', (string) $chatSessionId, $matches) === 1) {
            $session = $this->sessionModel
                ->where('id', (int) $matches[1])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();
            if ($session) {
                $goal = $this->findMatchingGoalForSession($session, $this->careerGoalModel->getUserGoals($userId));
                if ($goal) {
                    return $goal;
                }
            }
        }

        $goalMap = session()->get('premium_mentor_goal_map') ?? [];
        $goalId = $goalMap[$chatSessionId] ?? null;

        if ($goalId) {
            $goal = $this->careerGoalModel
                ->where('id', $goalId)
                ->where('user_id', $userId)
                ->first();
            if ($goal) {
                return $goal;
            }
        }

        $goal = $this->careerGoalModel
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->orderBy('updated_at', 'DESC')
            ->first();
        if ($goal) {
            $goalMap[$chatSessionId] = $goal['id'];
            session()->set('premium_mentor_goal_map', $goalMap);
        }

        return $goal ?: null;
    }

    private function getOrCreateProgressSessionRecord($userId, $chatSessionId, $goal, $userProfile)
    {
        if (preg_match('/^plan-(\d+)$/', (string) $chatSessionId, $matches) === 1) {
            $existing = $this->sessionModel
                ->where('id', (int) $matches[1])
                ->where('user_id', $userId)
                ->where('status', 'active')
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $planMap = session()->get('premium_mentor_plan_map') ?? [];
        $planId = $planMap[$chatSessionId] ?? null;

        if ($planId) {
            $existing = $this->sessionModel
                ->where('id', $planId)
                ->where('user_id', $userId)
                ->first();
            if ($existing) {
                return $existing;
            }
        }

        $currentRole = $this->truncateValue((string) ($userProfile['current_role'] ?? ''), 100);
        $targetRole = $this->truncateValue((string) ($goal['aspiration'] ?? $goal['specific_goal'] ?? 'Career Goal'), 100);
        $timeline = $this->truncateValue((string) ($goal['time_bound'] ?? 'Ongoing'), 50);

        $existing = $this->sessionModel->findSimilarActiveSession(
            (int) $userId,
            'goal_review',
            $currentRole,
            $targetRole,
            $timeline
        );
        if ($existing) {
            $planMap[$chatSessionId] = (int) ($existing['id'] ?? 0);
            session()->set('premium_mentor_plan_map', $planMap);

            return $existing;
        }

        $initialProgress = [
            'progress_percentage' => max(self::INITIAL_PLAN_PROGRESS, (int) ($goal['progress_percentage'] ?? 0)),
            'completed_milestones' => [],
            'next_milestones' => $this->normalizeStringList(json_decode((string) ($goal['achievable_steps'] ?? '[]'), true)),
            'last_nudge' => 'Pick one small step this week and mark it done.',
            'updated_at' => date('c')
        ];

        $newId = $this->sessionModel->insert([
            'user_id' => $userId,
            'session_type' => 'goal_review',
            'current_role' => $currentRole,
            'target_role' => $targetRole,
            'timeline' => $timeline,
            'action_plan' => $goal['achievable_steps'] ?? json_encode([]),
            'progress_tracking' => json_encode($initialProgress),
            'status' => 'active'
        ], true);

        if (!$newId) {
            return null;
        }

        $planMap[$chatSessionId] = $newId;
        session()->set('premium_mentor_plan_map', $planMap);

        return $this->sessionModel->find($newId);
    }

    private function buildProgressTrackingPayload($userId, $goal, $sessionRecord, $latestUserMessage, $latestAssistantMessage)
    {
        $current = json_decode((string) ($sessionRecord['progress_tracking'] ?? ''), true);
        if (!is_array($current)) {
            $current = [
                'progress_percentage' => max(self::INITIAL_PLAN_PROGRESS, (int) ($goal['progress_percentage'] ?? 0)),
                'completed_milestones' => [],
                'next_milestones' => [],
                'last_nudge' => ''
            ];
        }

        $history = $this->getConversationHistory($userId);
        $lines = [];
        foreach (array_slice($history, -12) as $item) {
            $speaker = ($item['role'] ?? '') === 'assistant' ? 'Mentor' : 'User';
            $content = trim((string) ($item['content'] ?? ''));
            if ($content !== '') {
                $lines[] = $speaker . ': ' . $content;
            }
        }

        $prompt = "You are updating career plan progress.\n" .
            "Current SMART goal:\n" .
            "- Aspiration: " . ($goal['aspiration'] ?? 'Career growth') . "\n" .
            "- Specific Goal: " . ($goal['specific_goal'] ?? '') . "\n" .
            "- Timeline: " . ($goal['time_bound'] ?? 'Not specified') . "\n" .
            "- Achievable Steps: " . implode(', ', $this->normalizeStringList(json_decode((string) ($goal['achievable_steps'] ?? '[]'), true))) . "\n\n" .
            "Current tracking JSON:\n" . json_encode($current) . "\n\n" .
            "Recent conversation:\n" . implode("\n", $lines) . "\n\n" .
            "Latest user update: " . $latestUserMessage . "\n" .
            "Latest mentor response: " . $latestAssistantMessage . "\n\n" .
            "Return ONLY valid JSON object:\n" .
            "{\n" .
            "  \"progress_percentage\": 0,\n" .
            "  \"completed_milestones\": [\"...\"],\n" .
            "  \"next_milestones\": [\"...\"],\n" .
            "  \"last_nudge\": \"one short actionable nudge for the immediate next task\"\n" .
            "}\n" .
            "Rules: progress_percentage integer 0-100, milestones concise, keep continuity.";

        $raw = $this->generateCareerResponse($prompt);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = $this->extractJsonObject($raw);
        }

        if (!is_array($decoded)) {
            return $this->fallbackProgressPayload($current, $latestUserMessage);
        }

       return [
            'progress_percentage' => max(0, min(100, (int) ($decoded['progress_percentage'] ?? ($current['progress_percentage'] ?? 0)))),
            'completed_milestones' => $this->normalizeStringList($decoded['completed_milestones'] ?? ($current['completed_milestones'] ?? [])),
            'next_milestones' => $this->normalizeStringList($decoded['next_milestones'] ?? ($current['next_milestones'] ?? [])),
            'last_nudge' => trim((string) ($decoded['last_nudge'] ?? ($current['last_nudge'] ?? 'Keep going, one focused step at a time.'))),
            'updated_at' => date('c')
        ];

        
    }

    private function fallbackProgressPayload($current, $latestUserMessage)
    {
        return $current; // No change if AI fails to parse, prevents false progress inflation
    }

    protected function hydrateSessionProgress($sessions, $userId)
    {
        if (!is_array($sessions)) {
            return [];
        }

        $activeGoals = $this->canUseCareerGoals() ? $this->careerGoalModel->getUserGoals($userId) : [];
        $recentMessages = $this->canUseMentorMemory()
            ? $this->mentorMessageModel->getRecentByUserId($userId, 30)
            : [];

        foreach ($sessions as &$session) {
            $tracking = json_decode((string) ($session['progress_tracking'] ?? ''), true);
            if (!is_array($tracking)) {
                $tracking = [];
            }

            $matchedGoal = $this->findMatchingGoalForSession($session, $activeGoals);
            if ($this->shouldBackfillLegacyProgress($tracking, $matchedGoal, $recentMessages)) {
                $tracking = $this->buildBackfilledProgressTracking($tracking, $matchedGoal, $recentMessages);
                if (!empty($session['id'])) {
                    $this->sessionModel->updateProgress($session['id'], $tracking);
                }
            }

            $session['progress_percentage'] = max(0, min(100, (int) ($tracking['progress_percentage'] ?? 0)));
            $session['last_nudge'] = trim((string) ($tracking['last_nudge'] ?? ''));
            $session['next_milestones'] = $this->normalizeStringList($tracking['next_milestones'] ?? []);
            $session['main_goal_text'] = $this->resolveSessionMainGoalText($session, $activeGoals);
            $session['timeline_label'] = $this->formatTimelineLabel($session, $session['main_goal_text']);
        }

        return $this->deduplicateActiveSessions($sessions);
    }

    private function deduplicateActiveSessions(array $sessions): array
    {
        $unique = [];

        foreach ($sessions as $session) {
            $key = $this->buildSessionDisplayKey($session);
            if ($key === '') {
                $unique[] = $session;
                continue;
            }

            if (!isset($unique[$key]) || $this->isBetterDisplaySession($session, $unique[$key])) {
                $unique[$key] = $session;
            }
        }

        return array_values($unique);
    }

    private function buildSessionDisplayKey(array $session): string
    {
        $goalText = $this->normalizePlanIdentity((string) ($session['main_goal_text'] ?? ''));
        $targetRole = $this->normalizePlanIdentity((string) ($session['target_role'] ?? ''));

        // Exclude timeline from the key to ensure that plans for the same role and goal 
        // are deduplicated even if the AI adjusts the timeline estimate during the conversation.
        $parts = array_filter([$targetRole, $goalText], static fn ($part) => $part !== '');

        return implode('|', $parts);
    }

    private function isBetterDisplaySession(array $candidate, array $current): bool
    {
        $candidateProgress = (int) ($candidate['progress_percentage'] ?? 0);
        $currentProgress = (int) ($current['progress_percentage'] ?? 0);
        if ($candidateProgress !== $currentProgress) {
            return $candidateProgress > $currentProgress;
        }

        $candidateMilestones = count($candidate['next_milestones'] ?? []);
        $currentMilestones = count($current['next_milestones'] ?? []);
        if ($candidateMilestones !== $currentMilestones) {
            return $candidateMilestones > $currentMilestones;
        }

        return strtotime((string) ($candidate['updated_at'] ?? $candidate['created_at'] ?? '')) >
            strtotime((string) ($current['updated_at'] ?? $current['created_at'] ?? ''));
    }

    private function shouldBackfillLegacyProgress($tracking, $matchedGoal, $recentMessages)
    {
        $currentProgress = (int) ($tracking['progress_percentage'] ?? 0);
        if ($currentProgress > 0) {
            return false;
        }

        if (is_array($matchedGoal) && ((int) ($matchedGoal['progress_percentage'] ?? 0)) > 0) {
            return true;
        }

        foreach ($recentMessages as $message) {
            $content = trim((string) ($message['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            if ($this->isCompletedMilestoneMessage($content) || $this->isStartedMilestoneMessage($content)) {
                return true;
            }
        }

        return false;
    }

    private function buildBackfilledProgressTracking($tracking, $matchedGoal, $recentMessages)
    {
        if (!is_array($tracking)) {
            $tracking = [];
        }

        $progress = max(self::INITIAL_PLAN_PROGRESS, (int) ($tracking['progress_percentage'] ?? 0));
        $nextMilestones = $this->normalizeStringList($tracking['next_milestones'] ?? []);
        $completedMilestones = $this->normalizeStringList($tracking['completed_milestones'] ?? []);
        $lastNudge = trim((string) ($tracking['last_nudge'] ?? ''));

        if (is_array($matchedGoal)) {
            $progress = max($progress, (int) ($matchedGoal['progress_percentage'] ?? 0), self::INITIAL_PLAN_PROGRESS);
            if (empty($nextMilestones)) {
                $nextMilestones = $this->normalizeStringList(json_decode((string) ($matchedGoal['achievable_steps'] ?? '[]'), true));
            }
        }

        $startedCount = 0;
        $completedCount = 0;
        foreach ($recentMessages as $message) {
            $content = trim((string) ($message['content'] ?? ''));
            if ($content === '') {
                continue;
            }

            if ($this->isCompletedMilestoneMessage($content)) {
                $completedCount++;
            } elseif ($this->isStartedMilestoneMessage($content)) {
                $startedCount++;
            }
        }

        if ($completedCount > 0) {
            $progress = max($progress, min(100, self::INITIAL_PLAN_PROGRESS + ($completedCount * self::COMPLETED_MILESTONE_PROGRESS_STEP)));
        } elseif ($startedCount > 0) {
            $progress = max($progress, min(100, self::INITIAL_PLAN_PROGRESS + ($startedCount * self::STARTED_MILESTONE_PROGRESS_STEP)));
        }

        if ($lastNudge === '') {
            $lastNudge = $completedCount > 0
                ? 'Good progress so far. Keep moving to the next milestone.'
                : 'You already started momentum here. Continue with the next milestone this week.';
        }

        return [
            'progress_percentage' => max(self::INITIAL_PLAN_PROGRESS, min(100, $progress)),
            'completed_milestones' => $completedMilestones,
            'next_milestones' => $nextMilestones,
            'last_nudge' => $lastNudge,
            'updated_at' => date('c')
        ];
    }

    private function findMatchingGoalForSession($session, $activeGoals)
    {
        $targetRole = strtolower(trim((string) ($session['target_role'] ?? '')));

        foreach ($activeGoals as $goal) {
            $aspiration = strtolower(trim((string) ($goal['aspiration'] ?? '')));
            $specificGoal = strtolower(trim((string) ($goal['specific_goal'] ?? '')));

            if ($targetRole !== '' && $aspiration !== '' && strpos($aspiration, $targetRole) !== false) {
                return $goal;
            }

            if ($targetRole !== '' && $specificGoal !== '' && strpos($specificGoal, $targetRole) !== false) {
                return $goal;
            }
        }

        return $activeGoals[0] ?? null;
    }

    private function resolveSessionMainGoalText($session, $activeGoals)
    {
        $targetRole = strtolower(trim((string) ($session['target_role'] ?? '')));
        $currentRole = trim((string) ($session['current_role'] ?? ''));

        foreach ($activeGoals as $goal) {
            $aspiration = strtolower(trim((string) ($goal['aspiration'] ?? '')));
            $specificGoal = trim((string) ($goal['specific_goal'] ?? ''));

            if ($specificGoal === '') {
                continue;
            }

            if ($targetRole !== '' && ($aspiration !== '' && strpos($aspiration, $targetRole) !== false)) {
                return $specificGoal;
            }

            if ($targetRole !== '' && stripos($specificGoal, $targetRole) !== false) {
                return $specificGoal;
            }
        }

        if ($currentRole !== '' && $targetRole !== '') {
            return 'Transition from ' . $currentRole . ' to ' . ($session['target_role'] ?? 'your target role');
        }

        if ($targetRole !== '') {
            return 'Work toward becoming a ' . ($session['target_role'] ?? 'target professional');
        }

        return 'Continue progressing on your active career plan';
    }

    private function formatTimelineLabel($session, $mainGoalText)
    {
        $timeline = trim((string) ($session['timeline'] ?? ''));
        if ($timeline === '') {
            return 'Timeline to complete this plan will be refined with your mentor.';
        }

        $normalizedTimeline = $this->normalizeTimelinePhrase($timeline);
        if ($this->isTimelineDurationOnly($normalizedTimeline)) {
            return 'Timeline: ' . $normalizedTimeline;
        }

        return 'Timeline: ' . $normalizedTimeline;
    }

    private function normalizeTimelinePhrase($timeline)
    {
        $timeline = trim((string) $timeline);
        if ($timeline === '') {
            return '';
        }

        $timeline = preg_replace('/\s+/', ' ', $timeline);

        if (preg_match('/(?:within|in)\s+(\d+\s+(?:day|days|week|weeks|month|months|year|years))/i', $timeline, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/(\d+\s+(?:day|days|week|weeks|month|months|year|years))/i', $timeline, $matches)) {
            return trim($matches[1]);
        }

        return $timeline;
    }

    private function isTimelineDurationOnly($timeline)
    {
        return (bool) preg_match('/^\d+\s+(day|days|week|weeks|month|months|year|years)$/i', trim((string) $timeline));
    }

    

    private function isStartedMilestoneMessage($message)
    {
        $text = strtolower(trim((string) $message));
        foreach (['started', 'starting', 'began', 'begin', 'working on', 'enrolled', 'trying'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function isCompletedMilestoneMessage($message)
    {
        $text = strtolower(trim((string) $message));
        foreach (['done', 'completed', 'finished', 'achieved', 'submitted', 'built', 'solved'] as $keyword) {
            if (strpos($text, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function truncateValue($value, $limit)
    {
        $value = trim((string) $value);
        if (strlen($value) <= $limit) {
            return $value;
        }

        return substr($value, 0, $limit);
    }

    private function canUseCareerGoals()
    {
        if ($this->careerGoalsFeatureEnabled !== null) {
            return $this->careerGoalsFeatureEnabled;
        }

        try {
            $db = \Config\Database::connect();
            $this->careerGoalsFeatureEnabled = $db->tableExists('career_goals');
        } catch (Throwable $e) {
            $this->careerGoalsFeatureEnabled = false;
            log_message('error', 'career_goals availability check failed: {error}', ['error' => $e->getMessage()]);
            return false;
        }

        if (!$this->careerGoalsFeatureEnabled) {
            log_message('warning', 'career_goals table missing; SMART goal persistence is disabled for this request.');
        }

        return $this->careerGoalsFeatureEnabled;
    }

    private function canUseMentorMemory()
    {
        if ($this->mentorMemoryFeatureEnabled !== null) {
            return $this->mentorMemoryFeatureEnabled;
        }

        try {
            $db = \Config\Database::connect();
            $this->mentorMemoryFeatureEnabled = $db->tableExists('premium_mentor_memories') && $db->tableExists('premium_mentor_messages');
        } catch (Throwable $e) {
            $this->mentorMemoryFeatureEnabled = false;
            log_message('error', 'premium mentor memory availability check failed: {error}', ['error' => $e->getMessage()]);
            return false;
        }

        if (!$this->mentorMemoryFeatureEnabled) {
            log_message('warning', 'premium mentor memory tables missing; persistent mentor continuity is disabled for this request.');
        }

        return $this->mentorMemoryFeatureEnabled;
    }

    private function getPersistentMentorMemory($userId)
    {
        if (!$this->canUseMentorMemory()) {
            return null;
        }

        return $this->mentorMemoryModel->getByUserId($userId);
    }

    private function buildPersistentMemoryInstructions($mentorMemory)
    {
        if (!is_array($mentorMemory) || trim((string) ($mentorMemory['memory_summary'] ?? '')) === '') {
            return 'Long-term mentor memory summary: none yet. Learn durable facts about the candidate and keep continuity.';
        }

        $summary = trim((string) $mentorMemory['memory_summary']);
        $keyFactsList = json_decode((string) ($mentorMemory['key_facts'] ?? '[]'), true);
        $keyFacts = $this->normalizeStringList($keyFactsList);

        $block = "Long-term mentor memory summary:\n" . $summary;
        if (!empty($keyFacts)) {
            $block .= "\n\nKey facts:\n- " . implode("\n- ", $keyFacts);
        }

        return $block;
    }

    private function compactMentorMemoryIfNeeded($userId, $userProfile)
    {
        if (!$this->canUseMentorMemory()) {
            return;
        }

        $messages = $this->mentorMessageModel->getUncompactedByUserId($userId, self::MAX_CONVERSATION_MESSAGES);
        if (count($messages) < self::MEMORY_COMPACTION_THRESHOLD) {
            return;
        }

        $existingMemory = $this->getPersistentMentorMemory($userId);
        $summary = $this->summarizeMentorMessages($messages, $existingMemory, $userProfile);
        if ($summary === null) {
            return;
        }

        $payload = [
            'user_id' => $userId,
            'memory_summary' => $summary['memory_summary'],
            'key_facts' => $summary['key_facts'],
            'last_compacted_at' => date('Y-m-d H:i:s'),
        ];

        if ($existingMemory) {
            $this->mentorMemoryModel->update($existingMemory['id'], $payload);
        } else {
            $this->mentorMemoryModel->insert($payload, true);
        }

        foreach ($messages as $message) {
            $this->mentorMessageModel->update($message['id'], ['compacted' => 1]);
        }
    }

    private function summarizeMentorMessages($messages, $existingMemory, $userProfile)
    {
        $conversationLines = [];
        foreach ($messages as $message) {
            $speaker = ($message['role'] ?? '') === 'assistant' ? 'Mentor' : 'Candidate';
            $content = trim((string) ($message['content'] ?? ''));
            if ($content !== '') {
                $conversationLines[] = $speaker . ': ' . $content;
            }
        }

        if (empty($conversationLines)) {
            return null;
        }

        $profileFacts = [
            'Current Role: ' . ($userProfile['current_role'] ?? 'Not specified'),
            'Target Role: ' . ($userProfile['target_role'] ?? 'Not specified'),
            'Experience: ' . ($userProfile['experience'] ?? 'Not specified'),
            'Skills: ' . implode(', ', $userProfile['skills'] ?? []),
        ];

        $prompt = "You are compacting long-term mentor memory for one candidate.\n\n" .
            "Existing memory summary:\n" . trim((string) ($existingMemory['memory_summary'] ?? 'None')) . "\n\n" .
            "Candidate profile:\n- " . implode("\n- ", $profileFacts) . "\n\n" .
            "New conversation turns:\n" . implode("\n", $conversationLines) . "\n\n" .
            "Return ONLY valid JSON object:\n" .
            "{\n" .
            "  \"memory_summary\": \"short durable summary for future mentoring continuity\",\n" .
            "  \"key_facts\": [\"fact 1\", \"fact 2\", \"fact 3\"]\n" .
            "}\n\n" .
            "Rules:\n" .
            "- Capture durable context, not every detail.\n" .
            "- Include goals, blockers, strengths, commitments, and progress.\n" .
            "- Keep memory_summary under 180 words.\n" .
            "- key_facts must be concise.";

        $raw = $this->generateCareerResponse($prompt);
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $decoded = $this->extractJsonObject($raw);
        }

        if (!is_array($decoded) || empty($decoded['memory_summary'])) {
            return null;
        }

        return [
            'memory_summary' => trim((string) $decoded['memory_summary']),
            'key_facts' => json_encode($this->normalizeStringList($decoded['key_facts'] ?? [])),
        ];
    }

    private function buildFeatureExecutionInstructions($premiumFeatures)
    {
        $instructions = [];

        if (!empty($premiumFeatures['smart_goals_generator'])) {
            $instructions[] = 'If goals are discussed, include a SMART goal draft with Specific, Measurable, Achievable, Realistic, and Time-bound wording.';
        }
        if (!empty($premiumFeatures['milestone_tracker'])) {
            $instructions[] = 'Include 3 concrete milestones the user can complete in sequence.';
        }
        if (!empty($premiumFeatures['progress_analytics'])) {
            $instructions[] = 'Add a short progress check note describing how to measure improvement over time.';
        }
        if (!empty($premiumFeatures['skill_gap_analysis'])) {
            $instructions[] = 'Explicitly separate current strengths from skill gaps.';
        }
        if (!empty($premiumFeatures['learning_roadmap'])) {
            $instructions[] = 'Provide a phased learning roadmap ordered from immediate to advanced steps.';
        }
        if (!empty($premiumFeatures['course_recommendations'])) {
            $instructions[] = 'Recommend 2 or 3 learning resource types or course topics, not generic study advice.';
        }
        if (!empty($premiumFeatures['mock_interview_questions'])) {
            $instructions[] = 'Include 3 mock interview questions tailored to the target role.';
        }
        if (!empty($premiumFeatures['answer_templates'])) {
            $instructions[] = 'Include one concise answer template the user can adapt.';
        }
        if (!empty($premiumFeatures['company_insights'])) {
            $instructions[] = 'Add a short note on what hiring teams or HR typically evaluate for this topic.';
        }
        if (!empty($premiumFeatures['resume_scorecard'])) {
            $instructions[] = 'Score the resume topic across clarity, relevance, and impact in a simple 10-point style.';
        }
        if (!empty($premiumFeatures['rewrite_suggestions'])) {
            $instructions[] = 'Give 2 rewritten example lines or bullets where useful.';
        }
        if (!empty($premiumFeatures['positioning_tips'])) {
            $instructions[] = 'Explain how to position the user for the target role.';
        }
        if (!empty($premiumFeatures['market_positioning'])) {
            $instructions[] = 'Explain likely market positioning and negotiation leverage factors.';
        }
        if (!empty($premiumFeatures['negotiation_script'])) {
            $instructions[] = 'Include a short salary negotiation script.';
        }
        if (!empty($premiumFeatures['risk_flags'])) {
            $instructions[] = 'Mention common negotiation mistakes or red flags to avoid.';
        }
        if (!empty($premiumFeatures['next_step_nudge'])) {
            $instructions[] = 'End with one concrete next action the user can take today.';
        }

        if (empty($instructions)) {
            return 'Respond with clear, personalized guidance.';
        }

        return "Execution requirements:\n- " . implode("\n- ", $instructions);
    }

    private function decorateResponseWithPremiumFeatures($message, $premiumFeatures, $userProfile, $progressUpdate)
    {
        $activeFeatures = array_keys(array_filter($premiumFeatures));
        if (empty($activeFeatures)) {
            return $message;
        }

        $notes = [];

        if (!empty($premiumFeatures['skill_gap_analysis']) && !empty($userProfile['skills'])) {
            $notes[] = 'Current strengths to build on: ' . implode(', ', array_slice($userProfile['skills'], 0, 3));
        }

        if (!empty($premiumFeatures['progress_analytics']) && is_array($progressUpdate) && isset($progressUpdate['progress_percentage'])) {
            $notes[] = 'Progress tracker: ' . (int) $progressUpdate['progress_percentage'] . '% toward your current plan.';
        }

        if (!empty($premiumFeatures['milestone_tracker']) && is_array($progressUpdate) && !empty($progressUpdate['next_milestones'])) {
            $notes[] = 'Next milestone: ' . $progressUpdate['next_milestones'][0];
        }

        if (empty($notes)) {
            return $message;
        }

        return rtrim($message) . "\n\nPremium Focus:\n- " . implode("\n- ", $notes);
    }

    private function planSupportsFeature($planFeatureText, $keywords)
    {
        if ($planFeatureText === '') {
            return true;
        }

        foreach ($keywords as $keyword) {
            if (strpos($planFeatureText, strtolower($keyword)) !== false) {
                return true;
            }
        }

        return false;
    }
}
