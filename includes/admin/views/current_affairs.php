<?php
$adminPageTitle = 'డైలీ కరెంట్ అఫైర్స్ ఇంజిన్';
$adminPageSubtitle = 'మాన్యువల్ / AI స్టేజింగ్ · హిస్టారికల్ పుర్జ్';
$apiUrl = admin_url('current_affairs_api.php');
$csrf = admin_csrf_token();
$today = date('Y-m-d');
?>
<div id="ca-admin-root" class="space-y-6 font-telugu" data-api="<?= admin_e($apiUrl) ?>" data-csrf="<?= admin_e($csrf) ?>">
  <?php require __DIR__ . '/../partials/page_header.php'; ?>

  <div class="admin-card p-5">
    <h2 class="text-sm font-bold text-slate-900">డైలీ కరెంట్ అఫైర్స్ — ప్రశ్న స్టేజింగ్</h2>
    <p class="text-xs text-slate-500 mt-1">Mode A: మాన్యువల్ (50+ అయితే లాటరీ 25) · Mode B: AI జనరేషన్ (25)</p>
    <label class="block mt-4 text-xs font-semibold">పరీక్ష తేదీ
      <input type="date" id="caExamDate" class="admin-input w-full max-w-xs mt-1" value="<?= admin_e($today) ?>" />
    </label>
    <p id="caPoolStatus" class="text-xs text-slate-600 mt-2"></p>
  </div>

  <div class="grid lg:grid-cols-2 gap-5">
    <div class="admin-card p-5">
      <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Mode A — Manual Entry</h3>
      <textarea id="caBulkText" rows="14" class="admin-input w-full mt-3 text-xs font-mono" placeholder="Q1. ప్రశ్న&#10;A) ...&#10;B) ...&#10;C) ...&#10;D) ...&#10;Answer: A"></textarea>
      <button type="button" id="caManualSave" class="admin-btn admin-btn-primary mt-3 text-sm">మాన్యువల్ సేవ్</button>
    </div>
    <div class="admin-card p-5">
      <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wide">Mode B — AI Generate</h3>
      <p class="text-xs text-slate-500 mt-2">సక్రియ AI స్లాట్ ఉంటే వెబ్-స్మార్ట్ ప్రాంప్ట్; లేకుంటే క్యూరేటెడ్ ఫాల్‌బ్యాక్ 25 ప్రశ్నలు.</p>
      <button type="button" id="caAiGenerate" class="admin-btn admin-btn-primary mt-4 text-sm">AI తో 25 ప్రశ్నలు జనరేట్</button>
      <button type="button" id="caClearDate" class="admin-btn mt-2 text-sm text-red-700 border-red-200">ఈ తేదీ క్లియర్</button>
      <div id="caAiConfirm" class="ca-ai-confirm mt-4 p-3 border border-slate-200 rounded-lg bg-slate-50" hidden>
        <p class="text-xs text-slate-700">AI జనరేషన్ ప్రారంభించాలా?</p>
        <div class="flex gap-2 mt-2">
          <button type="button" id="caAiProceed" class="admin-btn admin-btn-primary text-xs">[Proceed]</button>
          <button type="button" id="caAiCancel" class="admin-btn text-xs">రద్దు</button>
        </div>
      </div>
      <div id="caAiProgress" class="ca-ai-progress" hidden>
        <div class="ca-ai-progress__bar"><div id="caAiProgressFill" class="ca-ai-progress__fill"></div></div>
        <p id="caAiProgressLabel" class="ca-ai-progress__label">0% — ప్రారంభం…</p>
      </div>
    </div>
  </div>

  <div class="admin-card p-5">
    <h3 class="text-sm font-bold text-slate-900">తేదీ వారీ స్టేటస్</h3>
    <div class="overflow-x-auto mt-3">
      <table class="admin-table w-full text-xs">
        <thead><tr><th>తేదీ</th><th>మొత్తం</th><th>Manual</th><th>AI</th><th>రెడీ</th></tr></thead>
        <tbody id="caStatsBody"></tbody>
      </table>
    </div>
  </div>

  <div class="admin-card p-5 border-amber-200 bg-amber-50/30">
    <h3 class="text-sm font-bold text-red-800">Historical Data Purge Tool</h3>
    <p class="text-xs text-slate-600 mt-1">1 సంవత్సరం కంటే పాత నెలల డేటాను శాశ్వతంగా తొలగించండి (ఆప్టిమైజేషన్).</p>
    <div id="caPurgeList" class="mt-4 space-y-2"></div>
  </div>
</div>

<script>
(function () {
  var root = document.getElementById('ca-admin-root');
  if (!root) return;
  var API = root.getAttribute('data-api');
  var CSRF = root.getAttribute('data-csrf');
  var dateInp = document.getElementById('caExamDate');
  var statusEl = document.getElementById('caPoolStatus');

  function post(action, data) {
    var fd = new FormData();
    fd.append('action', action);
    fd.append('_csrf', CSRF);
    Object.keys(data || {}).forEach(function (k) { fd.append(k, data[k]); });
    return fetch(API, { method: 'POST', body: fd, credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }

  function refreshPool() {
    fetch(API + '?action=pool&date=' + encodeURIComponent(dateInp.value), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        statusEl.textContent = j.count + ' ప్రశ్నలు · ' + (j.ready ? '✓ 25+ రెడీ' : 'ఇంకా 25 కావాలి');
      });
  }

  function loadStats() {
    fetch(API + '?action=stats', { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (j) {
      if (!j.ok) return;
      var tb = document.getElementById('caStatsBody');
      tb.innerHTML = (j.dates || []).map(function (d) {
        return '<tr><td>' + d.exam_date + '</td><td>' + d.total_questions + '</td><td>' + d.manual_count + '</td><td>' + d.ai_count + '</td><td>' + (d.total_questions >= 25 ? '✓' : '—') + '</td></tr>';
      }).join('') || '<tr><td colspan="5">—</td></tr>';
      var pl = document.getElementById('caPurgeList');
      pl.innerHTML = (j.purge_months || []).map(function (m) {
        return '<div class="flex flex-wrap items-center justify-between gap-2 p-3 bg-white border rounded-lg">' +
          '<span class="font-semibold">' + m.label + ' (' + m.question_count + ' ప్రశ్నలు)</span>' +
          '<button type="button" class="admin-btn text-red-700 border-red-300 ca-purge-btn" data-ym="' + m.ym + '">శాశ్వత తొలగింపు</button></div>';
      }).join('') || '<p class="text-xs text-slate-500">పుర్జ్ చేయడానికి నెలలు లేవు.</p>';
      pl.querySelectorAll('.ca-purge-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
          if (!confirm('ఈ నెల డేటా శాశ్వతంగా తొలగించాలా?')) return;
          post('purge_month', { ym: btn.getAttribute('data-ym') }).then(function (j) {
            alert(j.ok ? 'తొలగించబడింది: ' + j.deleted : (j.error || 'Error'));
            loadStats();
          });
        });
      });
    });
  }

  document.getElementById('caManualSave').addEventListener('click', function () {
    post('manual_save', { exam_date: dateInp.value, bulk_text: document.getElementById('caBulkText').value })
      .then(function (j) {
        alert(j.ok ? 'సేవ్: ' + j.inserted + ' ప్రశ్నలు' + (j.lottery ? ' (లాటరీ మోడ్)' : '') : (j.error || 'Error'));
        refreshPool();
        loadStats();
      });
  });

  var aiConfirm = document.getElementById('caAiConfirm');
  var aiProgress = document.getElementById('caAiProgress');
  var aiFill = document.getElementById('caAiProgressFill');
  var aiLabel = document.getElementById('caAiProgressLabel');

  document.getElementById('caAiGenerate').addEventListener('click', function () {
    aiConfirm.hidden = false;
  });
  document.getElementById('caAiCancel').addEventListener('click', function () {
    aiConfirm.hidden = true;
  });
  document.getElementById('caAiProceed').addEventListener('click', function () {
    aiConfirm.hidden = true;
    aiProgress.hidden = false;
    aiFill.style.width = '0%';
    aiLabel.textContent = '0% — ప్రారంభం…';
    post('ai_job_start', { exam_date: dateInp.value }).then(function (j) {
      if (!j.ok) { alert(j.error || 'Error'); aiProgress.hidden = true; return; }
      runAiTicks(j.job_id, 0);
    });
  });

  function runAiTicks(jobId, n) {
    if (n > 20) { aiProgress.hidden = true; return; }
    post('ai_job_tick', { job_id: jobId }).then(function (j) {
      if (!j.ok) { alert(j.error || 'Error'); aiProgress.hidden = true; return; }
      aiFill.style.width = (j.percent || 0) + '%';
      aiLabel.textContent = (j.percent || 0) + '% — ' + (j.message || '');
      if (j.status === 'complete') {
        aiLabel.textContent = '100% — పూర్తి (' + (j.inserted || 0) + ' ప్రశ్నలు)';
        refreshPool();
        loadStats();
        setTimeout(function () { aiProgress.hidden = true; }, 1200);
        return;
      }
      setTimeout(function () { runAiTicks(jobId, n + 1); }, 400);
    });
  }

  document.getElementById('caClearDate').addEventListener('click', function () {
    if (!confirm('ఈ తేదీ పూర్తి పూల్ తొలగించాలా?')) return;
    post('clear_date', { exam_date: dateInp.value }).then(function () { refreshPool(); loadStats(); });
  });

  dateInp.addEventListener('change', refreshPool);
  refreshPool();
  loadStats();
})();
</script>
