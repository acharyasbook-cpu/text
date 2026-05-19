<?php

declare(strict_types=1);

define('EXAM_FOCUS_LAYOUT', true);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/controllers/CurrentAffairsController.php';

(new CurrentAffairsController())->gateway();
