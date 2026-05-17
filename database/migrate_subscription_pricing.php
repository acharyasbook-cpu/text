<?php

declare(strict_types=1);

/**
 * Time-based sub-course subscription pricing (offer + strikethrough original).
 * Run: php database/migrate_subscription_pricing.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

function msp_col(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $col]);

    return (int) $st->fetchColumn() > 0;
}

if (!msp_col($pdo, 'sub_course_plans', 'original_price_inr')) {
    $pdo->exec(
        'ALTER TABLE sub_course_plans ADD COLUMN original_price_inr DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER price_inr'
    );
    echo "added sub_course_plans.original_price_inr\n";
}

if (!msp_col($pdo, 'sub_course_plans', 'label_te')) {
    $pdo->exec(
        'ALTER TABLE sub_course_plans ADD COLUMN label_te VARCHAR(120) NULL DEFAULT NULL AFTER label'
    );
    echo "added sub_course_plans.label_te\n";
}

$defaults = [
    '6_months' => ['6 Months', '6 నెలల ప్లాన్', 499.00, 1000.00, 6],
    '1_year' => ['1 Year', '1 సంవత్సర ప్లాన్', 699.00, 1500.00, 12],
    'until_exam' => ['Up to Exam', 'పరీక్ష వరకు ప్లాన్', 999.00, 2500.00, null],
];

$scIds = $pdo->query('SELECT id FROM sub_courses')->fetchAll(PDO::FETCH_COLUMN) ?: [];
$ins = $pdo->prepare(
    'INSERT IGNORE INTO sub_course_plans (sub_course_id, plan_code, label, label_te, price_inr, original_price_inr, duration_months, status, is_active)
     VALUES (?,?,?,?,?,?,?,1,1)'
);
$upd = $pdo->prepare(
    'UPDATE sub_course_plans SET label=?, label_te=?, price_inr=?, original_price_inr=?, duration_months=?, is_active=1, status=1
     WHERE sub_course_id=? AND plan_code=?'
);

foreach ($scIds as $scId) {
    $scId = (int) $scId;
    foreach ($defaults as $code => [$en, $te, $offer, $orig, $months]) {
        $ins->execute([$scId, $code, $en, $te, $offer, $orig, $months]);
        $upd->execute([$en, $te, $offer, $orig, $months, $scId, $code]);
    }
}

echo 'subscription plans normalized for ' . count($scIds) . " sub-courses\n";
echo "migrate_subscription_pricing: complete\n";
