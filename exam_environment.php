<?php

declare(strict_types=1);

/** Legacy entry — canonical CBT lives under admin/mcq_generator/exam_environment.php */
require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

$qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
$target = ca_exam_environment_script() . ($qs !== '' ? '?' . $qs : '');
header('Location: ' . base_url($target), true, 302);
exit;
