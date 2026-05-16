<?php

declare(strict_types=1);

if (!defined('ACHARYA_ROOT')) {
    define('ACHARYA_ROOT', dirname(__DIR__, 2));
}

require ACHARYA_ROOT . '/includes/admin/bootstrap.php';

if (admin_user()) {
    admin_redirect(admin_dashboard_path());
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $admin = (new AdminRepository())->verifyAdmin($email, $password);
    if ($admin) {
        session_regenerate_id(true);
        $_SESSION['admin'] = $admin;
        admin_redirect(admin_dashboard_path());
    }
    $error = 'Invalid administrator credentials.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | Acharya Books</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-blue-900 flex items-center justify-center p-4 font-sans">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <h1 class="text-2xl font-bold text-white">Acharya Books</h1>
      <p class="text-slate-400 text-sm mt-1">Administrator Control Panel</p>
    </div>
    <div class="bg-white rounded-2xl shadow-2xl p-8 border border-slate-200/20">
      <h2 class="text-xl font-semibold text-slate-800">Secure Sign In</h2>
      <p class="text-sm text-slate-500 mt-1">Authorized personnel only</p>

      <?php if ($error): ?>
        <p class="mt-4 text-sm text-red-700 bg-red-50 border border-red-100 rounded-lg px-4 py-3"><?= admin_e($error) ?></p>
      <?php endif; ?>

      <form method="post" class="mt-6 space-y-4" autocomplete="off">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
          <input type="email" name="email" required
                 class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none" />
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
          <input type="password" name="password" required
                 class="w-full border border-slate-300 rounded-lg px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600 outline-none" />
        </div>
        <button type="submit"
                class="w-full py-3 bg-blue-700 hover:bg-blue-800 text-white font-semibold rounded-lg transition-colors shadow-lg shadow-blue-900/20">
          Sign In to Dashboard
        </button>
      </form>
      <p class="mt-6 text-xs text-center text-slate-400">
        First time? Run <a href="<?= admin_e(admin_site_url('admin_setup.php')) ?>" class="text-blue-600 hover:underline">admin_setup.php</a>
      </p>
    </div>
    <p class="text-center text-xs text-slate-500 mt-6">
      <a href="<?= admin_e(admin_site_url('index.php')) ?>" class="hover:text-white transition-colors">← Back to public site</a>
    </p>
  </div>
</body>
</html>
