<?php

declare(strict_types=1);

/**
 * Subscription coupons + usage logs (sub-course scoped, promoter field).
 * Run: php database/migrate_st_coupons.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

function st_coupons_col(PDO $pdo, string $col): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $st->execute(['st_coupons', $col]);

    return (int) $st->fetchColumn() > 0;
}

function st_coupons_index_exists(PDO $pdo, string $indexName): bool
{
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $st->execute(['st_coupons', $indexName]);

    return (int) $st->fetchColumn() > 0;
}

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS st_coupons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coupon_code VARCHAR(64) NOT NULL,
  promoter_name VARCHAR(128) NULL DEFAULT NULL COMMENT 'Issued to / promoter label',
  discount_type ENUM('percentage','fixed_amount') NOT NULL DEFAULT 'percentage',
  discount_value DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  applicable_sub_course_id INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = all sub-courses',
  expiry_date DATE NULL DEFAULT NULL,
  usage_limit INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = unlimited uses',
  used_count INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uk_st_coupons_code (coupon_code),
  KEY idx_st_coupons_sub (applicable_sub_course_id),
  KEY idx_st_coupons_active (is_active, expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

// --- Idempotent upgrades for older installs ---

if (!st_coupons_col($pdo, 'promoter_name')) {
    $pdo->exec(
        'ALTER TABLE st_coupons ADD COLUMN promoter_name VARCHAR(128) NULL DEFAULT NULL COMMENT \'Issued to / promoter label\' AFTER coupon_code'
    );
    echo "added st_coupons.promoter_name\n";
}

if (st_coupons_col($pdo, 'applicable_course_id') && !st_coupons_col($pdo, 'applicable_sub_course_id')) {
    $pdo->exec(
        'ALTER TABLE st_coupons ADD COLUMN applicable_sub_course_id INT UNSIGNED NULL DEFAULT NULL COMMENT \'NULL = all sub-courses\' AFTER discount_value'
    );
    if (st_coupons_index_exists($pdo, 'idx_st_coupons_course')) {
        try {
            $pdo->exec('ALTER TABLE st_coupons DROP INDEX idx_st_coupons_course');
        } catch (Throwable $e) {
            // ignore
        }
    }
    $pdo->exec('ALTER TABLE st_coupons DROP COLUMN applicable_course_id');
    $pdo->exec('ALTER TABLE st_coupons ADD KEY idx_st_coupons_sub (applicable_sub_course_id)');
    echo "migrated st_coupons: applicable_course_id -> applicable_sub_course_id (legacy values cleared; re-save coupons in admin)\n";
} elseif (!st_coupons_col($pdo, 'applicable_sub_course_id')) {
    $pdo->exec(
        'ALTER TABLE st_coupons ADD COLUMN applicable_sub_course_id INT UNSIGNED NULL DEFAULT NULL COMMENT \'NULL = all sub-courses\' AFTER discount_value'
    );
    if (!st_coupons_index_exists($pdo, 'idx_st_coupons_sub')) {
        $pdo->exec('ALTER TABLE st_coupons ADD KEY idx_st_coupons_sub (applicable_sub_course_id)');
    }
    echo "added st_coupons.applicable_sub_course_id\n";
}

if (st_coupons_col($pdo, 'applicable_course_id')) {
    if (st_coupons_index_exists($pdo, 'idx_st_coupons_course')) {
        try {
            $pdo->exec('ALTER TABLE st_coupons DROP INDEX idx_st_coupons_course');
        } catch (Throwable $e) {
            // ignore
        }
    }
    try {
        $pdo->exec('ALTER TABLE st_coupons DROP COLUMN applicable_course_id');
        echo "dropped legacy st_coupons.applicable_course_id\n";
    } catch (Throwable $e) {
        // column may already be gone
    }
}

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS coupon_usage_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  coupon_id INT UNSIGNED NOT NULL,
  student_id INT UNSIGNED NOT NULL,
  sub_course_id INT UNSIGNED NOT NULL,
  discount_applied DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  final_amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  used_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_cul_coupon (coupon_id),
  KEY idx_cul_student (student_id),
  KEY idx_cul_sub (sub_course_id),
  CONSTRAINT fk_cul_coupon FOREIGN KEY (coupon_id) REFERENCES st_coupons(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

echo "migrate_st_coupons: complete\n";
