<?php
/** @var array<string,mixed> $ctx */
/** @var array<string,list<string>> $monthDays */
require_once dirname(__DIR__, 3) . '/includes/public_site_helpers.php';
$tier = (string) ($ctx['tier'] ?? 'free');
$today = (string) ($ctx['today'] ?? date('Y-m-d'));
?>
<section class="ca-hub-section max-w-4xl mx-auto">
  <header class="mb-8">
    <p class="text-[10px] font-bold uppercase tracking-widest text-royal font-sans">Daily Current Affairs</p>
    <h1 class="font-telugu text-2xl sm:text-3xl font-bold text-slate-900 mt-1">డైలీ కరెంట్ అఫైర్స్ హబ్</h1>
    <p class="font-telugu text-sm text-slate-600 mt-2">
      <?php if ($tier === 'premium'): ?>
        Premium: నేటి టెస్ట్ + గత 1 సంవత్సరం అపరిమిత ప్రయత్నాలు · నెలవారీ ఫోల్డర్లు
      <?php else: ?>
        ఉచిత ఖాతా: నేటి తేదీ పరీక్ష మాత్రమే · ఒక్క ప్రయత్నం
      <?php endif; ?>
    </p>
  </header>

  <div class="ca-today-card classical-card border border-[#E3E6F0] rounded-xl p-6 mb-8 bg-white">
    <h2 class="font-telugu text-lg font-bold text-slate-900">నేటి పరీక్ష · <?= e(date('d M Y', strtotime($today))) ?></h2>
    <?php if (empty($ctx['today_ready'])): ?>
    <p class="font-telugu text-sm text-amber-700 mt-3">ఈ రోజు 25 ప్రశ్నలు ఇంకా అడ్మిన్ స్టేజ్ చేయలేదు.</p>
    <?php elseif (!empty($ctx['can_take_today'])): ?>
    <a href="<?= e(base_url(ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($today))) ?>"
       class="classical-btn-primary inline-flex mt-4 px-6 py-3 font-telugu text-sm">
      పరీక్ష ప్రారంభించండి →
    </a>
    <?php elseif (!empty($ctx['today_attempted'])): ?>
    <p class="font-telugu text-sm text-slate-600 mt-3">మీరు ఈ రోజు పరీక్ష పూర్తి చేశారు. Premium తో మళ్ళీ ప్రయత్నించండి.</p>
    <?php else: ?>
    <p class="font-telugu text-sm text-slate-600 mt-3">పరీక్ష అందుబాటులో లేదు.</p>
    <?php endif; ?>
  </div>

  <?php if (!empty($ctx['show_archive']) && !empty($ctx['months'])): ?>
  <div class="ca-archive-stack space-y-3">
    <h2 class="font-telugu text-lg font-bold text-slate-900 mb-2">గత నెలలు (కొత్తది పైన)</h2>
    <?php foreach ($ctx['months'] as $m):
        $ym = (string) ($m['ym'] ?? '');
        $dates = $monthDays[$ym] ?? [];
        if ($ym === date('Y-m', strtotime($today)) || $dates === []) {
            continue;
        }
    ?>
    <details class="ca-month-folder classical-card border border-[#E3E6F0] rounded-xl bg-white overflow-hidden">
      <summary class="font-telugu px-5 py-4 cursor-pointer font-semibold text-slate-900 hover:bg-slate-50 list-none flex justify-between items-center">
        <span>[<?= e($m['label'] ?? $ym) ?>]</span>
        <span class="text-xs text-slate-500"><?= count($dates) ?> రోజులు</span>
      </summary>
      <ul class="border-t border-[#E3E6F0] divide-y divide-[#E3E6F0]">
        <?php foreach ($dates as $d): ?>
        <li>
          <a class="flex justify-between items-center px-5 py-3 text-sm font-telugu hover:bg-royal/5"
             href="<?= e(base_url(ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($d))) ?>">
            <span><?= e(date('d M Y (l)', strtotime($d))) ?></span>
            <span class="text-royal font-semibold">ప్రయత్నించు →</span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </details>
    <?php endforeach; ?>

    <?php
    $currentYm = date('Y-m', strtotime($today));
    $currentDates = array_filter($monthDays[$currentYm] ?? [], static fn ($d) => $d !== $today);
    if ($currentDates !== []):
    ?>
    <details class="ca-month-folder classical-card border border-[#E3E6F0] rounded-xl bg-white overflow-hidden" open>
      <summary class="font-telugu px-5 py-4 cursor-pointer font-semibold text-slate-900 hover:bg-slate-50 list-none">
        [<?= e(date('F Y', strtotime($today)) . ' Current Affairs') ?>] — ఈ నెల
      </summary>
      <ul class="border-t border-[#E3E6F0] divide-y divide-[#E3E6F0]">
        <?php foreach ($currentDates as $d): ?>
        <li>
          <a class="flex justify-between items-center px-5 py-3 text-sm font-telugu hover:bg-royal/5"
             href="<?= e(base_url(ca_exam_environment_script() . '?action=exam&date=' . rawurlencode($d))) ?>">
            <span><?= e(date('d M Y', strtotime($d))) ?></span>
            <span class="text-royal font-semibold">ప్రయత్నించు →</span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </details>
    <?php endif; ?>
  </div>
  <?php elseif ($tier === 'free'): ?>
  <p class="font-telugu text-xs text-slate-500 border border-dashed border-[#E3E6F0] rounded-lg p-4 text-center">
    గత నెలల క్యాలెండర్ Premium సబ్‌స్క్రిప్షన్ తో మాత్రమే కనిపిస్తుంది.
  </p>
  <?php endif; ?>
</section>
