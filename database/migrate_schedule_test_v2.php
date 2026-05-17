<?php

declare(strict_types=1);

/**
 * Schedule Test Manager v2 — config anchors, row metadata, index fixes.
 * Run after v1: php database/migrate_schedule_test_v2.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

function stv2_table(PDO $pdo, string $name): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $st->execute([$name]);

    return (int) $st->fetchColumn() > 0;
}

function stv2_col(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $col]);

    return (int) $st->fetchColumn() > 0;
}

function stv2_index(PDO $pdo, string $table, string $index): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $st->execute([$table, $index]);

    return (int) $st->fetchColumn() > 0;
}

if (!stv2_table($pdo, 'st_schedule_days')) {
    echo "Run v1 first: php database/migrate_schedule_test_manager.php\n";
    exit(1);
}

if (!stv2_col($pdo, 'st_schedule_config', 'anchor_start_date')) {
    $pdo->exec(
        'ALTER TABLE st_schedule_config
         ADD COLUMN anchor_start_date DATE NULL DEFAULT NULL
         COMMENT "Date-wise planner start anchor" AFTER planner_mode'
    );
    echo "st_schedule_config.anchor_start_date added\n";
}

if (!stv2_col($pdo, 'st_schedule_days', 'title_en')) {
    $pdo->exec(
        'ALTER TABLE st_schedule_days
         ADD COLUMN title_en VARCHAR(220) NULL DEFAULT NULL AFTER title_te'
    );
    echo "st_schedule_days.title_en added\n";
}

if (!stv2_col($pdo, 'st_schedule_rows', 'row_label_te')) {
    $pdo->exec(
        'ALTER TABLE st_schedule_rows
         ADD COLUMN row_label_te VARCHAR(200) NULL DEFAULT NULL AFTER subject_id'
    );
    echo "st_schedule_rows.row_label_te added\n";
}

if (!stv2_col($pdo, 'st_schedule_completions', 'attempt_id')) {
    $pdo->exec(
        'ALTER TABLE st_schedule_completions
         ADD COLUMN attempt_id INT UNSIGNED NULL DEFAULT NULL AFTER schedule_row_id,
         ADD KEY idx_attempt (attempt_id)'
    );
    echo "st_schedule_completions.attempt_id added\n";
}

// Planner slot unique key (avoids NULL collisions on dual day_index / schedule_date uniques)
if (!stv2_col($pdo, 'st_schedule_days', 'planner_slot')) {
    $pdo->exec(
        "ALTER TABLE st_schedule_days
         ADD COLUMN planner_slot VARCHAR(32) NULL DEFAULT NULL
         COMMENT 'd:N or date:YYYY-MM-DD' AFTER schedule_date"
    );
    echo "st_schedule_days.planner_slot added\n";

    $pdo->exec(
        "UPDATE st_schedule_days SET planner_slot = CONCAT('d:', day_index)
         WHERE day_index IS NOT NULL AND (planner_slot IS NULL OR planner_slot = '')"
    );
    $pdo->exec(
        "UPDATE st_schedule_days SET planner_slot = CONCAT('date:', schedule_date)
         WHERE schedule_date IS NOT NULL AND (planner_slot IS NULL OR planner_slot = '')"
    );
    echo "st_schedule_days.planner_slot backfilled\n";
}

if (!stv2_index($pdo, 'st_schedule_days', 'uk_sc_term_slot')) {
    if (stv2_index($pdo, 'st_schedule_days', 'uk_sc_term_day')) {
        $pdo->exec('ALTER TABLE st_schedule_days DROP INDEX uk_sc_term_day');
        echo "dropped uk_sc_term_day\n";
    }
    if (stv2_index($pdo, 'st_schedule_days', 'uk_sc_term_date')) {
        $pdo->exec('ALTER TABLE st_schedule_days DROP INDEX uk_sc_term_date');
        echo "dropped uk_sc_term_date\n";
    }
    $pdo->exec(
        'ALTER TABLE st_schedule_days ADD UNIQUE KEY uk_sc_term_slot (sub_course_id, term_key, planner_slot)'
    );
    echo "added uk_sc_term_slot\n";
}

if (!stv2_index($pdo, 'st_schedule_completions', 'idx_user_completed')) {
    $pdo->exec(
        'ALTER TABLE st_schedule_completions ADD KEY idx_user_completed (user_id, completed_at)'
    );
    echo "st_schedule_completions.idx_user_completed added\n";
}

echo "migrate_schedule_test_v2: complete\n";
