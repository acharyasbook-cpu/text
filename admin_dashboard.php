<?php

declare(strict_types=1);

$query = $_SERVER['QUERY_STRING'] ?? '';
$target = '/admin/dashboard.php' . ($query !== '' ? '?' . $query : '');
header('Location: ' . $target, true, 301);
exit;
