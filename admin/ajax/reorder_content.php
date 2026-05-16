<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__, 2));
require ACHARYA_ROOT . '/includes/admin/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'POST required']);
        exit;
    }

    $raw = file_get_contents('php://input') ?: '';
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    AdminAuthController::requireAdminApi(true, $data);

    $entity = preg_replace('/[^a-z_]/', '', (string) ($data['entity'] ?? ''));
    $action = (string) ($data['action'] ?? 'move');
    $courseId = (int) ($data['course_id'] ?? 0);
    if ($courseId > 0) {
        $courseId = (new AdminRepository())->resolveContentManagerCourseId($courseId);
    }
    $ctx = [
        'course_id' => $courseId,
        'sub_course_id' => (int) ($data['sub_course_id'] ?? 0),
        'subject_id' => (int) ($data['subject_id'] ?? 0),
        'topic_id' => (int) ($data['topic_id'] ?? 0),
    ];

    $orderRepo = new ContentOrderRepository();
    if (!$orderRepo->isValidEntity($entity)) {
        throw new InvalidArgumentException('Unknown entity: ' . $entity);
    }

    if ($action === 'reorder_batch') {
        $ids = $data['ordered_ids'] ?? [];
        if (!is_array($ids)) {
            $ids = [];
        }
        $items = $orderRepo->reorderBatch($entity, $ids, $ctx);
        echo json_encode(['ok' => true, 'items' => $items]);
        exit;
    }

    $id = (int) ($data['id'] ?? 0);
    $direction = (string) ($data['direction'] ?? '');
    $siblingId = isset($data['sibling_id']) ? (int) $data['sibling_id'] : null;
    if ($id < 1) {
        throw new InvalidArgumentException('id required');
    }
    if ($direction !== 'up' && $direction !== 'down' && ($siblingId === null || $siblingId < 1)) {
        throw new InvalidArgumentException('direction or sibling_id required');
    }

    $result = $orderRepo->move($entity, $id, $direction, $ctx, $siblingId);
    echo json_encode($result);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
