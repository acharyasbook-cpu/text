<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error_te' => 'Method not allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    verify_csrf($_POST['_csrf'] ?? null);
} catch (InvalidArgumentException $e) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error_te' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!current_user()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error_te' => 'లాగిన్ అవసరం.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$planId = (int) ($_POST['plan_id'] ?? 0);
$code = trim((string) ($_POST['coupon_code'] ?? ''));

if (!CouponRepository::tableReady()) {
    echo json_encode([
        'ok' => false,
        'error_te' => 'ఈ కూపన్ చెల్లదు లేదా గడువు ముగిసింది.',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$repo = new CouponRepository();
$v = $repo->validateForPlan($planId, $code);

if (!empty($v['ok'])) {
    echo json_encode([
        'ok' => true,
        'message_te' => 'కూపన్ విజయవంతంగా అప్లై అయింది!',
        'original_inr' => (float) ($v['original_inr'] ?? 0),
        'discount_inr' => (float) ($v['discount_inr'] ?? 0),
        'final_inr' => (float) ($v['final_inr'] ?? 0),
        'coupon_id' => (int) ($v['coupon_id'] ?? 0),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => false,
    'error_te' => (string) ($v['error_te'] ?? 'ఈ కూపన్ చెల్లదు లేదా గడువు ముగిసింది.'),
], JSON_UNESCAPED_UNICODE);
