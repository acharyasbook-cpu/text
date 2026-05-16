<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$subjectId = (int) ($_GET['subject_id'] ?? 0);
$subCourseId = (int) ($_GET['sub_course_id'] ?? 0);
$user = current_user();
$userId = $user ? (int) $user['id'] : null;
$courseSlug = trim((string) ($_GET['course'] ?? ''));

try {
    if ($subjectId < 1) {
        throw new InvalidArgumentException('subject_id required');
    }

    $courses = new CourseRepository();
    $topics = $courses->topicsForSubject($subjectId);
    $subject = $courses->findSubject($subjectId);
    $hasAccess = false;
    if ($user && $subCourseId > 0) {
        $hasAccess = (new SubscriptionRepository())->userHasActivePlanForSubCourse($userId, $subCourseId);
    }
    $workspace = $subject
        ? $courses->enrichTopicsForSubjectWorkspace($topics, $subject, $hasAccess)
        : [];

    echo json_encode([
        'ok' => true,
        'subject_id' => $subjectId,
        'sub_course_id' => $subCourseId,
        'topics_workspace' => $workspace,
    ], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
