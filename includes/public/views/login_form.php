<section class="max-w-md mx-auto">
  <div class="bg-white border border-[#E3E6F0] rounded-xl shadow-sm p-8">
    <h1 class="font-telugu text-2xl font-bold text-slate-900 text-center">లాగిన్</h1>
    <p class="text-sm text-slate-600 text-center mt-2">ఆచార్య బుక్ — విద్యార్థి / అడ్మిన్ యూనిఫైడ్ గేట్‌వే</p>

    <?php if (!empty($error)): ?>
    <p class="mt-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg px-4 py-3"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="mt-6 space-y-4" autocomplete="on">
      <?php if (!empty($return)): ?>
      <input type="hidden" name="return" value="<?= e($return) ?>" />
      <?php endif; ?>
      <div>
        <label class="block text-xs font-bold text-slate-800 mb-1">Email</label>
        <input type="email" name="email" required value="<?= e($email ?? '') ?>"
               class="w-full border border-[#E3E6F0] rounded-lg px-3 py-2.5 text-sm text-slate-900 focus:border-royal outline-none" />
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-800 mb-1">Password</label>
        <input type="password" name="password" required
               class="w-full border border-[#E3E6F0] rounded-lg px-3 py-2.5 text-sm text-slate-900 focus:border-royal outline-none" />
      </div>
      <button type="submit" class="classical-btn-primary w-full py-3">Sign In</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
      New student? <a href="<?= e(base_url('register.php')) ?>" class="font-semibold text-royal hover:underline">Create account</a>
    </p>
    <p class="mt-3 text-xs text-center text-slate-400">Admin users are routed to the control panel automatically.</p>
  </div>
</section>
