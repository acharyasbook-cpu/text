<?php

/**
 * Freemium: topics.can_download + bootstrap support.
 * CLI: php database/migrate_freemium_twenty.php
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function ft_table(PDO $pdo, string $t): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND TABLE_TYPE=\'BASE TABLE\''
    );
    $st->execute([$db, $t]);

    return (int) $st->fetchColumn() > 0;
}

function ft_col(PDO $pdo, string $table, string $col): bool
{
    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();
    $st = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
    );
    $st->execute([$db, $table, $col]);

    return (int) $st->fetchColumn() > 0;
}

$topics = ft_table($pdo, 'topics') ? 'topics' : (ft_table($pdo, 'lessons') ? 'lessons' : null);
if (!$topics) {
    fwrite(STDERR, "topics table missing.\n");
    exit(1);
}

echo "migrate_freemium_twenty: start\n";

if (!ft_col($pdo, $topics, 'can_download')) {
    $pdo->exec(
        "ALTER TABLE `{$topics}` ADD COLUMN can_download TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Paid students may download notes PDF/text' AFTER is_free_preview"
    );
    echo "added {$topics}.can_download\n";
}

echo "migrate_freemium_twenty: done\n";
