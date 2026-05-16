<?php

/**
 * Topic exams (unlimited per topic), main_courses view, optional subjects.sub_course_id
 * Run after migrate_four_tier.php (topics table must exist).
 * CLI: php database/migrate_topic_exams.php
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

function te_colExists(PDO $pdo, string $t, string $c): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $t, $c]);

    return (int) $st->fetchColumn() > 0;
}

function te_tableExists(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

echo "migrate_topic_exams: start\n";

$topicsTable = te_tableExists($pdo, 'topics') ? 'topics' : (te_tableExists($pdo, 'lessons') ? 'lessons' : null);
if (!$topicsTable) {
    fwrite(STDERR, "No topics/lessons table; run migrate_four_tier first.\n");
    exit(1);
}

// VIEW main_courses — only when canonical main-programme table is still named `courses`
try {
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $typeSt = $pdo->prepare(
        'SELECT TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?'
    );
    $typeSt->execute([$db, 'main_courses']);
    $mainTp = $typeSt->fetchColumn();
    if ($mainTp === 'BASE TABLE') {
        echo "main_courses physical table exists — skipping main_courses view\n";
    } elseif (te_tableExists($pdo, 'courses')) {
        $pdo->exec('CREATE OR REPLACE VIEW main_courses AS SELECT * FROM courses');
        echo "created view main_courses\n";
    }
} catch (Throwable $e) {
    echo 'main_courses view: ' . $e->getMessage() . "\n";
}

// subjects.sub_course_id — denormalized primary programme link (pivot remains source for multi-assign)
if (te_tableExists($pdo, 'sub_course_subjects') && te_tableExists($pdo, 'sub_courses') && !te_colExists($pdo, 'subjects', 'sub_course_id')) {
    try {
        $pdo->exec('ALTER TABLE subjects ADD COLUMN sub_course_id INT UNSIGNED NULL AFTER course_id');
        try {
            $pdo->exec('ALTER TABLE subjects ADD INDEX idx_subject_sub_course (sub_course_id)');
        } catch (Throwable $e) {
            // index may already exist
        }
        $pdo->exec(
            'UPDATE subjects s
             JOIN (
               SELECT subject_id, MIN(sub_course_id) AS scid FROM sub_course_subjects GROUP BY subject_id
             ) x ON x.subject_id = s.id
             SET s.sub_course_id = x.scid'
        );
        $pdo->exec(
            'ALTER TABLE subjects ADD CONSTRAINT fk_subjects_sub_course
             FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE SET NULL'
        );
        echo "added subjects.sub_course_id backfilled from pivot\n";
    } catch (Throwable $e) {
        echo 'sub_course_id column: ' . $e->getMessage() . "\n";
    }
}

if (!te_tableExists($pdo, 'exams')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE exams (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic_id INT UNSIGNED NOT NULL,
  title VARCHAR(200) NOT NULL,
  title_te VARCHAR(220) DEFAULT NULL,
  slug VARCHAR(100) NOT NULL COMMENT 'unique per topic',
  external_url VARCHAR(512) DEFAULT NULL COMMENT 'Optional external exam URL',
  test_id INT UNSIGNED DEFAULT NULL COMMENT 'Optional platform tests.id',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_topic_exam_slug (topic_id, slug),
  KEY idx_exams_topic_sort (topic_id, sort_order),
  CONSTRAINT fk_exams_topic FOREIGN KEY (topic_id) REFERENCES `{$topicsTable}`(id) ON DELETE CASCADE,
  CONSTRAINT fk_exams_test FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created exams\n";
}

// Legacy topic columns → first-class exam rows (idempotent)
if (te_colExists($pdo, $topicsTable, 'exam_test_id')) {
    $pdo->exec(
        "INSERT IGNORE INTO exams (topic_id, title, slug, external_url, test_id, sort_order, status, is_active)
         SELECT t.id,
                CONCAT(COALESCE(NULLIF(TRIM(t.title), ''), 'Topic'), ' — Platform test'),
                CONCAT('migrated-test-', t.id),
                NULL,
                t.exam_test_id,
                0,
                1,
                1
         FROM `{$topicsTable}` t
         WHERE t.exam_test_id IS NOT NULL AND t.exam_test_id > 0"
    );
}
if (te_colExists($pdo, $topicsTable, 'exam_link')) {
    $pdo->exec(
        "INSERT IGNORE INTO exams (topic_id, title, slug, external_url, test_id, sort_order, status, is_active)
         SELECT t.id,
                CONCAT(COALESCE(NULLIF(TRIM(t.title), ''), 'Topic'), ' — External exam'),
                CONCAT('migrated-link-', t.id),
                NULLIF(TRIM(t.exam_link), ''),
                NULL,
                1,
                1,
                1
         FROM `{$topicsTable}` t
         WHERE t.exam_link IS NOT NULL AND TRIM(t.exam_link) <> ''"
    );
}

echo "migrate_topic_exams: complete\n";
