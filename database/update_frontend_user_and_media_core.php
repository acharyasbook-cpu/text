<?php

/**
 * Frontend: platform branding, study progress, user analytics indexes.
 * CLI: php database/update_frontend_user_and_media_core.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function ufumc_table(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND TABLE_TYPE='BASE TABLE'"
    );
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

function ufumc_col(PDO $pdo, string $t, string $c): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $t, $c]);

    return (int) $st->fetchColumn() > 0;
}

echo "update_frontend_user_and_media_core: start\n";

if (!ufumc_table($pdo, 'platform_settings')) {
    $pdo->exec("CREATE TABLE platform_settings (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(64) NOT NULL UNIQUE,
        setting_value TEXT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "created platform_settings\n";
    $pdo->exec("INSERT INTO platform_settings (setting_key, setting_value) VALUES
        ('site_logo_path', NULL),
        ('site_name', 'Acharya Books'),
        ('site_name_te', 'ఆచార్య బుక్'),
        ('site_tagline_te', 'మోడర్న్ గురుకుల్')");
}

if (!ufumc_table($pdo, 'topic_study_progress')) {
    $topics = ufumc_table($pdo, 'topics') ? 'topics' : 'lessons';
    $pdo->exec("CREATE TABLE topic_study_progress (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        topic_id INT UNSIGNED NOT NULL,
        progress_pct TINYINT UNSIGNED NOT NULL DEFAULT 0,
        notes_opened TINYINT(1) NOT NULL DEFAULT 0,
        last_read_at TIMESTAMP NULL DEFAULT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_topic (user_id, topic_id),
        KEY idx_user_progress (user_id, progress_pct),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "created topic_study_progress\n";
}

if (ufumc_table($pdo, 'users')) {
    if (!ufumc_col($pdo, 'users', 'mobile_verified')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN mobile_verified TINYINT(1) NOT NULL DEFAULT 0 AFTER phone');
        echo "added users.mobile_verified\n";
    }
    if (!ufumc_col($pdo, 'users', 'last_login_at')) {
        $pdo->exec('ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL AFTER updated_at');
        echo "added users.last_login_at\n";
    }
}

if (ufumc_table($pdo, 'test_attempts')) {
    try {
        $pdo->exec('CREATE INDEX idx_ta_user_submitted ON test_attempts (user_id, submitted_at)');
        echo "index test_attempts user_submitted\n";
    } catch (Throwable $e) {
        echo "skip idx_ta_user_submitted\n";
    }
}

if (ufumc_table($pdo, 'user_subscriptions')) {
    try {
        $pdo->exec('CREATE INDEX idx_us_user_status ON user_subscriptions (user_id, status)');
        echo "index user_subscriptions user_status\n";
    } catch (Throwable $e) {
        echo "skip idx_us_user_status\n";
    }
}

echo "update_frontend_user_and_media_core: done\n";
