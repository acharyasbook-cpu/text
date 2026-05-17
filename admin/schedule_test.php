<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require_once ACHARYA_ROOT . '/includes/admin/bootstrap.php';

$activeView = 'schedule';
$pageTitle = 'Schedule Test Manager | Admin';
$adminPageTitle = 'Schedule Test Manager';
$adminPageSubtitle = 'రోజువారీ / తేదీవారీ షెడ్యూల్ · హైబ్రిడ్ ప్రశ్న క్యూరేటర్';

$repo = new AdminRepository();
$courses = $repo->allCourses();
$subCourses = $repo->allSubCoursesForSelect();

require ACHARYA_ROOT . '/includes/admin/layout_start.php';
require ACHARYA_ROOT . '/includes/admin/partials/page_header.php';
require ACHARYA_ROOT . '/includes/admin/views/schedule_test_manager.php';
require ACHARYA_ROOT . '/includes/admin/layout_end.php';
