<?php

/**
 * Run hierarchy / four-tier / topic exams / exam tiers / dynamic seed in order.
 * CLI: php database/migrate_and_seed_new_structure.php
 *
 * Idempotent: delegates to each script’s own guards.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$dbPath = $root . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found at {$dbPath}\n");
    exit(1);
}

$phpBin = PHP_BINARY !== '' ? PHP_BINARY : 'php';
$scripts = [
    'migrate_hierarchy.php',
    'migrate_four_tier.php',
    'migrate_topic_exams.php',
    'migrate_exam_hierarchy.php',
    'migrate_dynamic_hierarchy.php',
];

foreach ($scripts as $rel) {
    $path = __DIR__ . '/' . $rel;
    if (!is_readable($path)) {
        fwrite(STDERR, "Missing migration script: {$path}\n");
        exit(1);
    }
    echo "\n========== {$rel} ==========\n\n";
    passthru(escapeshellarg($phpBin) . ' ' . escapeshellarg($path), $exitCode);
    if ($exitCode !== 0) {
        fwrite(STDERR, "\nFailed at {$rel} (exit {$exitCode})\n");
        exit($exitCode);
    }
}

echo "\n=== migrate_and_seed_new_structure: all steps completed ===\n";
