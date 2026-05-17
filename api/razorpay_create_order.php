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
$subRepo = new SubscriptionRepository();
$plan = $subRepo->findPlanById($planId);
if (!$plan) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid plan']);
    exit;
}

if ($subRepo->userHasActivePlanForSubCourse((int) $user['id'], (int) $plan['sub_course_id'])) {
    echo json_encode(['ok' => false, 'error' => 'You already have access']);
    exit;
}

$result = RazorpayCheckout::createOrderForPlan($plan, (int) $user['id']);
if (!$result['ok']) {
    echo json_encode($result);
    exit;
}

echo json_encode($result);
