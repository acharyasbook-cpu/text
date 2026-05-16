<?php

/**
 * AP TET — idempotent subjects / programmes / pivots / pricing + optional five-tier test skeletons.
 *
 * Prerequisites: migrate_four_tier.php, migrate_dynamic_hierarchy.php (recommended).
 *
 * CLI: php database/seed_ap_tet_standard_structure.php
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found.\n");
    exit(1);
}

require_once $dbPath;
require_once dirname(__DIR__) . '/includes/ApTetCatalog.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "seed_ap_tet_standard_structure: start\n";

try {
    ApTetCatalog::standardizeApTet($pdo);
    echo "AP TET papers / subjects / pivots synced.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'standardizeApTet failed: ' . $e->getMessage() . "\n");
    exit(1);
}

ApTetCatalog::ensureSkeletonFiveTierTests($pdo);
echo "Five-tier skeleton tests ensured where missing.\n";

echo "seed_ap_tet_standard_structure: complete\n";
