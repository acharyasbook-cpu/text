<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$subCourseId = (int) ($_GET['sub_course_id'] ?? 0);
$user = current_user();
$userId = $user ? (int) $user['id'] : null;
$courseSlug = trim((string) ($_GET['course'] ?? ''));

try {
    if ($subCourseId < 1) {
        throw new InvalidArgumentException('sub_course_id required');
    }

    $service = new SubjectScheduleService();
    $matrix = $service->buildSubCourseMatrixView($subCourseId, $userId, $courseSlug);
    $repo = new SubjectTermMatrixRepository();

    echo json_encode([
        'ok' => true,
        'sub_course_id' => $subCourseId,
        'matrix' => $matrix,
        'global' => $repo->globalDefaults(),
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
