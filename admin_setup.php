<?php

declare(strict_types=1);

require __DIR__ . '/db_connect.php';

$messages = [];
try {
    $pdo = getDBConnection();

    $cols = $pdo->query("SHOW COLUMNS FROM tests LIKE 'passing_marks'")->fetch();
    if (!$cols) {
        $pdo->exec('ALTER TABLE tests ADD COLUMN passing_marks SMALLINT UNSIGNED NOT NULL DEFAULT 25 AFTER total_marks');
        $messages[] = 'Added passing_marks to tests.';
    }

    $cols = $pdo->query("SHOW COLUMNS FROM test_questions LIKE 'explanation'")->fetch();
    if (!$cols) {
        $pdo->exec('ALTER TABLE test_questions ADD COLUMN explanation TEXT NULL AFTER correct_option');
        $messages[] = 'Added explanation to test_questions.';
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS payments (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        package_id INT UNSIGNED DEFAULT NULL,
        amount_inr DECIMAL(10,2) NOT NULL,
        payment_method VARCHAR(40) DEFAULT 'manual',
        transaction_ref VARCHAR(120) DEFAULT NULL,
        status ENUM('completed','pending','failed','refunded') NOT NULL DEFAULT 'completed',
        paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (package_id) REFERENCES sub_course_packages(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");

    $messages[] = 'Payments table ready.';

    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $check->execute(['admin@acharyabooks.com']);
    if ($check->fetch()) {
        $pdo->prepare('UPDATE users SET password_hash=?, role="admin" WHERE email=?')->execute([$hash, 'admin@acharyabooks.com']);
        $messages[] = 'Admin password updated.';
    } else {
        $pdo->prepare('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)')
            ->execute(['Platform Admin', 'admin@acharyabooks.com', $hash, 'admin']);
        $messages[] = 'Admin user created.';
    }

    $messages[] = 'Login: admin@acharyabooks.com / admin123';
} catch (Throwable $e) {
    $messages[] = 'Error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><title>Admin Setup</title>
<script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-100 p-8 font-sans">
<div class="max-w-md mx-auto bg-white rounded-xl shadow p-6">
<h1 class="text-xl font-bold text-slate-800 mb-4">Admin Setup</h1>
<?php foreach ($messages as $m): ?><p class="text-sm text-slate-600 mb-2"><?= htmlspecialchars($m) ?></p><?php endforeach; ?>
<a href="admin_login.php" class="inline-block mt-4 px-4 py-2 bg-blue-700 text-white rounded-lg text-sm font-semibold">Go to Admin Login</a>
</div></body></html>
