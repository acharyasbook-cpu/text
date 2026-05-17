<?php

declare(strict_types=1);

/** Legacy dashboard route — redirect to unified manager. */
header('Location: ' . admin_url('schedule_test.php'), true, 302);
exit;
