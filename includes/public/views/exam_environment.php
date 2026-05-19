<?php
/** @var array<string,mixed> $ctx */
/** @var array<string,list<string>> $monthDays */
require_once dirname(__DIR__, 3) . '/includes/public_site_helpers.php';
require_once dirname(__DIR__, 3) . '/includes/CurrentAffairsDummyData.php';
$user = current_user();
$premium = !empty($ctx['premium']);
$today = (string) ($ctx['today'] ?? date('Y-m-d'));
$canToday = !empty($ctx['can_take_today']);
$todayDone = !empty($ctx['today_attempted']);
$archiveMonths = $ctx['archive_months'] ?? [];
$todayExamUrl = (string) ($ctx['today_exam_url'] ?? base_url(ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($today)));
$paywallMessage = 'ఇది కేవలం పెయిడ్ మెంబర్స్‌కు మాత్రమే! ఇందులో ప్రతి నెలలో ప్రతి రోజు ఉండే కరెంట్ అఫైర్స్ ఎగ్జామ్స్ ఉంటాయి. దయచేసి ఆచార్య బుక్స్ ప్రైమ్ మెంబర్ అవ్వండి!';
require dirname(__DIR__, 2) . '/includes/secure/secure_shell_start.php';
?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/current-affairs-cbt.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 3) . '/assets/css/current-affairs-cbt.css') ?>" />

<main class="ca-cbt-gateway max-w-5xl mx-auto px-4 py-6 sm:py-10 font-telugu" id="caExamEnvironment">
  <header class="ca-cbt-header text-center mb-8">
    <p class="text-xs uppercase tracking-widest text-amber-600 font-semibold">Online CBT Exam Environment</p>
    <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 mt-2">డైలీ కరెంట్ అఫైర్స్</h1>
    <p class="text-sm text-slate-600 mt-2">25 మార్క్ · 25 నిమిషాల టైమర్ · కంపెటిటివ్ సిమ్యులేటర్</p>
  </header>

  <?php if ($todayDone && !$premium): ?>
  <div class="ca-paywall-banner mb-6 p-4 rounded-xl border-2 border-amber-400 bg-amber-50 text-amber-900 text-center text-sm font-semibold">
    ⚠️ ఈ రోజు మీరు ఇప్పటికే ఒకసారి పరీక్ష రాశారు. Premium తో అపరిమిత రీటేక్స్ & నెలవారీ ఆర్కైవ్ అన్‌లాక్ అవుతుంది.
  </div>
  <?php endif; ?>

  <div class="grid md:grid-cols-2 gap-5">
    <section class="ca-cbt-panel <?= $canToday ? 'ca-cbt-panel--active' : ($todayDone && !$premium ? 'ca-cbt-panel--locked' : 'ca-cbt-panel--active') ?>" id="caPillarToday">
      <h2 class="ca-cbt-panel__title">ఈరోజు ఉచిత టెస్ట్</h2>
      <p class="ca-cbt-panel__desc text-sm text-slate-600 mt-2"><?= e(date('d M Y', strtotime($today))) ?></p>
      <?php if ($canToday): ?>
      <a href="<?= e($todayExamUrl) ?>" class="ca-cbt-launch-btn mt-5" id="caTodayExamBtn">పరీక్ష ప్రారంభించండి →</a>
      <p class="text-xs text-slate-500 mt-2">25 ప్రశ్నలు · A/B/C/D · [Submit Exam]</p>
      <?php elseif ($todayDone && !$premium): ?>
      <p class="text-amber-800 text-sm mt-4 font-semibold">సిమ్యులేటర్ లాక్ — రోజుకు 1 ప్రయత్నం పూర్తయింది.</p>
      <button type="button" class="ca-cbt-lock-btn mt-3" data-ca-open-paywall>Premium తో మళ్ళీ రాయండి</button>
      <?php elseif ($premium): ?>
      <a href="<?= e($todayExamUrl) ?>" class="ca-cbt-launch-btn mt-5">మళ్ళీ ప్రయత్నించండి →</a>
      <?php else: ?>
      <a href="<?= e($todayExamUrl) ?>" class="ca-cbt-launch-btn mt-5">పరీక్ష ప్రారంభించండి →</a>
      <?php endif; ?>
    </section>

    <section class="ca-cbt-panel ca-cbt-panel--active" id="caPillarArchive">
      <h2 class="ca-cbt-panel__title">నెలవారీ కరెంట్ అఫైర్స్ ఆర్కైవ్</h2>
      <p class="text-sm text-slate-600 mt-2">గత 12 నెలల పరీక్షల ఫోల్డర్లు · క్రమబద్ధంగా</p>
      <div class="mt-4 space-y-2 max-h-80 overflow-y-auto" id="caMonthGrid">
        <?php foreach ($archiveMonths as $m):
            $ym = (string) ($m['ym'] ?? '');
            if ($ym === '') continue;
            $label = CurrentAffairsDummyData::teluguMonthYear($ym);
            $dates = $monthDays[$ym] ?? [];
        ?>
        <details class="ca-month-folder border border-slate-200 rounded-lg bg-white<?= $premium ? '' : ' ca-month-folder--paywall' ?>"
                 data-ym="<?= e($ym) ?>"
                 <?= $premium ? '' : ' data-ca-paywall-folder' ?>>
          <summary class="px-4 py-2.5 font-semibold cursor-pointer text-sm select-none">
            [<?= e($label) ?>]
            <span class="text-xs font-normal text-slate-500 ml-1">(<?= count($dates) ?> రోజులు)</span>
          </summary>
          <?php if ($premium && $dates !== []): ?>
          <ul class="border-t divide-y text-sm">
            <?php foreach ($dates as $d): ?>
            <li>
              <a class="block px-4 py-2 hover:bg-amber-50 font-telugu"
                 href="<?= e(base_url(ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($d))) ?>">
                <?= e(date('d M Y', strtotime($d))) ?> →
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php elseif ($premium): ?>
          <p class="px-4 py-3 text-xs text-slate-500 border-t">ఈ నెలలో పరీక్షలు లేవు.</p>
          <?php endif; ?>
        </details>
        <?php endforeach; ?>
      </div>
    </section>
  </div>
</main>

<div id="caPremiumModal" class="ca-premium-modal" hidden role="dialog" aria-labelledby="caPremiumModalTitle">
  <div class="ca-premium-modal__backdrop" data-ca-premium-close></div>
  <div class="ca-premium-modal__panel">
    <button type="button" class="ca-premium-modal__close" data-ca-premium-close aria-label="మూసివేయి">×</button>
    <h3 id="caPremiumModalTitle" class="font-bold text-lg text-slate-900 font-telugu">Premium అవసరం</h3>
    <p class="text-sm text-slate-700 mt-4 leading-relaxed font-telugu font-semibold"><?= e($paywallMessage) ?></p>
    <a href="<?= e(base_url('dashboard.php?panel=profile')) ?>" class="ca-cbt-launch-btn inline-flex mt-6">ప్రైమ్ మెంబర్ అవ్వండి →</a>
  </div>
</div>

<script>
(function () {
  var modal = document.getElementById('caPremiumModal');
  if (!modal) return;
  function openPaywall() { modal.hidden = false; }
  function closePaywall() { modal.hidden = true; }
  modal.querySelectorAll('[data-ca-premium-close]').forEach(function (el) {
    el.addEventListener('click', closePaywall);
  });
  document.querySelectorAll('[data-ca-open-paywall]').forEach(function (btn) {
    btn.addEventListener('click', openPaywall);
  });
  document.querySelectorAll('[data-ca-paywall-folder]').forEach(function (folder) {
    folder.querySelector('summary').addEventListener('click', function (e) {
      e.preventDefault();
      folder.removeAttribute('open');
      openPaywall();
    });
  });
})();
</script>
<?php require dirname(__DIR__, 2) . '/includes/secure/secure_shell_end.php'; ?>
