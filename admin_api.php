<?php

declare(strict_types=1);

require __DIR__ . '/includes/admin/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);

    exit;
}

$raw = file_get_contents('php://input') ?: '';
$data = json_decode($raw, true);
if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);

    exit;
}

AdminAuthController::requireAdminApi(true, $data);

$status = !empty($data['status']);
$repo = new AdminRepository();

try {
    $entityRaw = (string) ($data['entity'] ?? '');
    if ($entityRaw === 'test_tier') {
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $testType = preg_replace('/[^a-z]/', '', (string) ($data['test_type'] ?? ''));
        $allowedTiers = ['topic', 'division', 'revision', 'grand', 'model'];
        if ($subjectId < 1 || !in_array($testType, $allowedTiers, true)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Invalid subject or tier']);

            exit;
        }
        $repo->setTestsLiveForSubjectTier($subjectId, $testType, $status);
        echo json_encode(['ok' => true, 'entity' => 'test_tier', 'subject_id' => $subjectId, 'test_type' => $testType, 'status' => $status]);

        exit;
    }

    $entity = preg_replace('/[^a-z_]/', '', $entityRaw);
    $id = (int) ($data['id'] ?? 0);
    $allowed = ['course', 'category', 'subject', 'module', 'test', 'sub_course', 'scs', 'topic'];
    if (!in_array($entity, $allowed, true) || $id < 1) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => 'Invalid entity or id']);

        exit;
    }

    if ($entity === 'subject' && !empty($data['cascade_tests'])) {
        $repo->setSubjectVisibilityWithCascade($id, $status);
    } else {
        $repo->setEntityStatus($entity, $id, $status);
    }

    echo json_encode(['ok' => true, 'entity' => $entity, 'id' => $id, 'status' => $status]);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
