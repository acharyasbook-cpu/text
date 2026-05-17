<?php

declare(strict_types=1);

/**
 * Schedule Test Manager — planner days, multi-subject rows, completions.
 * Run: php database/migrate_schedule_test_manager.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

function stm_table(PDO $pdo, string $name): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $st->execute([$name]);

    return (int) $st->fetchColumn() > 0;
}

if (!stm_table($pdo, 'st_schedule_config')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE st_schedule_config (
  sub_course_id INT UNSIGNED NOT NULL,
  term_key ENUM('short_term','long_term') NOT NULL,
  planner_mode ENUM('day_wise','date_wise') NOT NULL DEFAULT 'day_wise',
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (sub_course_id, term_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created st_schedule_config\n";
}

if (!stm_table($pdo, 'st_schedule_days')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE st_schedule_days (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_course_id INT UNSIGNED NOT NULL,
  term_key ENUM('short_term','long_term') NOT NULL,
  day_index SMALLINT UNSIGNED NULL COMMENT 'Day 1,2,... for day_wise',
  schedule_date DATE NULL COMMENT 'For date_wise planner',
  title_te VARCHAR(220) NULL DEFAULT NULL,
  layout_snapshot JSON NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_sc_term (sub_course_id, term_key),
  KEY idx_sc_date (sub_course_id, schedule_date),
  UNIQUE KEY uk_sc_term_day (sub_course_id, term_key, day_index),
  UNIQUE KEY uk_sc_term_date (sub_course_id, term_key, schedule_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created st_schedule_days\n";
}

if (!stm_table($pdo, 'st_schedule_rows')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE st_schedule_rows (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  schedule_day_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  topic_ids JSON NOT NULL,
  total_marks SMALLINT UNSIGNED NOT NULL DEFAULT 25,
  test_id INT UNSIGNED NULL DEFAULT NULL,
  question_mode ENUM('ai_pool','manual','external') NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_day_sort (schedule_day_id, sort_order),
  KEY idx_subject (subject_id),
  CONSTRAINT fk_st_row_day FOREIGN KEY (schedule_day_id) REFERENCES st_schedule_days(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created st_schedule_rows\n";
}

if (!stm_table($pdo, 'st_schedule_completions')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE st_schedule_completions (
  user_id INT UNSIGNED NOT NULL,
  schedule_row_id INT UNSIGNED NOT NULL,
  completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, schedule_row_id),
  KEY idx_row (schedule_row_id),
  CONSTRAINT fk_st_comp_row FOREIGN KEY (schedule_row_id) REFERENCES st_schedule_rows(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created st_schedule_completions\n";
}

echo "migrate_schedule_test_manager: complete\n";
