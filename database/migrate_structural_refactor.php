<?php

declare(strict_types=1);

/**
 * Sub-course term matrix + separate topic MCQ column.
 * Run: php database/migrate_structural_refactor.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

function srf_table(PDO $pdo, string $name): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $st->execute([$name]);

    return (int) $st->fetchColumn() > 0;
}

function srf_col(PDO $pdo, string $table, string $col): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute([$table, $col]);

    return (int) $st->fetchColumn() > 0;
}

$topics = 'topics';
if (srf_table($pdo, $topics) && !srf_col($pdo, $topics, 'mcq_content')) {
    $after = srf_col($pdo, $topics, 'notes_content') ? 'notes_content' : 'content';
    $pdo->exec("ALTER TABLE `{$topics}` ADD COLUMN mcq_content LONGTEXT NULL AFTER `{$after}`");
    echo "added {$topics}.mcq_content\n";
}

if (!srf_table($pdo, 'sub_course_term_boxes')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE sub_course_term_boxes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_course_id INT UNSIGNED NOT NULL,
  term_key ENUM('short_term','long_term') NOT NULL,
  label_en VARCHAR(120) NULL DEFAULT NULL,
  label_te VARCHAR(120) NULL DEFAULT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  schedule_days SMALLINT UNSIGNED NOT NULL DEFAULT 250,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_sub_course_term (sub_course_id, term_key),
  KEY idx_sub_course_enabled (sub_course_id, is_enabled)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created sub_course_term_boxes\n";
}

if (!srf_table($pdo, 'sub_course_term_schedule')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE sub_course_term_schedule (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_course_id INT UNSIGNED NOT NULL,
  term_key ENUM('short_term','long_term') NOT NULL,
  day_number SMALLINT UNSIGNED NOT NULL COMMENT '1..365',
  test_id INT UNSIGNED NULL DEFAULT NULL,
  subject_id INT UNSIGNED NULL DEFAULT NULL,
  topic_id INT UNSIGNED NULL DEFAULT NULL,
  title VARCHAR(200) NULL DEFAULT NULL,
  title_te VARCHAR(220) NULL DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uk_sub_course_term_day (sub_course_id, term_key, day_number),
  KEY idx_sub_course_term (sub_course_id, term_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created sub_course_term_schedule\n";
}

if (srf_table($pdo, 'platform_settings')) {
    $defaults = [
        'schedule_test.short_term_label_en' => 'Short Term',
        'schedule_test.short_term_label_te' => 'షార్ట్ టర్మ్',
        'schedule_test.long_term_label_en' => 'Long Term',
        'schedule_test.long_term_label_te' => 'లాంగ్ టర్మ్',
        'schedule_test.short_term_enabled' => '1',
        'schedule_test.long_term_enabled' => '1',
        'schedule_test.schedule_days' => '250',
    ];
    $ins = $pdo->prepare(
        'INSERT IGNORE INTO platform_settings (setting_key, setting_value) VALUES (?,?)'
    );
    foreach ($defaults as $k => $v) {
        $ins->execute([$k, $v]);
    }
    echo "platform_settings schedule_test defaults seeded\n";
}

if (srf_table($pdo, 'subject_term_boxes') && srf_table($pdo, 'sub_course_term_boxes')) {
    $rows = $pdo->query(
        'SELECT scs.sub_course_id, stb.term_key, stb.label_en, stb.label_te, stb.is_enabled, stb.schedule_days, stb.sort_order
         FROM subject_term_boxes stb
         INNER JOIN sub_course_subjects scs ON scs.subject_id = stb.subject_id
         ORDER BY scs.sub_course_id, stb.sort_order'
    )->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $ins = $pdo->prepare(
        'INSERT IGNORE INTO sub_course_term_boxes (sub_course_id, term_key, label_en, label_te, is_enabled, schedule_days, sort_order)
         VALUES (?,?,?,?,?,?,?)'
    );
    $seen = [];
    foreach ($rows as $r) {
        $scId = (int) $r['sub_course_id'];
        $key = $scId . ':' . $r['term_key'];
        if (isset($seen[$key])) {
            continue;
        }
        $seen[$key] = true;
        $ins->execute([
            $scId,
            $r['term_key'],
            $r['label_en'],
            $r['label_te'],
            (int) $r['is_enabled'],
            (int) $r['schedule_days'],
            (int) $r['sort_order'],
        ]);
    }
    if ($rows !== []) {
        echo "backfilled sub_course_term_boxes from subject_term_boxes\n";
    }
}

echo "migrate_structural_refactor: complete\n";
