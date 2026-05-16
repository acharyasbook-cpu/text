<?php

declare(strict_types=1);

/**
 * Admin POST router — logo/branding uploads + dashboard actions.
 * URL: /admin/actions.php (pure PHP, no artisan).
 */

define('ACHARYA_ROOT', dirname(__DIR__));
require ACHARYA_ROOT . '/includes/admin/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['action'])) {
    admin_redirect(admin_dashboard_path() . '?view=overview');
}

require_admin();
AdminAuthController::verifyCsrf(AdminAuthController::csrfFromRequest());

ImageUploadService::ensureStorageRoots();

$action = (string) $_POST['action'];
$returnView = preg_replace('/[^a-z_]/', '', (string) ($_POST['return_view'] ?? 'overview'));
if ($returnView === '') {
    $returnView = 'overview';
}

try {
    if ($action === 'save_site_logo') {
        admin_process_site_logo_upload();
    } elseif ($action === 'clear_site_logo') {
        admin_process_clear_site_logo();
    } else {
        require ACHARYA_ROOT . '/includes/admin/actions.php';
        admin_redirect_after_action($returnView);

        return;
    }
} catch (Throwable $e) {
    admin_flash('error', $e->getMessage());
    admin_redirect(admin_dashboard_path() . '?view=' . $returnView);

    return;
}

admin_redirect_after_action($returnView);

/** Upload site logo — case-insensitive image types, max 10MB, public/assets/images/branding. */
function admin_process_site_logo_upload(): void
{
    $plat = new PlatformRepository();
    $oldPath = $plat->logoPath();
    $repo = new AdminRepository();

    $path = $repo->handleUpload('site_logo', 'branding');
    if (!$path) {
        throw new InvalidArgumentException('దయచేసి చిత్రం ఎంచుకోండి (JPG, PNG, GIF, SVG, WEBP — 10 MB వరకు).');
    }

    $plat->set('site_logo_path', $path);
    if ($oldPath !== null && $oldPath !== $path) {
        ImageUploadService::deleteIfStored($oldPath);
    }

    admin_flash('success', 'లోగో అప్‌డేట్ అయింది — పబ్లిక్ సైట్ హెడర్‌లో తక్షణం కనిపిస్తుంది.');
}

function admin_process_clear_site_logo(): void
{
    $plat = new PlatformRepository();
    $oldPath = $plat->logoPath();
    $plat->set('site_logo_path', null);
    ImageUploadService::deleteIfStored($oldPath);
    admin_flash('success', 'లోగో తొలగించబడింది.');
}

function admin_redirect_after_action(string $returnView): never
{
    $view = preg_replace('/[^a-z_]/', '', (string) ($_GET['view'] ?? $returnView));
    if ($view === '') {
        $view = 'overview';
    }

    $query = ['view' => $view];
    $tab = $_GET['tab'] ?? ($_POST['tab_redirect'] ?? '');
    if (is_string($tab) && $tab !== '') {
        $safeTab = preg_replace('/[^a-z_]/', '', $tab);
        if ($safeTab !== '') {
            $query['tab'] = $safeTab;
        }
    }

    admin_redirect(admin_dashboard_path() . '?' . http_build_query($query));
}
