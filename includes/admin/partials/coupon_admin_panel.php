<?php
/** @var string $pricingApi */
?>
<div id="couponAdminHub" class="coupon-admin-hub" data-api="<?= admin_e($pricingApi) ?>">
  <p id="couponMigrateHint" class="hidden admin-alert-error mb-4 font-telugu text-sm">
    కూపన్ టేబుల్ / లాగ్‌ల కోసం: <code>php database/migrate_st_coupons.php</code>
  </p>

  <section class="admin-card overflow-hidden mb-6" id="couponAdminWrap">
    <div class="admin-card-header">
      <h2 class="text-sm font-bold text-slate-900 font-telugu">కూపన్ అనలిటిక్స్ &amp; ట్రాకింగ్</h2>
      <p class="text-xs text-slate-500 mt-0.5 font-telugu">ప్రోమోటర్ · సబ్-కోర్స్ లక్ష్యం · వాడుక లాగ్‌లు · రెవెన్యూ</p>
    </div>
    <div class="admin-table-wrap overflow-x-auto">
      <table class="admin-table min-w-[72rem]" id="couponAnalyticsTable">
        <thead>
          <tr class="bg-slate-50 text-left text-[11px]">
            <th class="font-telugu px-3 py-2 whitespace-nowrap">కోడ్</th>
            <th class="font-telugu px-3 py-2 whitespace-nowrap">ఇచ్చిన వారు (ప్రోమోటర్)</th>
            <th class="font-telugu px-3 py-2 whitespace-nowrap">లక్ష్య సబ్-కోర్స్</th>
            <th class="font-telugu px-3 py-2 whitespace-nowrap">తగ్గింపు</th>
            <th class="font-telugu px-3 py-2 whitespace-nowrap">మొత్తం వాడుక (లాగ్‌లు)</th>
            <th class="font-telugu px-3 py-2 whitespace-nowrap">విద్యార్థులు</th>
            <th class="font-telugu px-3 py-2 whitespace-nowrap">రెవెన్యూ (₹)</th>
            <th class="font-telugu px-3 py-2 whitespace-nowrap">గడువు / స్థితి</th>
            <th class="px-3 py-2 text-right font-telugu whitespace-nowrap">చర్యలు</th>
          </tr>
        </thead>
        <tbody id="couponTableBody" class="bg-white">
          <tr><td colspan="9" class="px-4 py-8 text-center text-slate-500 font-telugu">లోడ్ అవుతోంది…</td></tr>
        </tbody>
      </table>
    </div>

    <div class="px-5 py-5 border-t border-slate-100 bg-slate-50/80 space-y-3">
      <h3 class="text-xs font-bold text-slate-800 font-telugu" id="couponFormTitle">కొత్త కూపన్</h3>
      <input type="hidden" id="couponEditId" value="" />
      <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
        <label class="block text-xs font-telugu text-slate-600">కూపన్ కోడ్
          <input type="text" id="couponInpCode" class="admin-input w-full mt-1 font-mono uppercase" maxlength="64" />
        </label>
        <label class="block text-xs font-telugu text-slate-600">ప్రోమోటర్ / ఇచ్చిన వారు (Issued To)
          <input type="text" id="couponInpPromoter" class="admin-input w-full mt-1" maxlength="128" placeholder="Ramu" />
        </label>
        <label class="block text-xs font-telugu text-slate-600">లక్ష్య సబ్-కోర్స్
          <select id="couponInpSubCourse" class="admin-input w-full mt-1">
            <option value="">అన్ని సబ్-కోర్సులు</option>
          </select>
        </label>
        <label class="block text-xs font-telugu text-slate-600">తగ్గింపు రకం
          <select id="couponInpType" class="admin-input w-full mt-1">
            <option value="percentage">శాతం (%)</option>
            <option value="fixed_amount">స్థిర ₹</option>
          </select>
        </label>
        <label class="block text-xs font-telugu text-slate-600">విలువ
          <input type="number" id="couponInpValue" class="admin-input w-full mt-1" min="0" step="0.01" />
        </label>
        <label class="block text-xs font-telugu text-slate-600">గడువు తేదీ (ఐచ్ఛికం)
          <input type="date" id="couponInpExpiry" class="admin-input w-full mt-1" />
        </label>
        <label class="block text-xs font-telugu text-slate-600">మొత్తం వాడుక పరిమితి (ఖాళీ = అపరిమితం)
          <input type="number" id="couponInpLimit" class="admin-input w-full mt-1" min="1" step="1" placeholder="—" />
        </label>
      </div>
      <label class="inline-flex items-center gap-2 text-xs font-telugu text-slate-700 cursor-pointer">
        <input type="checkbox" id="couponInpActive" class="rounded border-slate-300" checked />
        సక్రియం (లైవ్)
      </label>
      <div class="flex flex-wrap gap-2 items-center">
        <button type="button" id="couponSaveBtn" class="admin-btn admin-btn-primary text-xs font-telugu px-5">సేవ్</button>
        <button type="button" id="couponResetBtn" class="admin-btn admin-btn-secondary text-xs font-telugu px-4">రీసెట్</button>
        <p id="couponFormMsg" class="text-xs text-slate-600 font-telugu min-h-[1rem]"></p>
      </div>
    </div>
  </section>
</div>

<script>
(function () {
  var root = document.getElementById('couponAdminHub');
  if (!root) return;
  var api = root.dataset.api;
  var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  var tbody = document.getElementById('couponTableBody');
  var formMsg = document.getElementById('couponFormMsg');
  var subSel = document.getElementById('couponInpSubCourse');
  if (!tbody || !subSel) return;

  function esc(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function fmtMoney(n) {
    var x = parseFloat(n) || 0;
    return x.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
  }

  function todayStr() {
    var t = new Date();
    var m = ('0' + (t.getMonth() + 1)).slice(-2);
    var d = ('0' + t.getDate()).slice(-2);
    return t.getFullYear() + '-' + m + '-' + d;
  }

  function couponStatus(c) {
    var t = todayStr();
    var active = parseInt(c.is_active, 10) === 1;
    var exp = c.expiry_date ? String(c.expiry_date).slice(0, 10) : '';
    var lim = c.usage_limit != null && c.usage_limit !== '' ? parseInt(c.usage_limit, 10) : null;
    var used = parseInt(c.used_count, 10) || 0;
    if (!active) return '<span class="text-slate-500 font-telugu text-xs">నిష్క్రియం</span>';
    if (exp && exp < t) return '<span class="text-amber-700 font-telugu text-xs">గడువు ముగిసింది</span>';
    if (lim != null && lim > 0 && used >= lim) return '<span class="text-red-700 font-telugu text-xs">పరిమితి నిండింది</span>';
    return '<span class="text-emerald-700 font-telugu text-xs">సక్రియం</span>';
  }

  function discountLabel(c) {
    return c.discount_type === 'fixed_amount'
      ? '<span class="font-telugu text-xs">స్థిర</span> ₹' + esc(String(c.discount_value))
      : esc(String(c.discount_value)) + '% <span class="font-telugu text-[10px] text-slate-500">శాతం</span>';
  }

  function targetSubLabel(c) {
    if (!c.applicable_sub_course_id) {
      return '<span class="text-slate-500 font-telugu text-xs">అన్ని సబ్-కోర్సులు</span>';
    }
    var main = c.main_course_name_te || c.main_course_name || '';
    var sub = c.sub_course_name_te || c.sub_course_name || ('#' + c.applicable_sub_course_id);
    var line = (main ? esc(main) + ' — ' : '') + esc(sub);
    return '<span class="text-sm font-telugu">' + line + '</span>';
  }

  function fillSubCourseOptions(rows) {
    var keep = '<option value="">అన్ని సబ్-కోర్సులు</option>';
    subSel.innerHTML = keep + (rows || []).map(function (r) {
      var main = r.course_name_te || r.course_name || '';
      var sub = r.name_te || r.name || ('#' + r.id);
      var lab = (main ? main + ' — ' : '') + sub;
      return '<option value="' + r.id + '">' + esc(lab) + '</option>';
    }).join('');
  }

  function resetForm() {
    document.getElementById('couponEditId').value = '';
    document.getElementById('couponFormTitle').textContent = 'కొత్త కూపన్';
    document.getElementById('couponInpCode').value = '';
    document.getElementById('couponInpPromoter').value = '';
    document.getElementById('couponInpSubCourse').value = '';
    document.getElementById('couponInpType').value = 'percentage';
    document.getElementById('couponInpValue').value = '';
    document.getElementById('couponInpExpiry').value = '';
    document.getElementById('couponInpLimit').value = '';
    document.getElementById('couponInpActive').checked = true;
    if (formMsg) formMsg.textContent = '';
  }

  function fillForm(c) {
    document.getElementById('couponEditId').value = c.id || '';
    document.getElementById('couponFormTitle').textContent = 'కూపన్ సవరణ #' + c.id;
    document.getElementById('couponInpCode').value = c.coupon_code || '';
    document.getElementById('couponInpPromoter').value = c.promoter_name || '';
    document.getElementById('couponInpSubCourse').value = c.applicable_sub_course_id ? String(c.applicable_sub_course_id) : '';
    document.getElementById('couponInpType').value = c.discount_type === 'fixed_amount' ? 'fixed_amount' : 'percentage';
    document.getElementById('couponInpValue').value = c.discount_value != null ? c.discount_value : '';
    var ex = c.expiry_date ? String(c.expiry_date).slice(0, 10) : '';
    document.getElementById('couponInpExpiry').value = ex;
    document.getElementById('couponInpLimit').value = c.usage_limit != null && c.usage_limit !== '' ? c.usage_limit : '';
    document.getElementById('couponInpActive').checked = parseInt(c.is_active, 10) === 1;
    if (formMsg) formMsg.textContent = '';
    var wrap = document.getElementById('couponAdminWrap');
    if (wrap) window.scrollTo({ top: wrap.offsetTop - 24, behavior: 'smooth' });
  }

  function renderCoupons(rows) {
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="9" class="px-4 py-8 text-center text-slate-500 font-telugu bg-white">కూపన్‌లు లేవు — కింద ఫారమ్‌తో చేర్చండి</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(function (c) {
      var logs = parseInt(c.redemption_count, 10) || 0;
      var studs = parseInt(c.enrolled_student_count, 10) || 0;
      var rev = parseFloat(c.total_revenue_from_logs) || 0;
      var names = c.enrolled_student_names ? String(c.enrolled_student_names) : '';
      var namesShort = names.length > 120 ? names.slice(0, 120) + '…' : names;
      var expLine = c.expiry_date
        ? '<span class="text-slate-600">' + esc(String(c.expiry_date).slice(0, 10)) + '</span>'
        : '<span class="text-slate-400 font-telugu text-[10px]">గడువు లేదు</span>';
      return '<tr class="hover:bg-slate-50/50 align-top" data-coupon-id="' + esc(String(c.id)) + '">' +
        '<td class="px-3 py-2 font-mono text-sm font-semibold whitespace-nowrap">' + esc(c.coupon_code) + '</td>' +
        '<td class="px-3 py-2 text-sm font-telugu">' + (c.promoter_name ? esc(c.promoter_name) : '<span class="text-slate-400">—</span>') + '</td>' +
        '<td class="px-3 py-2">' + targetSubLabel(c) + '</td>' +
        '<td class="px-3 py-2">' + discountLabel(c) + '</td>' +
        '<td class="px-3 py-2 text-sm tabular-nums">' + esc(String(logs)) + '</td>' +
        '<td class="px-3 py-2 text-sm">' +
          '<span class="font-semibold tabular-nums">' + esc(String(studs)) + '</span>' +
          (namesShort ? '<div class="text-[10px] text-slate-500 mt-1 max-w-[14rem] leading-snug" title="' + esc(names) + '">' + esc(namesShort) + '</div>' : '') +
        '</td>' +
        '<td class="px-3 py-2 text-sm tabular-nums font-medium">₹' + esc(fmtMoney(rev)) + '</td>' +
        '<td class="px-3 py-2 text-xs space-y-1">' + expLine + '<div>' + couponStatus(c) + '</div></td>' +
        '<td class="px-3 py-2 text-right whitespace-nowrap">' +
        '<button type="button" class="coupon-edit text-indigo-700 text-xs font-telugu underline mr-2" data-id="' + esc(String(c.id)) + '">మార్చు</button>' +
        '<button type="button" class="coupon-del text-red-700 text-xs font-telugu underline" data-id="' + esc(String(c.id)) + '">తొలగించు</button>' +
        '</td></tr>';
    }).join('');

    tbody.querySelectorAll('.coupon-edit').forEach(function (b) {
      b.addEventListener('click', function () {
        var id = b.getAttribute('data-id');
        var row = rows.find(function (x) { return String(x.id) === String(id); });
        if (row) fillForm(row);
      });
    });
    tbody.querySelectorAll('.coupon-del').forEach(function (b) {
      b.addEventListener('click', function () {
        if (!confirm('ఈ కూపన్ తొలగించాలా?')) return;
        fetch(api, {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
          body: JSON.stringify({ action: 'coupon_delete', _csrf: csrf, id: parseInt(b.getAttribute('data-id'), 10) }),
        })
          .then(function (r) {
            if (!r.ok) throw new Error('సర్వర్ స్పందన లేదు (HTTP ' + r.status + ')');
            return r.json();
          })
          .then(function (d) {
            if (!d.ok) throw new Error(d.error);
            return loadCoupons();
          })
          .catch(function (e) { alert(e.message); });
      });
    });
  }

  function loadCoupons() {
    return fetch(api + '?action=coupons_list', { credentials: 'same-origin' })
      .then(function (r) {
        if (!r.ok) throw new Error('సర్వర్ స్పందన లేదు (HTTP ' + r.status + ')');
        return r.json();
      })
      .then(function (d) {
        if (!d.ok) throw new Error(d.error || 'Load failed');
        if (!d.table_ready) document.getElementById('couponMigrateHint')?.classList.remove('hidden');
        if (!d.usage_logs_ready) document.getElementById('couponMigrateHint')?.classList.remove('hidden');
        fillSubCourseOptions(d.sub_courses || []);
        renderCoupons(d.coupons || []);
      });
  }

  document.getElementById('couponResetBtn').addEventListener('click', resetForm);

  document.getElementById('couponSaveBtn').addEventListener('click', function () {
    if (formMsg) formMsg.textContent = 'సేవ్…';
    var editId = document.getElementById('couponEditId').value;
    var coupon = {
      coupon_code: document.getElementById('couponInpCode').value,
      promoter_name: document.getElementById('couponInpPromoter').value,
      applicable_sub_course_id: document.getElementById('couponInpSubCourse').value,
      discount_type: document.getElementById('couponInpType').value,
      discount_value: document.getElementById('couponInpValue').value,
      expiry_date: document.getElementById('couponInpExpiry').value,
      usage_limit: document.getElementById('couponInpLimit').value,
      is_active: document.getElementById('couponInpActive').checked ? 1 : 0,
    };
    if (editId) coupon.id = parseInt(editId, 10);
    fetch(api, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify({ action: 'coupon_save', _csrf: csrf, coupon: coupon }),
    })
      .then(function (r) {
        if (!r.ok) throw new Error('సర్వర్ స్పందన లేదు (HTTP ' + r.status + ')');
        return r.json();
      })
      .then(function (d) {
        if (!d.ok) throw new Error(d.error);
        if (formMsg) formMsg.textContent = '✓ సేవ్ అయింది';
        resetForm();
        return loadCoupons();
      })
      .catch(function (e) { if (formMsg) formMsg.textContent = e.message; });
  });

  loadCoupons().catch(function (e) {
    var msg = e && e.message ? e.message : 'నెట్‌వర్క్ / డేటాబేస్ కనెక్షన్ విఫలమైంది. పేజీని రిఫ్రెష్ చేయండి.';
    tbody.innerHTML = '<tr><td colspan="9" class="text-red-600 px-4 py-4 bg-white font-telugu">' + esc(msg) + '</td></tr>';
  });
})();
</script>
