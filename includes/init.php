<?php

declare(strict_types=1);

if (!defined('ACHARYA_ROOT')) {
    define('ACHARYA_ROOT', dirname(__DIR__));
}

$sessionDir = dirname(__DIR__) . '/storage/sessions';
if (!is_dir($sessionDir)) {
    mkdir($sessionDir, 0775, true);
}
session_save_path($sessionDir);
session_start();

$config = require dirname(__DIR__) . '/config/app.php';
$dbConfig = require dirname(__DIR__) . '/config/database.php';

date_default_timezone_set($config['timezone']);

$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$basePath = ($scriptDir === '/' || $scriptDir === '') ? '' : rtrim($scriptDir, '/');
$config['base_url'] = $basePath;

function base_url(string $path = ''): string
{
    global $config;
    $base = $config['base_url'] ?? '';
    $path = ltrim($path, '/');
    return $path === '' ? ($base ?: '/') : $base . '/' . $path;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        redirect('login.php');
    }
    return $user;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

/** @return PDO */
function db(): PDO
{
    require_once dirname(__DIR__) . '/db_connect.php';

    return getDBConnection();
}

require_once dirname(__DIR__) . '/models/SchemaHelper.php';

require_once dirname(__DIR__) . '/models/CourseRepository.php';
require_once dirname(__DIR__) . '/models/UserRepository.php';
require_once dirname(__DIR__) . '/models/TestRepository.php';
require_once dirname(__DIR__) . '/models/SubscriptionRepository.php';
require_once dirname(__DIR__) . '/models/PlatformRepository.php';
require_once dirname(__DIR__) . '/models/StudentAnalyticsRepository.php';
require_once dirname(__DIR__) . '/models/AdminRepository.php';
require_once dirname(__DIR__) . '/controllers/HeaderController.php';
