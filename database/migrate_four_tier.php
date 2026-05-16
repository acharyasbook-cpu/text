<?php

/**
 * Four-tier hierarchy: Course → Sub-course → Subject (global + pivot) → Topic
 * Run: php database/migrate_four_tier.php
 * Idempotent where possible. Run after migrate_hierarchy.php for smoothest path.
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found.\n");
    exit(1);
}
require_once $dbPath;

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function colExists(PDO $pdo, string $t, string $c): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $t, $c]);

    return (int) $st->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

echo "migrate_four_tier: start\n";

// --- 1) sub_courses
if (!tableExists($pdo, 'sub_courses')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE sub_courses (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  course_id INT UNSIGNED NOT NULL,
  slug VARCHAR(100) NOT NULL,
  name VARCHAR(200) NOT NULL,
  name_te VARCHAR(220) DEFAULT NULL,
  description TEXT,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_sc_course FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
  UNIQUE KEY uk_course_sub_slug (course_id, slug),
  KEY idx_sc_course_live (course_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created sub_courses\n";
}

if (!colExists($pdo, 'sub_courses', 'status')) {
    $pdo->exec('ALTER TABLE sub_courses ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1');
}

// Populate from course_categories if present & sub_courses empty
$cntSc = (int) $pdo->query('SELECT COUNT(*) FROM sub_courses')->fetchColumn();
if ($cntSc === 0 && tableExists($pdo, 'course_categories')) {
    $ins = $pdo->prepare(
        'INSERT INTO sub_courses (course_id, slug, name, name_te, sort_order, status, is_active)
         SELECT cc.course_id, CONCAT(c.slug, "-", cc.slug), cc.name, cc.name_te, cc.sort_order, cc.status, 1
         FROM course_categories cc JOIN courses c ON c.id = cc.course_id'
    );
    $ins->execute();
    echo "sub_courses seeded from course_categories\n";
}

if ($cntSc === 0 && !tableExists($pdo, 'course_categories')) {
    $pdo->exec(
        'INSERT INTO sub_courses (course_id, slug, name, name_te, sort_order, status, is_active)
         SELECT id, CONCAT(slug, "-general"), CONCAT(name, " — Overview"), NULL, 0, 1, 1 FROM courses'
    );
    echo "sub_courses seeded one-per-course\n";
}

// --- 2) Global subjects + pivot (before altering subjects FK)
if (!tableExists($pdo, 'sub_course_subjects')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE sub_course_subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_course_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uk_sc_sub (sub_course_id, subject_id),
  KEY idx_scs_subject (subject_id),
  CONSTRAINT fk_scs_sc FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE,
  CONSTRAINT fk_scs_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created sub_course_subjects\n";
}

if (tableExists($pdo, 'sub_course_subjects') && !colExists($pdo, 'sub_course_subjects', 'status')) {
    $pdo->exec('ALTER TABLE sub_course_subjects ADD COLUMN status TINYINT(1) NOT NULL DEFAULT 1');
    echo "Added sub_course_subjects.status\n";
}

// Unique global slugs (prefix with course slug)
if (colExists($pdo, 'subjects', 'course_id')) {
    try {
        $pdo->exec(
            'UPDATE subjects s JOIN courses c ON c.id = s.course_id
             SET s.slug = CASE WHEN LOCATE(CONCAT(c.slug,"-"), s.slug) = 1 THEN s.slug ELSE CONCAT(c.slug, "-", s.slug) END'
        );
    } catch (Throwable $e) {
        echo 'slug prefix note: ' . $e->getMessage() . "\n";
    }
}

$pivotEmpty = (int) $pdo->query('SELECT COUNT(*) FROM sub_course_subjects')->fetchColumn() === 0;
if ($pivotEmpty && colExists($pdo, 'subjects', 'course_id')) {
    // Map each subject → sub_course by category slug match, else first sub_course of course
    if (tableExists($pdo, 'course_categories')) {
        $sql = 'INSERT IGNORE INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active)
            SELECT sc.id, s.id, s.sort_order, COALESCE(s.status,1), COALESCE(s.is_active,1)
            FROM subjects s
            JOIN courses c ON c.id = s.course_id
            LEFT JOIN course_categories cc ON cc.id = s.category_id
            LEFT JOIN sub_courses sc ON sc.course_id = c.id AND (
                (cc.id IS NOT NULL AND sc.slug = CONCAT(c.slug, "-", cc.slug))
                OR (cc.id IS NULL AND sc.slug = (
                    SELECT sc2.slug FROM sub_courses sc2 WHERE sc2.course_id = c.id ORDER BY sc2.sort_order LIMIT 1
                ))
            )
            WHERE sc.id IS NOT NULL';
        try {
            $pdo->exec($sql);
            echo "pivot populated (category-aware)\n";
        } catch (Throwable $e) {
            echo 'pivot warn: ' . $e->getMessage() . "\n";
        }
    } else {
        $pdo->exec(
            'INSERT IGNORE INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active)
             SELECT (
                SELECT id FROM sub_courses sc WHERE sc.course_id = s.course_id ORDER BY sc.sort_order, sc.id LIMIT 1
             ), s.id, s.sort_order, COALESCE(s.status,1), COALESCE(s.is_active,1)
             FROM subjects s'
        );
        echo "pivot populated (first sub_course per subject course)\n";
    }

    // Orphans: subjects with no pivot yet
    $pdo->exec(
        'INSERT IGNORE INTO sub_course_subjects (sub_course_id, subject_id, sort_order, status, is_active)
         SELECT (
            SELECT id FROM sub_courses sc WHERE sc.course_id = s.course_id ORDER BY sc.sort_order, sc.id LIMIT 1
         ), s.id, s.sort_order, COALESCE(s.status,1), COALESCE(s.is_active,1)
         FROM subjects s
         LEFT JOIN sub_course_subjects z ON z.subject_id = s.id WHERE z.id IS NULL'
    );
}

// Drop FK subjects → courses ; allow NULL course_id
$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
try {
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $fk = $pdo->prepare(
        'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=? AND TABLE_NAME="subjects" AND REFERENCED_TABLE_NAME IS NOT NULL'
    );
    $fk->execute([$db]);
    foreach (array_unique($fk->fetchAll(PDO::FETCH_COLUMN)) as $name) {
        if ($name) {
            $pdo->exec("ALTER TABLE subjects DROP FOREIGN KEY `$name`");
            echo "dropped FK on subjects: $name\n";
        }
    }
} catch (Throwable $e) {
    echo 'FK drop warn: ' . $e->getMessage() . "\n";
}

if (colExists($pdo, 'subjects', 'course_id')) {
    try {
        $pdo->exec('ALTER TABLE subjects MODIFY course_id INT UNSIGNED NULL');
    } catch (Throwable $e) {
        echo 'course_id nullable: ' . $e->getMessage() . "\n";
    }
}

// Unique slug only
try {
    $pdo->exec('ALTER TABLE subjects DROP INDEX uk_course_slug');
} catch (Throwable $e) {
    // ignore
}
try {
    $pdo->exec('ALTER TABLE subjects ADD UNIQUE KEY uk_subject_slug (slug)');
} catch (Throwable $e) {
    echo 'unique slug: ' . $e->getMessage() . " — fix duplicate slugs manually\n";
}

$pdo->exec('SET FOREIGN_KEY_CHECKS=1');

// --- 3) Topics (rename lessons)
if (tableExists($pdo, 'lessons') && !tableExists($pdo, 'topics')) {
    $pdo->exec('RENAME TABLE lessons TO topics');
    echo "renamed lessons → topics\n";
}

if (tableExists($pdo, 'topics')) {
    if (!colExists($pdo, 'topics', 'exam_link')) {
        $pdo->exec('ALTER TABLE topics ADD COLUMN exam_link VARCHAR(512) DEFAULT NULL AFTER summary');
    }
    if (!colExists($pdo, 'topics', 'exam_test_id')) {
        $pdo->exec('ALTER TABLE topics ADD COLUMN exam_test_id INT UNSIGNED NULL AFTER exam_link');
        try {
            $pdo->exec('ALTER TABLE topics ADD CONSTRAINT fk_topic_exam_test FOREIGN KEY (exam_test_id) REFERENCES tests(id) ON DELETE SET NULL');
        } catch (Throwable $e) {
            // optional
        }
    }
}

if (
    tableExists($pdo, 'study_materials') && tableExists($pdo, 'topics')
    && colExists($pdo, 'study_materials', 'lesson_id')
    && !colExists($pdo, 'study_materials', 'topic_id')
) {
    try {
        $pdo->exec('ALTER TABLE study_materials CHANGE lesson_id topic_id INT UNSIGNED DEFAULT NULL');
    } catch (Throwable $e) {
        echo 'study_materials rename column: ' . $e->getMessage() . "\n";
    }
    try {
        $pdo->exec('ALTER TABLE study_materials DROP FOREIGN KEY study_materials_ibfk_2');
    } catch (Throwable $e) {
    }
    try {
        $pdo->exec(
            'ALTER TABLE study_materials ADD CONSTRAINT fk_sm_topic FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE SET NULL'
        );
    } catch (Throwable $e) {
    }
}

// --- 4) Pricing plans per sub-course
if (!tableExists($pdo, 'sub_course_plans')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE sub_course_plans (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_course_id INT UNSIGNED NOT NULL,
  plan_code ENUM('6_months','1_year','until_exam') NOT NULL,
  label VARCHAR(100) NOT NULL,
  price_inr DECIMAL(10,2) NOT NULL,
  duration_months SMALLINT UNSIGNED DEFAULT NULL COMMENT 'NULL = until exam or open-ended',
  status TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uk_plan (sub_course_id, plan_code),
  CONSTRAINT fk_plans_sc FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE,
  KEY idx_plan_live (sub_course_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created sub_course_plans\n";
}

$defaults = [
    ['6_months', '6 Months', 499.00, 6],
    ['1_year', '1 Year', 699.00, 12],
    ['until_exam', 'Up to Exam', 999.00, null],
];
$scRows = $pdo->query('SELECT id FROM sub_courses')->fetchAll(PDO::FETCH_COLUMN);
$pIns = $pdo->prepare(
    'INSERT IGNORE INTO sub_course_plans (sub_course_id, plan_code, label, price_inr, duration_months, status, is_active) VALUES (?,?,?,?,?,1,1)'
);
foreach ($scRows as $scid) {
    foreach ($defaults as [$code, $label, $price, $months]) {
        $pIns->execute([(int) $scid, $code, $label, $price, $months]);
    }
}
echo "default plans seeded\n";

// --- 5) user_subscriptions: optional plan FK
try {
    $pdo->exec('ALTER TABLE user_subscriptions MODIFY package_id INT UNSIGNED NULL');
} catch (Throwable $e) {
}
if (!colExists($pdo, 'user_subscriptions', 'sub_course_plan_id')) {
    try {
        $pdo->exec(
            'ALTER TABLE user_subscriptions ADD COLUMN sub_course_plan_id INT UNSIGNED NULL AFTER package_id,
             ADD CONSTRAINT fk_us_plan FOREIGN KEY (sub_course_plan_id) REFERENCES sub_course_plans(id) ON DELETE SET NULL'
        );
    } catch (Throwable $e) {
        echo 'user_subscriptions plan col: ' . $e->getMessage() . "\n";
    }
}

echo "migrate_four_tier: complete\n";
