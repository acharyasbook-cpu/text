<?php

/**
 * Absolute catalog reset: 5 main courses + rigid sub-course order from CourseCatalogRegistry.
 * CLI: php database/migrate_catalog_reset.php
 *
 * Optional: CATALOG_SYNC_HIERARCHY=1 php database/migrate_catalog_reset.php
 *   → also runs migrate_dynamic_hierarchy.php to refresh subject pivots / plans.
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found.\n");
    exit(1);
}
require_once $dbPath;
require_once dirname(__DIR__) . '/includes/CourseCatalogRegistry.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function cr_tableType(PDO $pdo, string $name): ?string
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?'
    );
    $st->execute([$db, $name]);

    return $st->fetchColumn() ?: null;
}

function cr_colExists(PDO $pdo, string $t, string $c): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $t, $c]);

    return (int) $st->fetchColumn() > 0;
}

function cr_mainTable(PDO $pdo): string
{
    if (cr_tableType($pdo, 'main_courses') === 'BASE TABLE') {
        return 'main_courses';
    }

    return 'courses';
}

echo "migrate_catalog_reset: start\n";

$mainTable = cr_mainTable($pdo);
$hasTe = cr_colExists($pdo, 'sub_courses', 'name_te');
$hasScTe = cr_colExists($pdo, 'sub_courses', 'name_te');
$hasMcTe = cr_colExists($pdo, $mainTable, 'name_te');
$hasStatus = cr_colExists($pdo, 'sub_courses', 'status');
$hasMcStatus = cr_colExists($pdo, $mainTable, 'status');

$allowedMain = CourseCatalogRegistry::mainCourseSlugs();

// --- Purge extraneous main courses ---
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$stAllMc = $pdo->query("SELECT id, slug FROM `{$mainTable}`")->fetchAll(PDO::FETCH_ASSOC);
foreach ($stAllMc as $mc) {
    if (!in_array($mc['slug'], $allowedMain, true)) {
        $id = (int) $mc['id'];
        echo "delete main course: {$mc['slug']} (id {$id})\n";
        if (cr_tableType($pdo, 'sub_courses') === 'BASE TABLE') {
            $pdo->prepare('DELETE FROM sub_courses WHERE course_id=?')->execute([$id]);
        }
        $pdo->prepare("DELETE FROM `{$mainTable}` WHERE id=?")->execute([$id]);
    }
}

// --- Upsert main courses (sort_order 1..5) ---
$mcCols = ['slug', 'name', 'sort_order'];
$mcVals = ['?', '?', '?'];
if ($hasMcTe) {
    $mcCols[] = 'name_te';
    $mcVals[] = '?';
}
if ($hasMcStatus) {
    $mcCols[] = 'status';
    $mcVals[] = '1';
}
if (cr_colExists($pdo, $mainTable, 'is_active')) {
    $mcCols[] = 'is_active';
    $mcVals[] = '1';
}
$onMc = 'name=VALUES(name), sort_order=VALUES(sort_order)';
if ($hasMcTe) {
    $onMc .= ', name_te=VALUES(name_te)';
}
if ($hasMcStatus) {
    $onMc .= ', status=1';
}
if (cr_colExists($pdo, $mainTable, 'is_active')) {
    $onMc .= ', is_active=1';
}
$insMc = $pdo->prepare(
    'INSERT INTO `' . $mainTable . '` (`' . implode('`,`', $mcCols) . '`) VALUES (' . implode(',', $mcVals) . ')
     ON DUPLICATE KEY UPDATE ' . $onMc
);

$slugToCourseId = [];
foreach (CourseCatalogRegistry::mainCourses() as $mc) {
    $params = [$mc['slug'], $mc['name'], $mc['sort_order']];
    if ($hasMcTe) {
        $params[] = $mc['name_te'];
    }
    $insMc->execute($params);
    $st = $pdo->prepare("SELECT id FROM `{$mainTable}` WHERE slug=? LIMIT 1");
    $st->execute([$mc['slug']]);
    $slugToCourseId[$mc['slug']] = (int) $st->fetchColumn();
    echo "main course: {$mc['slug']} sort={$mc['sort_order']} id={$slugToCourseId[$mc['slug']]}\n";
}

// --- Per main course: purge + upsert sub-courses ---
if (cr_tableType($pdo, 'sub_courses') !== 'BASE TABLE') {
    echo "sub_courses table missing — main courses only.\n";
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "migrate_catalog_reset: done (partial)\n";
    exit(0);
}

$scCols = ['course_id', 'slug', 'name', 'sort_order'];
$scVals = ['?', '?', '?', '?'];
if ($hasScTe) {
    $scCols[] = 'name_te';
    $scVals[] = '?';
}
if ($hasStatus) {
    $scCols[] = 'status';
    $scVals[] = '1';
}
if (cr_colExists($pdo, 'sub_courses', 'is_active')) {
    $scCols[] = 'is_active';
    $scVals[] = '1';
}
$onSc = 'name=VALUES(name), sort_order=VALUES(sort_order)';
if ($hasScTe) {
    $onSc .= ', name_te=VALUES(name_te)';
}
if ($hasStatus) {
    $onSc .= ', status=1';
}
if (cr_colExists($pdo, 'sub_courses', 'is_active')) {
    $onSc .= ', is_active=1';
}
$insSc = $pdo->prepare(
    'INSERT INTO sub_courses (`' . implode('`,`', $scCols) . '`) VALUES (' . implode(',', $scVals) . ')
     ON DUPLICATE KEY UPDATE ' . $onSc
);

foreach ($slugToCourseId as $mcSlug => $courseId) {
    $allowedSc = CourseCatalogRegistry::subCourseSlugsFor($mcSlug);
    if ($allowedSc === []) {
        continue;
    }

    $in = implode(',', array_fill(0, count($allowedSc), '?'));
    $stRm = $pdo->prepare("SELECT id, slug FROM sub_courses WHERE course_id=? AND slug NOT IN ({$in})");
    $stRm->execute(array_merge([$courseId], $allowedSc));
    while ($row = $stRm->fetch(PDO::FETCH_ASSOC)) {
        $scId = (int) $row['id'];
        echo "delete sub-course: {$mcSlug}/{$row['slug']} (id {$scId})\n";
        if (cr_tableType($pdo, 'sub_course_subjects') === 'BASE TABLE') {
            $pdo->prepare('DELETE FROM sub_course_subjects WHERE sub_course_id=?')->execute([$scId]);
        }
        if (cr_tableType($pdo, 'sub_course_plans') === 'BASE TABLE') {
            $pdo->prepare('DELETE FROM sub_course_plans WHERE sub_course_id=?')->execute([$scId]);
        }
        if (cr_tableType($pdo, 'sub_course_term_boxes') === 'BASE TABLE') {
            $pdo->prepare('DELETE FROM sub_course_term_boxes WHERE sub_course_id=?')->execute([$scId]);
        }
        $pdo->prepare('DELETE FROM sub_courses WHERE id=?')->execute([$scId]);
    }

    foreach (CourseCatalogRegistry::subCoursesFor($mcSlug) as $sc) {
        $params = [$courseId, $sc['slug'], $sc['name'], $sc['sort_order']];
        if ($hasScTe) {
            $params[] = $sc['name_te'];
        }
        $insSc->execute($params);
    }
    echo "sub-courses seeded: {$mcSlug} (" . count($allowedSc) . ")\n";
}

$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

if (getenv('CATALOG_SYNC_HIERARCHY') === '1') {
    echo "Running migrate_dynamic_hierarchy.php (subject pivots)…\n";
    putenv('HIERARCHY_RESEED=0');
    passthru('php ' . escapeshellarg(dirname(__DIR__) . '/database/migrate_dynamic_hierarchy.php'), $code);
    if ($code !== 0) {
        fwrite(STDERR, "migrate_dynamic_hierarchy exited with code {$code}\n");
    }
} else {
    echo "Tip: CATALOG_SYNC_HIERARCHY=1 php database/migrate_catalog_reset.php — refresh SGT/TET pivots\n";
}

echo "migrate_catalog_reset: complete\n";
