<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\SubscriptionModel;

class ApiPaymentController extends ResourceController
{
    protected $format = 'json';
    private SubscriptionModel $subscriptionModel;

    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
    }

    /**
     * Step 1: Create a Razorpay order for mobile and return order_id.
     * POST /api/payment/create-order
     */
    public function createOrder()
    {
        $userId = 0;
        $planId = 0;
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            $json = $this->request->getJSON();
            if ($json) {
                $userId = (int) ($json->candidate_id ?? $json->user_id ?? 0);
                $planId = (int) ($json->plan_id ?? 0);
            }
        } else {
            $userId = (int) ($this->request->getPost('candidate_id') ?: $this->request->getPost('user_id'));
            $planId = (int) $this->request->getPost('plan_id');
        }

        if ($userId <= 0) {
            return $this->failUnauthorized('Invalid or missing user/candidate ID');
        }

        $plan = $this->subscriptionModel->find($planId);
        if (empty($plan) || (float) $plan['price'] <= 0) {
            return $this->fail('Invalid plan selected');
        }

        $amountPaise = (int) round((float) $plan['price'] * 100);
        $receipt     = 'rcpt_api_' . $userId . '_' . $planId . '_' . time();

        $payload = [
            'amount'   => $amountPaise,
            'currency' => getenv('RAZORPAY_CURRENCY') ?: 'INR',
            'receipt'  => $receipt,
            'notes'    => [
                'user_id' => $userId,
                'plan_id' => $planId,
            ],
        ];

        $response = $this->razorpayRequest('POST', 'orders', $payload);

        if (empty($response['id'])) {
            log_message('error', 'API Razorpay order creation failed: ' . json_encode($response));
            return $this->fail('Could not create payment order. Please try again.');
        }

        // Persist pending order
        $db = \Config\Database::connect();
        $db->table('payment_orders')->insert([
            'user_id'          => $userId,
            'plan_id'          => $planId,
            'razorpay_order_id'=> $response['id'],
            'amount'           => $plan['price'],
            'currency'         => $payload['currency'],
            'receipt'          => $receipt,
            'status'           => 'created',
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        return $this->respond([
            'status'    => 'success',
            'order_id'  => $response['id'],
            'amount'    => $amountPaise,
            'currency'  => $payload['currency'],
            'key_id'    => getenv('RAZORPAY_KEY_ID'),
            'plan_name' => $plan['name'],
        ]);
    }

    /**
     * Step 2: Verify payment signature after checkout.
     * POST /api/payment/verify
     */
    public function verify()
    {
        $userId            = 0;
        $razorpayOrderId   = '';
        $razorpayPaymentId = '';
        $razorpaySignature = '';

        $contentType = $this->request->getHeaderLine('Content-Type');
        if (str_contains($contentType, 'application/json')) {
            $json = $this->request->getJSON();
            if ($json) {
                $userId            = (int) ($json->candidate_id ?? $json->user_id ?? 0);
                $razorpayOrderId   = $json->razorpay_order_id ?? '';
                $razorpayPaymentId = $json->razorpay_payment_id ?? '';
                $razorpaySignature = $json->razorpay_signature ?? '';
            }
        } else {
            $userId            = (int) ($this->request->getPost('candidate_id') ?: $this->request->getPost('user_id'));
            $razorpayOrderId   = $this->request->getPost('razorpay_order_id');
            $razorpayPaymentId = $this->request->getPost('razorpay_payment_id');
            $razorpaySignature = $this->request->getPost('razorpay_signature');
        }

        if ($userId <= 0) {
            return $this->failUnauthorized('Invalid or missing user/candidate ID');
        }

        if (!$razorpayOrderId || !$razorpayPaymentId || !$razorpaySignature) {
            return $this->fail('Missing payment details');
        }

        // Verify HMAC-SHA256 signature
        $expectedSignature = hash_hmac(
            'sha256',
            $razorpayOrderId . '|' . $razorpayPaymentId,
            getenv('RAZORPAY_KEY_SECRET')
        );

        if (!hash_equals($expectedSignature, $razorpaySignature)) {
            log_message('warning', "API Payment signature mismatch for user $userId, order $razorpayOrderId");
            return $this->fail('Payment verification failed. Please contact support.');
        }

        $db    = \Config\Database::connect();
        $order = $db->table('payment_orders')
            ->where('razorpay_order_id', $razorpayOrderId)
            ->where('user_id', $userId)
            ->where('status', 'created')
            ->get()
            ->getRowArray();

        if (empty($order)) {
            return $this->fail('Order not found or already processed');
        }

        $plan = $this->subscriptionModel->find($order['plan_id']);
        if (empty($plan)) {
            return $this->fail('Plan not found');
        }

        // Mark order as paid
        $db->table('payment_orders')->where('id', $order['id'])->update([
            'razorpay_payment_id' => $razorpayPaymentId,
            'razorpay_signature'  => $razorpaySignature,
            'status'              => 'paid',
            'paid_at'             => date('Y-m-d H:i:s'),
        ]);

        // Activate subscription
        $this->activateSubscription($userId, $plan, $razorpayPaymentId, $razorpayOrderId);

        log_message('info', "API Subscription activated: user=$userId plan={$plan['name']} payment=$razorpayPaymentId");

        return $this->respond([
            'status'  => 'success',
            'message' => 'Payment successful! Your subscription is now active.',
        ]);
    }

    private function activateSubscription(int $userId, array $plan, string $paymentId, string $orderId): void
    {
        $db = \Config\Database::connect();
        $db->table('user_subscriptions')
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'superseded']);

        $this->subscriptionModel->saveSubscription([
            'user_id'    => $userId,
            'plan_id'    => $plan['id'],
            'start_date' => date('Y-m-d'),
            'end_date'   => date('Y-m-d', strtotime('+' . (int) $plan['duration_days'] . ' days')),
            'amount_paid'=> $plan['price'],
            'payment_id' => $paymentId,
            'order_id'   => $orderId,
            'status'     => 'active',
        ]);
    }

    private function razorpayRequest(string $method, string $endpoint, array $payload = []): array
    {
        $keyId     = getenv('RAZORPAY_KEY_ID');
        $keySecret = getenv('RAZORPAY_KEY_SECRET');
        $url       = 'https://api.razorpay.com/v1/' . $endpoint;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => $keyId . ':' . $keySecret,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 20,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return is_string($response) ? (json_decode($response, true) ?? []) : [];
    }
}
