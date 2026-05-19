<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/controllers/CurrentAffairsController.php';

(new CurrentAffairsController())->hub();
