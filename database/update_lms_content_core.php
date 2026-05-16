<?php

/**
 * LMS Content Manager core: notes binding + exam suite flags.
 * CLI: php database/update_lms_content_core.php
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
require_once $dbPath;

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function ulcc_col(PDO $pdo, string $t, string $c): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $t, $c]);

    return (int) $st->fetchColumn() > 0;
}

function ulcc_table(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND TABLE_TYPE=\'BASE TABLE\''
    );
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

$topics = ulcc_table($pdo, 'topics') ? 'topics' : (ulcc_table($pdo, 'lessons') ? 'lessons' : null);
echo "update_lms_content_core: start\n";

if ($topics && !ulcc_col($pdo, $topics, 'notes_bind_sub_topic_id')) {
    $pdo->exec("ALTER TABLE `{$topics}` ADD COLUMN notes_bind_sub_topic_id INT UNSIGNED NULL DEFAULT NULL AFTER notes_content");
    echo "added {$topics}.notes_bind_sub_topic_id\n";
}

if (ulcc_table($pdo, 'sub_topics') && !ulcc_col($pdo, 'sub_topics', 'is_active')) {
    $pdo->exec('ALTER TABLE sub_topics ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER status');
    echo "added sub_topics.is_active\n";
}

if (ulcc_table($pdo, 'topic_exam_suite')) {
    if (!ulcc_col($pdo, 'topic_exam_suite', 'is_required')) {
        $pdo->exec('ALTER TABLE topic_exam_suite ADD COLUMN is_required TINYINT(1) NOT NULL DEFAULT 1 AFTER is_enabled');
        echo "added topic_exam_suite.is_required\n";
    }
    $pdo->exec('UPDATE topic_exam_suite SET is_required = is_enabled WHERE is_required IS NULL');
}

echo "update_lms_content_core: done\n";
