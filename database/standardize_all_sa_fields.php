<?php

/**
 * AP DSC — full flagship standardisation: School Assistant (`ap-sa-*`), TGT (`ap-tgt-*`),
 * PGT (`ap-pgt-*`), pivots, plans, optional 5-tier exam skeleton (never applied to TET/CTET).
 *
 * Prerequisites: migrate_exam_hierarchy.php, migrate_four_tier.php; run migrate_dynamic_hierarchy.php
 * when changing flagship sub-course slugs (optionally HIERARCHY_RESEED=1).
 *
 * CLI: php database/standardize_all_sa_fields.php
 *      php database/update_all_dsc_tgt_pgt_hierarchy.php (same runner)
 */

declare(strict_types=1);

/**
 * @param bool $seedExamSkeleton When true, seeds SaExamPattern for every structured AP DSC paper (idempotent).
 */
function run_standardize_all_sa_fields(bool $seedExamSkeleton): void
{
    $dbPath = dirname(__DIR__) . '/db_connect.php';
    if (!is_readable($dbPath)) {
        fwrite(STDERR, "db_connect.php not found.\n");
        exit(1);
    }
    require_once $dbPath;
    require_once dirname(__DIR__) . '/includes/ApSaCatalog.php';
    require_once dirname(__DIR__) . '/includes/ApDscTgtPgtCatalog.php';
    require_once dirname(__DIR__) . '/includes/SaExamPattern.php';

    $pdo = getDBConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "standardize_all_sa_fields: SA programmes (subjects + pivots + plans)\n";
    try {
        ApSaCatalog::standardizeApDsc($pdo);
    } catch (Throwable $e) {
        fwrite(STDERR, 'AP DSC SA standardise failed: ' . $e->getMessage() . "\n");
        exit(1);
    }

    echo "standardize_all_sa_fields: TGT/PGT programmes (subjects + pivots + plans)\n";
    try {
        ApDscTgtPgtCatalog::standardizeApDscTgtPgt($pdo);
    } catch (Throwable $e) {
        fwrite(STDERR, 'AP DSC TGT/PGT standardise failed: ' . $e->getMessage() . "\n");
        exit(1);
    }

    echo "standardize_all_sa_fields: removing misplaced sa5-ap-dsc-* tests from TET/CTET (if any)\n";
    ApDscTgtPgtCatalog::purgeErroneousTierMarkersFromNonDsc($pdo);

    if (!$seedExamSkeleton) {
        echo "standardize_all_sa_fields: complete (structure only)\n";

        return;
    }

    echo "standardize_all_sa_fields: seeding 5-tier exam patterns for structured AP DSC papers\n";

    $st = $pdo->prepare('SELECT id FROM courses WHERE slug = ? LIMIT 1');
    $st->execute(['ap-dsc']);
    $courseId = (int) ($st->fetchColumn() ?: 0);
    if ($courseId < 1) {
        fwrite(STDERR, "Course not found: ap-dsc\n");
        exit(1);
    }

    foreach (['ap-sa-%', 'ap-tgt-%', 'ap-pgt-%'] as $pattern) {
        $subSt = $pdo->prepare(
            'SELECT s.id, s.slug FROM subjects s
             INNER JOIN sub_course_subjects scs ON scs.subject_id = s.id
             INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE c.id = ? AND sc.slug LIKE ? ORDER BY sc.sort_order, sc.slug, scs.sort_order'
        );
        $subSt->execute([$courseId, $pattern]);
        while ($row = $subSt->fetch(PDO::FETCH_ASSOC)) {
            $sid = (int) $row['id'];
            $slug = (string) $row['slug'];
            try {
                $did = SaExamPattern::seedForApDscSubject($pdo, $courseId, $sid, $slug);
                echo ($did ? 'seeded exams: ' : 'exams skipped (already exists): ') . "{$slug} (subject id {$sid})\n";
            } catch (Throwable $e) {
                echo "exam seed error {$slug}: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "standardize_all_sa_fields: complete\n";
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === realpath(__FILE__)) {
    run_standardize_all_sa_fields(true);
}
