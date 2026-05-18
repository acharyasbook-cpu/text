<?php
$pricingApi = admin_url('pricing_api.php');
$adminPageTitle = 'Pricing & Course Configuration';
$adminPageSubtitle = 'సమయ ఆధారిత సబ్-కోర్స్ సబ్‌స్క్రిప్షన్ — 6 నెలలు · 1 సంవత్సరం · పరీక్ష వరకు';
require __DIR__ . '/../partials/page_header.php';
?>
<div id="pricingHub" class="max-w-[96rem] mx-auto pb-10" data-api="<?= admin_e($pricingApi) ?>">

  <section class="admin-card p-5 mb-6 border-l-4 border-l-indigo-500">
    <h2 class="text-sm font-bold text-slate-900 font-telugu">ప్రైసింగ్ vs ప్రిపరేషన్ షెడ్యూల్</h2>
    <p class="text-sm text-slate-600 mt-2 font-telugu leading-relaxed max-w-3xl">
      <strong>షార్ట్-టర్మ్ / లాంగ్-టర్మ్</strong> విద్యార్థి సిద్ధతా వేగం మాత్రమే — రోజువారీ అపరిమిత ప్రాక్టీస్ కోసం.
      ధరలు కింది <strong>సమయ చెల్లుబాటు</strong> ప్యాకేజీలపై మాత్రమే వర్తిస్తాయి (₹ ఆఫర్ + మూల ధర స్ట్రైక్‌త్రూ).
    </p>
    <a href="<?= admin_e(admin_dashboard_url(['view' => 'schedule'])) ?>" class="inline-flex mt-3 admin-btn admin-btn-secondary text-xs font-telugu">షెడ్యూల్ టెస్ట్ సెట్టింగ్ →</a>
  </section>

  <p id="pricingMigrateHint" class="hidden admin-alert-error mb-4 font-telugu text-sm">
    ప్లాన్ టేబుల్ కోసం: <code>php database/migrate_four_tier.php</code> మరియు <code>php database/migrate_subscription_pricing.php</code>
  </p>

  <section class="admin-card overflow-hidden mb-6">
    <div class="admin-card-header">
      <h2 class="text-sm font-bold text-slate-900 font-telugu">సబ్-కోర్స్ సబ్‌స్క్రిప్షన్ ప్రైసింగ్</h2>
      <p class="text-xs text-slate-500 mt-0.5 font-telugu">ప్రతి ప్రోగ్రామ్‌కు 3 ప్యాకేజీలు — ఆఫర్ ధర · మూల ధర (స్ట్రైక్‌త్రూ) · వ్యవధి · లైవ్</p>
    </div>
    <div class="admin-table-wrap">
      <table class="admin-table admin-subscription-matrix" id="pricingMatrixTable">
        <thead>
          <tr class="bg-slate-50">
            <th rowspan="2" class="font-telugu align-bottom">మెయిన్ కోర్స్</th>
            <th rowspan="2" class="font-telugu align-bottom">సబ్-కోర్స్</th>
            <th colspan="4" class="text-center font-telugu border-l border-slate-200">6 నెలల ప్లాన్</th>
            <th colspan="4" class="text-center font-telugu border-l border-slate-200">1 సంవత్సర ప్లాన్</th>
            <th colspan="4" class="text-center font-telugu border-l border-slate-200">పరీక్ష వరకు</th>
          </tr>
          <tr class="text-[10px] bg-slate-50/80">
            <th class="border-l border-slate-200">ఆఫర్ ₹</th><th>మూల ₹</th><th>నెలలు</th><th>లైవ్</th>
            <th class="border-l border-slate-200">ఆఫర్ ₹</th><th>మూల ₹</th><th>నెలలు</th><th>లైవ్</th>
            <th class="border-l border-slate-200">ఆఫర్ ₹</th><th>మూల ₹</th><th>నెలలు</th><th>లైవ్</th>
          </tr>
        </thead>
        <tbody id="pricingMatrixBody" class="bg-white">
          <tr><td colspan="14" class="px-4 py-12 text-center text-slate-500 font-telugu bg-white">లోడ్ అవుతోంది…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="px-5 py-4 border-t border-slate-100 flex flex-wrap gap-3 items-center bg-white">
      <button type="button" id="pricingSaveBtn" class="admin-btn admin-btn-primary font-telugu px-6">మొత్తం ప్రైసింగ్ సేవ్</button>
      <p id="pricingSaveMsg" class="text-xs text-slate-600 min-h-[1rem] font-telugu"></p>
    </div>
  </section>

  <?php require __DIR__ . '/../partials/coupon_admin_panel.php'; ?>
</div>

<script>
(function () {
  var root = document.getElementById('pricingHub');
  if (!root) return;
  var api = root.dataset.api;
  var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  var body = document.getElementById('pricingMatrixBody');
  var msg = document.getElementById('pricingSaveMsg');
  var PLAN_ORDER = ['6_months', '1_year', 'until_exam'];

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function planCells(plan, scId) {
    if (!plan) {
      return '<td colspan="4" class="text-slate-300 text-xs border-l border-slate-100 bg-white">—</td>';
    }
    var pid = plan.plan_id || 0;
    var code = plan.plan_code || '';
    var months = plan.duration_months != null ? plan.duration_months : '';
    return '<td class="border-l border-slate-100 bg-white">' +
      '<input type="number" min="0" step="1" class="admin-input w-20 text-sm offer-inp" data-plan-id="' + pid + '" data-sc="' + scId + '" data-code="' + code + '" value="' + (plan.price_inr || 0) + '" />' +
      '</td><td class="bg-white">' +
      '<input type="number" min="0" step="1" class="admin-input w-20 text-sm orig-inp" data-plan-id="' + pid + '" data-sc="' + scId + '" data-code="' + code + '" value="' + (plan.original_price_inr || 0) + '" />' +
      '</td><td class="bg-white">' +
      '<input type="number" min="0" step="1" class="admin-input w-14 text-sm months-inp" data-plan-id="' + pid + '" data-sc="' + scId + '" data-code="' + code + '" value="' + months + '" placeholder="—" />' +
      '</td><td class="bg-white text-center">' +
      '<input type="checkbox" class="active-inp rounded border-slate-300" data-plan-id="' + pid + '" data-sc="' + scId + '" data-code="' + code + '"' + (plan.is_active ? ' checked' : '') + ' />' +
      '</td>';
  }

  function render(rows) {
    if (!rows.length) {
      body.innerHTML = '<tr><td colspan="14" class="px-4 py-12 text-center text-slate-500 font-telugu bg-white">సబ్-కోర్స్ డేటా లేదు</td></tr>';
      return;
    }
    body.innerHTML = rows.map(function (r) {
      var course = r.course_name_te || r.course_name;
      var sub = r.sub_course_name_te || r.sub_course_name;
      var plans = r.plans || {};
      var cells = PLAN_ORDER.map(function (code) { return planCells(plans[code], r.sub_course_id); }).join('');
      return '<tr data-sc="' + r.sub_course_id + '" class="hover:bg-slate-50/50">' +
        '<td class="font-telugu text-slate-700 text-sm bg-white">' + esc(course) + '</td>' +
        '<td class="font-semibold text-slate-900 font-telugu text-sm bg-white">' + esc(sub) + '</td>' +
        cells + '</tr>';
    }).join('');
  }

  function collectRows() {
    var out = [];
    body.querySelectorAll('tr[data-sc]').forEach(function (tr) {
      var scId = parseInt(tr.getAttribute('data-sc'), 10);
      tr.querySelectorAll('.offer-inp').forEach(function (offerInp) {
        var code = offerInp.getAttribute('data-code');
        var planId = parseInt(offerInp.getAttribute('data-plan-id'), 10) || 0;
        var origInp = tr.querySelector('.orig-inp[data-code="' + code + '"]');
        var monthsInp = tr.querySelector('.months-inp[data-code="' + code + '"]');
        var activeInp = tr.querySelector('.active-inp[data-code="' + code + '"]');
        var monthsVal = monthsInp && monthsInp.value !== '' ? parseInt(monthsInp.value, 10) : null;
        out.push({
          plan_id: planId,
          sub_course_id: scId,
          plan_code: code,
          price_inr: parseFloat(offerInp.value) || 0,
          original_price_inr: origInp ? parseFloat(origInp.value) || 0 : 0,
          duration_months: monthsVal,
          is_active: activeInp && activeInp.checked ? 1 : 0,
        });
      });
    });
    return out;
  }

  fetch(api + '?action=subscription_matrix', { credentials: 'same-origin' })
    .then(function (r) { return r.json(); })
    .then(function (d) {
      if (!d.ok) throw new Error(d.error || 'Load failed');
      if (!d.tables_ready) document.getElementById('pricingMigrateHint')?.classList.remove('hidden');
      render(d.data || []);
    })
    .catch(function (e) {
      body.innerHTML = '<tr><td colspan="14" class="text-red-600 px-4 py-4 bg-white">' + esc(e.message) + '</td></tr>';
    });

  document.getElementById('pricingSaveBtn').addEventListener('click', function () {
    msg.textContent = 'సేవ్…';
    fetch(api, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ action: 'save_subscriptions', _csrf: csrf, rows: collectRows() }),
    })
      .then(function (r) { return r.json(); })
      .then(function (d) {
        if (!d.ok) throw new Error(d.error);
        msg.textContent = '✓ సబ్‌స్క్రిప్షన్ ప్రైసింగ్ సేవ్ అయింది';
        return fetch(api + '?action=subscription_matrix', { credentials: 'same-origin' }).then(function (r) { return r.json(); });
      })
      .then(function (d) { if (d && d.ok) render(d.data || []); })
      .catch(function (e) { msg.textContent = e.message; });
  });
})();
</script>
