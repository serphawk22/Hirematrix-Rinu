<?php

namespace App\Controllers;

use App\Models\SubscriptionModel;

class PaymentController extends BaseController
{
    private SubscriptionModel $subscriptionModel;

    public function __construct()
    {
        $this->subscriptionModel = new SubscriptionModel();
    }

    /**
     * Step 1: Create a Razorpay order and return order_id to frontend.
     * POST /payment/create-order
     */
    public function createOrder(): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Not authenticated']);
        }

        $planId = (int) $this->request->getPost('plan_id');
        $plan   = $this->subscriptionModel->find($planId);

        if (empty($plan) || (float) $plan['price'] <= 0) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid plan']);
        }

        // Razorpay expects amount in paise (1 INR = 100 paise)
        $amountPaise = (int) round((float) $plan['price'] * 100);
        $receipt     = 'rcpt_' . $userId . '_' . $planId . '_' . time();

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
            log_message('error', 'Razorpay order creation failed: ' . json_encode($response));
            return $this->response->setStatusCode(500)->setJSON(['error' => 'Could not create payment order. Please try again.']);
        }

        // Persist pending order so we can verify it later
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

        return $this->response->setJSON([
            'order_id'  => $response['id'],
            'amount'    => $amountPaise,
            'currency'  => $payload['currency'],
            'key_id'    => getenv('RAZORPAY_KEY_ID'),
            'plan_name' => $plan['name'],
        ]);
    }

    /**
     * Step 2: Verify payment signature after Razorpay checkout success.
     * POST /payment/verify
     */
    public function verify(): \CodeIgniter\HTTP\ResponseInterface
    {
        $userId = (int) session()->get('user_id');
        if ($userId <= 0) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Not authenticated']);
        }

        $razorpayOrderId   = $this->request->getPost('razorpay_order_id');
        $razorpayPaymentId = $this->request->getPost('razorpay_payment_id');
        $razorpaySignature = $this->request->getPost('razorpay_signature');

        if (!$razorpayOrderId || !$razorpayPaymentId || !$razorpaySignature) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing payment details']);
        }

        // Verify HMAC-SHA256 signature
        $expectedSignature = hash_hmac(
            'sha256',
            $razorpayOrderId . '|' . $razorpayPaymentId,
            getenv('RAZORPAY_KEY_SECRET')
        );

        if (!hash_equals($expectedSignature, $razorpaySignature)) {
            log_message('warning', "Payment signature mismatch for user $userId, order $razorpayOrderId");
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Payment verification failed. Please contact support.']);
        }

        $db    = \Config\Database::connect();
        $order = $db->table('payment_orders')
            ->where('razorpay_order_id', $razorpayOrderId)
            ->where('user_id', $userId)
            ->where('status', 'created')
            ->get()
            ->getRowArray();

        if (empty($order)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Order not found or already processed']);
        }

        $plan = $this->subscriptionModel->find($order['plan_id']);
        if (empty($plan)) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Plan not found']);
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

        log_message('info', "Subscription activated: user=$userId plan={$plan['name']} payment=$razorpayPaymentId");

        return $this->response->setJSON([
            'success'  => true,
            'message'  => 'Payment successful! Your subscription is now active.',
            'redirect' => base_url('premium-mentor'),
        ]);
    }

    /**
     * Razorpay webhook endpoint (optional but recommended for reliability).
     * POST /payment/webhook
     */
    public function webhook(): \CodeIgniter\HTTP\ResponseInterface
    {
        $webhookSecret = getenv('RAZORPAY_WEBHOOK_SECRET');
        $body          = $this->request->getBody();
        $signature     = $this->request->getHeaderLine('X-Razorpay-Signature');

        if ($webhookSecret && $signature) {
            $expected = hash_hmac('sha256', $body, $webhookSecret);
            if (!hash_equals($expected, $signature)) {
                log_message('warning', 'Razorpay webhook signature mismatch');
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid signature']);
            }
        }

        $event = json_decode($body, true);
        if (empty($event['event'])) {
            return $this->response->setStatusCode(400);
        }

        if ($event['event'] === 'payment.captured') {
            $payment = $event['payload']['payment']['entity'] ?? [];
            $orderId = $payment['order_id'] ?? null;

            if ($orderId) {
                $db    = \Config\Database::connect();
                $order = $db->table('payment_orders')
                    ->where('razorpay_order_id', $orderId)
                    ->where('status', 'created')
                    ->get()
                    ->getRowArray();

                if (!empty($order)) {
                    $plan = $this->subscriptionModel->find($order['plan_id']);
                    if ($plan) {
                        $db->table('payment_orders')->where('id', $order['id'])->update([
                            'razorpay_payment_id' => $payment['id'] ?? null,
                            'status'              => 'paid',
                            'paid_at'             => date('Y-m-d H:i:s'),
                        ]);
                        $this->activateSubscription((int) $order['user_id'], $plan, $payment['id'] ?? '', $orderId);
                        log_message('info', "Webhook: subscription activated for order $orderId");
                    }
                }
            }
        }

        return $this->response->setJSON(['status' => 'ok']);
    }

    /**
     * Show payment history for the logged-in user.
     * GET /payment/history
     */
    public function history(): string
    {
        $userId = (int) session()->get('user_id');
        $db     = \Config\Database::connect();

        $orders = $db->table('payment_orders po')
            ->select('po.*, sp.name as plan_name')
            ->join('subscription_plans sp', 'sp.id = po.plan_id', 'left')
            ->where('po.user_id', $userId)
            ->orderBy('po.created_at', 'DESC')
            ->get()
            ->getResultArray();

        return view('premium/payment_history', [
            'title'  => 'Payment History',
            'orders' => $orders,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function activateSubscription(int $userId, array $plan, string $paymentId, string $orderId): void
    {
        // Deactivate any existing active subscription for this user
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

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
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
