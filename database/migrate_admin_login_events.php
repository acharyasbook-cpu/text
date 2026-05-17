<?php

declare(strict_types=1);

/**
 * Login events for admin analytics (multi-device session hints).
 * Run: php database/migrate_admin_login_events.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

$pdo->exec(
    <<<SQL
CREATE TABLE IF NOT EXISTS user_login_events (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  logged_in_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  user_agent_hash CHAR(64) NULL DEFAULT NULL,
  user_agent_snippet VARCHAR(255) NULL DEFAULT NULL,
  ip_address VARCHAR(45) NULL DEFAULT NULL,
  KEY idx_user_login (user_id, logged_in_at),
  KEY idx_user_agent (user_id, user_agent_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL
);

echo "migrate_admin_login_events: complete\n";
