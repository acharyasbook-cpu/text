<?php

/**
 * Five-tier testing model: Topic, Division, Revision, Grand, Model
 * - Extends tests.test_type and exams; adds topics.test_type
 * - test_bundle_items: compose Division/Revision/Grand/Model from smaller tests
 *
 * Run after migrate_four_tier / migrate_topic_exams.
 * CLI: php database/migrate_exam_hierarchy.php
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

function eh_col(PDO $pdo, string $t, string $c): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $t, $c]);

    return (int) $st->fetchColumn() > 0;
}

function eh_table(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND TABLE_TYPE=\'BASE TABLE\''
    );
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

$topicsTable = eh_table($pdo, 'topics') ? 'topics' : (eh_table($pdo, 'lessons') ? 'lessons' : null);

echo "migrate_exam_hierarchy: start\n";

// --- tests.test_type expanded
try {
    $pdo->exec(
        "ALTER TABLE tests MODIFY COLUMN test_type
         ENUM('topic','division','revision','grand','model') NOT NULL DEFAULT 'topic'"
    );
    echo "tests.test_type expanded\n";
} catch (Throwable $e) {
    echo 'tests.test_type: ' . $e->getMessage() . "\n";
}

if ($topicsTable && !eh_col($pdo, 'tests', 'topic_id')) {
    try {
        $pdo->exec(
            "ALTER TABLE tests ADD COLUMN topic_id INT UNSIGNED NULL AFTER subject_id,
             ADD KEY idx_tests_topic (topic_id),
             ADD CONSTRAINT fk_tests_topic FOREIGN KEY (topic_id) REFERENCES `{$topicsTable}`(id) ON DELETE SET NULL"
        );
        echo "tests.topic_id added\n";
    } catch (Throwable $e) {
        echo 'tests.topic_id: ' . $e->getMessage() . "\n";
    }
}

// --- topics.test_type (syllabus / exam-track label per topic row)
if ($topicsTable && !eh_col($pdo, $topicsTable, 'test_type')) {
    try {
        $pdo->exec(
            "ALTER TABLE `{$topicsTable}` ADD COLUMN test_type
            ENUM('topic','division','revision','grand','model') NOT NULL DEFAULT 'topic' AFTER sort_order"
        );
        echo "{$topicsTable}.test_type added\n";
    } catch (Throwable $e) {
        echo 'topics.test_type: ' . $e->getMessage() . "\n";
    }
}

// --- Composition table (bundle = parent test)
if (!eh_table($pdo, 'test_bundle_items')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE test_bundle_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  bundle_test_id INT UNSIGNED NOT NULL COMMENT 'Composite exam (division/revision/grand/model)',
  component_test_id INT UNSIGNED NOT NULL COMMENT 'Included topic/division/etc. test',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  UNIQUE KEY uk_bundle_component (bundle_test_id, component_test_id),
  KEY idx_tbi_component (component_test_id),
  CONSTRAINT fk_tbi_bundle FOREIGN KEY (bundle_test_id) REFERENCES tests(id) ON DELETE CASCADE,
  CONSTRAINT fk_tbi_component FOREIGN KEY (component_test_id) REFERENCES tests(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created test_bundle_items\n";
}

// --- exams: type + material link
if (eh_table($pdo, 'exams')) {
    if (!eh_col($pdo, 'exams', 'test_type')) {
        try {
            if (eh_col($pdo, 'exams', 'topic_id')) {
                $pdo->exec(
                    "ALTER TABLE exams ADD COLUMN test_type
                    ENUM('topic','division','revision','grand','model') NOT NULL DEFAULT 'topic' AFTER topic_id"
                );
            } else {
                $pdo->exec(
                    "ALTER TABLE exams ADD COLUMN test_type
                    ENUM('topic','division','revision','grand','model') NOT NULL DEFAULT 'topic'"
                );
            }
            echo "exams.test_type added\n";
        } catch (Throwable $e) {
            echo 'exams.test_type: ' . $e->getMessage() . "\n";
        }
    }
    if (!eh_col($pdo, 'exams', 'material_url')) {
        try {
            $pdo->exec(
                "ALTER TABLE exams ADD COLUMN material_url VARCHAR(512) DEFAULT NULL COMMENT 'PDF or study material URL'"
            );
            echo "exams.material_url added\n";
        } catch (Throwable $e) {
            echo 'exams.material_url: ' . $e->getMessage() . "\n";
        }
    }
}

echo "migrate_exam_hierarchy: complete\n";
