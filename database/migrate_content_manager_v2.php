<?php

/**
 * Content Manager v2: topic_exam_suite (revision / division / sub_grand / grand).
 * CLI: php database/migrate_content_manager_v2.php
 */

declare(strict_types=1);

$dbPath = dirname(__DIR__) . '/db_connect.php';
require_once $dbPath;

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function cm2_table(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND TABLE_TYPE=\'BASE TABLE\''
    );
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

$topics = cm2_table($pdo, 'topics') ? 'topics' : (cm2_table($pdo, 'lessons') ? 'lessons' : null);
if (!$topics) {
    fwrite(STDERR, "topics table missing.\n");
    exit(1);
}

echo "migrate_content_manager_v2: start\n";

if (!cm2_table($pdo, 'topic_exam_suite')) {
    $subFk = cm2_table($pdo, 'sub_topics')
        ? ', CONSTRAINT fk_tes_sub_topic FOREIGN KEY (sub_topic_id) REFERENCES sub_topics(id) ON DELETE CASCADE'
        : '';
    $pdo->exec(
        <<<SQL
CREATE TABLE topic_exam_suite (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic_id INT UNSIGNED NOT NULL,
  sub_topic_id INT UNSIGNED NULL DEFAULT NULL,
  suite_key VARCHAR(32) NOT NULL COMMENT 'revision|division|sub_grand|grand',
  custom_title VARCHAR(220) NOT NULL,
  custom_title_te VARCHAR(240) DEFAULT NULL,
  question_count SMALLINT UNSIGNED NOT NULL DEFAULT 50,
  total_marks SMALLINT UNSIGNED DEFAULT NULL,
  test_id INT UNSIGNED NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_topic_suite_scope (topic_id, sub_topic_id, suite_key),
  KEY idx_tes_topic (topic_id, sort_order),
  CONSTRAINT fk_tes_topic FOREIGN KEY (topic_id) REFERENCES `{$topics}`(id) ON DELETE CASCADE,
  CONSTRAINT fk_tes_test FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE SET NULL
  {$subFk}
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created topic_exam_suite\n";
}

echo "migrate_content_manager_v2: done\n";
