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

// Web path prefix to app root (e.g. '' or '/mysubdir'). Never use only dirname(SCRIPT_NAME) for
// scripts under /admin/* — that breaks public assets. Never rely on DOCUMENT_ROOT/realpath alone —
// that can diverge from SCRIPT_NAME and blank the layout.
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
$adminMarker = '/admin/';
$adminPos = strpos($scriptName, $adminMarker);
if ($adminPos !== false) {
    $prefix = $adminPos === 0 ? '' : substr($scriptName, 0, $adminPos);
    $basePath = $prefix === '' ? '' : rtrim($prefix, '/');
} else {
    $scriptDir = str_replace('\\', '/', dirname($scriptName));
    $basePath = ($scriptDir === '/' || $scriptDir === '.' || $scriptDir === '') ? '' : rtrim($scriptDir, '/');
}
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

function require_login(?string $returnPath = null): array
{
    $user = current_user();
    if (!$user) {
        $return = safe_return_path($returnPath ?? ($_SERVER['REQUEST_URI'] ?? ''));
        $q = $return !== '' ? '?return=' . rawurlencode(ltrim($return, '/')) : '';
        redirect('login.php' . $q);
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

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }

    return (string) $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '" />';
}

function verify_csrf(?string $token): void
{
    $expected = $_SESSION['csrf_token'] ?? '';
    if ($expected === '' || $token === null || !hash_equals($expected, $token)) {
        throw new InvalidArgumentException('Security token expired. Please try again.');
    }
}

function safe_return_path(?string $return): string
{
    $return = trim((string) $return);
    if ($return === '' || str_contains($return, '://') || str_starts_with($return, '//')) {
        return '';
    }
    if ($return[0] !== '/') {
        $return = '/' . $return;
    }
    if (str_contains($return, '..')) {
        return '';
    }

    return $return;
}

/** @return PDO */
function db(): PDO
{
    require_once dirname(__DIR__) . '/db_connect.php';

    return getDBConnection();
}

require_once dirname(__DIR__) . '/includes/ImageUploadService.php';
require_once dirname(__DIR__) . '/models/SchemaHelper.php';

require_once dirname(__DIR__) . '/models/CourseRepository.php';
require_once dirname(__DIR__) . '/models/UserRepository.php';
require_once dirname(__DIR__) . '/models/TestRepository.php';
require_once dirname(__DIR__) . '/models/SubscriptionRepository.php';
require_once dirname(__DIR__) . '/models/PlatformRepository.php';
require_once dirname(__DIR__) . '/models/StudentAnalyticsRepository.php';
require_once dirname(__DIR__) . '/models/AdminRepository.php';
require_once dirname(__DIR__) . '/models/ContentOrderRepository.php';
require_once dirname(__DIR__) . '/models/SubjectTermMatrixRepository.php';
require_once dirname(__DIR__) . '/models/SubjectScheduleService.php';
require_once dirname(__DIR__) . '/controllers/HeaderController.php';
