<?php

declare(strict_types=1);

/**
 * Exam Manager: sub_grand type + unlimited timer (duration_mins=0).
 * Run: php database/migrate_exam_manager_v2.php
 */

require dirname(__DIR__) . '/db_connect.php';

$pdo = getDBConnection();

try {
    $pdo->exec(
        "ALTER TABLE tests MODIFY COLUMN test_type
         ENUM('topic','revision','sub_grand','grand','division','model') NOT NULL DEFAULT 'topic'"
    );
    echo "tests.test_type: sub_grand + legacy values\n";
} catch (Throwable $e) {
    echo 'tests.test_type: ' . $e->getMessage() . "\n";
}

$pdo->exec("UPDATE tests SET test_type='sub_grand' WHERE test_type='division'");
echo "mapped division → sub_grand\n";

echo "migrate_exam_manager_v2: complete\n";
