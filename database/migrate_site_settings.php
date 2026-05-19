<?php

/**
 * Global site UI settings (home banners).
 * CLI: php database/migrate_site_settings.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function ss_table(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?'
    );
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

echo "migrate_site_settings: start\n";

if (!ss_table($pdo, 'st_site_settings')) {
    $pdo->exec(
        "CREATE TABLE st_site_settings (
            setting_key VARCHAR(128) NOT NULL PRIMARY KEY,
            setting_value MEDIUMTEXT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    echo "created st_site_settings\n";
}

if (ss_table($pdo, 'platform_settings')) {
    $keys = $pdo->query(
        "SELECT setting_key, setting_value FROM platform_settings
         WHERE setting_key LIKE 'home_%'"
    )->fetchAll();
    $ins = $pdo->prepare(
        'INSERT INTO st_site_settings (setting_key, setting_value) VALUES (?,?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    foreach ($keys as $row) {
        $ins->execute([$row['setting_key'], $row['setting_value']]);
    }
    echo 'migrated ' . count($keys) . " home_* keys from platform_settings\n";
}

echo "migrate_site_settings: done\n";
