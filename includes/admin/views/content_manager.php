<?php
require_once dirname(__DIR__) . '/content_manager_defaults.php';

$cmReady = SchemaHelper::contentManagerEnabled();
$cmV2Ready = SchemaHelper::topicExamSuiteEnabled();
$apiUrl = admin_url('content_api.php');
$examTemplates = content_manager_exam_suite_templates();
$deepMc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['mc'] ?? ''));
$deepSc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['sc'] ?? ''));
?>
<div class="font-telugu max-w-5xl mx-auto" id="contentManagerRoot"
     data-api="<?= admin_e($apiUrl) ?>"
     data-mc="<?= admin_e($deepMc) ?>"
     data-sc="<?= admin_e($deepSc) ?>"
     data-templates="<?= admin_e(json_encode($examTemplates, JSON_UNESCAPED_UNICODE)) ?>">

  <header class="mb-8 pb-6 border-b border-[#E3E6F0]">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Content Manager</h1>
    <p class="text-sm text-slate-600 mt-1">మెయిన్ కోర్స్ → సబ్ కోర్స్ → సబ్జెక్ట్ → టాపిక్ · నోట్స్ &amp; ఎగ్జామ్ సూట్</p>
  </header>

  <?php if (!$cmReady): ?>
  <div class="cm-card px-5 py-4 text-sm text-amber-900 bg-amber-50 border-amber-200">
    <p class="font-semibold">మైగ్రేషన్ అవసరం</p>
    <code class="mt-2 block text-xs">php database/migrate_content_manager.php</code>
  </div>
  <?php else: ?>

  <?php if (!$cmV2Ready): ?>
  <p class="mb-4 text-xs text-slate-500">ఎగ్జామ్ సూట్ కోసం: <code>php database/migrate_content_manager_v2.php</code></p>
  <?php endif; ?>
  <?php if ($cmReady && !SchemaHelper::topicNotesBindEnabled()): ?>
  <p class="mb-4 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">సబ్-టాపిక్ నోట్స్ బైండ్: <code>php database/update_lms_content_core.php</code></p>
  <?php endif; ?>
  <?php if ($cmReady && !SchemaHelper::imagePathEnabled('subjects')): ?>
  <p class="mb-4 text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">కవర్ చిత్రాలు &amp; నోట్స్ టాగిల్: <code>php database/update_lms_master_v3.php</code></p>
  <?php endif; ?>

  <section class="cm-card mb-6 overflow-hidden">
    <div class="px-5 py-3 border-b border-[#E3E6F0] bg-white">
      <h2 class="text-sm font-bold text-slate-900">మెటాడేటా (తప్పనిసరి)</h2>
    </div>
    <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-white">
      <div>
        <label class="cm-label block text-xs mb-1.5">1. మెయిన్ కోర్స్</label>
        <select id="cmMainCourse" class="cm-input cm-select w-full rounded-lg px-3 py-2.5 text-sm font-medium text-slate-900"></select>
        <div class="mt-1 flex gap-2"><button type="button" id="cmAddMainCourse" class="text-[11px] font-semibold text-slate-700">+ Add</button><button type="button" id="cmDelMainCourse" class="text-[11px] font-semibold text-red-600">Delete</button></div>
      </div>
      <div>
        <label class="cm-label block text-xs mb-1.5">2. సబ్ కోర్స్</label>
        <select id="cmSubCourse" class="cm-input cm-select w-full rounded-lg px-3 py-2.5 text-sm text-slate-900" disabled></select>
        <div class="mt-1 flex gap-2"><button type="button" id="cmAddSubCourse" class="text-[11px] font-semibold text-slate-700" disabled>+ Add</button><button type="button" id="cmDelSubCourse" class="text-[11px] font-semibold text-red-600" disabled>Delete</button></div>
      </div>
      <div>
        <label class="cm-label block text-xs mb-1.5">3. సబ్జెక్ట్</label>
        <select id="cmSubject" class="cm-input cm-select w-full rounded-lg px-3 py-2.5 text-sm text-slate-900" disabled></select>
        <div class="mt-1 flex flex-wrap items-center gap-2">
          <button type="button" id="cmAddSubject" class="text-[11px] font-semibold text-slate-700" disabled>+ Add</button>
          <button type="button" id="cmDelSubject" class="text-[11px] font-semibold text-red-600" disabled>Delete</button>
          <label id="cmSubjectLiveWrap" class="hidden text-[11px] font-semibold text-slate-800 flex items-center gap-1 ml-auto font-telugu">
            <input type="checkbox" id="cmSubjectLive" class="rounded border-slate-400" /> Live / ప్రచురణ
          </label>
        </div>
      </div>
      <div>
        <label class="cm-label block text-xs mb-1.5">4. టాపిక్</label>
        <select id="cmTopic" class="cm-input cm-select w-full rounded-lg px-3 py-2.5 text-sm text-slate-900" disabled></select>
        <div class="mt-1"><button type="button" id="cmDelTopic" class="text-[11px] font-semibold text-red-600" disabled>Delete topic</button></div>
      </div>
    </div>
    <div id="cmCoverRow" class="px-5 pb-5 grid sm:grid-cols-3 gap-4 border-t border-[#E3E6F0] bg-white hidden">
      <div class="cm-media-box p-3 border border-[#E3E6F0] rounded-lg bg-white" id="cmCoverCourse" hidden>
        <p class="text-xs font-bold text-slate-900 mb-2">మెయిన్ కోర్స్ కవర్</p>
        <div class="h-28 bg-[#fafafa] border border-dashed border-[#E3E6F0] rounded flex items-center justify-center overflow-hidden mb-2"><img id="cmCoverCourseImg" alt="" class="max-h-full max-w-full object-contain hidden" /></div>
        <input type="file" accept="image/*" class="cm-cover-file text-xs w-full" data-entity="course" />
      </div>
      <div class="cm-media-box p-3 border border-[#E3E6F0] rounded-lg bg-white" id="cmCoverSub" hidden>
        <p class="text-xs font-bold text-slate-900 mb-2">సబ్ కోర్స్ కవర్</p>
        <div class="h-28 bg-[#fafafa] border border-dashed border-[#E3E6F0] rounded flex items-center justify-center overflow-hidden mb-2"><img id="cmCoverSubImg" alt="" class="max-h-full max-w-full object-contain hidden" /></div>
        <input type="file" accept="image/*" class="cm-cover-file text-xs w-full" data-entity="sub_course" />
      </div>
      <div class="cm-media-box p-3 border border-[#E3E6F0] rounded-lg bg-white" id="cmCoverSubject" hidden>
        <p class="text-xs font-bold text-slate-900 mb-2">సబ్జెక్ట్ కవర్</p>
        <div class="h-28 bg-[#fafafa] border border-dashed border-[#E3E6F0] rounded flex items-center justify-center overflow-hidden mb-2"><img id="cmCoverSubjectImg" alt="" class="max-h-full max-w-full object-contain hidden" /></div>
        <input type="file" accept="image/*" class="cm-cover-file text-xs w-full" data-entity="subject" />
      </div>
    </div>
    <div class="px-5 pb-4 flex flex-wrap gap-2 items-center border-t border-[#E3E6F0] bg-white pt-3">
      <input type="text" id="cmNewTopicTitle" placeholder="కొత్త టాపిక్ పేరు (తెలుగు/ఇంగ్లీష్)" class="cm-input flex-1 min-w-[12rem] rounded-lg px-3 py-2 text-sm" disabled />
      <button type="button" id="cmAddTopicBtn" class="px-4 py-2 text-sm font-semibold rounded-lg border border-[#E3E6F0] text-slate-500 bg-slate-50" disabled>+ టాపిక్</button>
      <span id="cmCascadeStatus" class="text-xs text-slate-400 ml-auto"></span>
    </div>
  </section>

  <div id="cmWorkspace" class="hidden space-y-5">
    <section class="cm-card p-5 bg-white border border-[#E3E6F0]">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-sm font-bold text-slate-900 font-telugu">సబ్-టాపిక్ ఎనేబుల్ చేయాలా?</p>
          <p class="text-xs text-slate-600 mt-0.5">ఆన్ → నోట్స్ సబ్-టాపిక్ ID · ఆఫ్ → మెయిన్ టాపిక్ ID</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer shrink-0">
          <input type="checkbox" id="cmHasSubTopics" class="sr-only peer" />
          <span class="w-11 h-6 bg-[#E3E6F0] rounded-full peer peer-checked:bg-slate-800 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></span>
        </label>
      </div>
      <div id="cmSubTopicManual" class="hidden mt-4 pt-4 border-t border-[#E3E6F0] space-y-3">
        <label class="cm-label block text-xs text-slate-800">సబ్-టాపిక్ పేరు</label>
        <input type="text" id="cmSubTopicName" class="cm-input w-full max-w-lg rounded-lg px-3 py-2.5 text-sm font-telugu text-slate-900 border-[#E3E6F0]" placeholder="ఉదా: నదులు, ఎడారులు..." />
        <p id="cmNotesBindHint" class="text-xs text-slate-500 hidden font-telugu">నోట్స్ ఈ సబ్-టాపిక్‌కు మాత్రమే సేవ్ అవుతాయి.</p>
        <div id="cmSubTopicsExtra" class="space-y-2"></div>
        <button type="button" id="cmAddSubTopic" class="text-xs font-semibold text-slate-700">+ మరొక సబ్-టాపిక్</button>
      </div>
    </section>

    <section class="cm-card p-5 bg-white border border-[#E3E6F0]">
      <div class="flex flex-wrap items-center justify-between gap-4 mb-3">
        <div>
          <h3 class="text-sm font-bold text-slate-900 font-telugu">టాపిక్ స్టడీ మెటీరియల్ (నోట్స్)</h3>
          <p class="text-xs text-slate-600 mt-0.5 font-telugu">ఈ టాపిక్‌కు నోట్స్ ఇవ్వాలా?</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer shrink-0">
          <input type="checkbox" id="cmNotesEnabled" class="sr-only peer" checked />
          <span class="w-11 h-6 bg-[#E3E6F0] rounded-full peer peer-checked:bg-slate-800 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></span>
        </label>
      </div>
      <div id="cmNotesPanel">
      <p class="text-xs text-slate-600 mb-2">పూర్తి పాఠ్యం / సారాంశం — అభ్యర్థి పరీక్షకు ముందు చదువుతారు.</p>
      <p id="cmNotesTargetLabel" class="text-[11px] font-semibold text-slate-800 mb-2">బైండ్: మెయిన్ టాపిక్ ID</p>
      <textarea id="cmNotesContent" rows="12" class="cm-input w-full rounded-lg px-3 py-2.5 text-sm leading-relaxed resize-y min-h-[14rem] font-telugu text-slate-900 border-[#E3E6F0]" placeholder="ఈ టాపిక్‌కు సంబంధించిన నోట్స్ మొత్తం ఇక్కడ రాయండి..."></textarea>
      <button type="button" id="cmSaveBtn" class="mt-4 px-8 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold font-telugu shadow-sm">
        డేటాబేస్‌లో సేవ్ చేయి
      </button>
      <p id="cmSaveMsg" class="text-xs text-slate-600 mt-2 min-h-[1rem]"></p>
      </div>
    </section>

    <section id="cmExamSection" class="cm-card overflow-hidden bg-white border border-[#E3E6F0]">
      <div class="px-5 py-3 border-b border-[#E3E6F0]">
        <h3 class="text-sm font-bold text-slate-900 font-telugu">ఎగ్జామ్ మేనేజ్మెంట్ సూట్</h3>
        <p class="text-xs text-slate-600 mt-0.5">ఈ టెస్ట్ అవసరమా? — ఆఫ్ చేస్తే disabled</p>
      </div>
      <div id="cmExamGrid" class="p-5 grid sm:grid-cols-2 gap-4"></div>
    </section>
  </div>

<p id="cmPickTopicHint" class="text-center text-sm text-slate-400 py-16 border border-dashed border-[#E3E6F0] rounded-xl bg-white">
    పైన కోర్స్ → సబ్ కోర్స్ → సబ్జెక్ట్ → టాపిక్ ఎంచుకోండి.
  </p>

  <?php endif; ?>
</div>

<?php if ($cmReady): ?>
<script>
(function () {
  var root = document.getElementById('contentManagerRoot');
  if (!root) return;
  var api = root.getAttribute('data-api');
  var templates = JSON.parse(root.getAttribute('data-templates') || '[]');
  var deepMc = root.getAttribute('data-mc') || '';
  var deepSc = root.getAttribute('data-sc') || '';

  var el = {
    main: document.getElementById('cmMainCourse'),
    sub: document.getElementById('cmSubCourse'),
    subject: document.getElementById('cmSubject'),
    topic: document.getElementById('cmTopic'),
    workspace: document.getElementById('cmWorkspace'),
    hint: document.getElementById('cmPickTopicHint'),
    hasSub: document.getElementById('cmHasSubTopics'),
    subManual: document.getElementById('cmSubTopicManual'),
    subName: document.getElementById('cmSubTopicName'),
    subExtra: document.getElementById('cmSubTopicsExtra'),
    addSub: document.getElementById('cmAddSubTopic'),
    notes: document.getElementById('cmNotesContent'),
    saveBtn: document.getElementById('cmSaveBtn'),
    saveMsg: document.getElementById('cmSaveMsg'),
    notesTarget: document.getElementById('cmNotesTargetLabel'),
    notesBindHint: document.getElementById('cmNotesBindHint'),
    status: document.getElementById('cmCascadeStatus'),
    newTopic: document.getElementById('cmNewTopicTitle'),
    addTopicBtn: document.getElementById('cmAddTopicBtn'),
    examSection: document.getElementById('cmExamSection'),
    examGrid: document.getElementById('cmExamGrid'),
    notesEnabled: document.getElementById('cmNotesEnabled'),
    notesPanel: document.getElementById('cmNotesPanel'),
    coverRow: document.getElementById('cmCoverRow'),
    subjectLive: document.getElementById('cmSubjectLive'),
    subjectLiveWrap: document.getElementById('cmSubjectLiveWrap'),
  };

  var state = { examSuite: [], subTopics: [], subjects: [] };

  function postJson(body) {
    return fetch(api, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    }).then(function (r) { return r.json(); });
  }

  function mediaUrl(path) {
    if (!path) return '';
    if (String(path).indexOf('http') === 0) return path;
    return (window.location.pathname.indexOf('/admin/') >= 0 ? '../' : '') + path;
  }

  function toggleNotesUi() {
    if (!el.notesPanel || !el.notesEnabled) return;
    el.notesPanel.classList.toggle('hidden', !el.notesEnabled.checked);
  }

  function updateCovers() {
    if (!el.coverRow) return;
    var mc = el.main.value, sc = el.sub.value, su = el.subject.value;
    el.coverRow.classList.toggle('hidden', !mc);
    var cBox = document.getElementById('cmCoverCourse');
    var sBox = document.getElementById('cmCoverSub');
    var uBox = document.getElementById('cmCoverSubject');
    if (cBox) cBox.hidden = !mc;
    if (sBox) sBox.hidden = !sc;
    if (uBox) uBox.hidden = !su;
    if (mc) {
      fetchJson(api + '?action=entity&entity=course&id=' + mc).then(function (d) {
        if (d.ok && d.item && d.item.image_path) {
          var img = document.getElementById('cmCoverCourseImg');
          img.src = mediaUrl(d.item.image_path);
          img.classList.remove('hidden');
        }
      });
    }
    if (sc) {
      fetchJson(api + '?action=entity&entity=sub_course&id=' + sc).then(function (d) {
        if (d.ok && d.item && d.item.image_path) {
          var img2 = document.getElementById('cmCoverSubImg');
          img2.src = mediaUrl(d.item.image_path);
          img2.classList.remove('hidden');
        }
      });
    }
    if (su) {
      fetchJson(api + '?action=entity&entity=subject&id=' + su).then(function (d) {
        if (d.ok && d.item && d.item.image_path) {
          var img3 = document.getElementById('cmCoverSubjectImg');
          img3.src = mediaUrl(d.item.image_path);
          img3.classList.remove('hidden');
        }
      });
    }
  }

  function fetchJson(url) {
    return fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }

  function fillSelect(select, items, valueKey, labelFn, placeholder) {
    select.innerHTML = '';
    var o0 = document.createElement('option');
    o0.value = '';
    o0.textContent = placeholder || '—';
    select.appendChild(o0);
    (items || []).forEach(function (it) {
      var o = document.createElement('option');
      o.value = String(it[valueKey]);
      o.textContent = labelFn(it);
      select.appendChild(o);
    });
  }

  function esc(s) {
    return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;');
  }

  function resetFrom(level) {
    if (level <= 1) { el.sub.innerHTML = '<option value="">—</option>'; el.sub.disabled = true; }
    if (level <= 2) { el.subject.innerHTML = '<option value="">—</option>'; el.subject.disabled = true; }
    if (level <= 3) {
      el.topic.innerHTML = '<option value="">—</option>'; el.topic.disabled = true;
      el.newTopic.disabled = true; el.addTopicBtn.disabled = true;
    }
    el.workspace.classList.add('hidden');
    el.hint.classList.remove('hidden');
  }

  function toggleSubUi() {
    var on = el.hasSub.checked;
    el.subManual.classList.toggle('hidden', !on);
    if (el.notesBindHint) el.notesBindHint.classList.toggle('hidden', !on);
    if (el.notesTarget) {
      el.notesTarget.textContent = on ? 'బైండ్: సబ్-టాపిక్ ID (ప్రాథమిక పేరు)' : 'బైండ్: మెయిన్ టాపిక్ ID';
    }
    if (!on) {
      el.subName.value = '';
      el.subExtra.innerHTML = '';
    }
  }

  function examRequired(row) {
    if (row.is_required !== undefined && row.is_required !== null) {
      return row.is_required !== 0 && row.is_required !== '0';
    }
    return row.is_enabled !== 0 && row.is_enabled !== '0';
  }

  function setExamCardState(card) {
    var on = card.querySelector('.es-required').checked;
    card.querySelectorAll('.es-title, .es-q').forEach(function (inp) { inp.disabled = !on; });
    card.classList.toggle('opacity-55', !on);
  }

  function examCardHtml(row, tpl) {
    row = row || {};
    tpl = tpl || {};
    var key = row.suite_key || tpl.suite_key || '';
    var titleTe = row.custom_title_te || tpl.label_te || '';
    var titleEn = row.custom_title || tpl.label_en || '';
    var req = examRequired(row);
    return '<div class="exam-suite-card rounded-lg border border-[#E3E6F0] p-4 bg-white" data-suite-key="' + esc(key) + '">' +
      '<input type="hidden" class="es-id" value="' + (row.id || '') + '" />' +
      '<input type="hidden" class="es-test-id" value="' + (row.test_id || '') + '" />' +
      '<div class="flex items-start justify-between gap-2 mb-3">' +
      '<p class="text-sm font-bold text-slate-900 font-telugu">' + esc(titleTe) + '</p>' +
      '<label class="flex items-center gap-2 text-xs font-semibold text-slate-800 shrink-0 font-telugu">' +
      '<input type="checkbox" class="es-required rounded border-slate-400" ' + (req ? 'checked' : '') + ' /> ఈ టెస్ట్ అవసరమా?</label></div>' +
      '<label class="block text-[11px] font-semibold text-slate-600 mb-1">కస్టమ్ పేరు</label>' +
      '<input type="text" class="es-title cm-input w-full rounded px-2 py-1.5 text-sm mb-2" value="' + esc(titleEn) + '" />' +
      '<label class="block text-[11px] font-semibold text-slate-600 mb-1">ప్రశ్నలు / మార్కులు (50, 100, 150…)</label>' +
      '<input type="number" class="es-q cm-input w-32 rounded px-2 py-1.5 text-sm" min="10" max="999" step="1" value="' + (row.question_count || 50) + '" />' +
      '</div>';
  }

  function renderExamSuite(list) {
    state.examSuite = list || [];
    el.examGrid.innerHTML = '';
    var byKey = {};
    state.examSuite.forEach(function (r) { byKey[r.suite_key] = r; });
    templates.forEach(function (tpl) {
      el.examGrid.insertAdjacentHTML('beforeend', examCardHtml(byKey[tpl.suite_key] || tpl, tpl));
    });
    el.examGrid.querySelectorAll('.exam-suite-card').forEach(function (card) {
      setExamCardState(card);
      card.querySelector('.es-required').onchange = function () { setExamCardState(card); };
    });
  }

  function collectExamSuite() {
    var out = [];
    el.examGrid.querySelectorAll('.exam-suite-card').forEach(function (card, i) {
      var tpl = templates.find(function (t) { return t.suite_key === card.getAttribute('data-suite-key'); }) || {};
      out.push({
        id: card.querySelector('.es-id').value ? parseInt(card.querySelector('.es-id').value, 10) : 0,
        test_id: card.querySelector('.es-test-id').value ? parseInt(card.querySelector('.es-test-id').value, 10) : 0,
        suite_key: card.getAttribute('data-suite-key'),
        custom_title: card.querySelector('.es-title').value.trim(),
        custom_title_te: tpl.label_te || '',
        question_count: parseInt(card.querySelector('.es-q').value, 10) || 50,
        total_marks: parseInt(card.querySelector('.es-q').value, 10) || 50,
        is_required: card.querySelector('.es-required').checked ? 1 : 0,
        is_enabled: card.querySelector('.es-required').checked ? 1 : 0,
        sort_order: i,
      });
    });
    return out;
  }

  function extraSubRowHtml(st) {
    st = st || {};
    return '<div class="flex gap-2 sub-extra-row"><input type="hidden" class="st-id" value="' + (st.id || '') + '" />' +
      '<input type="text" class="st-name cm-input flex-1 rounded px-2 py-1.5 text-sm font-telugu" value="' + esc(st.sub_topic_name || '') + '" placeholder="సబ్-టాపిక్" />' +
      '<button type="button" class="st-remove text-xs text-red-600 px-2">×</button></div>';
  }

  function collectSubTopics() {
    var rows = [];
    var main = el.subName.value.trim();
    var notes = el.notes.value;
    if (main) {
      rows.push({
        id: 0,
        sub_topic_name: main,
        sub_topic_name_te: main,
        question_count: 50,
        sub_notes_content: notes,
      });
    }
    el.subExtra.querySelectorAll('.sub-extra-row').forEach(function (row) {
      var n = row.querySelector('.st-name').value.trim();
      if (!n) return;
      rows.push({
        id: row.querySelector('.st-id').value ? parseInt(row.querySelector('.st-id').value, 10) : 0,
        sub_topic_name: n,
        sub_topic_name_te: n,
        question_count: 50,
        sub_notes_content: '',
      });
    });
    if (state.subTopics.length && rows.length === 1 && state.subTopics[0].id) {
      rows[0].id = state.subTopics[0].id;
    }
    return rows;
  }

  function loadTopicConfig(topicId) {
    el.status.textContent = 'లోడ్…';
    return fetchJson(api + '?action=topic&topic_id=' + topicId).then(function (d) {
      el.status.textContent = '';
      if (!d.ok || !d.topic) throw new Error(d.error || 'Load failed');
      var t = d.topic;
      el.workspace.classList.remove('hidden');
      el.hint.classList.add('hidden');
      el.hasSub.checked = !!parseInt(t.has_sub_topics, 10);
      if (el.notesEnabled) {
        el.notesEnabled.checked = t.notes_enabled === undefined || parseInt(t.notes_enabled, 10) !== 0;
        toggleNotesUi();
      }
      el.notes.value = t.notes_content || '';
      state.subTopics = t.sub_topics || [];
      if (state.subTopics.length) {
        el.subName.value = state.subTopics[0].sub_topic_name_te || state.subTopics[0].sub_topic_name || '';
        el.subExtra.innerHTML = '';
        state.subTopics.slice(1).forEach(function (st) {
          el.subExtra.insertAdjacentHTML('beforeend', extraSubRowHtml(st));
        });
      } else {
        el.subName.value = '';
        el.subExtra.innerHTML = '';
      }
      renderExamSuite(t.exam_suite || templates);
      toggleSubUi();
      bindSubRemoves();
    });
  }

  function bindSubRemoves() {
    el.subExtra.querySelectorAll('.st-remove').forEach(function (btn) {
      btn.onclick = function () { btn.closest('.sub-extra-row').remove(); };
    });
  }

  function chainSelect(select, url, nextEl, labelFn, then) {
    return fetchJson(url).then(function (d) {
      fillSelect(select, d.items, 'id', labelFn);
      select.disabled = false;
      if (then) then(d);
    });
  }

  el.main.addEventListener('change', function () {
    resetFrom(1);
    var id = el.main.value;
    document.getElementById('cmAddSubCourse').disabled = !id;
    updateCovers();
    if (!id) return;
    chainSelect(el.sub, api + '?action=sub_courses&course_id=' + id, el.sub, function (s) { return s.name_te || s.name; });
  });

  el.sub.addEventListener('change', function () {
    resetFrom(2);
    var id = el.sub.value;
    document.getElementById('cmAddSubject').disabled = !id;
    document.getElementById('cmDelSubCourse').disabled = !id;
    updateCovers();
    if (!id) return;
    chainSelect(el.subject, api + '?action=subjects&sub_course_id=' + id, el.subject, function (s) { return s.name_te || s.name; }, function (d) {
      state.subjects = d.items || [];
    });
  });

  el.subject.addEventListener('change', function () {
    resetFrom(3);
    var id = el.subject.value;
    document.getElementById('cmDelTopic').disabled = !id;
    document.getElementById('cmDelSubject').disabled = !id;
    if (el.subjectLiveWrap) el.subjectLiveWrap.classList.toggle('hidden', !id);
    var row = (state.subjects || []).find(function (s) { return String(s.id) === String(id); });
    if (el.subjectLive && row) el.subjectLive.checked = parseInt(row.is_live, 10) !== 0;
    updateCovers();
    if (!id) return;
    chainSelect(el.topic, api + '?action=topics&subject_id=' + id, el.topic, function (t) { return t.title_te || t.title; }, function () {
      el.newTopic.disabled = false;
      el.addTopicBtn.disabled = false;
    });
  });

  el.topic.addEventListener('change', function () {
    var id = el.topic.value;
    if (!id) { resetFrom(4); return; }
    loadTopicConfig(id);
  });

  el.hasSub.addEventListener('change', toggleSubUi);
  if (el.notesEnabled) el.notesEnabled.addEventListener('change', toggleNotesUi);
  toggleNotesUi();

  document.querySelectorAll('.cm-cover-file').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var entity = inp.getAttribute('data-entity');
      var idMap = { course: el.main.value, sub_course: el.sub.value, subject: el.subject.value };
      var eid = idMap[entity];
      if (!eid || !inp.files || !inp.files[0]) return;
      var fd = new FormData();
      fd.append('action', 'upload_image');
      fd.append('entity', entity);
      fd.append('id', eid);
      fd.append('image_file', inp.files[0]);
      fetch(api, { method: 'POST', credentials: 'same-origin', body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.ok) throw new Error(d.error || 'Upload failed');
          updateCovers();
        }).catch(function (e) { alert(e.message); });
    });
  });

  if (el.subjectLive) {
    el.subjectLive.addEventListener('change', function () {
      var scid = parseInt(el.sub.value, 10), sid = parseInt(el.subject.value, 10);
      if (!scid || !sid) return;
      postJson({ action: 'set_subject_live', sub_course_id: scid, subject_id: sid, is_live: el.subjectLive.checked ? 1 : 0 });
    });
  }

  document.getElementById('cmAddMainCourse').addEventListener('click', function () {
    var name = prompt('Main course name');
    if (!name) return;
    postJson({ action: 'save_main_course', name: name, is_active: 1 }).then(function (d) {
      if (!d.ok) throw new Error(d.error);
      return loadMainCourses().then(function () { el.main.value = String(d.id); el.main.dispatchEvent(new Event('change')); });
    }).catch(function (e) { alert(e.message); });
  });
  document.getElementById('cmDelMainCourse').addEventListener('click', function () {
    var id = parseInt(el.main.value, 10);
    if (!id || !confirm('Delete main course?')) return;
    postJson({ action: 'delete_main_course', id: id }).then(function (d) {
      if (!d.ok) throw new Error(d.error);
      loadMainCourses();
    }).catch(function (e) { alert(e.message); });
  });
  document.getElementById('cmAddSubCourse').addEventListener('click', function () {
    var cid = parseInt(el.main.value, 10), name = prompt('Sub-course name');
    if (!cid || !name) return;
    postJson({ action: 'save_sub_course', course_id: cid, name: name, is_active: 1 }).then(function (d) {
      if (!d.ok) throw new Error(d.error);
      el.main.dispatchEvent(new Event('change'));
      setTimeout(function () { el.sub.value = String(d.id); el.sub.dispatchEvent(new Event('change')); }, 300);
    }).catch(function (e) { alert(e.message); });
  });
  document.getElementById('cmDelSubCourse').addEventListener('click', function () {
    var id = parseInt(el.sub.value, 10);
    if (!id || !confirm('Delete sub-course?')) return;
    postJson({ action: 'delete_sub_course', id: id }).then(function () { el.main.dispatchEvent(new Event('change')); });
  });
  document.getElementById('cmAddSubject').addEventListener('click', function () {
    var scid = parseInt(el.sub.value, 10), name = prompt('Subject name');
    if (!scid || !name) return;
    postJson({ action: 'save_subject', sub_course_id: scid, name: name, is_active: 1 }).then(function (d) {
      if (!d.ok) throw new Error(d.error);
      el.sub.dispatchEvent(new Event('change'));
      setTimeout(function () { el.subject.value = String(d.id); el.subject.dispatchEvent(new Event('change')); }, 300);
    }).catch(function (e) { alert(e.message); });
  });
  document.getElementById('cmDelSubject').addEventListener('click', function () {
    var id = parseInt(el.subject.value, 10);
    if (!id || !confirm('Delete subject?')) return;
    postJson({ action: 'delete_subject', id: id }).then(function () { el.sub.dispatchEvent(new Event('change')); });
  });
  document.getElementById('cmDelTopic').addEventListener('click', function () {
    var id = parseInt(el.topic.value, 10);
    if (!id || !confirm('Delete topic?')) return;
    postJson({ action: 'delete_topic', topic_id: id }).then(function () {
      el.subject.dispatchEvent(new Event('change'));
      el.workspace.classList.add('hidden');
      el.hint.classList.remove('hidden');
    });
  });
  el.addSub.addEventListener('click', function () {
    el.subExtra.insertAdjacentHTML('beforeend', extraSubRowHtml({}));
    bindSubRemoves();
  });

  el.addTopicBtn.addEventListener('click', function () {
    var sid = el.subject.value, title = el.newTopic.value.trim();
    if (!sid || !title) return;
    fetch(api, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'create_topic', subject_id: parseInt(sid, 10), title: title })
    }).then(function (r) { return r.json(); }).then(function (d) {
      if (!d.ok) throw new Error(d.error);
      el.newTopic.value = '';
      return fetchJson(api + '?action=topics&subject_id=' + sid).then(function (td) {
        fillSelect(el.topic, td.items, 'id', function (t) { return t.title_te || t.title; });
        el.topic.value = String(d.topic_id);
        loadTopicConfig(d.topic_id);
      });
    }).catch(function (e) { alert(e.message); });
  });

  el.saveBtn.addEventListener('click', function () {
    var topicId = parseInt(el.topic.value, 10);
    if (!topicId) return;
    if (el.hasSub.checked && !el.subName.value.trim()) {
      el.saveMsg.textContent = 'సబ్-టాపిక్ పేరు నమోదు చేయండి.';
      return;
    }
    el.saveBtn.disabled = true;
    el.saveMsg.textContent = 'సేవ్…';
    var payload = {
      action: 'save_topic_config',
      topic_id: topicId,
      has_sub_topics: el.hasSub.checked ? 1 : 0,
      notes_enabled: el.notesEnabled && el.notesEnabled.checked ? 1 : 0,
      question_count: 50,
      notes_content: el.notesEnabled && el.notesEnabled.checked ? el.notes.value : '',
      sub_topics: el.hasSub.checked ? collectSubTopics() : [],
      exam_suite: collectExamSuite(),
    };
    fetch(api, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    }).then(function (r) { return r.json(); }).then(function (d) {
      el.saveBtn.disabled = false;
      if (!d.ok) throw new Error(d.error);
      el.saveMsg.textContent = '✓ సేవ్ పూర్తయింది';
      setTimeout(function () { el.saveMsg.textContent = ''; }, 4000);
      return loadTopicConfig(topicId);
    }).catch(function (e) {
      el.saveBtn.disabled = false;
      el.saveMsg.textContent = e.message;
    });
  });

  function loadMainCourses() {
    return fetchJson(api + '?action=main_courses').then(function (d) {
      if (!d.ok) throw new Error(d.error || 'Failed');
      fillSelect(el.main, d.items, 'id', function (c) { return (c.name_te ? c.name_te + ' · ' : '') + c.name; }, '-- సెలెక్ట్ --');
    });
  }

  function bootDeepLink() {
    if (!deepMc || !deepSc) return Promise.resolve();
    return fetchJson(api + '?action=resolve_programme&mc=' + encodeURIComponent(deepMc) + '&sc=' + encodeURIComponent(deepSc))
      .then(function (d) {
        if (!d.ok) return;
        el.main.value = String(d.course_id);
        return chainSelect(el.sub, api + '?action=sub_courses&course_id=' + d.course_id, el.sub, function (s) { return s.name_te || s.name; })
          .then(function () {
            el.sub.value = String(d.sub_course_id);
            return chainSelect(el.subject, api + '?action=subjects&sub_course_id=' + d.sub_course_id, el.subject, function (s) { return s.name_te || s.name; });
          });
      });
  }

  loadMainCourses().then(bootDeepLink).catch(function (e) { el.status.textContent = e.message; });
})();
</script>
<?php endif; ?>
