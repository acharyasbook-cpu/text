<?php

declare(strict_types=1);

define('EXAM_FOCUS_LAYOUT', true);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';
redirect(ca_exam_environment_script() . '?action=result');
