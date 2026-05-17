<?php

declare(strict_types=1);

/**
 * Mobile gateway — MacroDroid webhook trigger for WhatsApp group posts.
 *
 * Webhook: https://trigger.macrodroid.com/9aad4101-7481-4873-bfb4-7a38dee2ad3a/acharyasbook
 * Query:   ?group={group_name}&message={message_body}
 */

define('ACHARYA_ROOT', dirname(__DIR__));
require ACHARYA_ROOT . '/includes/admin/bootstrap.php';
require_once ACHARYA_ROOT . '/models/WhatsAppHubRepository.php';
require_once ACHARYA_ROOT . '/services/WhatsAppDispatchService.php';
require_once ACHARYA_ROOT . '/services/WhatsAppMobileGatewayService.php';
require_once ACHARYA_ROOT . '/controllers/WhatsAppMobileGatewayController.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    (new WhatsAppMobileGatewayController())->dispatch();
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
