<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require_once ACHARYA_ROOT . '/includes/admin/bootstrap.php';
require_once ACHARYA_ROOT . '/models/PricingAdminRepository.php';
require_once ACHARYA_ROOT . '/models/CouponRepository.php';
require_once ACHARYA_ROOT . '/controllers/PricingAdminController.php';

(new PricingAdminController())->handle();
