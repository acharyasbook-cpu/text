<footer class="bg-royal-dark text-white mt-auto">
  <div class="h-1 bg-gradient-to-r from-gold via-gold-light to-gold"></div>
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
      <div>
        <p class="font-serif text-xl font-bold">Acharya Books</p>
        <p class="font-telugu text-gold-light text-sm mt-2">ధన్యవాదాలు — మీ నమ్మకానికి</p>
      </div>
      <div>
        <p class="text-xs uppercase tracking-widest text-gold mb-3">Platform</p>
        <ul class="space-y-2 text-sm text-slate-300">
          <li><a href="<?= e(base_url('courses.php')) ?>" class="hover:text-gold-light">All Courses</a></li>
          <li><a href="<?= e(base_url('dashboard.php')) ?>" class="hover:text-gold-light">Dashboard</a></li>
          <li><a href="<?= e(base_url('exams.php')) ?>" class="hover:text-gold-light">Online Exams</a></li>
        </ul>
      </div>
      <div>
        <p class="text-xs uppercase tracking-widest text-gold mb-3">Test Types</p>
        <ul class="space-y-2 text-sm text-slate-300">
          <li>Topic-wise Tests</li>
          <li>Division Tests</li>
          <li>Grand Mock Tests</li>
        </ul>
      </div>
      <div>
        <p class="text-xs uppercase tracking-widest text-gold mb-3">Contact</p>
        <p class="text-sm text-slate-300">info@acharyabooks.com</p>
        <p class="text-sm text-slate-300 mt-1">+91 98765 43210</p>
      </div>
    </div>
    <p class="mt-10 pt-6 border-t border-white/10 text-xs text-slate-400">
      &copy; <?= date('Y') ?> Acharya Books. All rights reserved.
    </p>
  </div>
</footer>
<?php require __DIR__ . '/layout-end.php'; ?>
