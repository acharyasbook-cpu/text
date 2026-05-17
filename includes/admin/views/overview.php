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
$brandingReturnView = 'overview';
$adminPageTitle = 'డాష్‌బోర్డ్ ఓవర్వ్యూ';
$adminPageSubtitle = 'ప్లాట్‌ఫామ్ మెట్రిక్స్ & త్వరిత చర్యలు';
require __DIR__ . '/../partials/page_header.php';
require __DIR__ . '/branding_logo_card.php';
?>

<div class="admin-metric-grid cols-4 mb-8">
  <div class="admin-card admin-metric">
    <p class="admin-metric-label">Total Students</p>
    <p class="admin-metric-value"><?= number_format((int) $s['total_students']) ?></p>
  </div>
  <div class="admin-card admin-metric">
    <p class="admin-metric-label">Total Courses</p>
    <p class="admin-metric-value text-indigo-600"><?= number_format((int) $s['total_courses']) ?></p>
  </div>
  <div class="admin-card admin-metric">
    <p class="admin-metric-label">Exams Conducted</p>
    <p class="admin-metric-value"><?= number_format((int) $s['total_exams']) ?></p>
  </div>
  <div class="admin-card admin-metric">
    <p class="admin-metric-label">Revenue</p>
    <p class="admin-metric-value text-emerald-700">₹<?= number_format((float) $s['revenue'], 0) ?></p>
  </div>
</div>

<div class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 admin-card p-6">
    <h2 class="font-semibold text-slate-800 mb-4">Student Enrollment Trend</h2>
    <canvas id="enrollmentChart" height="120"></canvas>
  </div>
  <div class="admin-card p-6">
    <h2 class="font-semibold text-slate-800 mb-4 font-telugu">త్వరిత చర్యలు</h2>
    <div class="space-y-2">
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'content'])) ?>" class="block px-4 py-3 rounded-xl bg-slate-50 hover:bg-indigo-50 text-sm font-medium text-slate-700 border border-slate-100 font-telugu transition-colors">కంటెంట్ మేనేజర్</a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'analytics'])) ?>" class="block px-4 py-3 rounded-xl bg-slate-50 hover:bg-indigo-50 text-sm font-medium text-slate-700 border border-slate-100 font-telugu transition-colors">వినియోగదారు విశ్లేషణ</a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'pricing'])) ?>" class="block px-4 py-3 rounded-xl bg-slate-50 hover:bg-indigo-50 text-sm font-medium text-slate-700 border border-slate-100 font-telugu transition-colors">ప్రైసింగ్ &amp; టైర్‌లు</a>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'students'])) ?>" class="block px-4 py-3 rounded-xl bg-slate-50 hover:bg-indigo-50 text-sm font-medium text-slate-700 border border-slate-100 transition-colors">Manage Students</a>
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
      borderColor: '#4F46E5',
      backgroundColor: 'rgba(79,70,229,0.08)',
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
