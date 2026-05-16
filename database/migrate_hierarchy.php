<?php

/**
 * Hierarchy migration — run once via CLI or browser:
 *   php database/migrate_hierarchy.php
 * Adds course_categories, subject_modules, status columns, indexes, seed categories.
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
if (!is_readable($dbPath)) {
    fwrite(STDERR, "db_connect.php not found.\n");
    exit(1);
}
require_once $dbPath;

function columnExists(PDO $pdo, string $table, string $col): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $table, $col]);

    return (int) $st->fetchColumn() > 0;
}

function indexExists(PDO $pdo, string $table, string $index): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=?'
    );
    $st->execute([$db, $table, $index]);

    return (int) $st->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $table): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?'
    );
    $st->execute([$db, $table]);

    return (int) $st->fetchColumn() > 0;
}

/** True only for physical tables (ALTER-safe). Views match tableExists but not this. */
function baseTableExists(PDO $pdo, string $table): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND TABLE_TYPE = \'BASE TABLE\''
    );
    $st->execute([$db, $table]);

    return (int) $st->fetchColumn() > 0;
}

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Starting hierarchy migration...\n";

foreach (['courses', 'subjects', 'tests', 'sub_course_packages', 'lessons', 'study_materials'] as $t) {
    if (!baseTableExists($pdo, $t)) {
        if (tableExists($pdo, $t)) {
            echo "Skipping {$t} (not a BASE TABLE — cannot ALTER)\n";
        } else {
            echo "Skipping {$t} (table missing — import database/schema.sql if you need it)\n";
        }

        continue;
    }
    if (!columnExists($pdo, $t, 'status')) {
        $pdo->exec("ALTER TABLE `$t` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1");
        echo "Added status to {$t}\n";
    }
    if ($t === 'courses' || $t === 'subjects' || $t === 'tests' || $t === 'sub_course_packages') {
        if (columnExists($pdo, $t, 'is_active')) {
            $pdo->exec("UPDATE `$t` SET `status` = `is_active` WHERE `status` <> `is_active`");
        }
    }
}

if (!tableExists($pdo, 'courses')) {
    fwrite(STDERR, "migrate_hierarchy: object `courses` not found. Import database/schema.sql or run setup.php first.\n");
    exit(1);
}

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS `course_categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `course_id` INT UNSIGNED NOT NULL,
  `slug` VARCHAR(40) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `name_te` VARCHAR(160) DEFAULT NULL,
  `description` TEXT,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_cc_course` FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `uk_course_cat_slug` (`course_id`, `slug`),
  KEY `idx_course_status` (`course_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);
echo "course_categories table OK\n";

if (baseTableExists($pdo, 'subjects')) {
    if (!columnExists($pdo, 'subjects', 'category_id')) {
        $pdo->exec('ALTER TABLE `subjects` ADD COLUMN `category_id` INT UNSIGNED NULL AFTER `course_id`');
        echo "Added subjects.category_id\n";
    }
    if (!columnExists($pdo, 'subjects', 'marks_allocated')) {
        $pdo->exec('ALTER TABLE `subjects` ADD COLUMN `marks_allocated` SMALLINT UNSIGNED NULL AFTER `description`');
        echo "Added subjects.marks_allocated\n";
    }

    $pdo->exec(
        <<<SQL
CREATE TABLE IF NOT EXISTS `subject_modules` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `subject_id` INT UNSIGNED NOT NULL,
  `module_type` ENUM('exam','revision_test','division_test') NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT,
  `marks` SMALLINT UNSIGNED DEFAULT NULL,
  `duration_mins` SMALLINT UNSIGNED DEFAULT NULL,
  `sort_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `status` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_sm_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects`(`id`) ON DELETE CASCADE,
  KEY `idx_subject_status` (`subject_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "subject_modules table OK\n";

    // Indexes for performance
    if (!indexExists($pdo, 'subjects', 'idx_subjects_course_status')) {
        $pdo->exec('ALTER TABLE `subjects` ADD INDEX `idx_subjects_course_status` (`course_id`, `status`)');
        echo "Index idx_subjects_course_status\n";
    }

    // Foreign key category_id (ignore error if exists)
    try {
        $pdo->exec(
            'ALTER TABLE `subjects` ADD CONSTRAINT `fk_subjects_category` FOREIGN KEY (`category_id`) REFERENCES `course_categories`(`id`) ON DELETE SET NULL'
        );
        echo "FK subjects.category_id\n";
    } catch (Throwable $e) {
        // already exists
    }
} else {
    echo "Skipping subject_modules / subjects FK (no writable `subjects` BASE TABLE)\n";
}

if (baseTableExists($pdo, 'courses') && !indexExists($pdo, 'courses', 'idx_courses_status')) {
    $pdo->exec('ALTER TABLE `courses` ADD INDEX `idx_courses_status` (`status`)');
    echo "Index idx_courses_status\n";
}
if (baseTableExists($pdo, 'tests') && !indexExists($pdo, 'tests', 'idx_tests_course_status')) {
    $pdo->exec('ALTER TABLE `tests` ADD INDEX `idx_tests_course_status` (`course_id`, `status`)');
    echo "Index idx_tests_course_status\n";
}

// Seed default categories (SGT, SA, TGT, PGT, PET) per course
$defaultCats = [
    ['sgt', 'SGT', 'ఎస్‌జీటీ', 1],
    ['sa', 'SA', 'ఎస్‌ఏ', 2],
    ['tgt', 'TGT', 'టీజీటీ', 3],
    ['pgt', 'PGT', 'పీజీటీ', 4],
    ['pet', 'PET', 'పీఈటీ', 5],
];

$courses = $pdo->query('SELECT id, slug FROM courses')->fetchAll(PDO::FETCH_ASSOC);
foreach ($courses as $c) {
    foreach ($defaultCats as [$slug, $name, $te, $ord]) {
        $ins = $pdo->prepare(
            'INSERT IGNORE INTO course_categories (course_id, slug, name, name_te, sort_order, status) VALUES (?,?,?,?,?,1)'
        );
        $ins->execute([(int) $c['id'], $slug, $name, $te, $ord]);
    }
}
echo "Seeded categories per course.\n";

if (baseTableExists($pdo, 'subjects')) {
    // Backfill subjects: attach to first active category per course if null
    $pdo->exec(
        'UPDATE subjects s JOIN (
        SELECT MIN(id) AS cid, course_id FROM course_categories WHERE status=1 GROUP BY course_id
     ) fc ON fc.course_id = s.course_id SET s.category_id = fc.cid WHERE s.category_id IS NULL'
    );

    // Default subject modules (one row each type per subject — admin can tweak)
    if (tableExists($pdo, 'subject_modules')) {
        $pdo->exec(
            "INSERT INTO subject_modules (subject_id, module_type, title, description, sort_order, status)
    SELECT s.id, 'exam', CONCAT('Exams — ', s.name), 'Topic / unit assessments', 1, s.status
    FROM subjects s WHERE NOT EXISTS (SELECT 1 FROM subject_modules sm WHERE sm.subject_id = s.id AND sm.module_type='exam')"
        );
        $pdo->exec(
            "INSERT INTO subject_modules (subject_id, module_type, title, description, sort_order, status)
    SELECT s.id, 'revision_test', CONCAT('Revision Tests — ', s.name), 'Full-subject recap tests', 2, s.status
    FROM subjects s WHERE NOT EXISTS (SELECT 1 FROM subject_modules sm WHERE sm.subject_id = s.id AND sm.module_type='revision_test')"
        );
        $pdo->exec(
            "INSERT INTO subject_modules (subject_id, module_type, title, description, sort_order, status)
    SELECT s.id, 'division_test', CONCAT('Division Tests — ', s.name), 'Section-wise / unit-wise mocks', 3, s.status
    FROM subjects s WHERE NOT EXISTS (SELECT 1 FROM subject_modules sm WHERE sm.subject_id = s.id AND sm.module_type='division_test')"
        );
    }

    echo "Backfill & default modules OK.\n";
} else {
    echo "Skipping subject backfill (table `subjects` missing)\n";
}
echo "Migration complete.\n";
