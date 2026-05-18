<?php

declare(strict_types=1);

/**
 * Global exam-subject catalog + sub-course mapping (examiner / MCQ engine).
 * Run: php database/migrate_st_subjects.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS st_subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  subject_name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_st_subject_name (subject_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS st_sub_course_subjects (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sub_course_id INT UNSIGNED NOT NULL,
  subject_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uk_st_sc_subject (sub_course_id, subject_id),
  KEY idx_st_scs_subject (subject_id),
  CONSTRAINT fk_st_scs_subject FOREIGN KEY (subject_id) REFERENCES st_subjects(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

// Optional FK to sub_courses when table exists
if ($pdo->query("SHOW TABLES LIKE 'sub_courses'")->fetch()) {
    try {
        $pdo->exec(
            'ALTER TABLE st_sub_course_subjects
             ADD CONSTRAINT fk_st_scs_sub_course FOREIGN KEY (sub_course_id) REFERENCES sub_courses(id) ON DELETE CASCADE'
        );
        echo "added FK st_sub_course_subjects -> sub_courses\n";
    } catch (Throwable $e) {
        // already exists
    }
}

$defaults = ['Telugu', 'English', 'Hindi', 'Maths', 'Science', 'Social Studies', 'Psychology', 'Current Affairs', 'General'];
$ins = $pdo->prepare('INSERT IGNORE INTO st_subjects (subject_name) VALUES (?)');
foreach ($defaults as $name) {
    $ins->execute([$name]);
}

echo "migrate_st_subjects: complete (" . count($defaults) . " default names seeded if missing)\n";
