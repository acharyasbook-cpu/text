<?php

declare(strict_types=1);

/**
 * AI MCQ Engine: PDF segments, examiners, API slots, staging pipeline, generation jobs.
 * Run: php database/migrate_mcq_ai_engine.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS st_pdf_segments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pdf_name VARCHAR(255) NOT NULL,
  topic_name VARCHAR(255) NOT NULL COMMENT 'Lesson / topic label',
  start_page SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  end_page SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  sub_course_id INT UNSIGNED NULL DEFAULT NULL,
  assigned_subject VARCHAR(80) NOT NULL DEFAULT 'General',
  parent_segment_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'Group snippet PDFs under one topic',
  storage_path VARCHAR(512) NULL DEFAULT NULL,
  page_count SMALLINT UNSIGNED NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pdf_sub (sub_course_id),
  KEY idx_pdf_subject (assigned_subject),
  KEY idx_pdf_parent (parent_segment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS st_examiners (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(180) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  assigned_subject VARCHAR(80) NOT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_examiner_email (email),
  KEY idx_examiner_subject (assigned_subject, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS st_ai_api_slots (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  slot_index TINYINT UNSIGNED NOT NULL,
  provider VARCHAR(32) NOT NULL DEFAULT 'openai',
  model_name VARCHAR(120) NOT NULL DEFAULT 'gpt-4o-mini',
  api_key_encrypted TEXT NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_slot_index (slot_index)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS st_mcq_generation_jobs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  segment_id INT UNSIGNED NOT NULL,
  api_slot_id INT UNSIGNED NULL DEFAULT NULL,
  subject_key VARCHAR(80) NOT NULL DEFAULT 'General',
  difficulty_scale ENUM('SGT','SA','PGT') NOT NULL DEFAULT 'SGT',
  language_mode ENUM('telugu','english','hindi','bilingual_en_te') NOT NULL DEFAULT 'bilingual_en_te',
  status ENUM('pending','processing','paused','completed','failed') NOT NULL DEFAULT 'pending',
  current_page SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  total_pages SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  questions_per_page TINYINT UNSIGNED NOT NULL DEFAULT 3,
  last_error TEXT NULL,
  excel_mapping JSON NULL,
  created_by VARCHAR(180) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_job_segment (segment_id),
  KEY idx_job_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS st_questions_staging (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  segment_id INT UNSIGNED NULL,
  job_id INT UNSIGNED NULL,
  page_number SMALLINT UNSIGNED NULL,
  subject_key VARCHAR(80) NOT NULL DEFAULT 'General',
  difficulty_scale ENUM('SGT','SA','PGT') NOT NULL DEFAULT 'SGT',
  question_text TEXT NOT NULL,
  question_text_te TEXT NULL,
  option_a VARCHAR(1000) NOT NULL DEFAULT '',
  option_b VARCHAR(1000) NOT NULL DEFAULT '',
  option_c VARCHAR(1000) NOT NULL DEFAULT '',
  option_d VARCHAR(1000) NOT NULL DEFAULT '',
  option_a_te VARCHAR(1000) NULL,
  option_b_te VARCHAR(1000) NULL,
  option_c_te VARCHAR(1000) NULL,
  option_d_te VARCHAR(1000) NULL,
  correct_option ENUM('A','B','C','D') NOT NULL DEFAULT 'A',
  bilingual_layout TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('raw_ai','examiner_approved','live_approved') NOT NULL DEFAULT 'raw_ai',
  examiner_id INT UNSIGNED NULL,
  approved_examiner_at TIMESTAMP NULL,
  approved_admin_at TIMESTAMP NULL,
  deployed_test_id INT UNSIGNED NULL,
  deployed_question_id INT UNSIGNED NULL,
  metadata JSON NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_stg_status (status),
  KEY idx_stg_segment (segment_id),
  KEY idx_stg_subject (subject_key),
  KEY idx_stg_job (job_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

// Seed 8 API slots if empty
$cnt = (int) $pdo->query('SELECT COUNT(*) FROM st_ai_api_slots')->fetchColumn();
if ($cnt === 0) {
    $ins = $pdo->prepare(
        'INSERT INTO st_ai_api_slots (slot_index, provider, model_name, api_key_encrypted, is_active)
         VALUES (?, ?, ?, ?, 0)'
    );
    $emptyKey = base64_encode('');
    for ($i = 1; $i <= 8; $i++) {
        $ins->execute([$i, 'openai', 'gpt-4o-mini', $emptyKey]);
    }
    echo "seeded 8 st_ai_api_slots rows\n";
}

echo "migrate_mcq_ai_engine: complete\n";
