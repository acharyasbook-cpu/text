<?php

/**
 * AP DSC + TS DSC — PGT Commerce & Economics programmes only (subjects, pivots, plans).
 *
 * CLI: php database/seed_pgt_commerce_economics.php
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found.\n");
    exit(1);
}

require_once $dbPath;
require_once dirname(__DIR__) . '/includes/ApDscTgtPgtCatalog.php';
require_once dirname(__DIR__) . '/includes/TsDscCatalog.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "seed_pgt_commerce_economics: start\n";

try {
    ApDscTgtPgtCatalog::seedPgtCommerceEconomicsApDsc($pdo);
    echo "AP DSC PGT Commerce & Economics synced.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'AP DSC seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

try {
    $tsCourseId = TsDscCatalog::seedPgtCommerceEconomicsTsDsc($pdo);
    echo "TS DSC PGT Commerce & Economics programmes / pivots synced.\n";
    ApDscTgtPgtCatalog::ensurePlansForSlugPattern($pdo, $tsCourseId, 'ts-pgt-commerce');
    ApDscTgtPgtCatalog::ensurePlansForSlugPattern($pdo, $tsCourseId, 'ts-pgt-economics');
    echo "TS DSC plan slabs ensured for commerce & economics.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'TS DSC seed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

echo "seed_pgt_commerce_economics: complete\n";
