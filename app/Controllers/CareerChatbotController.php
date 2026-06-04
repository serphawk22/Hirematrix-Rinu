<?php

namespace App\Controllers;

use App\Models\CareerGoalModel;
use App\Models\MentorModel;
use App\Models\CareerConversationModel;
use App\Libraries\AILibrary;

class CareerChatbotController extends BaseController
{
    protected $careerGoalModel;
    protected $mentorModel;
    protected $conversationModel;
    protected $aiLibrary;

    public function __construct()
    {
        $this->careerGoalModel = new CareerGoalModel();
        $this->mentorModel = new MentorModel();
        $this->conversationModel = new CareerConversationModel();
        $this->aiLibrary = new AILibrary();
    }

    public function index()
    {
        $data = [
            'title' => 'Career Guidance Chatbot',
            'user_goals' => $this->careerGoalModel->where('user_id', session()->get('user_id'))->findAll()
        ];
        return view('chatbot/career_chat', $data);
    }

    public function chat()
    {
        $message = $this->request->getPost('message');
        $sessionId = $this->request->getPost('session_id') ?: uniqid();
        $userId = session()->get('user_id');

        if (!$userId) {
            return $this->response->setJSON(['error' => 'Please login to continue']);
        }

        // Save user message
        $this->conversationModel->save([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'message' => $message,
            'response' => '',
            'message_type' => 'user'
        ]);

        // Get conversation context
        $context = $this->getConversationContext($sessionId);
        
        // Process message and generate response
        $response = $this->processCareerMessage($message, $context, $userId);
        
        // Save bot response
        $this->conversationModel->save([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'message' => '',
            'response' => $response['message'],
            'message_type' => 'bot',
            'stage' => $response['stage']
        ]);

        return $this->response->setJSON([
            'response' => $response['message'],
            'stage' => $response['stage'],
            'session_id' => $sessionId,
            'smart_goals' => $response['smart_goals'] ?? null,
            'mentors' => $response['mentors'] ?? []
        ]);
    }

    private function processCareerMessage($message, $context, $userId)
    {
        $stage = $this->determineConversationStage($context);
        
        switch ($stage) {
            case 'discovery':
                return $this->handleDiscoveryStage($message, $context);
            case 'goal_setting':
                return $this->handleGoalSettingStage($message, $context, $userId);
            case 'mentor_matching':
                return $this->handleMentorMatchingStage($message, $context);
            default:
                return $this->handleInitialMessage($message);
        }
    }

    private function handleInitialMessage($message)
    {
        $prompt = "You are a career guidance counselor. The user said: '{$message}'. 
                   Start a conversation to understand their career aspirations. Ask about:
                   - What they want to become
                   - Their current role/situation
                   - What motivates them
                   Keep it conversational and encouraging. Max 2-3 sentences.";

        $response = $this->aiLibrary->generateResponse($prompt);

        return [
            'message' => $response,
            'stage' => 'discovery'
        ];
    }

    private function handleDiscoveryStage($message, $context)
    {
        $conversationHistory = implode("\n", array_map(function($msg) {
            return "User: {$msg['message']}\nBot: {$msg['response']}";
        }, $context));

        $prompt = "Based on this conversation:\n{$conversationHistory}\n\nUser just said: '{$message}'
                   
                   If you have enough information about their career aspiration, ask them to be more specific about their goals.
                   If not, continue exploring their interests, current skills, and motivations.
                   
                   Look for signs they're ready to set SMART goals (Specific, Measurable, Achievable, Realistic, Time-bound).
                   Keep responses encouraging and max 3 sentences.";

        $response = $this->aiLibrary->generateResponse($prompt);

        // Check if ready for goal setting
        $readyForGoals = $this->isReadyForGoalSetting($conversationHistory . "\n" . $message);

        return [
            'message' => $response,
            'stage' => $readyForGoals ? 'goal_setting' : 'discovery'
        ];
    }

    private function handleGoalSettingStage($message, $context, $userId)
    {
        $conversationHistory = implode("\n", array_map(function($msg) {
            return "User: {$msg['message']}\nBot: {$msg['response']}";
        }, $context));

        // Extract SMART goals
        $smartGoals = $this->extractSmartGoals($conversationHistory . "\nUser: " . $message);

        if ($smartGoals) {
            // Save goals to database
            $this->careerGoalModel->save([
                'user_id' => $userId,
                'aspiration' => $smartGoals['aspiration'],
                'specific_goal' => $smartGoals['specific'],
                'measurable_criteria' => json_encode($smartGoals['measurable']),
                'achievable_steps' => json_encode($smartGoals['achievable']),
                'realistic_assessment' => $smartGoals['realistic'],
                'time_bound' => $smartGoals['time_bound'],
                'current_skills' => json_encode($smartGoals['current_skills'] ?? []),
                'target_skills' => json_encode($smartGoals['target_skills'] ?? [])
            ]);

            // Find relevant mentors
            $mentors = $this->findRelevantMentors($smartGoals['aspiration']);

            $response = "Perfect! I've created your SMART career goal:\n\n" .
                       "🎯 **Specific**: {$smartGoals['specific']}\n" .
                       "📊 **Measurable**: " . implode(', ', $smartGoals['measurable']) . "\n" .
                       "✅ **Achievable**: " . implode(', ', $smartGoals['achievable']) . "\n" .
                       "🎯 **Realistic**: {$smartGoals['realistic']}\n" .
                       "⏰ **Time-bound**: {$smartGoals['time_bound']}\n\n" .
                       "Would you like to connect with a mentor to accelerate your progress?";

            return [
                'message' => $response,
                'stage' => 'mentor_matching',
                'smart_goals' => $smartGoals,
                'mentors' => $mentors
            ];
        }

        // Continue refining goals
        $prompt = "Help the user create SMART goals based on: {$conversationHistory}\nUser: {$message}
                   
                   Ask specific questions to make their goal:
                   - Specific (what exactly do they want to achieve?)
                   - Measurable (how will they track progress?)
                   - Achievable (what steps are needed?)
                   - Realistic (considering their background?)
                   - Time-bound (by when?)";

        $response = $this->aiLibrary->generateResponse($prompt);

        return [
            'message' => $response,
            'stage' => 'goal_setting'
        ];
    }

    private function handleMentorMatchingStage($message, $context)
    {
        if (stripos($message, 'yes') !== false || stripos($message, 'mentor') !== false) {
            return [
                'message' => "Great! I've found some mentors who can help you achieve your goals. You can book a session with any of them below. They offer personalized guidance, skill development plans, and career strategy sessions.",
                'stage' => 'mentor_matching'
            ];
        }

        return [
            'message' => "No problem! Your goals are saved in your profile. You can always come back to connect with a mentor when you're ready. Good luck with your career journey! 🚀",
            'stage' => 'completed'
        ];
    }

    private function extractSmartGoals($conversation)
    {
        $prompt = "Extract SMART goals from this conversation: {$conversation}
                   
                   Return JSON format:
                   {
                     \"aspiration\": \"overall career aspiration\",
                     \"specific\": \"specific goal statement\",
                     \"measurable\": [\"measurable criteria 1\", \"criteria 2\"],
                     \"achievable\": [\"step 1\", \"step 2\", \"step 3\"],
                     \"realistic\": \"realistic assessment\",
                     \"time_bound\": \"timeline\",
                     \"current_skills\": [\"skill1\", \"skill2\"],
                     \"target_skills\": [\"skill1\", \"skill2\"]
                   }
                   
                   Only return valid JSON. If insufficient information, return null.";

        $response = $this->aiLibrary->generateResponse($prompt);
        
        // Try to parse JSON response
        $goals = json_decode($response, true);
        return $goals && isset($goals['specific']) ? $goals : null;
    }

    private function findRelevantMentors($aspiration)
    {
        // Simple keyword matching - can be enhanced with AI
        $keywords = explode(' ', strtolower($aspiration));
        
        $mentors = $this->mentorModel->where('status', 'active')->findAll();
        
        $relevantMentors = [];
        foreach ($mentors as $mentor) {
            $mentorText = strtolower($mentor['expertise'] . ' ' . implode(' ', json_decode($mentor['specializations'], true)));
            
            $relevanceScore = 0;
            foreach ($keywords as $keyword) {
                if (strlen($keyword) > 3 && stripos($mentorText, $keyword) !== false) {
                    $relevanceScore++;
                }
            }
            
            if ($relevanceScore > 0) {
                $mentor['relevance_score'] = $relevanceScore;
                $relevantMentors[] = $mentor;
            }
        }
        
        // Sort by relevance and return top 3
        usort($relevantMentors, function($a, $b) {
            return $b['relevance_score'] - $a['relevance_score'];
        });
        
        return array_slice($relevantMentors, 0, 3);
    }

    private function getConversationContext($sessionId)
    {
        return $this->conversationModel
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }

    private function determineConversationStage($context)
    {
        if (empty($context)) return 'discovery';
        
        $lastMessage = end($context);
        return $lastMessage['stage'] ?? 'discovery';
    }

    private function isReadyForGoalSetting($conversation)
    {
        // Simple heuristic - can be enhanced with AI
        $indicators = ['want to become', 'goal is', 'achieve', 'transition to', 'learn', 'get job'];
        
        foreach ($indicators as $indicator) {
            if (stripos($conversation, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }

    public function bookMentor()
    {
        $mentorId = $this->request->getPost('mentor_id');
        $goalId = $this->request->getPost('goal_id');
        $sessionType = $this->request->getPost('session_type');
        $scheduledAt = $this->request->getPost('scheduled_at');
        
        // Redirect to booking page or handle booking logic
        return redirect()->to("/mentoring/book/{$mentorId}?goal_id={$goalId}&type={$sessionType}");
    }
}