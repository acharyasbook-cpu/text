<?php

declare(strict_types=1);

/**
 * Add TGT tier; allow high questions_per_page counts.
 * Run: php database/migrate_mcq_difficulty_scales.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

$enum = "'SGT','SA','TGT','PGT'";

foreach (['st_mcq_generation_jobs', 'st_questions_staging'] as $table) {
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = "difficulty_scale"'
    );
    $st->execute([$table]);
    if ((int) $st->fetchColumn() > 0) {
        $pdo->exec(
            "ALTER TABLE `{$table}` MODIFY COLUMN difficulty_scale
             ENUM({$enum}) NOT NULL DEFAULT 'SGT'"
        );
        echo "updated {$table}.difficulty_scale\n";
    }
}

$st = $pdo->prepare(
    'SELECT COLUMN_TYPE FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = "st_mcq_generation_jobs" AND COLUMN_NAME = "questions_per_page"'
);
$st->execute();
if ($st->fetch()) {
    $pdo->exec(
        'ALTER TABLE st_mcq_generation_jobs MODIFY questions_per_page SMALLINT UNSIGNED NOT NULL DEFAULT 3'
    );
    echo "widened st_mcq_generation_jobs.questions_per_page\n";
}

echo "migrate_mcq_difficulty_scales: complete\n";
