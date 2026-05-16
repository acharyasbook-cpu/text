<?php

declare(strict_types=1);

/**
 * Ensure sort_order exists on hierarchy + exam tables (default 0).
 * Run: php database/migrate_content_sort_order.php
 */
require dirname(__DIR__) . '/includes/init.php';

$pdo = db();
$tables = [
    'main_courses' => 'sort_order',
    'courses' => 'sort_order',
    'sub_courses' => 'sort_order',
    'subjects' => 'sort_order',
    'topics' => 'sort_order',
    'sub_topics' => 'sort_order',
    'exams' => 'sort_order',
    'topic_exam_suite' => 'sort_order',
    'study_materials' => 'sort_order',
    'sub_course_subjects' => 'sort_order',
    'tests' => 'sort_order',
];

foreach ($tables as $table => $col) {
    if (!SchemaHelper::hasTable($table)) {
        echo "Skip missing table: {$table}\n";
        continue;
    }
    if (SchemaHelper::columnExists($table, $col)) {
        echo "OK {$table}.{$col}\n";
        continue;
    }
    $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$col}` INT NOT NULL DEFAULT 0");
    echo "Added {$table}.{$col}\n";
}

// Normalize sequential sort_order per scope
$scopes = [
    ['sub_courses', 'course_id'],
    ['subjects', 'course_id'],
    ['topics', 'subject_id'],
    ['sub_topics', 'topic_id'],
];
foreach ($scopes as [$table, $parentCol]) {
    if (!SchemaHelper::hasTable($table) || !SchemaHelper::columnExists($table, 'sort_order')) {
        continue;
    }
    $parents = $pdo->query("SELECT DISTINCT `{$parentCol}` AS pid FROM `{$table}` WHERE `{$parentCol}` IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($parents as $pid) {
        $st = $pdo->prepare("SELECT id FROM `{$table}` WHERE `{$parentCol}`=? ORDER BY sort_order ASC, id ASC");
        $st->execute([(int) $pid]);
        $i = 0;
        $upd = $pdo->prepare("UPDATE `{$table}` SET sort_order=? WHERE id=?");
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $upd->execute([$i++, (int) $id]);
        }
    }
    echo "Normalized {$table} by {$parentCol}\n";
}

echo "Done.\n";
