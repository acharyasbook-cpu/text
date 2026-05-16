<?php
/**
 * @var list<array<string,mixed>> $plans
 * @var bool $hasAccess
 * @var ?array $user
 * @var string $checkoutReturn
 */
$planUi = [
    '6_months' => ['short' => '6 నెలలు', 'hint_te' => '6 నెలల పూర్తి యాక్సెస్', 'hint' => '6 months access'],
    '1_year' => ['short' => '1 సంవత్సరం', 'hint_te' => '12 నెలల యాక్సెస్', 'hint' => '12 months access'],
    'until_exam' => ['short' => 'పరీక్ష వరకు', 'hint_te' => 'పరీక్ష తేదీ వరకు చెల్లుబాటు', 'hint' => 'Valid until examination'],
];
?>
<?php if ($hasAccess): ?>
<div class="programme-access-banner font-telugu mb-8">
  <p class="font-bold text-emerald-900">మీకు ఈ ప్రోగ్రామ్‌కు సక్రియ యాక్సెస్ ఉంది — అన్ని విషయాలు & టెస్టులు అన్‌లాక్.</p>
</div>
<?php elseif ($plans): ?>
<section class="programme-pricing mb-10" id="enrol">
  <header class="mb-5">
    <h2 class="font-telugu text-xl font-bold text-slate-900">చందా & ఎన్‌రోల్‌మెంట్</h2>
    <p class="font-telugu text-sm text-slate-600 mt-1">మీ సిద్ధతకు సరిపడా ప్లాన్ ఎంచుకోండి — తక్షణ యాక్సెస్</p>
  </header>
  <div class="grid sm:grid-cols-3 gap-4 lg:gap-5">
    <?php foreach ($plans as $pl):
        $code = (string) ($pl['plan_code'] ?? '');
        $meta = $planUi[$code] ?? ['short' => (string) ($pl['label'] ?? 'Plan'), 'hint_te' => '', 'hint' => ''];
    ?>
    <article class="programme-plan-card">
      <p class="font-telugu text-xs font-bold uppercase tracking-widest text-gold"><?= e($meta['short']) ?></p>
      <p class="font-semibold text-slate-900 mt-2"><?= e((string) ($pl['label'] ?? '')) ?></p>
      <p class="font-telugu text-xs text-slate-500 mt-1"><?= e($meta['hint_te']) ?></p>
      <p class="text-3xl font-bold text-royal mt-4">₹<?= number_format((float) ($pl['price_inr'] ?? 0), 0) ?></p>
      <?php if ($user): ?>
      <form method="post" action="<?= e(base_url('checkout.php')) ?>" class="mt-5">
        <?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= (int) ($pl['id'] ?? 0) ?>" />
        <input type="hidden" name="return" value="<?= e($checkoutReturn) ?>" />
        <button type="submit" class="classical-btn-primary w-full py-2.5 text-sm font-telugu">
          ఎన్‌రోల్ చేయండి →
        </button>
      </form>
      <?php else: ?>
      <a href="<?= e(base_url('login.php?return=' . rawurlencode($checkoutReturn))) ?>"
         class="classical-btn-primary w-full mt-5 py-2.5 text-sm font-telugu text-center block">
        లాగిన్ & ఎన్‌రోల్
      </a>
      <?php endif; ?>
    </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
