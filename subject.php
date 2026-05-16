<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';
require_once __DIR__ . '/controllers/CourseController.php';

$courseSlug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_GET['course'] ?? '')));
$subSlug = preg_replace('/[^a-z0-9-]/', '', strtolower(trim($_GET['sub'] ?? '')));
$subjectSlug = preg_replace('/[^a-z0-9_-]/', '', strtolower(trim($_GET['subject'] ?? '')));

if ($courseSlug === '' || $subjectSlug === '') {
    http_response_code(404);
    exit('Subject not found');
}

(new CourseController())->subject(
    $courseSlug,
    $subSlug !== '' ? $subSlug : null,
    $subjectSlug
);
