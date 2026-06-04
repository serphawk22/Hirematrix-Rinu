<?php

namespace App\Controllers;

class AdminAnalytics extends BaseController
{
    public function login()
    {
        if (session()->get('admin_logged_in')) {
            return redirect()->to(base_url('admin/dashboard'));
        }

        return view('admin/login');
    }

    public function authenticate()
    {
        $email = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        if ($email === '' || $password === '') {
            return redirect()->back()->with('error', 'Email and password are required.');
        }

        $adminEmail = strtolower(trim((string) (env('admin.analyticsEmail') ?? env('ADMIN_ANALYTICS_EMAIL') ?? 'admin@local.test')));
        $adminPassword = (string) (env('admin.analyticsPassword') ?? env('ADMIN_ANALYTICS_PASSWORD') ?? 'admin123');
        $adminPasswordHash = (string) (env('admin.analyticsPasswordHash') ?? env('ADMIN_ANALYTICS_PASSWORD_HASH') ?? '');

        $emailValid = hash_equals($adminEmail, $email);
        $passwordValid = $adminPasswordHash !== ''
            ? password_verify($password, $adminPasswordHash)
            : hash_equals($adminPassword, $password);

        if (!$emailValid || !$passwordValid) {
            return redirect()->back()->withInput()->with('error', 'Invalid admin credentials.');
        }

        session()->regenerate();
        session()->set([
            'admin_logged_in' => true,
            'admin_email' => $adminEmail,
        ]);

        return redirect()->to(base_url('admin/dashboard'));
    }

    public function logout()
    {
        session()->remove(['admin_logged_in', 'admin_email']);
        return redirect()->to(base_url('admin/login'));
    }

    public function dashboard()
    {
        $db = \Config\Database::connect();
        $days = max(1, min(60, (int) ($this->request->getGet('days') ?: 14)));
        $since = date('Y-m-d 00:00:00', strtotime('-' . ($days - 1) . ' days'));

        $dailyUsers = [];
        if ($db->tableExists('user_login_performance_logs')) {
            $dailyUsers = $db->table('user_login_performance_logs')
                ->select('DATE(login_at) AS day, COUNT(DISTINCT user_id) AS users_count')
                ->where('login_at >=', $since)
                ->groupBy('DATE(login_at)')
                ->orderBy('day', 'ASC')
                ->get()
                ->getResultArray();
        }

        $dailyApi = [];
        $apiTotals = ['total_calls' => 0, 'total_cost' => 0];
        $providerBreakdown = [];
        if ($db->tableExists('admin_api_usage_logs')) {
            $dailyApi = $db->table('admin_api_usage_logs')
                ->select('DATE(created_at) AS day, COUNT(*) AS calls_count, SUM(estimated_cost_usd) AS cost_usd')
                ->where('created_at >=', $since)
                ->groupBy('DATE(created_at)')
                ->orderBy('day', 'ASC')
                ->get()
                ->getResultArray();

            $providerBreakdown = $db->table('admin_api_usage_logs')
                ->select('provider, COUNT(*) AS calls_count, SUM(estimated_cost_usd) AS cost_usd')
                ->where('created_at >=', $since)
                ->groupBy('provider')
                ->orderBy('calls_count', 'DESC')
                ->get()
                ->getResultArray();

            $row = $db->table('admin_api_usage_logs')
                ->select('COUNT(*) AS total_calls, SUM(estimated_cost_usd) AS total_cost')
                ->get()
                ->getRowArray();
            if (is_array($row)) {
                $apiTotals = [
                    'total_calls' => (int) ($row['total_calls'] ?? 0),
                    'total_cost' => (float) ($row['total_cost'] ?? 0),
                ];
            }
        }

        $firstPageDurations = [];
        if ($db->tableExists('user_login_performance_logs')) {
            $firstPageDurations = $db->table('user_login_performance_logs l')
                ->select('l.login_at, l.user_id, l.user_email, l.user_role, l.first_page_path, l.duration_ms, u.name AS user_name')
                ->join('users u', 'u.id = l.user_id', 'left')
                ->orderBy('l.id', 'DESC')
                ->limit(100)
                ->get()
                ->getResultArray();
        }
        
        // --- Subscription and Revenue Analytics ---
        $totalRevenue = 0;
        $activeSubscriptionsCount = 0;
        $cancelledSubscriptionsCount = 0;
        $churnRate = 0;
        $subscriptionBreakdown = [];
        $monthlyRevenueTrend = [];

        if ($db->tableExists('user_subscriptions')) {
            // Total Revenue
            $revenueRow = $db->table('user_subscriptions')
                ->selectSum('amount_paid', 'total_paid')
                ->get()
                ->getRowArray();
            $totalRevenue = (float) ($revenueRow['total_paid'] ?? 0);

            // Active Subscriptions
            $activeSubscriptionsCount = $db->table('user_subscriptions')
                ->where('status', 'active')
                ->countAllResults();

            // Cancelled/Expired Subscriptions
            $cancelledSubscriptionsCount = $db->table('user_subscriptions')
                ->whereIn('status', ['cancelled', 'expired'])
                ->countAllResults();

            // Calculate Churn Rate Percentage
            $totalEverSubscribed = $activeSubscriptionsCount + $cancelledSubscriptionsCount;
            if ($totalEverSubscribed > 0) {
                $churnRate = ($cancelledSubscriptionsCount / $totalEverSubscribed) * 100;
            }

            // Subscription Breakdown by Plan
            if ($db->tableExists('subscription_plans')) {
                $subscriptionBreakdown = $db->table('user_subscriptions')
                    ->select('subscription_plans.name as plan_name, COUNT(user_subscriptions.id) as count')
                    ->join('subscription_plans', 'subscription_plans.id = user_subscriptions.plan_id', 'left')
                    ->groupBy('subscription_plans.name')
                    ->orderBy('count', 'DESC')
                    ->get()
                    ->getResultArray();
            }

            // Monthly Revenue Trend (last 6 months)
            $monthlyRevenueTrend = $db->table('user_subscriptions')
                ->select("DATE_FORMAT(start_date, '%Y-%m') as month, SUM(amount_paid) as revenue")
                ->where('start_date >=', date('Y-m-01', strtotime('-5 months')))
                ->groupBy('month')
                ->orderBy('month', 'ASC')
                ->get()
                ->getResultArray();
        }

        return view('admin/dashboard', [
            'days' => $days,
            'dailyUsers' => $dailyUsers,
            'dailyApi' => $dailyApi,
            'providerBreakdown' => $providerBreakdown,
            'apiTotals' => $apiTotals,
            'firstPageDurations' => $firstPageDurations,
            'totalRevenue' => $totalRevenue,
            'activeSubscriptionsCount' => $activeSubscriptionsCount,
            'cancelledSubscriptionsCount' => $cancelledSubscriptionsCount,
            'churnRate' => $churnRate,
            'subscriptionBreakdown' => $subscriptionBreakdown,
            'monthlyRevenueTrend' => $monthlyRevenueTrend,
        ]);
    }
    /**
     * Displays a list of all user subscriptions for admin management.
     */
    public function subscriptions()
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->to(base_url('admin/login'));
        }

        $db = \Config\Database::connect();
        $subscriptions = [];

        if ($db->tableExists('user_subscriptions')) {
            $builder = $db->table('user_subscriptions us');
            $builder->select('us.id, us.start_date, us.end_date, us.amount_paid, us.status, u.name as user_name, u.email as user_email, p.name as plan_name');
            $builder->join('users u', 'u.id = us.user_id', 'left');
            
            if ($db->tableExists('subscription_plans')) {
                $builder->join('subscription_plans p', 'p.id = us.plan_id', 'left');
            }

            $builder->orderBy('us.start_date', 'DESC');
            $subscriptions = $builder->get()->getResultArray();
        }

        return view('admin/subscriptions', ['subscriptions' => $subscriptions]);
    }

    /**
     * View specific subscription details
     */
    public function viewSubscription($id)
    {
        if (!session()->get('admin_logged_in')) {
            return redirect()->to(base_url('admin/login'));
        }

        $db = \Config\Database::connect();
        $subscription = null;

        if ($db->tableExists('user_subscriptions')) {
            $builder = $db->table('user_subscriptions us');
            $builder->select('us.*, u.name as user_name, u.email as user_email, p.name as plan_name');
            $builder->join('users u', 'u.id = us.user_id', 'left');
            if ($db->tableExists('subscription_plans')) {
                $builder->join('subscription_plans p', 'p.id = us.plan_id', 'left');
            }
            $subscription = $builder->where('us.id', $id)->get()->getRowArray();
        }

        if (!$subscription) {
            return redirect()->to(base_url('admin/subscriptions'))->with('error', 'Subscription not found.');
        }

        return view('admin/subscription_details', ['subscription' => $subscription]);
    }
}
    