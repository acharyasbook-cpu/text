<?php

/**
 * TS TET & CTET — Paper II dual-stream structure (Maths & Science · Social Studies).
 * Drops legacy single Paper II cards if present, rescinds skeleton placeholders, re-syncs subjects/pivots/plans,
 * then ensures five-tier skeleton tests per pivoted subject.
 *
 * CLI: php database/seed_tstet_ctet_paper2_structure.php
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found.\n");
    exit(1);
}

require_once $dbPath;
require_once dirname(__DIR__) . '/includes/TsTetCatalog.php';
require_once dirname(__DIR__) . '/includes/CtetCatalog.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function tstet_ctet_resolve_course_id(PDO $pdo, string $slug): int
{
    foreach (['courses', 'main_courses'] as $table) {
        try {
            $st = $pdo->prepare("SELECT id FROM `{$table}` WHERE slug = ? LIMIT 1");
            $st->execute([$slug]);
            $id = (int) ($st->fetchColumn() ?: 0);
            if ($id > 0) {
                return $id;
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return 0;
}

echo "seed_tstet_ctet_paper2_structure: start\n";

foreach (['ts-tet', 'ctet'] as $mcSlug) {
    $cid = tstet_ctet_resolve_course_id($pdo, $mcSlug);
    if ($cid < 1) {
        fwrite(STDERR, "warning: main course {$mcSlug} not found — skipping skeleton purge\n");
        continue;
    }
    $pdo->prepare('DELETE FROM tests WHERE course_id = ? AND slug LIKE ?')->execute([$cid, 'skel-%']);
    echo "removed draft skeleton tests for {$mcSlug} (course id {$cid})\n";
}

try {
    TsTetCatalog::standardizeTsTet($pdo);
    echo "TS TET programmes / subjects / pivots synced (Paper II streams).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'TS TET sync failed: ' . $e->getMessage() . "\n");
    exit(1);
}

try {
    CtetCatalog::standardizeCtet($pdo);
    echo "CTET programmes / subjects / pivots synced (Paper II streams).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'CTET sync failed: ' . $e->getMessage() . "\n");
    exit(1);
}

TsTetCatalog::ensureSkeletonFiveTierTests($pdo);
CtetCatalog::ensureSkeletonFiveTierTests($pdo);
echo "Five-tier skeleton tests ensured for TS TET & CTET.\n";

echo "seed_tstet_ctet_paper2_structure: complete\n";
