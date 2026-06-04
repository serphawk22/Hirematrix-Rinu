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

    public function getUserPaymentHistory(int $userId): array
    {
        $db = \Config\Database::connect();
        return $db->table('payment_orders po')
            ->select('po.*, sp.name as plan_name')
            ->join('subscription_plans sp', 'sp.id = po.plan_id', 'left')
            ->where('po.user_id', $userId)
            ->orderBy('po.created_at', 'DESC')
            ->get()
            ->getResultArray();
    }
}