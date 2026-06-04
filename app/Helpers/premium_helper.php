<?php

defined('SYSTEMPATH') || exit('No direct script access allowed');

if (!function_exists('isPremiumUser')) {
    function isPremiumUser(int $userId): bool
    {
        if ($userId <= 0) {
            return false;
        }

        $subscriptionModel = model('SubscriptionModel');
        $subscription = $subscriptionModel->getUserActiveSubscription($userId);

        return !empty($subscription);
    }
}

if (!function_exists('requirePremiumForFeature')) {
    function premiumPlansUrlForFeature(string $feature): string
    {
        $feature = strtolower($feature);

        if (str_contains($feature, 'mentor')) {
            $service = 'mentor';
        } elseif (str_contains($feature, 'resume')) {
            $service = 'resume-studio';
        } elseif (str_contains($feature, 'career transition')) {
            $service = 'career-transition';
        } else {
            $service = 'all';
        }

        return base_url('premium/plans?service=' . rawurlencode($service));
    }

    function requirePremiumForFeature(int $userId, string $feature = 'premium_ai'): void
    {
        if (!isPremiumUser($userId)) {
            session()->setFlashdata(
                'error',
                "This {$feature} requires Premium subscription. <a href='" . premiumPlansUrlForFeature($feature) . "'>Upgrade now -></a>"
            );

            header('Location: ' . premiumPlansUrlForFeature($feature));
            exit;
        }
    }
}
