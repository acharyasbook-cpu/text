<?php
/** @var array<string,mixed> $ctx */
/** @var array<string,list<string>> $monthDays */
require_once dirname(__DIR__, 3) . '/includes/public_site_helpers.php';
$user = current_user();
$tier = (string) ($ctx['tier'] ?? 'free');
$premium = $tier === 'premium';
$today = (string) ($ctx['today'] ?? date('Y-m-d'));
$canToday = !empty($ctx['can_take_today']) && !empty($ctx['today_ready']);
$todayDone = !empty($ctx['today_attempted']);
require dirname(__DIR__, 2) . '/includes/secure/secure_shell_start.php';
?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/current-affairs-cbt.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/css/current-affairs-cbt.css') ?>" />

<main class="ca-cbt-gateway max-w-5xl mx-auto px-4 py-6 sm:py-10 font-telugu">
  <header class="ca-cbt-header text-center mb-8">
    <p class="text-xs uppercase tracking-widest text-amber-600 font-semibold">Online CBT Exam Environment</p>
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-2">డైలీ కరెంట్ అఫైర్స్</h1>
    <p class="text-sm text-slate-600 mt-2">25 మార్క్ · కంపెటిటివ్ ఎగ్జామ్ మోడ్</p>
  </header>

  <?php if ($todayDone && !$premium): ?>
  <div class="ca-paywall-banner mb-6 p-4 rounded-xl border-2 border-amber-400 bg-amber-50 text-amber-900 text-center font-telugu text-sm font-semibold">
    ⚠️ ఈ రోజు మీరు ఇప్పటికే ఒకసారి పరీక్ష రాశారు. Premium సబ్‌స్క్రిప్షన్ తో అపరిమిత రీటేక్స్ & గత సంవత్సరం ఆర్కైవ్ అన్‌లాక్ అవుతుంది.
  </div>
  <?php endif; ?>

  <div class="grid md:grid-cols-2 gap-5">
    <section class="ca-cbt-panel <?= $canToday ? 'ca-cbt-panel--active' : ($todayDone && !$premium ? 'ca-cbt-panel--locked' : 'ca-cbt-panel--muted') ?>">
      <h2 class="ca-cbt-panel__title">ఈరోజు ఉచిత టెస్ట్</h2>
      <p class="ca-cbt-panel__desc text-sm text-slate-600 mt-2"><?= e(date('d M Y', strtotime($today))) ?></p>
      <?php if (empty($ctx['today_ready'])): ?>
      <p class="text-amber-700 text-sm mt-4">ఈ రోజు ప్రశ్నలు ఇంకా సిద్ధం కాలేదు.</p>
      <?php elseif ($canToday): ?>
      <a href="<?= e(base_url(ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($today))) ?>"
         class="ca-cbt-launch-btn mt-5">పరీక్ష ప్రారంభించండి →</a>
      <?php elseif ($todayDone && !$premium): ?>
      <p class="text-amber-800 text-sm mt-4 font-semibold">సిమ్యులేటర్ లాక్ — రోజుకు 1 ప్రయత్నం పూర్తయింది.</p>
      <button type="button" class="ca-cbt-lock-btn mt-3" onclick="document.getElementById('caPremiumModal').hidden=false">Premium తో మళ్ళీ రాయండి</button>
      <?php elseif ($premium): ?>
      <a href="<?= e(base_url(ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($today))) ?>"
         class="ca-cbt-launch-btn mt-5">మళ్ళీ ప్రయత్నించండి →</a>
      <?php endif; ?>
    </section>

    <section class="ca-cbt-panel <?= $premium ? 'ca-cbt-panel--active' : 'ca-cbt-panel--locked' ?>" id="caArchivePanel">
      <h2 class="ca-cbt-panel__title">గత సంవత్సరపు కరెంట్ అఫైర్స్</h2>
      <p class="text-sm text-slate-600 mt-2">1 సంవత్సరం ఆర్కైవ్ · నెలవారీ ఫోల్డర్లు</p>
      <?php if (!$premium): ?>
      <button type="button" class="ca-cbt-lock-btn mt-5" id="caPremiumUnlockBtn" aria-haspopup="dialog">
        🔒 Premium అన్‌లాక్
      </button>
      <p class="text-xs text-slate-500 mt-3">ఉచిత ఖాతా: గత పరీక్షలు లాక్ చేయబడ్డాయి</p>
      <?php else: ?>
      <div class="mt-4 space-y-2 max-h-64 overflow-y-auto">
        <?php if (empty($ctx['months'])): ?>
        <p class="text-sm text-slate-500">ఆర్కైవ్ డేటా లేదు.</p>
        <?php else: ?>
        <?php foreach ($ctx['months'] as $m):
            $ym = (string) ($m['ym'] ?? '');
            $dates = $monthDays[$ym] ?? [];
            if ($dates === []) continue;
        ?>
        <details class="ca-month-folder border border-slate-200 rounded-lg bg-white">
          <summary class="px-4 py-2 font-semibold cursor-pointer text-sm">[<?= e($m['label'] ?? $ym) ?>]</summary>
          <ul class="border-t divide-y text-sm">
            <?php foreach ($dates as $d): ?>
            <li>
              <a class="block px-4 py-2 hover:bg-amber-50"
                 href="<?= e(base_url(ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($d))) ?>">
                <?= e(date('d M Y', strtotime($d))) ?> →
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </details>
        <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </section>
  </div>
</main>

<div id="caPremiumModal" class="ca-premium-modal" hidden>
  <div class="ca-premium-modal__backdrop" data-ca-premium-close></div>
  <div class="ca-premium-modal__panel">
    <button type="button" class="ca-premium-modal__close" data-ca-premium-close>×</button>
    <h3 class="font-bold text-lg text-slate-900">Premium అవసరం</h3>
    <p class="text-sm text-slate-600 mt-2">గత సంవత్సరపు కరెంట్ అఫైర్స్ + అపరిమిత రీటేక్స్ కోసం సబ్‌స్క్రిప్షన్ తీసుకోండి.</p>
    <a href="<?= e(base_url('dashboard.php?panel=profile')) ?>" class="ca-cbt-launch-btn inline-flex mt-4">ప్లాన్‌లు చూడండి →</a>
  </div>
</div>

<script>
(function () {
  var btn = document.getElementById('caPremiumUnlockBtn');
  var modal = document.getElementById('caPremiumModal');
  if (!btn || !modal) return;
  btn.addEventListener('click', function () { modal.hidden = false; });
  modal.querySelectorAll('[data-ca-premium-close]').forEach(function (el) {
    el.addEventListener('click', function () { modal.hidden = true; });
  });
})();
</script>
<?php require dirname(__DIR__, 2) . '/includes/secure/secure_shell_end.php'; ?>
