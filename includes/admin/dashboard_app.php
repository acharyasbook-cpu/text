<?php

declare(strict_types=1);

if (!defined('ACHARYA_ROOT')) {
    define('ACHARYA_ROOT', dirname(__DIR__, 2));
}

require ACHARYA_ROOT . '/includes/admin/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_admin();
    require ACHARYA_ROOT . '/includes/admin/actions.php';
}

if (($_GET['view'] ?? '') === 'subjects') {
    $qs = $_GET;
    unset($qs['view']);
    $qs['view'] = 'courses';
    $qs['tab'] = 'subjects';
    header('Location: ' . admin_dashboard_url($qs));
    exit;
}

$view = $_GET['view'] ?? 'overview';
$allowed = ['overview', 'content', 'schedule', 'pricing', 'whatsapp', 'analytics', 'hierarchy', 'courses', 'exams', 'students', 'programme', 'current_affairs', 'home_banner'];
if (!in_array($view, $allowed, true)) {
    $view = 'overview';
}

$navProgramme = null;

$repo = new AdminRepository();
$titles = [
    'overview'  => 'Dashboard Overview',
    'content'   => 'Content Manager',
    'schedule'  => 'Schedule Test',
    'pricing'   => 'Pricing & Gateways',
    'whatsapp'  => 'WhatsApp Hub',
    'analytics' => 'Users Analysis',
    'hierarchy' => 'Hierarchy & Live / Draft',
    'courses'   => 'Course & Subject Management',
    'exams'     => 'Exam & MCQ Manager',
    'students'  => 'Students & Subscriptions',
    'programme' => 'Programme Workspace',
    'current_affairs' => 'Daily Current Affairs Engine',
    'home_banner' => 'హోమ్ బ్యానర్ బ్రాండింగ్ నిర్వహణ',
];

$pageTitle = $titles[$view] . ' | Admin';
$activeView = $view;

if ($view === 'programme') {
    $pageTitle = 'Programme Workspace | Admin';
}

$workspaceError = null;
$workspaceSubjects = [];
$programmeRow = null;

if ($view === 'programme') {
    $mc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['mc'] ?? ''));
    $sc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['sc'] ?? ''));
    if ($mc !== '' && $sc !== '') {
        header('Location: ' . admin_dashboard_url(['view' => 'content', 'mc' => $mc, 'sc' => $sc]));
        exit;
    }
    if (!SchemaHelper::hierarchyFourTier()) {
        $workspaceError = 'Run migrate_four_tier.php to enable programme workspaces.';
    } else {
        $mc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['mc'] ?? 'ap-dsc'));
        $sc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['sc'] ?? 'pet'));
        if ($mc === '' || $sc === '') {
            $workspaceError = 'Missing course (mc) or sub-course (sc) slug.';
        } else {
            $programmeRow = $repo->resolveSubCourseByCourseAndSlug($mc, $sc);
            $navProgramme = ['mc' => $mc, 'sc' => $sc];
            if (!$programmeRow) {
                $workspaceError = 'No sub-course matches those slugs.';
            } else {
                $workspaceSubjects = $repo->subjectsWithTieredTestsForWorkspace((int) $programmeRow['id']);
                $pageTitle = ($programmeRow['name'] ?? 'Programme') . ' · Workspace | Admin';
            }
        }
    }
}

require ACHARYA_ROOT . '/includes/admin/layout_start.php';

switch ($view) {
    case 'programme':
        require ACHARYA_ROOT . '/includes/admin/views/programme_workspace.php';
        break;
    case 'hierarchy':
        require ACHARYA_ROOT . '/includes/admin/views/hierarchy.php';
        break;
    case 'courses':
        require ACHARYA_ROOT . '/includes/admin/views/courses.php';
        break;
    case 'exams':
        require ACHARYA_ROOT . '/includes/admin/views/exams.php';
        break;
    case 'students':
        require ACHARYA_ROOT . '/includes/admin/views/students.php';
        break;
    case 'content':
        require ACHARYA_ROOT . '/includes/admin/views/content_manager.php';
        break;
    case 'schedule':
        require ACHARYA_ROOT . '/includes/admin/views/schedule_test.php';
        break;
    case 'pricing':
        require ACHARYA_ROOT . '/includes/admin/views/pricing.php';
        break;
    case 'whatsapp':
        require ACHARYA_ROOT . '/includes/admin/views/whatsapp.php';
        break;
    case 'analytics':
        require ACHARYA_ROOT . '/includes/admin/views/analytics.php';
        break;
    case 'current_affairs':
        require ACHARYA_ROOT . '/includes/admin/views/current_affairs.php';
        break;
    case 'home_banner':
        require ACHARYA_ROOT . '/includes/admin/views/home_banner_branding.php';
        break;
    default:
        $dash = $repo->dashboardStats();
        $stats = $dash['stats'];
        $enrollment = $dash['enrollment'];
        require ACHARYA_ROOT . '/includes/admin/views/overview.php';
}

require ACHARYA_ROOT . '/includes/admin/layout_end.php';
