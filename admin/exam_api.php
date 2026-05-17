<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require_once ACHARYA_ROOT . '/includes/admin/bootstrap.php';
require_once ACHARYA_ROOT . '/models/ExamManagerRepository.php';
require_once ACHARYA_ROOT . '/controllers/ExamManagerController.php';

(new ExamManagerController())->handle();
