<?php

declare(strict_types=1);

/**
 * Subject Short-Term / Long-Term matrix + 250-day sequential exam schedule.
 * Run: php database/migrate_subject_term_matrix.php
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

if (!stm_table($pdo, 'subject_term_boxes')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE subject_term_boxes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_id INT UNSIGNED NOT NULL,
  term_key ENUM('short_term','long_term') NOT NULL,
  label_en VARCHAR(120) NULL DEFAULT NULL,
  label_te VARCHAR(120) NULL DEFAULT NULL,
  is_enabled TINYINT(1) NOT NULL DEFAULT 1,
  schedule_days SMALLINT UNSIGNED NOT NULL DEFAULT 250,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_subject_term (subject_id, term_key),
  KEY idx_subject_enabled (subject_id, is_enabled),
  CONSTRAINT fk_stb_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created subject_term_boxes\n";
}

if (!stm_table($pdo, 'subject_term_schedule')) {
    $pdo->exec(
        <<<SQL
CREATE TABLE subject_term_schedule (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_id INT UNSIGNED NOT NULL,
  term_key ENUM('short_term','long_term') NOT NULL,
  day_number SMALLINT UNSIGNED NOT NULL COMMENT '1..250',
  test_id INT UNSIGNED NULL DEFAULT NULL,
  topic_id INT UNSIGNED NULL DEFAULT NULL,
  title VARCHAR(200) NULL DEFAULT NULL,
  title_te VARCHAR(220) NULL DEFAULT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  UNIQUE KEY uk_subject_term_day (subject_id, term_key, day_number),
  KEY idx_subject_term (subject_id, term_key),
  CONSTRAINT fk_sts_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
    );
    echo "created subject_term_schedule\n";
}

if (stm_table($pdo, 'platform_settings')) {
    $defaults = [
        'term_matrix.short_term_label_en' => 'Short Term',
        'term_matrix.short_term_label_te' => 'షార్ట్ టర్మ్',
        'term_matrix.long_term_label_en' => 'Long Term',
        'term_matrix.long_term_label_te' => 'లాంగ్ టర్మ్',
        'term_matrix.short_term_enabled' => '1',
        'term_matrix.long_term_enabled' => '1',
        'term_matrix.schedule_days' => '250',
    ];
    $ins = $pdo->prepare(
        'INSERT IGNORE INTO platform_settings (setting_key, setting_value) VALUES (?,?)'
    );
    foreach ($defaults as $k => $v) {
        $ins->execute([$k, $v]);
    }
    echo "platform_settings term_matrix defaults seeded\n";
}

echo "migrate_subject_term_matrix: complete\n";
