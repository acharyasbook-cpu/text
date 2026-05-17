<?php
/**
 * @var list<array<string,mixed>> $plans
 * @var string $checkoutReturn
 * @var ?array $user
 */
$plans = $plans ?? [];
$checkoutReturn = $checkoutReturn ?? '';
$user = $user ?? current_user();
$razorpayConfig = file_exists(dirname(__DIR__, 4) . '/config/razorpay.php')
    ? (require dirname(__DIR__, 4) . '/config/razorpay.php')
    : [];
$keyId = trim((string) ($razorpayConfig['key_id'] ?? getenv('RAZORPAY_KEY_ID') ?: ''));
$planUi = [
    '6_months' => ['short' => '6 నెలలు', 'hint_te' => '6 నెలల పూర్తి యాక్సెస్'],
    '1_year' => ['short' => '1 సంవత్సరం', 'hint_te' => '12 నెలల యాక్సెస్'],
    'until_exam' => ['short' => 'పరీక్ష వరకు', 'hint_te' => 'పరీక్ష తేదీ వరకు చెల్లుబాటు'],
];
?>
<div id="freemiumCheckoutModal" class="freemium-modal" hidden aria-hidden="true" role="dialog" aria-labelledby="freemiumModalTitle"
     data-razorpay-key="<?= e($keyId) ?>"
     data-checkout-return="<?= e($checkoutReturn) ?>"
     data-order-url="<?= e(base_url('api/razorpay_create_order.php')) ?>"
     data-verify-url="<?= e(base_url('api/razorpay_verify.php')) ?>">
  <div class="freemium-modal__backdrop" data-freemium-close></div>
  <div class="freemium-modal__panel font-telugu">
    <button type="button" class="freemium-modal__close" data-freemium-close aria-label="Close">×</button>
    <header class="freemium-modal__head">
      <h2 id="freemiumModalTitle" class="text-lg font-bold text-slate-900">పూర్తి యాక్సెస్ అన్‌లాక్</h2>
      <p class="text-sm text-slate-600 mt-1">మొదటి 2 టాపిక్‌లు ఉచిత — మిగతా కోసం ప్లాన్ ఎంచుకోండి</p>
    </header>
    <?php if ($plans === []): ?>
    <p class="text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-lg p-4">ఈ ప్రోగ్రామ్‌కు ప్లాన్‌లు ఇంకా కాన్ఫిగర్ చేయబడలేదు. అడ్మిన్‌ను సంప్రదించండి.</p>
    <?php else: ?>
    <div class="freemium-modal__plans grid gap-3 sm:grid-cols-1">
      <?php foreach ($plans as $pl):
          $code = (string) ($pl['plan_code'] ?? '');
          $meta = $planUi[$code] ?? ['short' => (string) ($pl['label'] ?? 'Plan'), 'hint_te' => ''];
          $planId = (int) ($pl['id'] ?? 0);
          $amountPaise = (int) round((float) ($pl['price_inr'] ?? 0) * 100);
      ?>
      <article class="freemium-plan-card" data-plan-id="<?= $planId ?>" data-amount-paise="<?= $amountPaise ?>">
        <p class="text-xs font-bold uppercase tracking-widest text-gold"><?= e($meta['short']) ?></p>
        <p class="font-semibold text-slate-900 mt-1"><?= e((string) ($pl['label'] ?? '')) ?></p>
        <p class="text-xs text-slate-500 mt-0.5"><?= e($meta['hint_te']) ?></p>
        <p class="text-2xl font-bold text-royal mt-2">₹<?= number_format((float) ($pl['price_inr'] ?? 0), 0) ?></p>
        <?php if ($user): ?>
        <button type="button"
                class="freemium-plan-pay classical-btn-primary w-full mt-3 py-2.5 text-sm"
                data-plan-id="<?= $planId ?>">
          Razorpay తో చెల్లించండి →
        </button>
        <form method="post" action="<?= e(base_url('checkout.php')) ?>" class="mt-2 hidden freemium-fallback-form">
          <?= csrf_field() ?>
          <input type="hidden" name="plan_id" value="<?= $planId ?>" />
          <input type="hidden" name="return" value="<?= e($checkoutReturn) ?>" />
          <button type="submit" class="w-full text-xs text-slate-600 underline">తక్షణ డెమో చెక్కౌట్</button>
        </form>
        <?php else: ?>
        <a href="<?= e(base_url('login.php?return=' . rawurlencode($checkoutReturn))) ?>"
           class="classical-btn-primary w-full mt-3 py-2.5 text-sm text-center block">
          లాగిన్ & ఎన్‌రోల్
        </a>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <p id="freemiumModalStatus" class="text-xs text-slate-500 mt-4 min-h-[1rem]" role="status"></p>
  </div>
</div>
