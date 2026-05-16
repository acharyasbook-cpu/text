  </main>
</div>

<footer class="border-t border-[#E3E6F0] bg-white mt-12">
  <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center text-sm text-slate-600">
    <p class="font-telugu font-semibold text-slate-900"><?= e($header['site_name_te'] ?? 'ఆచార్య బుక్') ?></p>
    <p class="mt-1">&copy; <?= date('Y') ?> <?= e($header['site_name'] ?? 'Acharya Books') ?>. All rights reserved.</p>
  </div>
</footer>

<?php if (!empty($view) && in_array($view, ['home', 'learn'], true)): ?>
<script src="<?= e(base_url('assets/js/classical-media-poll.js')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 2) . '/assets/js/classical-media-poll.js') ?>"></script>
<?php endif; ?>

</body>
</html>
