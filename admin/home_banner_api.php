<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require_once ACHARYA_ROOT . '/includes/admin/bootstrap.php';
require_once ACHARYA_ROOT . '/includes/HomeBannerSettings.php';
require_once ACHARYA_ROOT . '/includes/ImageUploadService.php';

header('Content-Type: application/json; charset=utf-8');
require_admin();

$action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'get');

try {
    if ($action === 'get') {
        echo json_encode(['ok' => true, 'settings' => HomeBannerSettings::all()]);
        exit;
    }

    if ($action === 'save') {
        $token = (string) ($_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if ($token === '' || !hash_equals(admin_csrf_token(), $token)) {
            throw new RuntimeException('CSRF validation failed');
        }
        $section = (string) ($_POST['section'] ?? 'hero');
        $payload = [
            'bg_color' => (string) ($_POST['bg_color'] ?? ''),
            'line1' => (string) ($_POST['line1'] ?? ''),
            'line2' => (string) ($_POST['line2'] ?? ''),
            'line3' => (string) ($_POST['line3'] ?? ''),
            'line1_size' => (string) ($_POST['line1_size'] ?? ''),
            'line2_size' => (string) ($_POST['line2_size'] ?? ''),
            'line3_size' => (string) ($_POST['line3_size'] ?? ''),
            'eyebrow' => (string) ($_POST['eyebrow'] ?? ''),
        ];
        if (!empty($_FILES['bg_image']['tmp_name'])) {
            $payload['bg_image'] = ImageUploadService::storeFromFileArray(
                $_FILES['bg_image'],
                'platform',
                $section === 'ca' ? 'home_ca_banner' : 'home_hero_banner'
            );
        }
        if ($section === 'ca') {
            HomeBannerSettings::saveCa($payload);
        } else {
            HomeBannerSettings::saveHero($payload);
        }
        echo json_encode(['ok' => true, 'settings' => HomeBannerSettings::all()]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
} catch (Throwable $e) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
