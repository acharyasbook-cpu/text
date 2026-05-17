<?php

/**
 * Dynamic hierarchy: physical main_courses table + courses view compatibility,
 * topics.status visibility, DSC/TET/TET programme seed, pricing slab labels.
 *
 * Prerequisites: migrate_hierarchy.php, migrate_four_tier.php (and optionally migrate_topic_exams.php).
 * CLI: php database/migrate_dynamic_hierarchy.php
 *
 * Idempotent: safe to run multiple times.
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found.\n");
    exit(1);
}
require_once $dbPath;
require_once dirname(__DIR__) . '/includes/ApSaCatalog.php';
require_once dirname(__DIR__) . '/includes/ApDscTgtPgtCatalog.php';
require_once dirname(__DIR__) . '/includes/TsDscCatalog.php';
require_once dirname(__DIR__) . '/includes/ApTetCatalog.php';
require_once dirname(__DIR__) . '/includes/TsTetCatalog.php';
require_once dirname(__DIR__) . '/includes/CtetCatalog.php';
require_once dirname(__DIR__) . '/includes/CourseCatalogRegistry.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function mh_tableType(PDO $pdo, string $name): ?string
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?'
    );
    $st->execute([$db, $name]);

    return $st->fetchColumn() ?: null;
}

function mh_colExists(PDO $pdo, string $t, string $c): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $t, $c]);

    return (int) $st->fetchColumn() > 0;
}

function mh_hasBaseTable(PDO $pdo, string $name): bool
{
    return mh_tableType($pdo, $name) === 'BASE TABLE';
}

function mh_hasView(PDO $pdo, string $name): bool
{
    return mh_tableType($pdo, $name) === 'VIEW';
}

echo "migrate_dynamic_hierarchy: start\n";

// --- 1) Promote main_courses: physical table + courses as updatable view

if (mh_hasBaseTable($pdo, 'main_courses') && mh_hasView($pdo, 'courses')) {
    echo "main_courses + courses view already configured\n";
} elseif (mh_hasBaseTable($pdo, 'courses')) {
    if (mh_hasView($pdo, 'main_courses')) {
        $pdo->exec('DROP VIEW IF EXISTS `main_courses`');
        echo "dropped view main_courses (replacing with base table)\n";
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec('RENAME TABLE `courses` TO `main_courses`');
    $pdo->exec('CREATE OR REPLACE VIEW `courses` AS SELECT * FROM `main_courses`');
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    echo "renamed courses → main_courses; created view courses\n";
} elseif (mh_tableType($pdo, 'courses') === 'VIEW' && mh_hasBaseTable($pdo, 'main_courses')) {
    echo "already using main_courses base + courses view\n";
} else {
    fwrite(STDERR, "Neither courses nor main_courses base table found. Install schema/migrations first.\n");
    exit(1);
}

// --- 2) topics.visibility (status TinyInt)
$tTopics = mh_hasBaseTable($pdo, 'topics') ? 'topics' : (mh_hasBaseTable($pdo, 'lessons') ? 'lessons' : null);
if ($tTopics && !mh_colExists($pdo, $tTopics, 'status')) {
    $pdo->exec("ALTER TABLE `{$tTopics}` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1");
    echo "added {$tTopics}.status\n";
}

// --- 3) Normalize subscription plan copy & rupee amounts
if (mh_hasBaseTable($pdo, 'sub_course_plans')) {
    $pdo->exec(
        "UPDATE sub_course_plans SET label = CASE plan_code
            WHEN '6_months' THEN '6 Months (₹499)'
            WHEN '1_year' THEN '1 Year (₹699)'
            WHEN 'until_exam' THEN 'Up to Exam (₹999)'
            ELSE label END,
        price_inr = CASE plan_code
            WHEN '6_months' THEN 499.00
            WHEN '1_year' THEN 699.00
            WHEN 'until_exam' THEN 999.00
            ELSE price_inr END"
    );
    echo "sub_course_plans labels/prices normalized\n";
}

if (!mh_hasBaseTable($pdo, 'sub_courses') || !mh_hasBaseTable($pdo, 'sub_course_subjects')) {
    echo "sub_courses / pivot missing — run migrate_four_tier.php first. Stopping seed.\n";
    echo "migrate_dynamic_hierarchy: partial complete\n";
    exit(0);
}

// --- 4) Granular sub-courses (SGT, PET, SA × subject, TGT × subject, PGT × subject)
$targetSlugs = ['ap-dsc', 'ts-dsc', 'ap-tet', 'ts-tet', 'ctet'];
$slugByCourse = [];
$stMc = $pdo->prepare('SELECT id, slug FROM main_courses WHERE slug = ?');
foreach ($targetSlugs as $mcSlug) {
    $stMc->execute([$mcSlug]);
    if ($row = $stMc->fetch(PDO::FETCH_ASSOC)) {
        $slugByCourse[$mcSlug] = (int) $row['id'];
    }
}

if (count($slugByCourse) !== count($targetSlugs)) {
    echo 'warning: expected main courses missing from DB — have: ' . implode(',', array_keys($slugByCourse)) . "\n";
}

$subjectCanonical = [
    ['slug' => 'telugu', 'name' => 'Telugu', 'te' => 'తెలుగు', 'sort' => 1],
    ['slug' => 'hindi', 'name' => 'Hindi', 'te' => 'హిందీ', 'sort' => 2],
    ['slug' => 'english', 'name' => 'English', 'te' => 'ఇంగ్లీష్', 'sort' => 3],
    ['slug' => 'maths', 'name' => 'Mathematics', 'te' => 'గణితం', 'sort' => 4],
    ['slug' => 'physical-science', 'name' => 'Physical Science', 'te' => 'భౌతిక శాస్త్రం', 'sort' => 5],
    ['slug' => 'biological-science', 'name' => 'Biological Science', 'te' => 'జీవ శాస్త్రం', 'sort' => 6],
    ['slug' => 'social-studies', 'name' => 'Social Studies', 'te' => 'సామాజిక శాస్త్రం', 'sort' => 7],
    ['slug' => 'commerce', 'name' => 'Commerce', 'te' => 'వాణిజ్యం', 'sort' => 8],
    ['slug' => 'economics', 'name' => 'Economics', 'te' => 'ఆర్థిక శాస్త్రం', 'sort' => 9],
];

$insSubject = $pdo->prepare(
    'INSERT INTO subjects (course_id, category_id, slug, name, name_te, description, sort_order, status, is_active)
     VALUES (NULL, NULL, ?, ?, ?, NULL, ?, 1, 1)
     ON DUPLICATE KEY UPDATE name = VALUES(name), name_te = VALUES(name_te), sort_order = VALUES(sort_order), status = 1, is_active = 1'
);

foreach ($subjectCanonical as $sc) {
    $insSubject->execute([$sc['slug'], $sc['name'], $sc['te'], $sc['sort']]);
}

// AP DSC — SGT programme subject rows (eight papers under SGT)
$sgtSubjectRowsAp = [
    ['gk-current-affairs', 'GK & Current Affairs', 'జి.కె. & కరెంట్ అఫైర్స్', 1],
    ['perspective-education', 'Perspective in Education', 'విద్యా దృక్కోణాలు', 2],
    ['telugu', 'Telugu', 'తెలుగు', 3],
    ['english', 'English', 'ఇంగ్లీష్', 4],
    ['mathematics', 'Mathematics', 'గణితం', 5],
    ['science', 'Science', 'సైన్స్', 6],
    ['social-studies', 'Social Studies', 'సామాజిక శాస్త్రం', 7],
    ['tri-methods', 'Tri-Methods (Methodology)', 'ట్రై-విధానాలు (పద్ధతిశాస్త్రం)', 8],
];

// TS DSC — SGT: Telangana-specific paper list (no standalone Telugu/English papers)
$sgtSubjectRowsTs = [
    ['gk-current-affairs', 'GK & Current Affairs', 'జీకే & కరెంట్ అఫైర్స్', 1],
    ['perspective-education', 'Perspective in Education', 'పర్ స్పెక్టివ్ ఇన్ ఎడ్యుకేషన్', 2],
    ['content-mathematics', 'Content (Mathematics)', 'కంటెంట్ (గణితం)', 3],
    ['content-science', 'Content (Science)', 'కంటెంట్ (సైన్స్)', 4],
    ['content-social-studies', 'Content (Social Studies)', 'కంటెంట్ (సామాజిక శాస్త్రం)', 5],
    ['tri-methods', 'Tri-Methods (Methodology)', 'ట్రై-మెథడాలజీ', 6],
];

foreach ($sgtSubjectRowsAp as $row) {
    $fullSlug = 'ap-dsc-sgt-' . $row[0];
    $insSubject->execute([$fullSlug, $row[1], $row[2], $row[3]]);
}

foreach ($sgtSubjectRowsTs as $row) {
    $fullSlug = 'ts-dsc-sgt-' . $row[0];
    $insSubject->execute([$fullSlug, $row[1], $row[2], $row[3]]);
}

// AP DSC — AP SA + AP TGT + AP PGT paper subjects (global subjects library)
ApSaCatalog::ensureSubjects($pdo);
ApDscTgtPgtCatalog::ensureSubjects($pdo);

/** Allowed sub_course.slug values per main course (canonical registry whitelist). */
function mh_allowed_subcourse_slugs_for_course(string $mcSlug): array
{
    return CourseCatalogRegistry::hierarchyWhitelistFor($mcSlug);
}

function mh_exam_short_prefix(string $mcSlug): string
{
    return match ($mcSlug) {
        'ap-dsc' => 'AP',
        'ts-dsc' => 'TS',
        'ap-tet' => 'AP TET',
        'ts-tet' => 'TS TET',
        'ctet' => 'CTET',
        default => strtoupper(str_replace('-', ' ', $mcSlug)),
    };
}

function mh_subject_title_token(string $key): string
{
    return match ($key) {
        'telugu' => 'Telugu',
        'hindi' => 'Hindi',
        'english' => 'English',
        'maths' => 'Maths',
        'physical-science' => 'Physical Science',
        'biological-science' => 'Biological Science',
        'social-studies' => 'Social Studies',
        'commerce' => 'Commerce',
        'economics' => 'Economics',
        default => ucfirst(str_replace('-', ' ', $key)),
    };
}

function mh_flagshipNeedsProgrammeReset(PDO $pdo, string $mcSlug, int $courseId, bool $force): bool
{
    if ($force) {
        return true;
    }
    $standard = mh_allowed_subcourse_slugs_for_course($mcSlug);
    $in = implode(',', array_fill(0, count($standard), '?'));
    $sql = "SELECT COUNT(*) FROM sub_courses WHERE course_id = ? AND slug NOT IN ({$in})";
    $st = $pdo->prepare($sql);
    $st->execute(array_merge([$courseId], $standard));

    return (int) $st->fetchColumn() > 0;
}

function mh_resolveSubjectId(PDO $pdo, string $preferred, array $aliases): int
{
    $slugs = array_values(array_unique(array_merge([$preferred], $aliases)));
    $in = implode(',', array_fill(0, count($slugs), '?'));
    $sql = "SELECT id, slug FROM subjects WHERE slug IN ({$in})";
    $st = $pdo->prepare($sql);
    $st->execute($slugs);
    $found = [];
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        $found[(string) $r['slug']] = (int) $r['id'];
    }
    foreach ($slugs as $s) {
        if (isset($found[$s])) {
            return $found[$s];
        }
    }

    throw new RuntimeException('Subject slug missing after insert: ' . $preferred);
}

function mh_applyRegistrySubCourseLabels(PDO $pdo, string $mcSlug, int $courseId): void
{
    $hasTe = mh_colExists($pdo, 'sub_courses', 'name_te');
    $upd = $pdo->prepare(
        'UPDATE sub_courses SET name=?, sort_order=?, status=1, is_active=1'
        . ($hasTe ? ', name_te=?' : '')
        . ' WHERE course_id=? AND slug=?'
    );
    foreach (CourseCatalogRegistry::subCoursesFor($mcSlug) as $row) {
        $params = [$row['name'], $row['sort_order']];
        if ($hasTe) {
            $params[] = $row['name_te'];
        }
        $params[] = $courseId;
        $params[] = $row['slug'];
        $upd->execute($params);
    }
}

function mh_ensurePlansForCourse(PDO $pdo, PDOStatement $plansInsert, int $courseId): void
{
    $stAll = $pdo->prepare('SELECT id FROM sub_courses WHERE course_id = ? ORDER BY sort_order ASC, id ASC');
    $stAll->execute([$courseId]);
    $defaults = [
        ['6_months', '6 Months (₹499)', 499.00, 6],
        ['1_year', '1 Year (₹699)', 699.00, 12],
        ['until_exam', 'Up to Exam (₹999)', 999.00, null],
    ];
    while ($rid = $stAll->fetchColumn()) {
        $scid = (int) $rid;
        foreach ($defaults as [$code, $label, $price, $months]) {
            $plansInsert->execute([$scid, $code, $label, $price, $months]);
        }
    }
}

function mh_syncApDscSgtPivots(PDO $pdo, int $courseId, PDOStatement $pivotIns): void
{
    $stId = $pdo->prepare('SELECT id FROM sub_courses WHERE course_id = ? AND slug = ? LIMIT 1');
    $stId->execute([$courseId, 'sgt']);
    $sgtId = (int) $stId->fetchColumn();
    if ($sgtId < 1) {
        return;
    }
    $pdo->prepare('DELETE FROM sub_course_subjects WHERE sub_course_id=?')->execute([$sgtId]);
    $order = 0;
    foreach ([
        'gk-current-affairs',
        'perspective-education',
        'telugu',
        'english',
        'mathematics',
        'science',
        'social-studies',
        'tri-methods',
    ] as $skey) {
        $ssid = 'ap-dsc-sgt-' . $skey;
        $pivotIns->execute([$sgtId, mh_resolveSubjectId($pdo, $ssid, []), $order, 1]);
        ++$order;
    }
}

function mh_syncTsDscSgtPivots(PDO $pdo, int $courseId, PDOStatement $pivotIns): void
{
    $stId = $pdo->prepare('SELECT id FROM sub_courses WHERE course_id = ? AND slug = ? LIMIT 1');
    $stId->execute([$courseId, 'sgt']);
    $sgtId = (int) $stId->fetchColumn();
    if ($sgtId < 1) {
        return;
    }
    $pdo->prepare('DELETE FROM sub_course_subjects WHERE sub_course_id=?')->execute([$sgtId]);
    $order = 0;
    foreach ([
        'gk-current-affairs',
        'perspective-education',
        'content-mathematics',
        'content-science',
        'content-social-studies',
        'tri-methods',
    ] as $skey) {
        $ssid = 'ts-dsc-sgt-' . $skey;
        $pivotIns->execute([$sgtId, mh_resolveSubjectId($pdo, $ssid, []), $order, 1]);
        ++$order;
    }
}

/** @return array<int,array<string,mixed>> */
function mh_seedSubCourses(PDO $pdo, array $slugByCourse, array $subCourseDefines, array $paperNames, bool $truncateProgrammes): int
{
    $plansInsert = null;
    if (mh_hasBaseTable($pdo, 'sub_course_plans')) {
        $plansInsert = $pdo->prepare(
            'INSERT IGNORE INTO sub_course_plans (sub_course_id, plan_code, label, price_inr, duration_months, status, is_active) VALUES (?,?,?,?,?,1,1)'
        );
    }

    $insSc = $pdo->prepare(
        'INSERT INTO sub_courses (course_id, slug, name, description, sort_order, status, is_active)
         VALUES (?,?,?,?,?,?,?)
         ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), sort_order = VALUES(sort_order), status = VALUES(status), is_active = VALUES(is_active)'
    );

    $pivotIns = $pdo->prepare(
        'INSERT IGNORE INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active) VALUES (?,?,?,?,1)'
    );

    $countSc = 0;

    foreach ($slugByCourse as $mcSlug => $courseId) {
        if (mh_flagshipNeedsProgrammeReset($pdo, $mcSlug, (int) $courseId, $truncateProgrammes)) {
            fwrite(STDOUT, "replacing legacy sub-courses for main course: {$mcSlug} (id {$courseId})\n");
            $pdo->prepare(
                'DELETE scs FROM sub_course_subjects scs
                 JOIN sub_courses sc ON sc.id = scs.sub_course_id
                 WHERE sc.course_id = ?'
            )->execute([$courseId]);
            $pdo->prepare('DELETE FROM sub_courses WHERE course_id = ?')->execute([$courseId]);
        }

        $papers = $paperNames[$mcSlug] ?? ['sgt' => 'SGT Programme', 'pet' => 'PET Programme'];

        if ($mcSlug === 'ap-tet') {
            ApTetCatalog::ensureProgrammes($pdo, (int) $courseId);
            ApTetCatalog::ensureSubjects($pdo);
            ApTetCatalog::syncAllProgrammePivots($pdo, (int) $courseId);
            ApTetCatalog::ensurePlansForProgrammes($pdo, (int) $courseId);
            mh_applyRegistrySubCourseLabels($pdo, 'ap-tet', (int) $courseId);
            $countSc += count(ApTetCatalog::programmes());

            if ($plansInsert) {
                $stAll = $pdo->prepare('SELECT id FROM sub_courses WHERE course_id = ? ORDER BY sort_order');
                $stAll->execute([$courseId]);
                $defaults = [
                    ['6_months', '6 Months (₹499)', 499.00, 6],
                    ['1_year', '1 Year (₹699)', 699.00, 12],
                    ['until_exam', 'Up to Exam (₹999)', 999.00, null],
                ];
                while ($rid = $stAll->fetchColumn()) {
                    $scid = (int) $rid;
                    foreach ($defaults as [$code, $label, $price, $months]) {
                        $plansInsert->execute([$scid, $code, $label, $price, $months]);
                    }
                }
            }

            continue;
        }

        if ($mcSlug === 'ts-tet') {
            TsTetCatalog::standardizeTsTet($pdo);
            mh_applyRegistrySubCourseLabels($pdo, 'ts-tet', (int) $courseId);
            $countSc += count(TsTetCatalog::programmes());

            continue;
        }

        if ($mcSlug === 'ctet') {
            CtetCatalog::standardizeCtet($pdo);
            mh_applyRegistrySubCourseLabels($pdo, 'ctet', (int) $courseId);
            $countSc += count(CtetCatalog::programmes());

            continue;
        }

        if ($mcSlug === 'ap-dsc') {
            ApSaCatalog::ensureSubjects($pdo);
            ApDscTgtPgtCatalog::ensureSubjects($pdo);
            ApSaCatalog::ensureProgrammes($pdo, (int) $courseId);
            ApDscTgtPgtCatalog::ensureTgtProgrammes($pdo, (int) $courseId);
            ApDscTgtPgtCatalog::ensurePgtProgrammes($pdo, (int) $courseId);
            mh_syncApDscSgtPivots($pdo, (int) $courseId, $pivotIns);
            ApSaCatalog::syncProgrammePivots($pdo, (int) $courseId);
            ApDscTgtPgtCatalog::syncTgtProgrammePivots($pdo, (int) $courseId);
            ApDscTgtPgtCatalog::syncPgtProgrammePivots($pdo, (int) $courseId);
            mh_applyRegistrySubCourseLabels($pdo, 'ap-dsc', (int) $courseId);
            $countSc += count(CourseCatalogRegistry::subCoursesFor('ap-dsc'));
            if ($plansInsert) {
                mh_ensurePlansForCourse($pdo, $plansInsert, (int) $courseId);
            }

            continue;
        }

        if ($mcSlug === 'ts-dsc') {
            TsDscCatalog::ensureSubjects($pdo);
            TsDscCatalog::ensureSaProgrammes($pdo, (int) $courseId);
            mh_syncTsDscSgtPivots($pdo, (int) $courseId, $pivotIns);
            TsDscCatalog::syncSaProgrammePivots($pdo, (int) $courseId);
            mh_applyRegistrySubCourseLabels($pdo, 'ts-dsc', (int) $courseId);
            $countSc += count(CourseCatalogRegistry::subCoursesFor('ts-dsc'));
            if ($plansInsert) {
                mh_ensurePlansForCourse($pdo, $plansInsert, (int) $courseId);
                TsDscCatalog::ensurePlansForTsStructuredProgrammes($pdo, (int) $courseId);
            }

            continue;
        }

        foreach ($subCourseDefines as $def) {
            $slug = $def['slug'];
            $name = $def['name'];
            $desc = null;
            if ($slug === 'sgt') {
                $desc = $papers['sgt'];
            } elseif ($slug === 'pet') {
                $desc = $papers['pet'];
            }
            $insSc->execute([$courseId, $slug, $name, $desc, $def['sort'], 1, 1]);
            ++$countSc;
        }

        if ($mcSlug === 'ap-dsc') {
            ApSaCatalog::ensureProgrammes($pdo, (int) $courseId);
            ApDscTgtPgtCatalog::ensureTgtProgrammes($pdo, (int) $courseId);
            ApDscTgtPgtCatalog::ensurePgtProgrammes($pdo, (int) $courseId);
        }
        if ($mcSlug === 'ts-dsc') {
            TsDscCatalog::ensureSubjects($pdo);
            TsDscCatalog::ensureSaProgrammes($pdo, (int) $courseId);
            TsDscCatalog::ensureTgtProgrammes($pdo, (int) $courseId);
            TsDscCatalog::ensurePgtProgrammes($pdo, (int) $courseId);
        }

        $stId = $pdo->prepare('SELECT id FROM sub_courses WHERE course_id = ? AND slug = ? LIMIT 1');

        $link = static function (int $subId, array $subjectSlugs, PDO $pdo, PDOStatement $pivotIns): void {
            $ord = 0;
            foreach ($subjectSlugs as $ss) {
                $aliases = [];
                if ($ss === 'english') {
                    $aliases = ['ap-dsc-english'];
                }
                if ($ss === 'telugu') {
                    $aliases = ['ap-dsc-telugu', 'ts-dsc-telugu'];
                }
                if ($ss === 'maths') {
                    $aliases = ['mathematics', 'ap-dsc-maths'];
                }
                $sid = mh_resolveSubjectId($pdo, $ss, $aliases);
                $pivotIns->execute([$subId, $sid, $ord, 1]);
                ++$ord;
            }
        };

        // SGT — AP/TS DSC: eight subject papers; other programmes: single legacy paper row
        $stId->execute([$courseId, 'sgt']);
        $sgtId = (int) $stId->fetchColumn();
        if ($mcSlug === 'ap-dsc') {
            $order = 0;
            foreach ([
                'gk-current-affairs',
                'perspective-education',
                'telugu',
                'english',
                'mathematics',
                'science',
                'social-studies',
                'tri-methods',
            ] as $skey) {
                $ssid = $mcSlug . '-sgt-' . $skey;
                $pivotIns->execute([$sgtId, mh_resolveSubjectId($pdo, $ssid, []), $order, 1]);
                ++$order;
            }
        } elseif ($mcSlug === 'ts-dsc') {
            $order = 0;
            foreach ([
                'gk-current-affairs',
                'perspective-education',
                'content-mathematics',
                'content-science',
                'content-social-studies',
                'tri-methods',
            ] as $skey) {
                $ssid = $mcSlug . '-sgt-' . $skey;
                $pivotIns->execute([$sgtId, mh_resolveSubjectId($pdo, $ssid, []), $order, 1]);
                ++$order;
            }
        } else {
            $sgtPaperSlug = 'sgt-paper-' . str_replace('-', '_', $mcSlug);
            $sgtName = $papers['sgt'];
            $pdo->prepare(
                'INSERT INTO subjects (course_id, category_id, slug, name, name_te, description, sort_order, status, is_active)
                 VALUES (NULL, NULL, ?, ?, NULL, NULL, 0, 1, 1)
                 ON DUPLICATE KEY UPDATE name = VALUES(name), status = 1, is_active = 1'
            )->execute([$sgtPaperSlug, $sgtName]);
            $pivotIns->execute([$sgtId, mh_resolveSubjectId($pdo, $sgtPaperSlug, []), 0, 1]);
        }

        // PET
        $stId->execute([$courseId, 'pet']);
        $petId = (int) $stId->fetchColumn();
        $petPaperSlug = 'pet-paper-' . str_replace('-', '_', $mcSlug);
        $petName = $papers['pet'];
        $pdo->prepare(
            'INSERT INTO subjects (course_id, category_id, slug, name, name_te, description, sort_order, status, is_active)
             VALUES (NULL, NULL, ?, ?, NULL, NULL, 0, 1, 1)
             ON DUPLICATE KEY UPDATE name = VALUES(name), status = 1, is_active = 1'
        )->execute([$petPaperSlug, $petName]);
        $pivotIns->execute([$petId, mh_resolveSubjectId($pdo, $petPaperSlug, []), 0, 1]);

        // SA — AP DSC: generic programme card stays empty; each track uses ap-sa-* programmes
        $saSubjectKeys = ['telugu', 'hindi', 'english', 'maths', 'physical-science', 'biological-science', 'social-studies'];
        if ($mcSlug === 'ap-dsc') {
            ApSaCatalog::purgeGenericSchoolAssistantPivots($pdo, (int) $courseId);
            $saSubjectKeys = [];
        }
        if ($mcSlug === 'ts-dsc') {
            TsDscCatalog::purgeGenericSchoolAssistantPivots($pdo, (int) $courseId);
            $saSubjectKeys = [];
        }
        $stId->execute([$courseId, 'school-assistant']);
        $saId = (int) $stId->fetchColumn();
        $link($saId, $saSubjectKeys, $pdo, $pivotIns);

        if ($mcSlug === 'ap-dsc') {
            ApSaCatalog::syncProgrammePivots($pdo, (int) $courseId);
        }
        if ($mcSlug === 'ts-dsc') {
            TsDscCatalog::syncSaProgrammePivots($pdo, (int) $courseId);
        }

        // TGT — AP DSC: generic card empty; tracks use ap-tgt-*
        $tgtSubjectKeys = ['telugu', 'english', 'hindi', 'maths', 'physical-science', 'biological-science', 'social-studies'];
        if ($mcSlug === 'ap-dsc') {
            ApDscTgtPgtCatalog::purgeGenericTgtPivots($pdo, (int) $courseId);
            $tgtSubjectKeys = [];
        }
        if ($mcSlug === 'ts-dsc') {
            TsDscCatalog::purgeGenericTgtPivots($pdo, (int) $courseId);
            $tgtSubjectKeys = [];
        }
        $stId->execute([$courseId, 'tgt']);
        $tgtId = (int) $stId->fetchColumn();
        $link($tgtId, $tgtSubjectKeys, $pdo, $pivotIns);

        // PGT — AP DSC: generic card empty; tracks use ap-pgt-*
        $pgtSubjectKeys = ['telugu', 'english', 'hindi', 'maths', 'physical-science', 'biological-science', 'social-studies', 'commerce', 'economics'];
        if ($mcSlug === 'ap-dsc') {
            ApDscTgtPgtCatalog::purgeGenericPgtPivots($pdo, (int) $courseId);
            $pgtSubjectKeys = [];
        }
        if ($mcSlug === 'ts-dsc') {
            TsDscCatalog::purgeGenericPgtPivots($pdo, (int) $courseId);
            $pgtSubjectKeys = [];
        }
        $stId->execute([$courseId, 'pgt']);
        $pgtId = (int) $stId->fetchColumn();
        $link($pgtId, $pgtSubjectKeys, $pdo, $pivotIns);

        if ($mcSlug === 'ap-dsc') {
            ApDscTgtPgtCatalog::syncTgtProgrammePivots($pdo, (int) $courseId);
            ApDscTgtPgtCatalog::syncPgtProgrammePivots($pdo, (int) $courseId);
        }
        if ($mcSlug === 'ts-dsc') {
            TsDscCatalog::syncTgtProgrammePivots($pdo, (int) $courseId);
            TsDscCatalog::syncPgtProgrammePivots($pdo, (int) $courseId);
            TsDscCatalog::ensurePlansForTsStructuredProgrammes($pdo, (int) $courseId);
        }

        if ($plansInsert) {
            $stAll = $pdo->prepare('SELECT id FROM sub_courses WHERE course_id = ? ORDER BY sort_order');
            $stAll->execute([$courseId]);
            $defaults = [
                ['6_months', '6 Months (₹499)', 499.00, 6],
                ['1_year', '1 Year (₹699)', 699.00, 12],
                ['until_exam', 'Up to Exam (₹999)', 999.00, null],
            ];
            while ($rid = $stAll->fetchColumn()) {
                $scid = (int) $rid;
                foreach ($defaults as [$code, $label, $price, $months]) {
                    $plansInsert->execute([$scid, $code, $label, $price, $months]);
                }
            }
        }
    }

    return $countSc;
}

$truncate = getenv('HIERARCHY_RESEED') === '1';
if ($truncate) {
    echo "HIERARCHY_RESEED=1 — wiping flagship sub_courses + pivots before seed\n";
}

$paperNames = [];
foreach ($targetSlugs as $mc) {
    $paperNames[$mc] = [
        'sgt' => 'SGT Programme',
        'pet' => 'PET Programme',
    ];
}

$subCourseDefines = [
    ['slug' => 'sgt', 'name' => 'SGT', 'sort' => 10],
    ['slug' => 'pet', 'name' => 'PET', 'sort' => 20],
    ['slug' => 'school-assistant', 'name' => 'School Assistant (SA)', 'sort' => 30],
    ['slug' => 'tgt', 'name' => 'TGT', 'sort' => 48],
    ['slug' => 'pgt', 'name' => 'PGT', 'sort' => 60],
];

$seeded = mh_seedSubCourses($pdo, $slugByCourse, $subCourseDefines, $paperNames, $truncate);
echo "sub_courses upserted rows (approx iterations): {$seeded}\n";
echo "Tip: run with HIERARCHY_RESEED=1 php database/migrate_dynamic_hierarchy.php to replace flagship programmes cleanly.\n";
echo "Tip: TS DSC skeleton tests → php database/seed_ts_dsc_standardized_structure.php\n";
echo "Tip: AP TET structure/tests → php database/seed_ap_tet_standard_structure.php\n";
echo "Tip: Strict TET purge + reseed (AP 7 papers, TS/CT Paper I–II streams) → php database/purge_and_reseed_tet_structure.php\n";
echo "Tip: TS TET / CTET Paper II streams only → php database/seed_tstet_ctet_paper2_structure.php\n";

echo "migrate_dynamic_hierarchy: complete\n";
