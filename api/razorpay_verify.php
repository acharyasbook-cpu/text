<?php

declare(strict_types=1);

require dirname(__DIR__) . '/includes/init.php';
require_once dirname(__DIR__) . '/includes/RazorpayCheckout.php';

header('Content-Type: application/json; charset=utf-8');

$user = current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Login required']);
    exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
}

$planId = (int) ($data['plan_id'] ?? 0);
$orderId = trim((string) ($data['razorpay_order_id'] ?? ''));
$paymentId = trim((string) ($data['razorpay_payment_id'] ?? ''));
$signature = trim((string) ($data['razorpay_signature'] ?? ''));
$return = safe_return_path($data['return'] ?? '');
$couponCode = trim((string) ($data['coupon_code'] ?? ''));

if (!RazorpayCheckout::verifyPaymentSignature($orderId, $paymentId, $signature)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Payment verification failed']);
    exit;
}

$subRepo = new SubscriptionRepository();
try {
    $result = $subRepo->purchaseSubCoursePlan(
        (int) $user['id'],
        $planId,
        'razorpay:' . $paymentId,
        $couponCode !== '' ? $couponCode : null
    );
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
    exit;
}

$redirect = $return !== '' ? base_url(ltrim($return, '/')) : base_url('dashboard.php');
echo json_encode([
    'ok' => true,
    'redirect' => $redirect,
    'transaction_ref' => $result['transaction_ref'] ?? '',
]);
