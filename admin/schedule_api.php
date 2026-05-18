<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require_once ACHARYA_ROOT . '/includes/admin/bootstrap.php';
require_once ACHARYA_ROOT . '/models/ScheduleTestRepository.php';
require_once ACHARYA_ROOT . '/models/ExamManagerRepository.php';
require_once ACHARYA_ROOT . '/models/WhatsAppHubRepository.php';
require_once ACHARYA_ROOT . '/services/ScheduleDailyNotificationService.php';
require_once ACHARYA_ROOT . '/services/WhatsAppMobileGatewayService.php';
require_once ACHARYA_ROOT . '/controllers/ScheduleTestController.php';

(new ScheduleTestController())->handle();
