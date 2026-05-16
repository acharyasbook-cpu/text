<?php

$defaults = [
    'host'     => getenv('DB_HOST') ?: '127.0.0.1',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_NAME') ?: 'acharya_books',
    'username' => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4',
];

$local = __DIR__ . '/database.local.php';
if (is_readable($local)) {
    return array_merge($defaults, require $local);
}

return $defaults;
