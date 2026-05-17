<?php

declare(strict_types=1);

$adminSessionDir = dirname(__DIR__, 2) . '/storage/sessions';
if (!is_dir($adminSessionDir)) {
    mkdir($adminSessionDir, 0775, true);
}
session_save_path($adminSessionDir);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('ACHARYA_ADMIN');
    session_start();
}

require_once dirname(__DIR__, 2) . '/db_connect.php';

if (!function_exists('db')) {
    function db(): PDO
    {
        return getDBConnection();
    }
}

require_once dirname(__DIR__, 2) . '/includes/ImageUploadService.php';
require_once dirname(__DIR__, 2) . '/includes/public_site_helpers.php';
require_once dirname(__DIR__, 2) . '/controllers/AdminAuthController.php';
require_once dirname(__DIR__, 2) . '/controllers/ContentManagerController.php';
require_once __DIR__ . '/helpers/whatsapp.php';
require_once dirname(__DIR__, 2) . '/models/SchemaHelper.php';

ImageUploadService::ensureStorageRoots();

date_default_timezone_set('Asia/Kolkata');

function admin_e(?string $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}

function admin_in_panel(): bool
{
  return str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/');
}

function admin_dashboard_path(): string
{
  return admin_in_panel() ? 'dashboard.php' : 'admin_dashboard.php';
}

function admin_login_path(): string
{
  return admin_in_panel() ? 'login.php' : 'admin_login.php';
}

function admin_logout_path(): string
{
  return admin_in_panel() ? 'logout.php' : 'admin_logout.php';
}

/** Public site URL (project root), not under /admin/. */
/** Public asset URL from admin panel (no Laravel — uses site root). */
function admin_media_url(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    return admin_site_url(ltrim($path, '/'));
}

function admin_site_url(string $path = ''): string
{
  $path = ltrim($path, '/');
  $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
  $root = admin_in_panel() ? rtrim(dirname($scriptDir), '/') : $scriptDir;
  if ($root === '/' || $root === '.') {
    $root = '';
  }

  return $path === '' ? ($root ?: '/') : $root . '/' . $path;
}

function admin_url(string $path = ''): string
{
  $path = ltrim($path, '/');
  $siteRootOnly = ['index.php', 'admin_api.php', 'admin_setup.php'];
  if ($path !== '' && in_array($path, $siteRootOnly, true)) {
    return admin_site_url($path);
  }

  if (admin_in_panel()) {
    $legacy = [
      'admin_dashboard.php' => 'dashboard.php',
      'admin_login.php' => 'login.php',
      'admin_logout.php' => 'logout.php',
      'admin_actions.php' => 'actions.php',
    ];
    if (isset($legacy[$path])) {
      $path = $legacy[$path];
    }
  }

  $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

  return $path === '' ? ($base ?: '') : $base . '/' . $path;
}

/** Build admin dashboard URL with query args (avoids broken relative links). */
function admin_dashboard_url(array $query): string
{
  return admin_url(admin_dashboard_path() . '?' . http_build_query($query));
}

/** Workspace URL for a main-course + sub-course slug pair. */
function admin_programme_url(string $mainCourseSlug, string $subCourseSlug): string
{
    return admin_dashboard_url([
        'view' => 'programme',
        'mc' => $mainCourseSlug,
        'sc' => $subCourseSlug,
    ]);
}

function admin_redirect(string $path): never
{
    header('Location: ' . admin_url($path));
    exit;
}

function admin_user(): ?array
{
    return $_SESSION['admin'] ?? null;
}

function require_admin(): array
{
    return AdminAuthController::requireAdminWeb();
}

function admin_csrf_token(): string
{
    return AdminAuthController::csrfToken();
}

function admin_csrf_field(): string
{
    $t = admin_csrf_token();

    return '<input type="hidden" name="_csrf" value="' . admin_e($t) . '" />';
}

function admin_flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['admin_flash'][$key] = $message;

        return null;
    }
    $msg = $_SESSION['admin_flash'][$key] ?? null;
    unset($_SESSION['admin_flash'][$key]);

    return $msg;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?: 'item';

    return trim($text, '-');
}

require_once dirname(__DIR__, 2) . '/models/TestRepository.php';
require_once dirname(__DIR__, 2) . '/models/SubscriptionRepository.php';
require_once dirname(__DIR__, 2) . '/models/AnalyticsRepository.php';
require_once dirname(__DIR__, 2) . '/controllers/AnalyticsController.php';
require_once dirname(__DIR__, 2) . '/models/AdminRepository.php';
require_once dirname(__DIR__, 2) . '/models/ContentOrderRepository.php';
require_once dirname(__DIR__, 2) . '/models/PlatformRepository.php';
require_once dirname(__DIR__, 2) . '/models/SubjectTermMatrixRepository.php';
require_once dirname(__DIR__, 2) . '/models/SubjectScheduleService.php';
require_once dirname(__DIR__, 2) . '/models/CourseRepository.php';
require_once dirname(__DIR__, 2) . '/services/WhatsAppDispatchService.php';
require_once dirname(__DIR__, 2) . '/models/WhatsAppHubRepository.php';
require_once dirname(__DIR__, 2) . '/controllers/WhatsAppHubController.php';
