<?php

/**
 * TS DSC — idempotent structure sync + optional five-tier test skeletons.
 *
 * Prerequisites: migrate_four_tier.php, migrate_dynamic_hierarchy.php (or equivalent).
 *
 * CLI: php database/seed_ts_dsc_standardized_structure.php
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found.\n");
    exit(1);
}

require_once $dbPath;
require_once dirname(__DIR__) . '/includes/TsDscCatalog.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "seed_ts_dsc_standardized_structure: start\n";

try {
    TsDscCatalog::standardizeTsDsc($pdo);
    echo "TS DSC programmes / subjects / pivots synced.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'standardizeTsDsc failed: ' . $e->getMessage() . "\n");
    exit(1);
}

TsDscCatalog::ensureSkeletonFiveTierTests($pdo);
echo "Five-tier skeleton tests ensured (draft placeholders where missing).\n";

echo "seed_ts_dsc_standardized_structure: complete\n";
