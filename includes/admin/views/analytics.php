<?php
$analyticsApi = admin_url('analytics_api.php');
$adminPageTitle = 'వినియోగదారు విశ్లేషణ & రెవెన్యూ';
$adminPageSubtitle = 'సమయ ఆధారిత సబ్‌స్క్రిప్షన్ · లాగిన్ లాగ్‌లు · WhatsApp సంప్రదింపు';
require __DIR__ . '/../partials/page_header.php';
?>
<div id="analyticsHub" class="max-w-6xl mx-auto pb-10 font-telugu" data-api="<?= admin_e($analyticsApi) ?>">

  <section id="anOverview" class="admin-metric-grid cols-4 mb-6">
    <div class="admin-card admin-metric">
      <p class="admin-metric-label font-telugu">మొత్తం వినియోగదారులు</p>
      <p class="admin-metric-value" data-k="total_students">—</p>
      <p class="text-xs text-slate-500 mt-2"><span data-k="paid_students">0</span> Paid · <span data-k="free_students">0</span> Free</p>
    </div>
    <div class="admin-card admin-metric">
      <p class="admin-metric-label font-telugu">మొత్తం రెవెన్యూ</p>
      <p class="admin-metric-value text-emerald-700" data-k="revenue_total_inr">—</p>
    </div>
    <div class="admin-card admin-metric">
      <p class="admin-metric-label font-telugu">పరీక్ష ప్రయత్నాలు</p>
      <p class="admin-metric-value text-indigo-600" data-k="total_exam_attempts">—</p>
    </div>
    <div class="admin-card admin-metric sm:col-span-2 xl:col-span-1">
      <p class="admin-metric-label font-telugu mb-2">రెవెన్యూ (ప్లాన్ వారీగా)</p>
      <div id="anRevenuePlans" class="grid grid-cols-3 gap-2 text-center text-xs"></div>
    </div>
  </section>

  <section class="admin-card p-5 mb-6">
    <h2 class="text-sm font-bold text-slate-900 font-telugu">కోర్స్ పాపులారిటీ ఇండెక్స్</h2>
    <div id="anPopularity" class="space-y-2 text-sm mt-3"></div>
  </section>

  <section class="admin-card p-5 mb-4">
    <div class="flex flex-wrap gap-3 items-end">
      <div class="flex-1 min-w-[200px]">
        <label class="admin-label block mb-1 font-telugu">గ్లోబల్ సెర్చ్</label>
        <input type="search" id="anSearch" placeholder="పేరు · ఫోన్ · ఇమెయిల్" class="admin-input" />
      </div>
      <div><label class="admin-label block mb-1">From</label><input type="date" id="anFrom" class="admin-input" /></div>
      <div><label class="admin-label block mb-1">To</label><input type="date" id="anTo" class="admin-input" /></div>
      <button type="button" id="anApply" class="admin-btn admin-btn-primary font-telugu">ఫిల్టర్</button>
      <button type="button" id="anExport" class="admin-btn admin-btn-secondary font-telugu">Export CSV</button>
    </div>
    <p id="anListMeta" class="text-xs text-slate-500 mt-3"></p>
  </section>

  <section class="admin-card overflow-hidden">
    <div class="admin-table-wrap">
      <table class="admin-table" id="anStudentTable">
        <thead>
          <tr class="bg-slate-50">
            <th class="font-telugu">విద్యార్థి</th>
            <th class="font-telugu">సంప్రదింపు</th>
            <th class="font-telugu">సక్రియ ప్లాన్</th>
            <th class="font-telugu">నమోదు / గడువు</th>
            <th class="font-telugu">చివరి లాగిన్</th>
            <th class="font-telugu text-right">పరీక్షలు</th>
            <th class="font-telugu text-center">డివైస్</th>
            <th class="font-telugu text-center">WhatsApp</th>
          </tr>
        </thead>
        <tbody id="anStudentBody" class="bg-white">
          <tr><td colspan="8" class="px-4 py-8 text-center text-slate-500 bg-white">లోడ్ అవుతోంది…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="flex justify-between items-center px-4 py-3 border-t border-slate-100">
      <button type="button" id="anPrev" class="admin-btn admin-btn-ghost text-sm" disabled>← మునుపటి</button>
      <span id="anPageLabel" class="text-xs text-slate-500"></span>
      <button type="button" id="anNext" class="admin-btn admin-btn-ghost text-sm text-indigo-600" disabled>తదుపరి →</button>
    </div>
  </section>
</div>

<div id="anModal" class="admin-modal-backdrop" aria-hidden="true">
  <article class="admin-modal" role="dialog">
    <header class="flex items-start justify-between gap-4 px-6 py-4 border-b border-slate-100">
      <div>
        <h2 id="anModalTitle" class="text-lg font-bold text-slate-900 font-telugu">విద్యార్థి ప్రొఫైల్</h2>
        <p id="anModalSub" class="text-xs text-slate-500 mt-0.5"></p>
      </div>
      <button type="button" id="anModalClose" class="text-slate-500 hover:text-slate-900 text-2xl leading-none" aria-label="Close">&times;</button>
    </header>
    <div id="anModalBody" class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto text-sm"></div>
  </article>
</div>

<script>
(function () {
  const root = document.getElementById('analyticsHub');
  if (!root) return;
  const API = root.dataset.api;
  const $ = (id) => document.getElementById(id);
  let page = 1, total = 0, debounceTimer = null;
  const limit = 50;

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function fmtDate(s) {
    if (!s) return '—';
    try { return new Date(String(s).replace(' ', 'T')).toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' }); }
    catch { return s; }
  }

  function inr(n) { return '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 }); }

  function waUrl(phone, name, tpl) {
    let digits = String(phone || '').replace(/\D+/g, '');
    if (!digits) return '';
    if (digits.length === 10) digits = '91' + digits;
    const n = (name || '').trim() || 'విద్యార్థి';
    const texts = {
      welcome: 'హలో ' + n + ', ఆచార్య బుక్స్ ప్లాట్‌ఫామ్‌కు స్వాగతం! మీ అభ్యాస ప్రయాణానికి మేము సహాయం చేస్తాము.',
      reminder: 'హలో ' + n + ', ఆచార్య బుక్స్ నుండి — దయచేసి ఈ రోజు మీ షెడ్యూల్ టెస్ట్ పూర్తి చేయండి.',
    };
    const text = texts[tpl || 'welcome'] || texts.welcome;
    return 'https://wa.me/' + digits + '?text=' + encodeURIComponent(text);
  }

  function deviceBadge(count) {
    const n = parseInt(count, 10) || 1;
    if (n > 1) return '<span class="admin-badge admin-badge-amber" title="Multi-device">' + n + ' devices</span>';
    return '<span class="admin-badge admin-badge-slate">Single</span>';
  }

  function planLabel(code, label) {
    const map = { '6_months': '6 నెలలు', '1_year': '1 సంవత్సరం', 'until_exam': 'పరీక్ష వరకు' };
    if (label) return esc(label);
    return esc(map[code] || code || '—');
  }

  function qs(extra) {
    const p = new URLSearchParams();
    const q = $('anSearch').value.trim(), from = $('anFrom').value, to = $('anTo').value;
    if (q) p.set('q', q);
    if (from) p.set('from', from);
    if (to) p.set('to', to);
    if (extra) Object.entries(extra).forEach(([k, v]) => p.set(k, v));
    return p.toString();
  }

  async function loadOverview() {
    const res = await fetch(API + '?' + qs({ action: 'overview' }));
    const json = await res.json();
    if (!json.ok) return;
    const d = json.data;
    root.querySelectorAll('[data-k]').forEach(el => {
      const k = el.dataset.k;
      if (k === 'revenue_total_inr') el.textContent = inr(d.revenue_total_inr);
      else if (d[k] != null) el.textContent = Number(d[k]).toLocaleString('en-IN');
    });
    $('anRevenuePlans').innerHTML = (d.revenue_by_plan || []).map(p =>
      `<div class="rounded-lg bg-slate-50 border border-slate-100 p-2">
        <p class="font-semibold text-[10px] leading-tight">${esc(p.label)}</p>
        <p class="text-emerald-700 font-bold mt-1">${inr(p.amount_inr)}</p>
      </div>`).join('');

    const rows = d.course_popularity || [];
    const max = Math.max(...rows.map(r => Number(r.enrollments) || 0), 1);
    $('anPopularity').innerHTML = rows.length ? rows.map((r, i) => {
      const pct = Math.round((Number(r.enrollments) / max) * 100);
      const name = r.name_te || r.name || r.course_name;
      return `<div class="flex items-center gap-3">
        <span class="w-6 text-xs font-bold text-slate-400">${i + 1}</span>
        <div class="flex-1"><p class="font-semibold truncate">${esc(name)}</p>
        <div class="h-2 bg-slate-200 rounded-full mt-1 overflow-hidden"><span class="block h-full bg-indigo-600 rounded-full" style="width:${pct}%"></span></div></div>
        <span class="text-xs font-bold tabular-nums">${r.enrollments}</span></div>`;
    }).join('') : '<p class="text-slate-500 text-xs">ఇంకా డేటా లేదు</p>';
  }

  async function loadStudents() {
    $('anStudentBody').innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-500 bg-white">లోడ్…</td></tr>';
    const res = await fetch(API + '?' + qs({ action: 'students', page: String(page), limit: String(limit) }));
    const json = await res.json();
    if (!json.ok) {
      $('anStudentBody').innerHTML = '<tr><td colspan="8" class="text-red-600 px-4 bg-white">' + esc(json.error) + '</td></tr>';
      return;
    }
    total = json.meta?.total || 0;
    const rows = json.data || [];
    $('anListMeta').textContent = total + ' విద్యార్థులు · పేజీ ' + page;
    $('anPageLabel').textContent = 'Page ' + page;
    $('anPrev').disabled = page <= 1;
    $('anNext').disabled = page * limit >= total;

    if (!rows.length) {
      $('anStudentBody').innerHTML = '<tr><td colspan="8" class="px-4 py-8 text-center text-slate-500 bg-white">ఫలితాలు లేవు</td></tr>';
      return;
    }

    $('anStudentBody').innerHTML = rows.map(r => {
      const wa = waUrl(r.phone, r.name, 'welcome');
      const waCell = wa
        ? `<a href="${esc(wa)}" target="_blank" rel="noopener" class="admin-wa-link" onclick="event.stopPropagation()" title="WhatsApp">💬</a>`
        : '<span class="text-slate-300 text-xs">—</span>';
      const plan = r.active_plan_code
        ? `<span class="admin-badge admin-badge-indigo font-telugu text-[10px]">${planLabel(r.active_plan_code, r.active_plan_label)}</span>`
        : '<span class="text-slate-400 text-xs font-telugu">ఉచితం</span>';
      return `<tr class="is-clickable bg-white hover:bg-slate-50/80" data-id="${r.id}">
        <td class="font-semibold text-slate-900 bg-white">${esc(r.name)}</td>
        <td class="text-slate-600 bg-white"><div class="text-sm">${esc(r.email)}</div><div class="text-xs">${esc(r.phone || '—')}</div></td>
        <td class="bg-white">${plan}</td>
        <td class="text-xs text-slate-500 bg-white"><div>${fmtDate(r.subscription_started_at || r.created_at)}</div><div class="text-[10px] text-amber-700 font-telugu">గడువు: ${fmtDate(r.subscription_expires_at)}</div></td>
        <td class="text-xs text-slate-500 bg-white">${fmtDate(r.last_login_at || r.last_active_at)}</td>
        <td class="text-right font-semibold tabular-nums bg-white">${r.exams_taken ?? 0}</td>
        <td class="text-center bg-white">${deviceBadge(r.device_count)}</td>
        <td class="text-center bg-white">${waCell}</td>
      </tr>`;
    }).join('');
  }

  async function openProfile(id) {
    const modal = $('anModal');
    modal.classList.add('is-open');
    $('anModalBody').innerHTML = '<p class="text-slate-500">లోడ్…</p>';
    const res = await fetch(API + '?action=student_profile&id=' + encodeURIComponent(id));
    const json = await res.json();
    if (!json.ok) {
      $('anModalBody').innerHTML = '<p class="text-red-600">' + esc(json.error) + '</p>';
      return;
    }
    const p = json.data, u = p.user;
    $('anModalTitle').textContent = u.name;
    $('anModalSub').textContent = u.email + (u.phone ? ' · ' + u.phone : '');
    const waW = waUrl(u.phone, u.name, 'welcome');
    const waR = waUrl(u.phone, u.name, 'reminder');
    const waBlock = (waW || waR) ? `<div class="flex flex-wrap gap-2 mb-4">
      ${waW ? `<a href="${esc(waW)}" target="_blank" rel="noopener" class="admin-wa-link">💬 స్వాగత సందేశం</a>` : ''}
      ${waR ? `<a href="${esc(waR)}" target="_blank" rel="noopener" class="admin-wa-link">📋 రిమైండర్</a>` : ''}
    </div>` : '';

    const subs = (p.subscriptions || []).map(s =>
      `<li class="admin-card p-3 mb-2 text-xs"><strong>${esc(s.sub_course_name || s.package_name)}</strong> · ${esc(s.plan_display || s.status)}</li>`
    ).join('') || '<li class="text-slate-400 text-xs">సక్రియ సబ్‌స్క్రిప్షన్‌లు లేవు</li>';

    $('anModalBody').innerHTML = waBlock + `
      <section class="grid sm:grid-cols-2 gap-4">
        <div class="admin-card p-4"><h3 class="text-xs font-bold uppercase text-slate-500 mb-2">లాగిన్ & యాక్టివిటీ</h3>
          <p class="text-xs">నమోదు: ${fmtDate(u.created_at)}</p>
          <p class="text-xs mt-1">చివరి లాగిన్: ${fmtDate(u.last_login_at || u.last_active_at)}</p>
        </div>
        <div class="admin-card p-4"><h3 class="text-xs font-bold uppercase text-slate-500 mb-2">పరీక్షలు</h3>
          <p class="text-2xl font-bold">${p.performance?.total_attempts ?? 0}</p>
          <p class="text-xs text-slate-500">సగటు ${p.performance?.avg_percent ?? 0}%</p>
        </div>
      </section>
      <section><h3 class="text-xs font-bold uppercase text-slate-500 mb-2">సబ్-కోర్స్ ఎన్‌రోల్‌మెంట్</h3><ul class="list-none p-0">${subs}</ul></section>`;
  }

  function refresh() { loadOverview(); loadStudents(); }

  $('anApply').addEventListener('click', () => { page = 1; refresh(); });
  $('anExport').addEventListener('click', () => { window.location.href = API + '?' + qs({ action: 'export_csv' }); });
  $('anSearch').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { page = 1; loadStudents(); }, 320);
  });
  $('anPrev').addEventListener('click', () => { if (page > 1) { page--; loadStudents(); } });
  $('anNext').addEventListener('click', () => { if (page * limit < total) { page++; loadStudents(); } });
  $('anStudentBody').addEventListener('click', e => {
    const tr = e.target.closest('tr[data-id]');
    if (tr && !e.target.closest('a')) openProfile(tr.dataset.id);
  });
  $('anModalClose').addEventListener('click', () => $('anModal').classList.remove('is-open'));
  $('anModal').addEventListener('click', e => { if (e.target === $('anModal')) $('anModal').classList.remove('is-open'); });

  refresh();
})();
</script>
