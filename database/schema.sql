-- Acharya Books — Full educational platform schema
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS acharya_books CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE acharya_books;

DROP TABLE IF EXISTS test_attempt_answers;
DROP TABLE IF EXISTS test_attempts;
DROP TABLE IF EXISTS test_questions;
DROP TABLE IF EXISTS tests;
DROP TABLE IF EXISTS lessons;
DROP TABLE IF EXISTS study_materials;
DROP TABLE IF EXISTS user_subscriptions;
DROP TABLE IF EXISTS sub_course_packages;
DROP TABLE IF EXISTS subjects;
DROP TABLE IF EXISTS courses;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    phone VARCHAR(20) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','admin') NOT NULL DEFAULT 'student',
    avatar_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE courses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    name_te VARCHAR(160) DEFAULT NULL,
    region VARCHAR(40) DEFAULT NULL,
    description TEXT,
    icon VARCHAR(40) DEFAULT 'book',
    sort_order TINYINT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE subjects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    slug VARCHAR(60) NOT NULL,
    name VARCHAR(120) NOT NULL,
    name_te VARCHAR(160) DEFAULT NULL,
    description TEXT,
    sort_order TINYINT UNSIGNED DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY uk_course_slug (course_id, slug)
) ENGINE=InnoDB;

CREATE TABLE lessons (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id INT UNSIGNED NOT NULL,
    slug VARCHAR(80) NOT NULL,
    title VARCHAR(200) NOT NULL,
    title_te VARCHAR(220) DEFAULT NULL,
    summary TEXT,
    content LONGTEXT,
    duration_mins SMALLINT UNSIGNED DEFAULT 30,
    sort_order SMALLINT UNSIGNED DEFAULT 0,
    is_free_preview TINYINT(1) DEFAULT 0,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY uk_subject_lesson_slug (subject_id, slug)
) ENGINE=InnoDB;

CREATE TABLE study_materials (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    subject_id INT UNSIGNED NOT NULL,
    lesson_id INT UNSIGNED DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    material_type ENUM('pdf','notes','video','link') NOT NULL DEFAULT 'notes',
    file_url VARCHAR(500) DEFAULT NULL,
    description TEXT,
    sort_order SMALLINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Sub-course packages: subject-only or division-test bundles (not daily-count)
CREATE TABLE sub_course_packages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(80) NOT NULL UNIQUE,
    package_type ENUM('subject','division_tests','course_bundle') NOT NULL,
    course_id INT UNSIGNED DEFAULT NULL,
    subject_id INT UNSIGNED DEFAULT NULL,
    name VARCHAR(160) NOT NULL,
    name_te VARCHAR(200) DEFAULT NULL,
    description TEXT,
    price_inr DECIMAL(10,2) NOT NULL DEFAULT 0,
    includes_division_tests TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE user_subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    package_id INT UNSIGNED NOT NULL,
    purchased_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL DEFAULT NULL,
    status ENUM('active','expired','cancelled') NOT NULL DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (package_id) REFERENCES sub_course_packages(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_package (user_id, package_id)
) ENGINE=InnoDB;

CREATE TABLE tests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    course_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED DEFAULT NULL,
    slug VARCHAR(80) NOT NULL,
    title VARCHAR(200) NOT NULL,
    title_te VARCHAR(220) DEFAULT NULL,
    test_type ENUM('topic','division','grand') NOT NULL,
    division_label VARCHAR(80) DEFAULT NULL COMMENT 'Unit/Section name for division tests',
    duration_mins SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    total_questions SMALLINT UNSIGNED NOT NULL DEFAULT 50,
    total_marks SMALLINT UNSIGNED NOT NULL DEFAULT 50,
    negative_marking DECIMAL(3,2) DEFAULT 0.25,
    instructions TEXT,
    package_id INT UNSIGNED DEFAULT NULL COMMENT 'Required package for access',
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    FOREIGN KEY (package_id) REFERENCES sub_course_packages(id) ON DELETE SET NULL,
    UNIQUE KEY uk_course_test_slug (course_id, slug)
) ENGINE=InnoDB;

CREATE TABLE test_questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    test_id INT UNSIGNED NOT NULL,
    question_order SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    question_text TEXT NOT NULL,
    question_text_te TEXT,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_option ENUM('A','B','C','D') NOT NULL,
    marks TINYINT UNSIGNED DEFAULT 1,
    topic_tag VARCHAR(80) DEFAULT NULL,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE test_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    test_id INT UNSIGNED NOT NULL,
    started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL,
    time_taken_secs INT UNSIGNED DEFAULT NULL,
    score DECIMAL(6,2) DEFAULT NULL,
    max_score DECIMAL(6,2) DEFAULT NULL,
    correct_count SMALLINT UNSIGNED DEFAULT 0,
    wrong_count SMALLINT UNSIGNED DEFAULT 0,
    unanswered_count SMALLINT UNSIGNED DEFAULT 0,
    status ENUM('in_progress','submitted','abandoned') NOT NULL DEFAULT 'in_progress',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE test_attempt_answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    selected_option ENUM('A','B','C','D') DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT NULL,
    FOREIGN KEY (attempt_id) REFERENCES test_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES test_questions(id) ON DELETE CASCADE,
    UNIQUE KEY uk_attempt_question (attempt_id, question_id)
) ENGINE=InnoDB;

SET FOREIGN_KEY_CHECKS = 1;
