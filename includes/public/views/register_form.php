<section class="max-w-lg mx-auto">
  <div class="bg-white border border-[#E3E6F0] rounded-xl shadow-sm p-8">
    <h1 class="font-telugu text-2xl font-bold text-slate-900 text-center">కొత్త వినియోగదారు</h1>
    <p class="text-sm text-slate-600 text-center mt-2 font-telugu">పూర్తి పేరు, మొబైల్, ఇమెయిల్ — నమోదు చేయండి</p>

    <?php if (!empty($error)): ?>
    <p class="mt-4 text-sm text-red-800 bg-red-50 border border-red-200 rounded-lg px-4 py-3"><?= e($error) ?></p>
    <?php endif; ?>

    <form method="post" class="mt-6 space-y-4">
      <div>
        <label class="block text-xs font-bold text-slate-800 mb-1 font-telugu">పూర్తి పేరు</label>
        <input type="text" name="name" required value="<?= e($form['name'] ?? '') ?>"
               class="w-full border border-[#E3E6F0] rounded-lg px-3 py-2.5 text-sm font-telugu" />
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-800 mb-1">Mobile</label>
        <input type="tel" name="phone" required value="<?= e($form['phone'] ?? '') ?>"
               class="w-full border border-[#E3E6F0] rounded-lg px-3 py-2.5 text-sm" placeholder="10-digit mobile" />
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-800 mb-1">Email</label>
        <input type="email" name="email" required value="<?= e($form['email'] ?? '') ?>"
               class="w-full border border-[#E3E6F0] rounded-lg px-3 py-2.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-800 mb-1">Password</label>
        <input type="password" name="password" required minlength="6"
               class="w-full border border-[#E3E6F0] rounded-lg px-3 py-2.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs font-bold text-slate-800 mb-1">Confirm password</label>
        <input type="password" name="password_confirm" required minlength="6"
               class="w-full border border-[#E3E6F0] rounded-lg px-3 py-2.5 text-sm" />
      </div>
      <button type="submit" class="classical-btn-primary w-full py-3 font-telugu">నమోదు చేయి</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">
      Already registered? <a href="<?= e(base_url('login.php')) ?>" class="font-semibold text-royal hover:underline">Login</a>
    </p>
  </div>
</section>
