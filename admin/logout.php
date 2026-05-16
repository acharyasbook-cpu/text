<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require ACHARYA_ROOT . '/includes/admin/bootstrap.php';
unset($_SESSION['admin']);
session_destroy();
admin_redirect(admin_login_path());
