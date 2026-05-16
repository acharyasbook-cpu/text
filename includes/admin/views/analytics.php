<?php
/** BI Analytics — platform revenue, student 360°, export. */
$analyticsApi = admin_url('analytics_api.php');
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Noto+Sans+Telugu:wght@400;500;600;700&display=swap" />
<style>
  #analyticsHub { font-family: "Noto Sans Telugu", Inter, system-ui, sans-serif; }
  #analyticsHub .an-card { background:#fff; border:1px solid #E8ECF4; border-radius:14px; box-shadow:0 1px 2px rgba(15,23,42,.04); }
  #analyticsHub .an-metric-value { font-variant-numeric: tabular-nums; letter-spacing:-0.02em; }
  #analyticsHub .an-weak { background:#FEF2F2; border:1px solid #FECACA; color:#B91C1C; border-radius:8px; padding:.35rem .65rem; font-size:.75rem; font-weight:600; }
  #analyticsHub .an-table th { font-size:.7rem; text-transform:uppercase; letter-spacing:.04em; color:#64748B; }
  #analyticsHub .an-table tbody tr { cursor:pointer; transition:background .12s; }
  #analyticsHub .an-table tbody tr:hover { background:#F8FAFC; }
  #analyticsHub .an-progress { height:8px; border-radius:999px; background:#E2E8F0; overflow:hidden; }
  #analyticsHub .an-progress > span { display:block; height:100%; background:linear-gradient(90deg,#1E3A8A,#2563EB); border-radius:999px; }
  #analyticsHub .an-modal-backdrop { position:fixed; inset:0; background:rgba(15,23,42,.45); z-index:80; display:none; align-items:flex-start; justify-content:center; padding:1.5rem; overflow-y:auto; }
  #analyticsHub .an-modal-backdrop.is-open { display:flex; }
  #analyticsHub .an-modal { width:min(920px,100%); background:#fff; border-radius:16px; border:1px solid #E2E8F0; box-shadow:0 24px 48px rgba(15,23,42,.18); margin:auto; }
  #analyticsHub .font-telugu { font-family: "Noto Sans Telugu", Inter, system-ui, sans-serif !important; }
</style>

<div id="analyticsHub" class="font-telugu max-w-6xl mx-auto pb-10" data-api="<?= admin_e($analyticsApi) ?>">
  <header class="mb-6 flex flex-wrap items-start justify-between gap-4">
    <div>
      <h1 class="text-2xl font-bold text-slate-900 font-telugu">వినియోగదారు విశ్లేషణ &amp; రెవెన్యూ</h1>
      <p class="text-sm text-slate-600 mt-1 font-telugu">User Analytics · Platform Revenue · Student 360° Profiles</p>
    </div>
    <a href="<?= admin_e(admin_dashboard_url(['view' => 'overview'])) ?>" class="text-sm font-semibold text-slate-600 hover:text-royal">← డాష్‌బోర్డ్</a>
  </header>

  <!-- Platform metrics -->
  <section id="anOverview" class="grid sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="an-card p-5">
      <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 font-telugu">మొత్తం వినియోగదారులు</p>
      <p class="an-metric-value text-3xl font-bold text-slate-900 mt-2" data-k="total_students">—</p>
      <p class="text-xs text-slate-500 mt-2 font-telugu"><span data-k="paid_students">0</span> Paid · <span data-k="free_students">0</span> Free</p>
    </div>
    <div class="an-card p-5">
      <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 font-telugu">మొత్తం రెవెన్యూ</p>
      <p class="an-metric-value text-3xl font-bold text-emerald-700 mt-2" data-k="revenue_total_inr">—</p>
      <p class="text-xs text-slate-500 mt-2 font-telugu">ఫిల్టర్ చేసిన కాలంలో</p>
    </div>
    <div class="an-card p-5 sm:col-span-2 xl:col-span-1">
      <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 font-telugu">పరీక్ష ప్రయత్నాలు</p>
      <p class="an-metric-value text-3xl font-bold text-royal mt-2" data-k="total_exam_attempts">—</p>
    </div>
    <div class="an-card p-5 sm:col-span-2">
      <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2 font-telugu">రెవెన్యూ మ్యాట్రిక్స్ (ప్లాన్ వారీగా)</p>
      <div id="anRevenuePlans" class="grid grid-cols-3 gap-2 text-center text-xs"></div>
    </div>
  </section>

  <!-- Course popularity -->
  <section class="an-card p-5 mb-6">
    <h2 class="text-sm font-bold text-slate-900 font-telugu">కోర్స్ పాపులారిటీ ఇండెక్స్</h2>
    <p class="text-[11px] text-slate-500 mb-3 font-telugu">సబ్-కోర్స్‌లను ఎన్‌రోల్‌మెంట్ సాంద్రత ప్రకారం ర్యాంక్ చేయబడింది</p>
    <div id="anPopularity" class="space-y-2 text-sm"></div>
  </section>

  <!-- Filters & search -->
  <section class="an-card p-5 mb-4">
    <div class="flex flex-wrap gap-3 items-end">
      <div class="flex-1 min-w-[200px]">
        <label class="block text-xs font-semibold text-slate-600 mb-1 font-telugu">గ్లోబల్ సెర్చ్</label>
        <input type="search" id="anSearch" placeholder="పేరు · ఫోన్ · ఇమెయిల్"
          class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-royal/30 focus:border-royal outline-none font-telugu" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">From</label>
        <input type="date" id="anFrom" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">To</label>
        <input type="date" id="anTo" class="rounded-lg border border-slate-200 px-3 py-2.5 text-sm" />
      </div>
      <button type="button" id="anApply" class="px-4 py-2.5 rounded-lg bg-slate-900 text-white text-sm font-bold font-telugu">ఫిల్టర్ వర్తించు</button>
      <button type="button" id="anExport" class="px-4 py-2.5 rounded-lg border border-slate-200 text-sm font-semibold text-slate-800 hover:bg-slate-50 font-telugu">Export CSV</button>
    </div>
    <p id="anListMeta" class="text-xs text-slate-500 mt-3 font-telugu"></p>
  </section>

  <!-- Student table -->
  <section class="an-card overflow-hidden">
    <div class="overflow-x-auto">
      <table class="an-table w-full text-sm">
        <thead class="bg-slate-50 border-b border-slate-200">
          <tr>
            <th class="text-left px-4 py-3">విద్యార్థి</th>
            <th class="text-left px-4 py-3">సంప్రదింపు</th>
            <th class="text-left px-4 py-3">నమోదు</th>
            <th class="text-left px-4 py-3">చివరి యాక్టివ్</th>
            <th class="text-right px-4 py-3">పరీక్షలు</th>
            <th class="text-right px-4 py-3">సగటు %</th>
            <th class="text-center px-4 py-3">Paid</th>
          </tr>
        </thead>
        <tbody id="anStudentBody">
          <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500 font-telugu">లోడ్ అవుతోంది…</td></tr>
        </tbody>
      </table>
    </div>
    <div class="flex justify-between items-center px-4 py-3 border-t border-slate-100">
      <button type="button" id="anPrev" class="text-sm font-semibold text-slate-600 disabled:opacity-40" disabled>← మునుపటి</button>
      <span id="anPageLabel" class="text-xs text-slate-500"></span>
      <button type="button" id="anNext" class="text-sm font-semibold text-royal disabled:opacity-40" disabled>తదుపరి →</button>
    </div>
  </section>
</div>

<!-- Student 360 modal -->
<div id="anModal" class="an-modal-backdrop" aria-hidden="true">
  <article class="an-modal" role="dialog" aria-labelledby="anModalTitle">
    <header class="flex items-start justify-between gap-4 px-6 py-4 border-b border-slate-100">
      <div>
        <h2 id="anModalTitle" class="text-lg font-bold text-slate-900 font-telugu">విద్యార్థి ప్రొఫైల్</h2>
        <p id="anModalSub" class="text-xs text-slate-500 mt-0.5"></p>
      </div>
      <button type="button" id="anModalClose" class="text-slate-500 hover:text-slate-900 text-2xl leading-none" aria-label="Close">&times;</button>
    </header>
    <div id="anModalBody" class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto text-sm">
      <p class="text-slate-500 font-telugu">లోడ్ అవుతోంది…</p>
    </div>
  </article>
</div>

<script>
(function () {
  const root = document.getElementById('analyticsHub');
  if (!root) return;
  const API = root.dataset.api;
  const $ = (id) => document.getElementById(id);

  let page = 1;
  const limit = 50;
  let total = 0;
  let debounceTimer = null;

  function qs(extra) {
    const p = new URLSearchParams();
    const q = $('anSearch').value.trim();
    const from = $('anFrom').value;
    const to = $('anTo').value;
    if (q) p.set('q', q);
    if (from) p.set('from', from);
    if (to) p.set('to', to);
    if (extra) Object.entries(extra).forEach(([k, v]) => p.set(k, v));
    return p.toString();
  }

  function esc(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
  }

  function fmtDate(s) {
    if (!s) return '—';
    try { return new Date(s.replace(' ', 'T')).toLocaleString('en-IN', { dateStyle: 'medium', timeStyle: 'short' }); }
    catch { return s; }
  }

  function inr(n) {
    return '₹' + Number(n || 0).toLocaleString('en-IN', { maximumFractionDigits: 0 });
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
    const plans = $('anRevenuePlans');
    plans.innerHTML = (d.revenue_by_plan || []).map(p => `
      <div class="rounded-lg bg-slate-50 border border-slate-100 p-2">
        <p class="font-semibold text-slate-800 text-[10px] leading-tight font-telugu">${esc(p.label)}</p>
        <p class="text-emerald-700 font-bold mt-1">${inr(p.amount_inr)}</p>
        <p class="text-[10px] text-slate-400">${p.transactions || 0} txns</p>
      </div>`).join('');

    const pop = $('anPopularity');
    const rows = d.course_popularity || [];
    if (!rows.length) {
      pop.innerHTML = '<p class="text-slate-500 text-xs font-telugu">ఇంకా ఎన్‌రోల్‌మెంట్ డేటా లేదు.</p>';
      return;
    }
    const max = Math.max(...rows.map(r => Number(r.enrollments) || 0), 1);
    pop.innerHTML = rows.map((r, i) => {
      const pct = Math.round((Number(r.enrollments) / max) * 100);
      const name = r.name_te || r.name || r.course_name;
      return `<div class="flex items-center gap-3">
        <span class="w-6 text-xs font-bold text-slate-400">${i + 1}</span>
        <div class="flex-1 min-w-0">
          <p class="font-semibold text-slate-800 truncate font-telugu">${esc(name)}</p>
          <div class="an-progress mt-1"><span style="width:${pct}%"></span></div>
        </div>
        <span class="text-xs font-bold text-slate-600 tabular-nums">${r.enrollments}</span>
      </div>`;
    }).join('');
  }

  async function loadStudents() {
    $('anStudentBody').innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-slate-500 font-telugu">లోడ్…</td></tr>';
    const res = await fetch(API + '?' + qs({ action: 'students', page: String(page), limit: String(limit) }));
    const json = await res.json();
    if (!json.ok) {
      $('anStudentBody').innerHTML = '<tr><td colspan="7" class="px-4 py-4 text-red-600">' + esc(json.error || 'Error') + '</td></tr>';
      return;
    }
    total = json.meta?.total || 0;
    const rows = json.data || [];
    $('anListMeta').textContent = total + ' విద్యార్థులు · పేజీ ' + page;
    $('anPageLabel').textContent = 'Page ' + page;
    $('anPrev').disabled = page <= 1;
    $('anNext').disabled = page * limit >= total;

    if (!rows.length) {
      $('anStudentBody').innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-slate-500 font-telugu">ఫలితాలు లేవు</td></tr>';
      return;
    }
    $('anStudentBody').innerHTML = rows.map(r => `
      <tr data-id="${r.id}">
        <td class="px-4 py-3 font-semibold text-slate-900">${esc(r.name)}</td>
        <td class="px-4 py-3 text-slate-600"><div>${esc(r.email)}</div><div class="text-xs">${esc(r.phone || '—')}</div></td>
        <td class="px-4 py-3 text-slate-500 text-xs">${fmtDate(r.created_at)}</td>
        <td class="px-4 py-3 text-slate-500 text-xs">${fmtDate(r.last_active_at)}</td>
        <td class="px-4 py-3 text-right font-semibold">${r.exams_taken ?? 0}</td>
        <td class="px-4 py-3 text-right">${r.avg_score_pct != null ? r.avg_score_pct + '%' : '—'}</td>
        <td class="px-4 py-3 text-center">${r.is_paid ? '<span class="text-emerald-700 font-bold">✓</span>' : '<span class="text-slate-300">—</span>'}</td>
      </tr>`).join('');
  }

  async function openProfile(id) {
    const modal = $('anModal');
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    $('anModalBody').innerHTML = '<p class="text-slate-500 font-telugu">లోడ్…</p>';
    const res = await fetch(API + '?action=student_profile&id=' + encodeURIComponent(id));
    const json = await res.json();
    if (!json.ok) {
      $('anModalBody').innerHTML = '<p class="text-red-600">' + esc(json.error) + '</p>';
      return;
    }
    const p = json.data;
    const u = p.user;
    $('anModalTitle').textContent = u.name;
    $('anModalSub').textContent = u.email + (u.phone ? ' · ' + u.phone : '');

    const weakSub = (p.weak_subjects || []).map(s =>
      `<span class="an-weak font-telugu">${esc(s.subject_name)} (${s.avg_percent}%)</span>`).join(' ') || '<span class="text-slate-400 text-xs font-telugu">ఇంకా గుర్తించబడలేదు</span>';
    const weakTop = (p.weak_topics || []).map(t =>
      `<span class="an-weak">${esc(t.topic_name)} (${t.accuracy_pct}%)</span>`).join(' ') || '<span class="text-slate-400 text-xs font-telugu">ఇంకా గుర్తించబడలేదు</span>';

    const subs = (p.subscriptions || []).map(s => {
      const exp = s.expires_at ? fmtDate(s.expires_at) : 'No expiry';
      const active = s.is_active ? 'text-emerald-700' : 'text-slate-400';
      return `<li class="border border-slate-100 rounded-lg p-3 mb-2">
        <p class="font-semibold text-slate-900 font-telugu">${esc(s.sub_course_name || s.package_name || 'Package')}</p>
        <p class="text-xs mt-1"><span class="${active} font-bold">${esc(s.plan_display || s.plan_label || s.status)}</span> · Expires: ${exp}</p>
      </li>`;
    }).join('') || '<li class="text-slate-400 text-xs font-telugu">సక్రియ సబ్‌స్క్రిప్షన్‌లు లేవు</li>';

    const pr = p.practice || {};
    const perf = p.performance || {};
    const study = p.study_stats || {};

    $('anModalBody').innerHTML = `
      <section class="grid sm:grid-cols-2 gap-4">
        <div class="an-card p-4">
          <h3 class="text-xs font-bold uppercase text-slate-500 mb-2 font-telugu">ప్రొఫైల్ కోర్</h3>
          <p><strong>నమోదు:</strong> ${fmtDate(u.created_at)}</p>
          <p class="mt-1"><strong>చివరి యాక్టివ్:</strong> ${fmtDate(u.last_active_at)}</p>
        </div>
        <div class="an-card p-4">
          <h3 class="text-xs font-bold uppercase text-slate-500 mb-2 font-telugu">ప్రాక్టీస్ టైమ్‌లైన్</h3>
          <p class="font-semibold font-telugu">${esc(pr.framework_label || '')}</p>
          <p class="mt-1 text-royal font-bold">${esc(pr.progress_label || '')}</p>
          <div class="an-progress mt-2"><span style="width:${pr.progress_percent || 0}%"></span></div>
        </div>
      </section>

      <section>
        <h3 class="text-xs font-bold uppercase text-slate-500 mb-2 font-telugu">కామర్షియల్ వాలిడిటీ మ్యాట్రిక్స్</h3>
        <ul class="list-none p-0 m-0">${subs}</ul>
      </section>

      <section class="grid sm:grid-cols-3 gap-3">
        <div class="an-card p-3 text-center"><p class="text-2xl font-bold">${perf.total_attempts ?? 0}</p><p class="text-[10px] text-slate-500 font-telugu">పరీక్షలు</p></div>
        <div class="an-card p-3 text-center"><p class="text-2xl font-bold text-royal">${perf.avg_percent ?? 0}%</p><p class="text-[10px] text-slate-500 font-telugu">సగటు గ్రేడ్</p></div>
        <div class="an-card p-3 text-center"><p class="text-2xl font-bold">${study.total_hours ?? 0}h</p><p class="text-[10px] text-slate-500 font-telugu">అధ్యయన సమయం</p></div>
      </section>
      <p class="text-xs text-slate-500 font-telugu">ఈ రోజు: ${study.today_minutes ?? 0} నిమిషాలు · ${study.active_days ?? 0} సక్రియ రోజులు</p>

      <section>
        <h3 class="text-xs font-bold uppercase text-red-700 mb-2 font-telugu">బలహీన అంశాలు (Weak Subjects)</h3>
        <div class="flex flex-wrap gap-2">${weakSub}</div>
      </section>
      <section>
        <h3 class="text-xs font-bold uppercase text-red-700 mb-2 font-telugu">బలహీన టాపిక్‌లు</h3>
        <div class="flex flex-wrap gap-2">${weakTop}</div>
      </section>`;
  }

  function refresh() {
    loadOverview();
    loadStudents();
  }

  $('anApply').addEventListener('click', () => { page = 1; refresh(); });
  $('anExport').addEventListener('click', () => {
    window.location.href = API + '?' + qs({ action: 'export_csv' });
  });
  $('anSearch').addEventListener('input', () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => { page = 1; loadStudents(); }, 320);
  });
  $('anPrev').addEventListener('click', () => { if (page > 1) { page--; loadStudents(); } });
  $('anNext').addEventListener('click', () => { if (page * limit < total) { page++; loadStudents(); } });
  $('anStudentBody').addEventListener('click', (e) => {
    const tr = e.target.closest('tr[data-id]');
    if (tr) openProfile(tr.dataset.id);
  });
  $('anModalClose').addEventListener('click', () => {
    $('anModal').classList.remove('is-open');
    $('anModal').setAttribute('aria-hidden', 'true');
  });
  $('anModal').addEventListener('click', (e) => {
    if (e.target === $('anModal')) {
      $('anModal').classList.remove('is-open');
    }
  });

  refresh();
})();
</script>
