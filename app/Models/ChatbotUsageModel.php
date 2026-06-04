<?php

namespace App\Models;

use CodeIgniter\Model;

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