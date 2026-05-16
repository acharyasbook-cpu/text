<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

$scope = $_GET['scope'] ?? 'courses';
$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

/** @return array{id:int,image_path:string,url:string,v:int} */
$mapRow = static function (array $row): array {
    $path = (string) ($row['image_path'] ?? '');
    $abs = $path !== '' ? ACHARYA_ROOT . '/' . ltrim($path, '/') : '';
    $v = ($abs !== '' && is_file($abs)) ? (int) filemtime($abs) : 0;

    return [
        'id' => (int) $row['id'],
        'image_path' => $path,
        'url' => $path !== '' ? acharya_media_url($path) : '',
        'v' => $v,
    ];
};

$repo = new CourseRepository();
$items = [];

if ($scope === 'logo') {
    $path = (new PlatformRepository())->logoPath() ?? '';
    $abs = $path !== '' ? ACHARYA_ROOT . '/' . ltrim($path, '/') : '';
    echo json_encode([
        'logo_path' => $path,
        'url' => $path !== '' ? acharya_media_url($path) : '',
        'v' => ($abs !== '' && is_file($abs)) ? (int) filemtime($abs) : 0,
    ], JSON_THROW_ON_ERROR);
    exit;
}

if ($scope === 'sub_courses' && $courseId > 0) {
    foreach ($repo->subCoursesForCourse($courseId) as $sc) {
        $items[] = $mapRow($sc);
    }
} else {
    foreach ($repo->allActive() as $c) {
        $items[] = $mapRow($c);
    }
}

echo json_encode(['items' => $items], JSON_THROW_ON_ERROR);
