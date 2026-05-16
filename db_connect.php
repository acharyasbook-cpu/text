<?php

declare(strict_types=1);

/**
 * Acharya Books — shared database connection.
 * Used by the admin panel and can be required by other scripts.
 */
function getDBConnection(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config/database.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    if (!class_exists('SchemaHelper', false)) {
        require_once __DIR__ . '/models/SchemaHelper.php';
    }
    SchemaHelper::ensureCatalogHealth();

    return $pdo;
}

// Convenience alias for legacy includes
if (!function_exists('db')) {
    function db(): PDO
    {
        return getDBConnection();
    }
}
