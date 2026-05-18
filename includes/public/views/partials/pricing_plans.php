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
$couponApi = base_url('coupon_api.php');
$couponTableReady = CouponRepository::tableReady();
?>
<?php if ($hasAccess): ?>
<div class="programme-access-banner font-telugu mb-8">
  <p class="font-bold text-emerald-900">మీకు ఈ ప్రోగ్రామ్‌కు సక్రియ యాక్సెస్ ఉంది — అన్ని విషయాలు & టెస్టులు అన్‌లాక్.</p>
</div>
<?php elseif ($plans): ?>
<section
  class="programme-pricing mb-10"
  id="enrol"
  data-coupon-api="<?= e($couponApi) ?>"
  data-csrf="<?= e(csrf_token()) ?>"
>
  <header class="mb-5">
    <h2 class="font-telugu text-xl font-bold text-slate-900">చందా & ఎన్‌రోల్‌మెంట్</h2>
    <p class="font-telugu text-sm text-slate-600 mt-1">మీ సిద్ధతకు సరిపడా ప్లాన్ ఎంచుకోండి — తక్షణ యాక్సెస్</p>
  </header>
  <div class="grid sm:grid-cols-3 gap-4 lg:gap-5">
    <?php foreach ($plans as $pl):
        $code = (string) ($pl['plan_code'] ?? '');
        $meta = $planUi[$code] ?? ['short' => (string) ($pl['label'] ?? 'Plan'), 'hint_te' => '', 'hint' => ''];
        $planId = (int) ($pl['id'] ?? 0);
    ?>
    <article class="programme-plan-card" data-plan-id="<?= $planId ?>">
      <p class="font-telugu text-xs font-bold uppercase tracking-widest text-gold"><?= e($meta['short']) ?></p>
      <p class="font-semibold text-slate-900 mt-2"><?= e((string) ($pl['label'] ?? '')) ?></p>
      <p class="font-telugu text-xs text-slate-500 mt-1"><?= e($meta['hint_te']) ?></p>
      <?php
        $offer = (float) ($pl['price_inr'] ?? 0);
        $original = (float) ($pl['original_price_inr'] ?? 0);
        ?>
      <div
        class="plan-price-block mt-4"
        data-base-offer="<?= e((string) $offer) ?>"
        data-base-original="<?= e((string) $original) ?>"
      >
        <p class="flex flex-wrap items-baseline gap-2">
          <span class="plan-final-price text-3xl font-bold text-indigo-700">₹<?= number_format($offer, 0) ?></span>
          <span class="plan-strike-offer text-lg text-slate-400 line-through font-medium hidden" aria-hidden="true"></span>
          <?php if ($original > $offer): ?>
          <span class="plan-strike-catalog text-lg text-slate-400 line-through font-medium">₹<?= number_format($original, 0) ?></span>
          <?php endif; ?>
        </p>
        <p class="plan-coupon-discount mt-1 text-sm text-emerald-800 font-telugu hidden"></p>
      </div>
      <?php if ($user && $couponTableReady): ?>
      <div class="coupon-apply-row mt-4 pt-3 border-t border-slate-100">
        <label class="block font-telugu text-xs font-semibold text-slate-700 mb-1" for="coupon-inp-<?= $planId ?>">
          కూపన్ కోడ్ ఎంటర్ చేయండి (Enter Coupon Code)
        </label>
        <div class="flex gap-2">
          <input
            type="text"
            id="coupon-inp-<?= $planId ?>"
            class="coupon-code-input flex-1 min-w-0 rounded-lg border border-slate-200 px-2 py-1.5 text-sm font-mono uppercase"
            maxlength="64"
            autocomplete="off"
            placeholder="RAMU10"
          />
          <button type="button" class="coupon-apply-btn classical-btn-primary px-3 py-1.5 text-xs whitespace-nowrap">
            Apply
          </button>
        </div>
        <p class="coupon-inline-msg mt-2 text-xs font-telugu min-h-[1.25rem]"></p>
      </div>
      <?php endif; ?>
      <?php if ($user): ?>
      <form method="post" action="<?= e(base_url('checkout.php')) ?>" class="mt-5 checkout-plan-form">
        <?= csrf_field() ?>
        <input type="hidden" name="plan_id" value="<?= $planId ?>" />
        <input type="hidden" name="return" value="<?= e($checkoutReturn) ?>" />
        <input type="hidden" name="coupon_code" value="" class="checkout-coupon-hidden" />
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
<?php if ($user && $couponTableReady): ?>
<script>
(function () {
  var root = document.getElementById('enrol');
  if (!root || !root.dataset.couponApi) return;

  function fmt(n) {
    var x = Math.round(Number(n) || 0);
    return x.toLocaleString('en-IN');
  }

  function resetPriceBlock(card) {
    var blk = card.querySelector('.plan-price-block');
    if (!blk) return;
    var base = parseFloat(blk.getAttribute('data-base-offer')) || 0;
    var cat = parseFloat(blk.getAttribute('data-base-original')) || 0;
    var finalEl = card.querySelector('.plan-final-price');
    var strikeOffer = card.querySelector('.plan-strike-offer');
    var strikeCat = card.querySelector('.plan-strike-catalog');
    var discLine = card.querySelector('.plan-coupon-discount');
    if (finalEl) finalEl.textContent = '₹' + fmt(base);
    if (strikeOffer) {
      strikeOffer.textContent = '';
      strikeOffer.classList.add('hidden');
    }
    if (strikeCat) {
      strikeCat.textContent = cat > base ? '₹' + fmt(cat) : '';
      strikeCat.classList.toggle('hidden', !(cat > base));
    }
    if (discLine) {
      discLine.textContent = '';
      discLine.classList.add('hidden');
    }
    var hid = card.querySelector('.checkout-coupon-hidden');
    if (hid) hid.value = '';
  }

  function applyCouponSuccess(card, data) {
    var blk = card.querySelector('.plan-price-block');
    if (!blk) return;
    var base = parseFloat(blk.getAttribute('data-base-offer')) || 0;
    var cat = parseFloat(blk.getAttribute('data-base-original')) || 0;
    var finalEl = card.querySelector('.plan-final-price');
    var strikeOffer = card.querySelector('.plan-strike-offer');
    var strikeCat = card.querySelector('.plan-strike-catalog');
    var discLine = card.querySelector('.plan-coupon-discount');
    var fin = parseFloat(data.final_inr) || 0;
    var disc = parseFloat(data.discount_inr) || 0;
    if (finalEl) finalEl.textContent = '₹' + fmt(fin);
    if (strikeOffer) {
      strikeOffer.textContent = '₹' + fmt(base);
      strikeOffer.classList.remove('hidden');
    }
    if (strikeCat) {
      if (cat > base) {
        strikeCat.textContent = '₹' + fmt(cat);
        strikeCat.classList.remove('hidden');
      } else {
        strikeCat.classList.add('hidden');
      }
    }
    if (discLine) {
      discLine.textContent = 'కూపన్ తగ్గింపు: ₹' + fmt(disc);
      discLine.classList.remove('hidden');
    }
    var inp = card.querySelector('.coupon-code-input');
    var hid = card.querySelector('.checkout-coupon-hidden');
    if (hid && inp) hid.value = inp.value.trim();
  }

  root.querySelectorAll('.coupon-apply-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var card = btn.closest('[data-plan-id]');
      if (!card) return;
      var planId = card.getAttribute('data-plan-id');
      var inp = card.querySelector('.coupon-code-input');
      var msg = card.querySelector('.coupon-inline-msg');
      var code = inp ? inp.value.trim() : '';
      if (!planId || !code) {
        if (msg) {
          msg.className = 'coupon-inline-msg mt-2 text-xs font-telugu min-h-[1.25rem] text-amber-800';
          msg.textContent = 'కూపన్ కోడ్ నమోదు చేయండి.';
        }
        return;
      }
      if (msg) {
        msg.className = 'coupon-inline-msg mt-2 text-xs font-telugu min-h-[1.25rem] text-slate-500';
        msg.textContent = 'తనిఖీ చేస్తోంది…';
      }
      var fd = new FormData();
      fd.append('_csrf', root.dataset.csrf || '');
      fd.append('plan_id', planId);
      fd.append('coupon_code', code);
      fetch(root.dataset.couponApi, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (x) {
          var j = x.j || {};
          if (j.ok) {
            if (msg) {
              msg.className = 'coupon-inline-msg mt-2 text-xs font-telugu min-h-[1.25rem] text-emerald-800';
              msg.textContent = j.message_te || 'కూపన్ విజయవంతంగా అప్లై అయింది!';
            }
            applyCouponSuccess(card, j);
          } else {
            resetPriceBlock(card);
            if (msg) {
              msg.className = 'coupon-inline-msg mt-2 text-xs font-telugu min-h-[1.25rem] text-red-800';
              msg.textContent = j.error_te || 'ఈ కూపన్ చెల్లదు లేదా గడువు ముగిసింది.';
            }
          }
        })
        .catch(function () {
          resetPriceBlock(card);
          if (msg) {
            msg.className = 'coupon-inline-msg mt-2 text-xs font-telugu min-h-[1.25rem] text-red-800';
            msg.textContent = 'ఈ కూపన్ చెల్లదు లేదా గడువు ముగిసింది.';
          }
        });
    });
  });

  root.querySelectorAll('.coupon-code-input').forEach(function (inp) {
    inp.addEventListener('input', function () {
      var card = inp.closest('[data-plan-id]');
      if (!card) return;
      resetPriceBlock(card);
      var msg = card.querySelector('.coupon-inline-msg');
      if (msg) {
        msg.textContent = '';
        msg.className = 'coupon-inline-msg mt-2 text-xs font-telugu min-h-[1.25rem]';
      }
    });
  });
})();
</script>
<?php endif; ?>
<?php endif; ?>
