<?php

declare(strict_types=1);

$__caAction = (string) ($_GET['action'] ?? 'hub');
if ($__caAction === 'exam' || $__caAction === 'result') {
    define('EXAM_FOCUS_LAYOUT', true);
}

/**
 * Student Current Affairs CBT — routed under admin/mcq_generator for product layout.
 * Uses public app session (includes/init.php), not ACHARYA_ADMIN.
 */
require dirname(__DIR__, 2) . '/includes/init.php';
require_once dirname(__DIR__, 2) . '/controllers/CurrentAffairsController.php';

$controller = new CurrentAffairsController();

match ($__caAction) {
    'exam' => $controller->exam(),
    'result' => $controller->result(),
    default => $controller->examEnvironment(),
};
