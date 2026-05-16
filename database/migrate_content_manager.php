<?php

/**
 * Content Manager: topics notes/sub-topics + sub_topics table.
 * CLI: php database/migrate_content_manager.php
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

function cm_col(PDO $pdo, string $t, string $c): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $t, $c]);

    return (int) $st->fetchColumn() > 0;
}

function cm_table(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND TABLE_TYPE=\'BASE TABLE\''
    );
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

$topics = cm_table($pdo, 'topics') ? 'topics' : (cm_table($pdo, 'lessons') ? 'lessons' : null);
if (!$topics) {
    fwrite(STDERR, "No topics/lessons table. Run migrate_four_tier.php first.\n");
    exit(1);
}

echo "migrate_content_manager: using `{$topics}`\n";

if (!cm_col($pdo, $topics, 'has_sub_topics')) {
    $pdo->exec("ALTER TABLE `{$topics}` ADD COLUMN has_sub_topics TINYINT(1) NOT NULL DEFAULT 0 AFTER is_free_preview");
    echo "added {$topics}.has_sub_topics\n";
}

if (!cm_col($pdo, $topics, 'notes_content')) {
    $after = cm_col($pdo, $topics, 'content') ? 'content' : 'summary';
    $pdo->exec("ALTER TABLE `{$topics}` ADD COLUMN notes_content LONGTEXT NULL AFTER `{$after}`");
    echo "added {$topics}.notes_content\n";
    if (cm_col($pdo, $topics, 'content')) {
        $pdo->exec("UPDATE `{$topics}` SET notes_content = content WHERE notes_content IS NULL AND content IS NOT NULL AND TRIM(content) <> ''");
        echo "backfilled notes_content from content\n";
    }
}

if (!cm_col($pdo, $topics, 'question_count')) {
    $pdo->exec("ALTER TABLE `{$topics}` ADD COLUMN question_count SMALLINT UNSIGNED NOT NULL DEFAULT 50 AFTER has_sub_topics");
    echo "added {$topics}.question_count\n";
}

if (!cm_table($pdo, 'sub_topics')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE sub_topics (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topic_id INT UNSIGNED NOT NULL,
  sub_topic_name VARCHAR(220) NOT NULL,
  sub_topic_name_te VARCHAR(240) DEFAULT NULL,
  slug VARCHAR(120) NOT NULL,
  question_count SMALLINT UNSIGNED NOT NULL DEFAULT 50,
  sub_notes_content LONGTEXT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  status TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_sub_topic_slug (topic_id, slug),
  KEY idx_sub_topics_topic (topic_id, sort_order),
  CONSTRAINT fk_sub_topics_topic FOREIGN KEY (topic_id) REFERENCES `{$topics}`(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created sub_topics\n";
}

echo "migrate_content_manager: done\n";
