<?php

declare(strict_types=1);

define('ACHARYA_ROOT', dirname(__DIR__));
require ACHARYA_ROOT . '/includes/admin/bootstrap.php';

if (admin_user()) {
    admin_redirect(admin_dashboard_path());
}
admin_redirect(admin_login_path());
