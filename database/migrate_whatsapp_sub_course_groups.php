<?php

declare(strict_types=1);

/**
 * Add per sub-course WhatsApp group invite links.
 * Run: php database/migrate_whatsapp_sub_course_groups.php
 */

define('ACHARYA_ROOT', dirname(__DIR__));
require ACHARYA_ROOT . '/db_connect.php';
require ACHARYA_ROOT . '/models/SchemaHelper.php';

$pdo = db();

if (!SchemaHelper::hasTable('sub_courses')) {
    fwrite(STDERR, "sub_courses table not found.\n");
    exit(1);
}

if (!SchemaHelper::columnExists('sub_courses', 'whatsapp_group_link')) {
    $pdo->exec('ALTER TABLE sub_courses ADD COLUMN whatsapp_group_link VARCHAR(512) NULL DEFAULT NULL');
    echo "Added sub_courses.whatsapp_group_link\n";
} else {
    echo "sub_courses.whatsapp_group_link already exists\n";
}

echo "Done.\n";
