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
        AdminAuthController::establishSession($admin);
        admin_redirect(admin_dashboard_path());
    }
    require_once ACHARYA_ROOT . '/controllers/McqAuth.php';
    require_once ACHARYA_ROOT . '/models/ExaminerRepository.php';
    $examiner = (new ExaminerRepository())->verifyLogin($email, $password);
    if ($examiner) {
        McqAuth::establishExaminerSession($examiner);
        admin_redirect('mcq_generator/');
    }
    $error = 'Invalid administrator or examiner credentials.';
}
$adminCss = admin_site_url('assets/css/admin-premium.css');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | Acharya Books</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Telugu:wght@500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="<?= admin_e($adminCss) ?>?v=1" />
</head>
<body class="admin-premium-shell min-h-screen flex items-center justify-center p-4" style="background:#F8F9FA">
  <div class="w-full max-w-md">
    <div class="text-center mb-8">
      <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Acharya Books</h1>
      <p class="text-slate-500 text-sm mt-1 font-telugu">ఎంటర్‌ప్రైజ్ అడ్మిన్ ప్యానెల్</p>
    </div>
    <div class="admin-card p-8">
      <h2 class="text-xl font-semibold text-slate-800">Secure Sign In</h2>
      <p class="text-sm text-slate-500 mt-1">Authorized personnel only</p>

      <?php if ($error): ?>
        <p class="mt-4 admin-alert-error text-sm"><?= admin_e($error) ?></p>
      <?php endif; ?>

      <form method="post" class="mt-6 space-y-4" autocomplete="off">
        <div>
          <label class="admin-label block mb-1">Email</label>
          <input type="email" name="email" required class="admin-input" />
        </div>
        <div>
          <label class="admin-label block mb-1">Password</label>
          <input type="password" name="password" required class="admin-input" />
        </div>
        <button type="submit" class="admin-btn admin-btn-primary w-full py-2.5">Sign in</button>
      </form>
      <p class="mt-6 text-center text-sm text-slate-500">
        <a href="<?= admin_e(admin_site_url('index.php')) ?>" class="text-indigo-600 font-semibold hover:underline">← Back to public site</a>
      </p>
    </div>
  </div>
</body>
</html>
