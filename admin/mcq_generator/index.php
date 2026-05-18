<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__, 2));
require_once ACHARYA_ROOT . '/includes/admin/bootstrap.php';
require_once ACHARYA_ROOT . '/controllers/McqAuth.php';

McqAuth::requireMcqWeb();

$isAdmin = McqAuth::isSuperAdmin();
$isExaminer = McqAuth::isExaminer();
$assignedSubject = McqAuth::assignedSubject();
$apiUrl = admin_url('api.php');
$activeView = 'mcq_generator';
$pageTitle = 'AI MCQ Engine | Admin';
$adminPageTitle = 'AI MCQ & Notes Generator';
$adminPageSubtitle = $isExaminer
    ? 'Examiner portal · ' . ($assignedSubject ?? '')
    : 'PDF segments · examiners · multi-API · two-stage approval';

require ACHARYA_ROOT . '/includes/admin/layout_start.php';
require ACHARYA_ROOT . '/includes/admin/partials/page_header.php';
require ACHARYA_ROOT . '/includes/admin/views/mcq_generator/index.php';
require ACHARYA_ROOT . '/includes/admin/layout_end.php';
