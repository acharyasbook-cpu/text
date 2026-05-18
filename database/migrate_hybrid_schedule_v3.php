<?php

declare(strict_types=1);

/**
 * Hybrid Schedule v3 — custom topics, row metadata, hybrid question_mode.
 * Run: php database/migrate_hybrid_schedule_v3.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

function hsv3_table(PDO $pdo, string $name): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $st->execute([$name]);

    return (int) $st->fetchColumn() > 0;
}

function hsv3_col(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $col]);

    return (int) $st->fetchColumn() > 0;
}

$topicsTable = hsv3_table($pdo, 'topics') ? 'topics' : (hsv3_table($pdo, 'lessons') ? 'lessons' : null);
if ($topicsTable) {
    if (!hsv3_col($pdo, $topicsTable, 'is_custom')) {
        $pdo->exec(
            "ALTER TABLE `{$topicsTable}`
             ADD COLUMN is_custom TINYINT(1) NOT NULL DEFAULT 0
             COMMENT 'Created from Schedule Test Manager' AFTER is_free_preview"
        );
        echo "{$topicsTable}.is_custom added\n";
    }
    if (!hsv3_col($pdo, $topicsTable, 'created_by_admin')) {
        $pdo->exec(
            "ALTER TABLE `{$topicsTable}`
             ADD COLUMN created_by_admin INT UNSIGNED NULL DEFAULT NULL
             COMMENT 'Admin user id when is_custom=1' AFTER is_custom"
        );
        echo "{$topicsTable}.created_by_admin added\n";
    }
}

if (hsv3_table($pdo, 'st_schedule_rows')) {
    if (!hsv3_col($pdo, 'st_schedule_rows', 'row_meta')) {
        $pdo->exec(
            'ALTER TABLE st_schedule_rows
             ADD COLUMN row_meta JSON NULL DEFAULT NULL
             COMMENT "Inline labels, topic overrides" AFTER question_mode'
        );
        echo "st_schedule_rows.row_meta added\n";
    }

    $st = $pdo->query("SHOW COLUMNS FROM st_schedule_rows LIKE 'question_mode'");
    $col = $st->fetch();
    $type = is_array($col) ? (string) ($col['Type'] ?? '') : '';
    if ($type !== '' && !str_contains($type, 'hybrid')) {
        $pdo->exec(
            "ALTER TABLE st_schedule_rows
             MODIFY COLUMN question_mode ENUM('ai_pool','manual','external','hybrid') NULL DEFAULT NULL"
        );
        echo "st_schedule_rows.question_mode extended with hybrid\n";
    }
}

echo "migrate_hybrid_schedule_v3: complete\n";
echo "Note: plan_type is represented by st_schedule_days.term_key (short_term | long_term).\n";
