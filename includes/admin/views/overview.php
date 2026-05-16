<?php
require_once dirname(__DIR__, 2) . '/public_site_helpers.php';
/** @var array $stats @var array $enrollment */
$s = $stats;
$labels = array_column($enrollment, 'month');
$counts = array_column($enrollment, 'count');
if (empty($labels)) {
    $labels = [date('Y-m')];
    $counts = [0];
}
?>
<div class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
  <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Students</p>
    <p class="text-3xl font-bold text-slate-900 mt-2"><?= number_format((int) $s['total_students']) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Courses</p>
    <p class="text-3xl font-bold text-brand mt-2"><?= number_format((int) $s['total_courses']) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Exams Conducted</p>
    <p class="text-3xl font-bold text-slate-900 mt-2"><?= number_format((int) $s['total_exams']) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-5 shadow-sm">
    <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">Revenue</p>
    <p class="text-3xl font-bold text-emerald-600 mt-2">₹<?= number_format((float) $s['revenue'], 0) ?></p>
  </div>
</div>

<?php
$platform = new PlatformRepository();
$logoUrl = $platform->logoPath() ? public_media_url($platform->logoPath()) : '';
?>
<div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm mb-6">
  <h2 class="font-semibold text-slate-800 mb-2">Site branding (logo)</h2>
  <p class="text-xs text-slate-500 mb-4">అప్‌లోడ్ చేసిన లోగో మొత్తం పబ్లిక్ సైట్ హెడర్‌లో తక్షణం కనిపిస్తుంది.</p>
  <form method="post" action="<?= admin_e(admin_url('actions.php')) ?>" enctype="multipart/form-data" class="flex flex-wrap items-end gap-4">
    <input type="hidden" name="action" value="save_site_logo" />
    <div class="flex items-center gap-4">
      <label class="relative w-20 h-20 rounded-full border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center cursor-pointer hover:border-brand overflow-hidden group">
        <?php if ($logoUrl): ?>
        <img src="<?= admin_e($logoUrl) ?>?v=<?= time() ?>" alt="" class="w-full h-full object-cover" />
        <?php else: ?>
        <span class="text-3xl font-bold text-slate-400 group-hover:text-brand">+</span>
        <?php endif; ?>
        <input type="file" name="site_logo" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" />
      </label>
      <div>
        <p class="text-sm font-medium text-slate-800">Circular logo</p>
        <p class="text-xs text-slate-500">PNG/JPG · max 2MB</p>
      </div>
    </div>
    <button type="submit" class="px-5 py-2.5 bg-slate-900 text-white text-sm font-semibold rounded-lg hover:bg-slate-800">Apply logo</button>
  </form>
  <?php if ($logoUrl): ?>
  <form method="post" action="<?= admin_e(admin_url('actions.php')) ?>" class="mt-3">
    <input type="hidden" name="action" value="clear_site_logo" />
    <button type="submit" class="px-4 py-2 text-sm text-red-600 border border-red-200 rounded-lg">Clear logo</button>
  </form>
  <?php endif; ?>
</div>

<div class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
    <h2 class="font-semibold text-slate-800 mb-4">Student Enrollment Trend</h2>
    <canvas id="enrollmentChart" height="120"></canvas>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-sm">
    <h2 class="font-semibold text-slate-800 mb-4">Quick Actions</h2>
    <div class="space-y-2">
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'content'])) ?>" class="block px-4 py-3 rounded-lg bg-slate-50 hover:bg-blue-50 text-sm font-medium text-slate-700 border border-slate-100 font-telugu">కంటెంట్ మేనేజర్ (4-Tier)</a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'courses'])) ?>" class="block px-4 py-3 rounded-lg bg-slate-50 hover:bg-blue-50 text-sm font-medium text-slate-700 border border-slate-100">+ Add Course / Subject</a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'exams'])) ?>" class="block px-4 py-3 rounded-lg bg-slate-50 hover:bg-blue-50 text-sm font-medium text-slate-700 border border-slate-100">+ Create Online Exam</a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'students'])) ?>" class="block px-4 py-3 rounded-lg bg-slate-50 hover:bg-blue-50 text-sm font-medium text-slate-700 border border-slate-100">Manage Students</a>
    </div>
  </div>
</div>

<script>
new Chart(document.getElementById('enrollmentChart'), {
  type: 'line',
  data: {
    labels: <?= json_encode($labels) ?>,
    datasets: [{
      label: 'New Students',
      data: <?= json_encode(array_map('intval', $counts)) ?>,
      borderColor: '#1e40af',
      backgroundColor: 'rgba(30,64,175,0.1)',
      fill: true,
      tension: 0.35,
    }],
  },
  options: {
    responsive: true,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } },
  },
});
</script>
