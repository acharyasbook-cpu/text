<?php

/**
 * Strict TET cleanup: remove DSC-style programmes (SGT / PET / SA / TGT / PGT) from AP TET, TS TET, CTET,
 * then reseed paper-based structures (TS TET / CTET Paper II = Maths & Science + Social Studies streams) and five-tier skeleton tests.
 *
 * Prerequisites: migrate_four_tier.php, migrate_dynamic_hierarchy.php (recommended).
 *
 * CLI: php database/purge_and_reseed_tet_structure.php
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found.\n");
    exit(1);
}

require_once $dbPath;
require_once dirname(__DIR__) . '/includes/ApTetCatalog.php';
require_once dirname(__DIR__) . '/includes/TsTetCatalog.php';
require_once dirname(__DIR__) . '/includes/CtetCatalog.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/** @return array{0:int,1:string}|null [id, slug] */
function tet_resolve_main_course(PDO $pdo, string $slug): ?array
{
    foreach (['courses', 'main_courses'] as $table) {
        try {
            $st = $pdo->prepare("SELECT id, slug FROM `{$table}` WHERE slug = ? LIMIT 1");
            $st->execute([$slug]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if ($row && (int) $row['id'] > 0) {
                return [(int) $row['id'], (string) $row['slug']];
            }
        } catch (Throwable $e) {
            continue;
        }
    }

    return null;
}

echo "purge_and_reseed_tet_structure: start\n";

$tetSlugs = ['ap-tet', 'ts-tet', 'ctet'];

foreach ($tetSlugs as $mcSlug) {
    $resolved = tet_resolve_main_course($pdo, $mcSlug);
    if ($resolved === null) {
        echo "skip {$mcSlug}: main course row not found\n";
        continue;
    }
    [$courseId] = $resolved;

    $pdo->prepare('DELETE FROM tests WHERE course_id = ? AND slug LIKE ?')->execute([$courseId, 'skel-%']);

    $pdo->prepare(
        'DELETE scs FROM sub_course_subjects scs
         INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
         WHERE sc.course_id = ?'
    )->execute([$courseId]);

    $pdo->prepare('DELETE FROM sub_courses WHERE course_id = ?')->execute([$courseId]);

    echo "purged sub_courses + pivots + skeleton tests for {$mcSlug} (course id {$courseId})\n";
}

try {
    ApTetCatalog::standardizeApTet($pdo);
    echo "AP TET reseeded (7 papers).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'AP TET reseed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

try {
    TsTetCatalog::standardizeTsTet($pdo);
    echo "TS TET reseeded (Paper 1 / Paper 2).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'TS TET reseed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

try {
    CtetCatalog::standardizeCtet($pdo);
    echo "CTET reseeded (Paper 1 / Paper 2).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'CTET reseed failed: ' . $e->getMessage() . "\n");
    exit(1);
}

ApTetCatalog::ensureSkeletonFiveTierTests($pdo);
TsTetCatalog::ensureSkeletonFiveTierTests($pdo);
CtetCatalog::ensureSkeletonFiveTierTests($pdo);
echo "Five-tier skeleton tests ensured for all TET pivoted subjects.\n";

echo "purge_and_reseed_tet_structure: complete\n";
