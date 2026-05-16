<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';
require_once __DIR__ . '/controllers/HomeController.php';

$slug = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['course'] ?? ''));
if ($slug === '') {
    redirect('index.php');
}

(new HomeController())->learn($slug);
