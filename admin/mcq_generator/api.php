<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__, 2));
require_once ACHARYA_ROOT . '/includes/admin/bootstrap.php';
require_once ACHARYA_ROOT . '/controllers/McqGeneratorController.php';

(new McqGeneratorController())->handle();
