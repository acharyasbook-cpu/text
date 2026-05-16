<?php

/**
 * LMS Content Manager master v3: cover images + topic notes toggle.
 * CLI: php database/update_lms_master_v3.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function ulmv3_col(PDO $pdo, string $t, string $c): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $t, $c]);

    return (int) $st->fetchColumn() > 0;
}

function ulmv3_table(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND TABLE_TYPE='BASE TABLE'"
    );
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

function ulmv3_add_image_path(PDO $pdo, string $table): void
{
    if (!ulmv3_table($pdo, $table) || ulmv3_col($pdo, $table, 'image_path')) {
        return;
    }
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN image_path VARCHAR(512) NULL DEFAULT NULL");
    echo "added {$table}.image_path\n";
}

echo "update_lms_master_v3: start\n";

foreach (['main_courses', 'courses'] as $t) {
    ulmv3_add_image_path($pdo, $t);
}
ulmv3_add_image_path($pdo, 'sub_courses');
ulmv3_add_image_path($pdo, 'subjects');

$topics = ulmv3_table($pdo, 'topics') ? 'topics' : (ulmv3_table($pdo, 'lessons') ? 'lessons' : null);
if ($topics && !ulmv3_col($pdo, $topics, 'notes_enabled')) {
    $pdo->exec("ALTER TABLE `{$topics}` ADD COLUMN notes_enabled TINYINT(1) NOT NULL DEFAULT 1 AFTER notes_content");
    echo "added {$topics}.notes_enabled\n";
}

echo "update_lms_master_v3: done\n";
