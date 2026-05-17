<?php

declare(strict_types=1);

/** @var AdminRepository $repo */
require_once ACHARYA_ROOT . '/models/ExamManagerRepository.php';

$examRepo = new ExamManagerRepository();
$tests = $examRepo->listTestsGrid();
$courses = $repo->allCourses();
$subjects = $repo->allSubjectsWithCourse();
$topicsCatalog = SchemaHelper::columnExists('tests', 'topic_id') ? $repo->topicsCatalogForAdmin() : [];
$editTestId = !empty($_GET['edit_test']) ? (int) $_GET['edit_test'] : null;
$testForm = null;
if ($editTestId) {
    foreach ($tests as $t) {
        if ((int) $t['id'] === $editTestId) {
            $testForm = $t;
            break;
        }
    }
}

$examTypes = ExamManagerRepository::EXAM_TYPES;
$negRates = ExamManagerRepository::NEGATIVE_RATES;
$apiUrl = admin_url('exam_api.php');
$csrf = admin_csrf_token();

$pickerCourseId = $testForm ? (int) $testForm['course_id'] : (int) ($courses[0]['id'] ?? 0);
$pickerSubjectId = $testForm && !empty($testForm['subject_id']) ? (int) $testForm['subject_id'] : null;
$pickerExclude = $testForm ? (int) $testForm['id'] : null;
$bundleTests = $pickerCourseId ? $repo->testsForBundlePicker($pickerCourseId, $pickerSubjectId, $pickerExclude) : [];
$bundleSelected = $testForm && SchemaHelper::testBundleEnabled() ? $repo->bundleComponentIds((int) $testForm['id']) : [];

$curType = $testForm ? ExamManagerRepository::normalizeTestType((string) ($testForm['test_type'] ?? 'topic')) : 'topic';
$curUnlimited = $testForm ? ((int) ($testForm['duration_mins'] ?? 0) <= 0) : false;
$curNeg = $testForm ? ((float) ($testForm['negative_marking'] ?? 0) > 0) : true;
$curNegVal = $testForm ? (float) ($testForm['negative_marking'] ?? 0.25) : 0.25;

$pageTitle = 'Exam Manager';
$pageSubtitle = 'హైబ్రిడ్ ప్రశ్న పూల్ · ఇన్‌లైన్ ఎడిటింగ్';
require ACHARYA_ROOT . '/includes/admin/partials/page_header.php';
?>

<div class="exam-manager-root font-telugu" data-api="<?= admin_e($apiUrl) ?>" data-csrf="<?= admin_e($csrf) ?>">
  
  
  <div class="grid lg:grid-cols-3 gap-6">
    
    <div class="lg:col-span-2 admin-card overflow-hidden">
      <div class="admin-card-header flex flex-wrap items-center justify-between gap-3">
        <div>
          <h2 class="admin-card-title font-telugu">పరీక్షల జాబితా</h2>
          <p class="text-xs text-slate-500 mt-0.5">టైప్ · టైమర్ · ప్రశ్నలు — ఇన్‌లైన్ నియంత్రణలు</p>
        </div>
        <span class="admin-badge"><?= count($tests) ?> exams</span>
      </div>
      <div class="overflow-x-auto">
        <table class="admin-table w-full text-sm" id="exam-records-table">
          <thead>
            <tr>
              <th>శీర్షిక</th>
              <th>రకం</th>
              <th>టైమర్</th>
              <th>ప్రశ్నలు</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($tests as $t):
                $tid = (int) $t['id'];
                $tt = ExamManagerRepository::normalizeTestType((string) ($t['test_type'] ?? 'topic'));
                $unlim = (int) ($t['duration_mins'] ?? 0) <= 0;
                $qc = (int) ($t['question_count'] ?? 0);
                ?>
            <tr data-test-id="<?= $tid ?>">
              <td>
                <span class="font-medium text-slate-800"><?= admin_e($t['title']) ?></span>
                <span class="block text-xs text-slate-500"><?= admin_e($t['course_name'] ?? '') ?></span>
              </td>
              <td>
                <select class="exam-inline-type admin-input py-1 text-xs font-telugu min-w-[10rem]" data-test-id="<?= $tid ?>">
                  <?php foreach ($examTypes as $key => $meta): ?>
                  <option value="<?= admin_e($key) ?>" <?= $tt === $key ? 'selected' : '' ?>><?= admin_e($meta['te']) ?></option>
                  <?php endforeach; ?>
                </select>
              </td>
              <td class="text-slate-600 whitespace-nowrap">
                <?php if ($unlim): ?>
                <span class="admin-badge admin-badge--success text-xs">అపరిమిత</span>
                <?php else: ?>
                <?= (int) $t['duration_mins'] ?> నిమి
                <?php endif; ?>
              </td>
              <td>
                <button type="button" class="exam-q-count-link text-brand font-semibold hover:underline" data-test-id="<?= $tid ?>" data-title="<?= admin_e($t['title']) ?>">
                  <?= $qc ?>
                </button>
              </td>
              <td class="text-right whitespace-nowrap">
                <a href="<?= admin_e(admin_dashboard_url(['view' => 'exams', 'edit_test' => $tid])) ?>" class="text-xs text-slate-600 hover:text-brand mr-2">సవరించు</a>
                <button type="button" class="exam-delete-btn text-xs text-red-600 hover:underline" data-test-id="<?= $tid ?>">తొలగించు</button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="admin-card p-5 h-fit sticky top-24">
      <h3 class="admin-card-title font-telugu mb-1"><?= $testForm ? 'పరీక్ష సవరించండి' : 'ఆన్‌లైన్ పరీక్ష సృష్టించండి' ?></h3>
      <p class="text-xs text-slate-500 mb-4">హైబ్రిడ్ ప్రశ్న విజార్డ్ · టైమర్ · నెగటివ్ మార్కింగ్</p>

      <form id="exam-create-form" class="space-y-3" autocomplete="off">
        <?php if ($testForm): ?>
        <input type="hidden" name="id" value="<?= (int) $testForm['id'] ?>" />
        <?php endif; ?>

        <div>
          <label class="admin-label font-telugu">శీర్షిక *</label>
          <input name="title" required value="<?= admin_e($testForm['title'] ?? '') ?>" class="admin-input w-full mt-1" />
        </div>
        <div>
          <label class="admin-label font-telugu">తెలుగు శీర్షిక</label>
          <input name="title_te" value="<?= admin_e($testForm['title_te'] ?? '') ?>" class="admin-input w-full mt-1 font-telugu" />
        </div>

        <div>
          <label class="admin-label">Course</label>
          <select name="course_id" id="em-course" required class="admin-input w-full mt-1">
            <?php foreach ($courses as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= $testForm && (int) $testForm['course_id'] === (int) $c['id'] ? 'selected' : '' ?>><?= admin_e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        
        <div>
          <label class="admin-label font-telugu">విషయం (Subject)</label>
          <select name="subject_id" id="em-subject" class="admin-input w-full mt-1">
            <option value="">— ఎంచుకోండి —</option>
            <?php foreach ($subjects as $s): ?>
            <option value="<?= (int) $s['id'] ?>" data-course="<?= (int) ($s['course_id'] ?? 0) ?>"
              <?= $testForm && (int) ($testForm['subject_id'] ?? 0) === (int) $s['id'] ? 'selected' : '' ?>>
              <?= admin_e($s['name']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label class="admin-label font-telugu">పరీక్ష రకం *</label>
          <select name="test_type" id="em-test-type" required class="admin-input w-full mt-1 font-telugu">
            <?php foreach ($examTypes as $key => $meta): ?>
            <option value="<?= admin_e($key) ?>" <?= $curType === $key ? 'selected' : '' ?>><?= admin_e($meta['te']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php if (SchemaHelper::testBundleEnabled()): ?>
        <div id="em-bundle-wrap" class="space-y-1" style="display:none">
          <label class="admin-label font-telugu text-xs">కంపోనెంట్ పరీక్షలు (బండిల్)</label>
          <select name="component_test_ids[]" id="em-bundle-select" multiple size="6" class="admin-input w-full text-xs">
            <?php foreach ($bundleTests as $bt): ?>
            <option value="<?= (int) $bt['id'] ?>" <?= in_array((int) $bt['id'], $bundleSelected, true) ? 'selected' : '' ?>>
              [<?= admin_e(ExamManagerRepository::normalizeTestType((string) ($bt['test_type'] ?? ''))) ?>] <?= admin_e($bt['title']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <div class="admin-card bg-slate-50 border border-slate-200 p-3 space-y-3" id="em-hybrid-wizard">
          <p class="text-sm font-semibold text-slate-800 font-telugu">స్మార్ట్ హైబ్రిడ్ ప్రశ్న విజార్డ్</p>
          <p class="text-xs text-slate-500">విషయం ఎంచుకున్న తర్వాత బ్యాంక్ నుండి ప్రశ్నలు లింక్ చేయండి</p>

          <div id="em-wizard-topic-wrap">
            <label class="admin-label font-telugu text-xs">టాపిక్ ఫిల్టర్ (విజార్డ్)</label>
            <select id="em-wizard-topic" class="admin-input w-full mt-1 text-sm">
              <option value="">— అన్ని టాపిక్‌లు —</option>
              <?php foreach ($topicsCatalog as $tc): ?>
              <option value="<?= (int) $tc['id'] ?>" data-subject-id="<?= (int) ($tc['subject_id'] ?? 0) ?>">
                <?= admin_e(($tc['subject_name'] ?? '') . ' — ' . ($tc['title'] ?? '')) ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <input type="search" id="em-pool-search" placeholder="ప్రశ్నలు శోధించండి…" class="admin-input w-full text-sm" disabled />
          </div>
          <div id="em-pool-toolbar" class="flex gap-2 text-xs hidden">
            <button type="button" id="em-pool-select-all" class="text-brand hover:underline">అన్నీ ఎంచుకోండి</button>
            <button type="button" id="em-pool-clear" class="text-slate-500 hover:underline">క్లియర్</button>
            <span id="em-pool-count" class="ml-auto text-slate-500"></span>
          </div>
          
          <div id="em-pool-list" class="max-h-48 overflow-y-auto border border-slate-200 rounded-lg bg-white divide-y text-sm hidden"></div>
          <p id="em-pool-hint" class="text-xs text-amber-700 font-telugu">ముందుగా విషయం ఎంచుకోండి</p>

          <div class="border-t border-slate-200 pt-3">
            <label class="admin-label font-telugu text-sm block mb-1">బయటి ప్రశ్నలను జోడించండి (Add External Questions Manually)</label>
            <textarea id="em-external-mcq" rows="6" class="admin-input w-full text-xs font-mono" placeholder="Q1. ప్రశ్న&#10;A) ...&#10;B) ...&#10;C) ...&#10;D) ...&#10;Answer: A"></textarea>
            <p class="text-xs text-slate-500 mt-1">McqParser ఫార్మాట్ — సేవ్ చేసినప్పుడు ఆటోమేటిక్ ఇంపోర్ట్</p>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div>
            <label class="admin-label font-telugu text-xs">టైమర్ (నిమిషాలు)</label>
            <input type="number" name="duration_mins" id="em-duration" min="1" value="<?= $curUnlimited ? 60 : (int) ($testForm['duration_mins'] ?? 60) ?>" class="admin-input w-full mt-1" <?= $curUnlimited ? 'disabled' : '' ?> />
          </div>
          <div class="flex items-end pb-1">
            <label class="flex items-center gap-2 text-sm font-telugu cursor-pointer">
              <input type="checkbox" id="em-unlimited" name="unlimited_time" value="1" <?= $curUnlimited ? 'checked' : '' ?> class="rounded border-slate-300 text-brand" />
              అపరిమిత సమయం
            </label>
          </div>
        </div>

        
        <div class="admin-card bg-slate-50 p-3 space-y-2">
          <label class="flex items-center gap-2 text-sm font-telugu cursor-pointer">
            <input type="checkbox" id="em-neg-enabled" <?= $curNeg ? 'checked' : '' ?> class="rounded border-slate-300 text-brand" />
            నెగటివ్ మార్కింగ్
          </label>
          <select id="em-neg-rate" class="admin-input w-full text-sm" <?= !$curNeg ? 'disabled' : '' ?>>
            <?php foreach ($negRates as $r): if ($r <= 0) {
                continue;
            } ?>
            <option value="<?= $r ?>" <?= abs($curNegVal - $r) < 0.01 ? 'selected' : '' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-2">
          <div><label class="admin-label text-xs">Pass</label><input type="number" name="passing_marks" value="<?= (int) ($testForm['passing_marks'] ?? 25) ?>" class="admin-input w-full mt-1" /></div>
          <div><label class="admin-label text-xs">Total Q</label><input type="number" name="total_questions" value="<?= (int) ($testForm['total_questions'] ?? 50) ?>" class="admin-input w-full mt-1" /></div>
        </div>
        <input name="slug" placeholder="slug" value="<?= admin_e($testForm['slug'] ?? '') ?>" class="admin-input w-full text-sm" />
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" <?= (!$testForm || !empty($testForm['is_active'])) ? 'checked' : '' ?> /> Active / Live</label>

        <button type="submit" class="admin-btn admin-btn--primary w-full font-telugu" id="em-save-btn">పరీక్ష సేవ్ చేయండి</button>
        <p id="em-form-status" class="text-xs text-center hidden"></p>
      </form>
    </div>
  </div>
</div>

<!-- Questions modal -->
<div id="exam-questions-modal" class="fixed inset-0 z-[80] hidden" aria-hidden="true">
  <div class="absolute inset-0 bg-slate-900/50" data-close-modal></div>
  <div class="absolute inset-4 md:inset-8 lg:inset-12 flex items-center justify-center pointer-events-none">
    <div class="admin-card w-full max-w-4xl max-h-full flex flex-col pointer-events-auto shadow-2xl">
      
      <div class="admin-card-header flex items-center justify-between gap-3 shrink-0">
        <div>
          <h3 class="admin-card-title font-telugu" id="eqm-title">ప్రశ్నల నిర్వహణ</h3>
          <p class="text-xs text-slate-500" id="eqm-sub"></p>
        </div>
        <button type="button" class="admin-btn admin-btn--ghost text-sm" data-close-modal>✕ మూసివేయి</button>
      </div>
      <div id="eqm-body" class="overflow-y-auto p-4 space-y-4 flex-1"></div>
    </div>
  </div>
</div>

<style>
.exam-manager-root .exam-q-count-link { font-variant-numeric: tabular-nums; }
.em-pool-item { display:flex; gap:.5rem; padding:.5rem .75rem; align-items:flex-start; }
.em-pool-item:hover { background:#f8fafc; }
.em-q-row { border:1px solid #e2e8f0; border-radius:.75rem; padding:1rem; }
.em-q-row textarea, .em-q-row input { font-family:'Noto Sans Telugu', system-ui, sans-serif; }
</style>

<script>
(function () {
  var root = document.querySelector('.exam-manager-root');
  if (!root) return;
  var API = root.getAttribute('data-api');
  var CSRF = root.getAttribute('data-csrf');
  var BUNDLE_TYPES = ['revision', 'sub_grand', 'grand'];

  function postJson(action, body) {
    body = body || {};
    body.action = action;
    body._csrf = CSRF;
    return fetch(API, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      credentials: 'same-origin',
      body: JSON.stringify(body)
    }).then(function (r) { return r.json(); });
  }

  function getJson(action, params) {
    var q = new URLSearchParams(params || {});
    q.set('action', action);
    return fetch(API + '?' + q.toString(), { credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }

  /* Inline type */
  document.querySelectorAll('.exam-inline-type').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var testId = parseInt(sel.getAttribute('data-test-id'), 10);
      postJson('patch_type', { test_id: testId, test_type: sel.value }).then(function (d) {
        if (!d.ok) alert(d.error || 'Failed');
      });
    });
  });

  /* Delete exam */
  document.querySelectorAll('.exam-delete-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('ఈ పరీక్షను తొలగించాలా?')) return;
      var id = parseInt(btn.getAttribute('data-test-id'), 10);
      postJson('delete_exam', { id: id }).then(function (d) {
        if (d.ok) location.reload();
        else alert(d.error || 'Failed');
      });
    });
  });

  /* Hybrid pool */
  var subjectSel = document.getElementById('em-subject');
  var courseSel = document.getElementById('em-course');
  var topicSel = document.getElementById('em-wizard-topic');
  var poolSearch = document.getElementById('em-pool-search');
  var poolList = document.getElementById('em-pool-list');
  var poolHint = document.getElementById('em-pool-hint');
  var poolToolbar = document.getElementById('em-pool-toolbar');
  var poolCount = document.getElementById('em-pool-count');
  var selectedPool = {};
  var poolDebounce = null;

  function filterSubjects() {
    if (!subjectSel || !courseSel) return;
    var cid = courseSel.value;
    Array.prototype.forEach.call(subjectSel.options, function (o) {
      if (!o.value) return;
      var dc = o.getAttribute('data-course');
      o.hidden = !!(cid && dc && dc !== cid);
    });
  }

  function filterWizardTopics() {
    if (!topicSel || !subjectSel) return;
    var sid = subjectSel.value;
    Array.prototype.forEach.call(topicSel.options, function (o) {
      if (!o.value) { o.hidden = false; return; }
      var ds = o.getAttribute('data-subject-id');
      o.hidden = !!(sid && ds && ds !== sid);
    });
  }

  function renderPool(items) {
    if (!poolList) return;
    poolList.innerHTML = '';
    if (!items || !items.length) {
      poolList.classList.add('hidden');
      poolHint.textContent = 'ఈ ఎంపికకు ప్రశ్నలు లేవు';
      poolHint.classList.remove('hidden');
      return;
    }
    poolHint.classList.add('hidden');
    poolList.classList.remove('hidden');
    poolToolbar.classList.remove('hidden');
    items.forEach(function (q) {
      var id = parseInt(q.id, 10);
      var label = document.createElement('label');
      label.className = 'em-pool-item';
      var checked = !!selectedPool[id];
      label.innerHTML = '<input type="checkbox" class="em-pool-cb mt-1" data-id="' + id + '" ' + (checked ? 'checked' : '') + ' />' +
        '<span><span class="text-xs text-slate-400">#' + id + '</span> ' +
        escapeHtml((q.question_text || '').substring(0, 120)) +
        (q.source_test_title ? '<span class="block text-xs text-slate-400">' + escapeHtml(q.source_test_title) + '</span>' : '') +
        '</span>';
      poolList.appendChild(label);
    });
    poolCount.textContent = items.length + ' ప్రశ్నలు';
    poolList.querySelectorAll('.em-pool-cb').forEach(function (cb) {
      cb.addEventListener('change', function () {
        var qid = parseInt(cb.getAttribute('data-id'), 10);
        if (cb.checked) selectedPool[qid] = true;
        else delete selectedPool[qid];
      });
    });
  }

  function loadPool() {
    var sid = subjectSel && parseInt(subjectSel.value, 10);
    if (!sid) {
      poolSearch.disabled = true;
      poolList.classList.add('hidden');
      poolToolbar.classList.add('hidden');
      poolHint.textContent = 'ముందుగు విషయం ఎంచుకోండి';
      poolHint.classList.remove('hidden');
      return;
    }
    poolSearch.disabled = false;
    var tid = topicSel && parseInt(topicSel.value, 10) || 0;
    getJson('pool_mcqs', { subject_id: sid, topic_id: tid, q: poolSearch.value || '' }).then(function (d) {
      if (d.ok) renderPool(d.items);
    });
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  if (subjectSel) subjectSel.addEventListener('change', function () { filterWizardTopics(); loadPool(); });
  if (topicSel) topicSel.addEventListener('change', loadPool);
  if (courseSel) courseSel.addEventListener('change', filterSubjects);
  if (poolSearch) poolSearch.addEventListener('input', function () {
    clearTimeout(poolDebounce);
    poolDebounce = setTimeout(loadPool, 300);
  });
  document.getElementById('em-pool-select-all') && document.getElementById('em-pool-select-all').addEventListener('click', function () {
    poolList.querySelectorAll('.em-pool-cb').forEach(function (cb) { cb.checked = true; selectedPool[parseInt(cb.getAttribute('data-id'), 10)] = true; });
  });
  document.getElementById('em-pool-clear') && document.getElementById('em-pool-clear').addEventListener('click', function () {
    selectedPool = {};
    poolList.querySelectorAll('.em-pool-cb').forEach(function (cb) { cb.checked = false; });
  });

  filterSubjects();
  filterWizardTopics();
  if (subjectSel && subjectSel.value) loadPool();

  /* Timer + negative */
  var unlimitedCb = document.getElementById('em-unlimited');
  var durationInp = document.getElementById('em-duration');
  unlimitedCb && unlimitedCb.addEventListener('change', function () {
    durationInp.disabled = unlimitedCb.checked;
  });
  var negEn = document.getElementById('em-neg-enabled');
  var negRate = document.getElementById('em-neg-rate');
  negEn && negEn.addEventListener('change', function () { negRate.disabled = !negEn.checked; });

  var typeSel = document.getElementById('em-test-type');
  var bundleWrap = document.getElementById('em-bundle-wrap');
  function syncBundle() {
    if (!bundleWrap || !typeSel) return;
    bundleWrap.style.display = BUNDLE_TYPES.indexOf(typeSel.value) >= 0 ? 'block' : 'none';
  }
  typeSel && typeSel.addEventListener('change', syncBundle);
  syncBundle();

  /* Save form */
  var form = document.getElementById('exam-create-form');
  var statusEl = document.getElementById('em-form-status');
  form && form.addEventListener('submit', function (e) {
    e.preventDefault();
    var fd = new FormData(form);
    var comp = [];
    var bundleSel = document.getElementById('em-bundle-select');
    if (bundleSel) {
      Array.prototype.forEach.call(bundleSel.selectedOptions, function (o) { comp.push(parseInt(o.value, 10)); });
    }
    var poolIds = Object.keys(selectedPool).map(function (k) { return parseInt(k, 10); });
    var payload = {
      id: fd.get('id') ? parseInt(fd.get('id'), 10) : null,
      title: fd.get('title'),
      title_te: fd.get('title_te'),
      course_id: parseInt(fd.get('course_id'), 10),
      subject_id: fd.get('subject_id') ? parseInt(fd.get('subject_id'), 10) : null,
      topic_id: topicSel && topicSel.value ? parseInt(topicSel.value, 10) : null,
      test_type: fd.get('test_type'),
      slug: fd.get('slug'),
      duration_mins: parseInt(fd.get('duration_mins') || '60', 10),
      unlimited_time: unlimitedCb && unlimitedCb.checked ? 1 : 0,
      passing_marks: parseInt(fd.get('passing_marks') || '25', 10),
      total_questions: parseInt(fd.get('total_questions') || '50', 10),
      total_marks: parseInt(fd.get('total_questions') || '50', 10),
      negative_enabled: negEn && negEn.checked ? 1 : 0,
      negative_marking: negRate ? parseFloat(negRate.value) : 0.25,
      is_active: fd.get('is_active') ? 1 : 0,
      component_test_ids: comp,
      pool_question_ids: poolIds,
      external_mcq_text: document.getElementById('em-external-mcq').value || ''
    };
    statusEl.classList.remove('hidden');
    statusEl.textContent = 'సేవ్ అవుతోంది…';
    postJson('save_exam', payload).then(function (d) {
      if (d.ok) {
        statusEl.textContent = d.message || 'సేవ్ అయ్యింది';
        statusEl.className = 'text-xs text-center text-emerald-600';
        setTimeout(function () { window.location.href = '<?= admin_e(admin_dashboard_url(['view' => 'exams'])) ?>'; }, 800);
      } else {
        statusEl.textContent = d.error || 'లోపం';
        statusEl.className = 'text-xs text-center text-red-600';
      }
    });
  });

  /* Questions modal */
  var modal = document.getElementById('exam-questions-modal');
  var eqmTitle = document.getElementById('eqm-title');
  var eqmSub = document.getElementById('eqm-sub');
  var eqmBody = document.getElementById('eqm-body');
  var activeTestId = 0;

  function openModal(testId, title) {
    activeTestId = testId;
    eqmTitle.textContent = title || 'ప్రశ్నలు';
    eqmSub.textContent = 'Test #' + testId;
    modal.classList.remove('hidden');
    loadQuestionsModal(testId);
  }

  function closeModal() {
    modal.classList.add('hidden');
    location.reload();
  }

  modal.querySelectorAll('[data-close-modal]').forEach(function (el) {
    el.addEventListener('click', closeModal);
  });

  document.querySelectorAll('.exam-q-count-link').forEach(function (lnk) {
    lnk.addEventListener('click', function () {
      openModal(parseInt(lnk.getAttribute('data-test-id'), 10), lnk.getAttribute('data-title'));
    });
  });

  function loadQuestionsModal(testId) {
    eqmBody.innerHTML = '<p class="text-sm text-slate-500 font-telugu">లోడ్ అవుతోంది…</p>';
    getJson('questions', { test_id: testId }).then(function (d) {
      if (!d.ok) { eqmBody.innerHTML = '<p class="text-red-600">' + escapeHtml(d.error || 'Error') + '</p>'; return; }
      eqmBody.innerHTML = '';
      if (!d.questions || !d.questions.length) {
        eqmBody.innerHTML = '<p class="text-sm text-slate-500 font-telugu">ఇంకా ప్రశ్నలు లేవు — కుడివైపు ఫారమ్ నుండి జోడించండి</p>';
        return;
      }
      d.questions.forEach(function (q) { eqmBody.appendChild(buildQuestionRow(q)); });
    });
  }

  function buildQuestionRow(q) {
    var wrap = document.createElement('div');
    wrap.className = 'em-q-row';
    wrap.setAttribute('data-qid', q.id);
    var opts = ['A','B','C','D'];
    var html = '<div class="flex justify-between items-start gap-2 mb-2">' +
      '<span class="text-xs font-semibold text-brand">Q' + q.question_order + '</span>' +
      '<button type="button" class="em-q-del text-xs text-red-600 font-telugu">తొలగించు</button></div>' +
      '<label class="text-xs text-slate-500 font-telugu">ప్రశ్న</label>' +
      '<textarea class="admin-input w-full text-sm mb-2 em-q-text">' + escapeHtml(q.question_text || '') + '</textarea>';
    opts.forEach(function (L) {
      var col = 'option_' + L.toLowerCase();
      html += '<div class="grid grid-cols-[2rem_1fr] gap-1 mb-1 items-center">' +
        '<span class="text-xs font-bold">' + L + '</span>' +
        '<input class="admin-input text-sm em-opt-' + L + '" value="' + escapeHtml(q[col] || '') + '" /></div>';
    });
    html += '<div class="flex flex-wrap gap-3 mt-2 items-center">' +
      '<label class="text-xs font-telugu">సరైన జవాబు <select class="admin-input py-1 em-q-key">' +
      opts.map(function (L) { return '<option value="' + L + '"' + (q.correct_option === L ? ' selected' : '') + '>' + L + '</option>'; }).join('') +
      '</select></label>' +
      '<button type="button" class="admin-btn admin-btn--primary text-xs em-q-save font-telugu">సేవ్</button>' +
      '</div>';
    wrap.innerHTML = html;
    wrap.querySelector('.em-q-save').addEventListener('click', function () {
      postJson('update_question', {
        id: parseInt(q.id, 10),
        test_id: activeTestId,
        question_order: parseInt(q.question_order, 10),
        question_text: wrap.querySelector('.em-q-text').value,
        option_a: wrap.querySelector('.em-opt-A').value,
        option_b: wrap.querySelector('.em-opt-B').value,
        option_c: wrap.querySelector('.em-opt-C').value,
        option_d: wrap.querySelector('.em-opt-D').value,
        correct_option: wrap.querySelector('.em-q-key').value,
        marks: parseInt(q.marks, 10) || 1
      }).then(function (r) {
        if (r.ok) wrap.classList.add('ring-2', 'ring-emerald-300');
        else alert(r.error || 'Failed');
      });
    });
    wrap.querySelector('.em-q-del').addEventListener('click', function () {
      if (!confirm('ఈ ప్రశ్నను తొలగించాలా?')) return;
      postJson('delete_question', { id: parseInt(q.id, 10) }).then(function (r) {
        if (r.ok) { wrap.remove(); }
        else alert(r.error || 'Failed');
      });
    });
    return wrap;
  }
})();
</script>
