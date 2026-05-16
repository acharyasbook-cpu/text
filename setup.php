<?php

declare(strict_types=1);

$messages = [];
$ok = true;

try {
    $dbConfig = require __DIR__ . '/config/database.php';
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;charset=%s', $dbConfig['host'], $dbConfig['port'], $dbConfig['charset']),
        $dbConfig['username'],
        $dbConfig['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    foreach (array_filter(array_map('trim', explode(';', $schema))) as $statement) {
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
    $messages[] = 'Schema installed.';

    $seed = file_get_contents(__DIR__ . '/database/seed.sql');
    foreach (array_filter(array_map('trim', explode(';', $seed))) as $statement) {
        if ($statement !== '' && !preg_match('/^USE /i', $statement)) {
            $pdo->exec($statement);
        }
    }

    $hash = password_hash('student123', PASSWORD_DEFAULT);
    $pdo->prepare('UPDATE users SET password_hash = ? WHERE email = ?')->execute([$hash, 'student@acharyabooks.com']);
    $messages[] = 'Seed data loaded. Demo login: student@acharyabooks.com / student123';

    ob_start();
    try {
        require __DIR__ . '/database/migrate_hierarchy.php';
        $migrateOut = trim(ob_get_clean());
        if ($migrateOut !== '') {
            foreach (preg_split('/\R/', $migrateOut) as $ln) {
                if ($ln !== '') {
                    $messages[] = $ln;
                }
            }
        }
    } catch (Throwable $migrateErr) {
        ob_end_clean();
        $messages[] = 'Hierarchy migration error: ' . $migrateErr->getMessage();
    }

    ob_start();
    try {
        require __DIR__ . '/database/migrate_four_tier.php';
        $migrateOut2 = trim(ob_get_clean());
        if ($migrateOut2 !== '') {
            foreach (preg_split('/\R/', $migrateOut2) as $ln) {
                if ($ln !== '') {
                    $messages[] = $ln;
                }
            }
        }
    } catch (Throwable $migrateErr2) {
        ob_end_clean();
        $messages[] = 'Four-tier migration error: ' . $migrateErr2->getMessage();
    }

    ob_start();
    try {
        require __DIR__ . '/database/migrate_topic_exams.php';
        $migrateOut3 = trim(ob_get_clean());
        if ($migrateOut3 !== '') {
            foreach (preg_split('/\R/', $migrateOut3) as $ln) {
                if ($ln !== '') {
                    $messages[] = $ln;
                }
            }
        }
    } catch (Throwable $migrateErr3) {
        ob_end_clean();
        $messages[] = 'Topic exams migration error: ' . $migrateErr3->getMessage();
    }

    ob_start();
    try {
        require __DIR__ . '/database/migrate_dynamic_hierarchy.php';
        $migrateOut4 = trim(ob_get_clean());
        if ($migrateOut4 !== '') {
            foreach (preg_split('/\R/', $migrateOut4) as $ln) {
                if ($ln !== '') {
                    $messages[] = $ln;
                }
            }
        }
    } catch (Throwable $migrateErr4) {
        ob_end_clean();
        $messages[] = 'Dynamic hierarchy migration error: ' . $migrateErr4->getMessage();
    }

    ob_start();
    try {
        require __DIR__ . '/database/migrate_exam_hierarchy.php';
        $migrateOut5 = trim(ob_get_clean());
        if ($migrateOut5 !== '') {
            foreach (preg_split('/\R/', $migrateOut5) as $ln) {
                if ($ln !== '') {
                    $messages[] = $ln;
                }
            }
        }
    } catch (Throwable $migrateErr5) {
        ob_end_clean();
        $messages[] = 'Exam hierarchy migration error: ' . $migrateErr5->getMessage();
    }
} catch (Throwable $e) {
    $ok = false;
    $messages[] = 'Error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Acharya Books Setup</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-6">
  <div class="max-w-lg w-full bg-white rounded-lg shadow-lg p-8">
    <h1 class="text-2xl font-bold text-blue-900 mb-4">Acharya Books Setup</h1>
    <?php foreach ($messages as $msg): ?>
      <p class="mb-2 text-sm <?= $ok ? 'text-green-700' : 'text-red-700' ?>"><?= htmlspecialchars($msg) ?></p>
    <?php endforeach; ?>
    <?php if ($ok): ?>
      <a href="dashboard.php" class="inline-block mt-4 px-6 py-2 bg-blue-900 text-white rounded">Go to Dashboard</a>
    <?php endif; ?>
  </div>
</body>
</html>
