<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require ACHARYA_ROOT . '/includes/admin/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

ImageUploadService::ensureStorageRoots();

try {
    (new ContentManagerController())->dispatch();
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
