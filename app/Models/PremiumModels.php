<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table = 'subscription_plans';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'name', 'description', 'price', 'duration_days', 'features',
        'chat_limit', 'mentor_sessions_included', 'priority_support', 'status'
    ];

    public function getActivePlans()
    {
        return $this->where('status', 'active')
                   ->orderBy('price', 'ASC')
                   ->findAll();
    }

    public function getUserActiveSubscription($userId)
    {
        $db = \Config\Database::connect();
        
        return $db->table('user_subscriptions us')
                  ->select('us.*, sp.name as plan_name, sp.features, sp.chat_limit, sp.mentor_sessions_included')
                  ->join('subscription_plans sp', 'sp.id = us.plan_id')
                  ->where('us.user_id', $userId)
                  ->where('us.status', 'active')
                  ->where('us.end_date >=', date('Y-m-d'))
                  ->orderBy('us.end_date', 'DESC')
                  ->get()
                  ->getRowArray();
    }

    public function saveSubscription($data)
    {
        $db = \Config\Database::connect();
        return $db->table('user_subscriptions')->insert($data);
    }
}

class ChatbotUsageModel extends Model
{
    protected $table = 'chatbot_usage';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'session_id', 'message_count', 'feature_used', 'date'
    ];

    public function trackUsage($userId, $sessionId, $featureUsed = null)
    {
        $today = date('Y-m-d');
        
        $existing = $this->where([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'date' => $today
        ])->first();

        if ($existing) {
            $this->update($existing['id'], [
                'message_count' => $existing['message_count'] + 1,
                'feature_used' => $featureUsed
            ]);
        } else {
            $this->save([
                'user_id' => $userId,
                'session_id' => $sessionId,
                'message_count' => 1,
                'feature_used' => $featureUsed,
                'date' => $today
            ]);
        }
    }

    public function getTodayUsage($userId)
    {
        return $this->where([
            'user_id' => $userId,
            'date' => date('Y-m-d')
        ])->countAllResults();
    }

    public function getMonthlyUsage($userId, $month = null)
    {
        $month = $month ?: date('Y-m');
        
        return $this->where('user_id', $userId)
                   ->like('date', $month)
                   ->countAllResults();
    }
}

class PremiumCareerSessionModel extends Model
{
    protected $table = 'premium_career_sessions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'session_type', 'current_role', 'target_role', 'timeline',
        'ai_analysis', 'action_plan', 'progress_tracking', 'status'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getUserActiveSessions($userId)
    {
        return $this->where('user_id', $userId)
                   ->where('status', 'active')
                   ->orderBy('created_at', 'DESC')
                   ->findAll();
    }

    public function updateProgress($sessionId, $progressData)
    {
        return $this->update($sessionId, [
            'progress_tracking' => json_encode($progressData)
        ]);
    }
}