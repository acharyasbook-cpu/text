<?php

declare(strict_types=1);

/**
 * Per-user 2-day demo anchor for sub-course schedule (first visit timestamp).
 * Run: php database/migrate_subcourse_demo_paywall.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

function sdpw_table(PDO $pdo, string $name): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $st->execute([$name]);

    return (int) $st->fetchColumn() > 0;
}

if (!sdpw_table($pdo, 'user_sub_course_demo')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE user_sub_course_demo (
  user_id INT UNSIGNED NOT NULL,
  sub_course_id INT UNSIGNED NOT NULL,
  started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, sub_course_id),
  KEY idx_sub (sub_course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created user_sub_course_demo\n";
}

echo "migrate_subcourse_demo_paywall: complete\n";
