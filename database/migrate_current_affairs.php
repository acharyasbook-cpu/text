<?php

/**
 * Daily Current Affairs engine tables.
 * CLI: php database/migrate_current_affairs.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function ca_table(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND TABLE_TYPE=\'BASE TABLE\''
    );
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

echo "migrate_current_affairs: start\n";

if (!ca_table($pdo, 'st_current_affairs_pool')) {
    $pdo->exec(
        "CREATE TABLE st_current_affairs_pool (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            exam_date DATE NOT NULL,
            question_text TEXT NOT NULL,
            option_a VARCHAR(512) NOT NULL,
            option_b VARCHAR(512) NOT NULL,
            option_c VARCHAR(512) NOT NULL,
            option_d VARCHAR(512) NOT NULL,
            correct_option ENUM('A','B','C','D') NOT NULL,
            mode ENUM('manual','ai') NOT NULL DEFAULT 'manual',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ca_pool_date (exam_date),
            INDEX idx_ca_pool_date_mode (exam_date, mode)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "created st_current_affairs_pool\n";
}

if (!ca_table($pdo, 'st_current_affairs_attempts')) {
    $pdo->exec(
        "CREATE TABLE st_current_affairs_attempts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNSIGNED NOT NULL,
            exam_date DATE NOT NULL,
            attempt_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_attempt_at DATETIME NULL,
            UNIQUE KEY uq_ca_user_date (user_id, exam_date),
            INDEX idx_ca_attempts_date (exam_date),
            INDEX idx_ca_attempts_user (user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "created st_current_affairs_attempts\n";
}

echo "migrate_current_affairs: done\n";
