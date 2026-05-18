<?php
/** @var bool $isAdmin @var bool $isExaminer @var ?string $assignedSubject @var string $apiUrl */
?>
<div id="mcqEngineRoot" class="max-w-[100rem] mx-auto pb-12 font-telugu"
     data-api="<?= admin_e($apiUrl) ?>"
     data-is-admin="<?= $isAdmin ? '1' : '0' ?>"
     data-examiner-subject="<?= admin_e($assignedSubject ?? '') ?>">

  <nav class="mcq-classic-nav" role="tablist" aria-label="MCQ Generator sections">
    <button type="button" class="mcq-classic-tab is-active" data-tab="segments" role="tab" aria-selected="true">PDF Segments</button>
    <button type="button" class="mcq-classic-tab" data-tab="generate" role="tab" aria-selected="false">AI Generator</button>
    <button type="button" class="mcq-classic-tab" data-tab="staging" role="tab" aria-selected="false">Examiner Review</button>
    <?php if ($isAdmin): ?>
    <button type="button" class="mcq-classic-tab" data-tab="subjects" role="tab" aria-selected="false">Subject Manager</button>
    <button type="button" class="mcq-classic-tab" data-tab="super" role="tab" aria-selected="false">Super-Approval</button>
    <button type="button" class="mcq-classic-tab" data-tab="apislots" role="tab" aria-selected="false">AI API Slots</button>
    <button type="button" class="mcq-classic-tab" data-tab="examiners" role="tab" aria-selected="false">Examiners Management</button>
    <?php endif; ?>
  </nav>

  <!-- PDF Segments -->
  <section class="mcq-panel" data-panel="segments">
    <div class="admin-card p-5 mb-4">
      <h2 class="text-sm font-bold text-slate-900">PDF Topic Segmenter</h2>
      <p class="text-xs text-slate-500 mt-1">Upload textbook PDF · map page ranges to lesson labels · link sub-course</p>
      <form id="mcqPdfUpload" class="mt-4 flex flex-wrap gap-3 items-end">
        <label class="text-xs">PDF file<input type="file" name="pdf" accept=".pdf" class="admin-input block mt-1" required /></label>
        <button type="submit" class="admin-btn admin-btn-primary text-xs">Upload PDF</button>
        <span id="mcqPdfUploadMsg" class="text-xs text-slate-500"></span>
      </form>
      <form id="mcqSegmentForm" class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
        <input type="hidden" name="id" id="segId" />
        <input type="hidden" name="storage_path" id="segStorage" />
        <label class="text-xs col-span-2">PDF name<input id="segPdfName" class="admin-input w-full mt-1" required /></label>
        <label class="text-xs col-span-2">Topic / Lesson<input id="segTopic" class="admin-input w-full mt-1" placeholder="Lesson 1: Amma Odi" required /></label>
        <label class="text-xs">Start page<input type="number" id="segStart" class="admin-input w-full mt-1" min="1" value="1" /></label>
        <label class="text-xs">End page<input type="number" id="segEnd" class="admin-input w-full mt-1" min="1" value="11" /></label>
        <label class="text-xs">Subject
          <select id="segSubject" class="admin-input w-full mt-1" <?= $isExaminer ? 'disabled' : '' ?>>
            <option value="">—</option>
          </select>
        </label>
        <label class="text-xs">Sub-course
          <select id="segSubCourse" class="admin-input w-full mt-1"><option value="">—</option></select>
        </label>
        <div class="sm:col-span-4 flex gap-2">
          <button type="submit" class="admin-btn admin-btn-primary text-xs">Save segment</button>
          <button type="button" id="segReset" class="admin-btn admin-btn-secondary text-xs">Reset</button>
        </div>
      </form>
    </div>
    <div class="admin-card overflow-hidden">
      <div class="admin-table-wrap overflow-x-auto">
        <table class="admin-table text-xs min-w-[48rem]">
          <thead><tr class="bg-slate-50">
            <th>Topic</th><th>PDF</th><th>Pages</th><th>Subject</th><th>Sub-course</th><th></th>
          </tr></thead>
          <tbody id="segTableBody"><tr><td colspan="6" class="p-6 text-center text-slate-500">Loading…</td></tr></tbody>
        </table>
      </div>
    </div>
  </section>

  <!-- AI Generate -->
  <section class="mcq-panel hidden" data-panel="generate">
    <div class="admin-card p-5">
      <h2 class="text-sm font-bold text-slate-900">Asynchronous AI MCQ Generation</h2>
      <p class="text-xs text-slate-500 mt-1">AP / TS DSC &amp; TET · difficulty tier drives cognitive depth · large counts auto-split per page</p>
      <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
        <label class="text-xs font-semibold text-slate-700">PDF segment
          <select id="genSegment" class="admin-input w-full mt-1.5"></select>
        </label>
        <label class="text-xs font-semibold text-slate-700">Difficulty level
          <select id="genScale" name="difficulty_level" class="admin-input w-full mt-1.5"></select>
        </label>
        <label class="text-xs font-semibold text-slate-700 col-span-2 lg:col-span-1">Questions per page
          <input type="number" id="genQpp" name="questions_per_page" class="admin-input w-full mt-1.5" min="1" step="1" value="3" inputmode="numeric" />
          <span id="genQppHint" class="block text-[10px] text-slate-500 mt-1 leading-snug">Unlimited entry · auto-chunked for quality</span>
        </label>
      </div>
      <p id="genScaleDesc" class="text-xs text-slate-600 mt-3 p-3 rounded-lg bg-slate-50 border border-slate-200 leading-relaxed hidden"></p>
      <?php if ($isAdmin): ?>
      <div class="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-100">
        <p class="text-xs font-bold text-slate-700 mb-2">Excel column mapper (optional JSON)</p>
        <textarea id="genExcelMap" class="admin-input w-full text-xs font-mono h-16" placeholder='{"question":"A","option_b":"B"}'></textarea>
      </div>
      <?php endif; ?>
      <div class="flex flex-wrap gap-2 mt-4">
        <button type="button" id="genStart" class="admin-btn admin-btn-primary text-xs">Start job</button>
        <button type="button" id="genResume" class="admin-btn admin-btn-secondary text-xs" disabled>Resume / Next page</button>
        <button type="button" id="genRetry" class="admin-btn admin-btn-ghost text-xs" disabled>Retry failed page</button>
      </div>
      <div id="genProgress" class="mt-4 space-y-2 text-xs"></div>
    </div>
  </section>

  <!-- Examiner staging -->
  <section class="mcq-panel hidden" data-panel="staging">
    <div class="admin-card p-5">
      <div class="flex justify-between items-center gap-3 mb-3">
        <h2 class="text-sm font-bold">Staging · raw_ai</h2>
        <button type="button" id="stgRefresh" class="admin-btn admin-btn-ghost text-xs">Refresh</button>
      </div>
      <div id="stgRawList" class="space-y-3 max-h-[32rem] overflow-y-auto"></div>
      <button type="button" id="stgApproveSection" class="admin-btn admin-btn-primary text-xs mt-4">Approve Section (examiner)</button>
    </div>
  </section>

  <?php if ($isAdmin): ?>
  <section class="mcq-panel hidden" data-panel="subjects">
    <div class="admin-card p-5 mb-4">
      <h2 class="text-sm font-bold text-slate-900 font-telugu">Dynamic Subject & Sub-Course Mapping</h2>
      <p class="text-xs text-slate-500 mt-1">Add global exam subjects once · map to multiple sub-courses · no duplicate names</p>
      <form id="catalogSubjectForm" class="mt-4 space-y-3">
        <label class="text-xs block font-telugu">కొత్త విషయం / Subject name
          <input type="text" id="catSubjectName" class="admin-input w-full max-w-md mt-1" placeholder="Psychology, Current Affairs…" required />
        </label>
        <div>
          <p class="text-xs font-semibold text-slate-700 mb-2 font-telugu">సబ్-కోర్సులు లింక్ చేయండి (multi-select)</p>
          <div id="catSubCourseChecks" class="grid sm:grid-cols-2 lg:grid-cols-3 gap-2 max-h-48 overflow-y-auto border border-slate-100 rounded-lg p-3 bg-slate-50/80"></div>
        </div>
        <button type="submit" class="admin-btn admin-btn-primary text-xs font-telugu">Add subject & map</button>
        <p id="catSubjectMsg" class="text-xs text-slate-500"></p>
      </form>
    </div>
    <div class="admin-card overflow-hidden">
      <div class="admin-card-header">
        <h3 class="text-xs font-bold text-slate-800 font-telugu">Existing subjects (lookup before adding)</h3>
      </div>
      <div class="admin-table-wrap overflow-x-auto">
        <table class="admin-table text-xs min-w-[40rem]">
          <thead><tr class="bg-slate-50">
            <th class="text-left font-telugu">విషయం</th>
            <th class="text-left font-telugu">లింక్ చేసిన సబ్-కోర్సులు</th>
            <th class="text-right font-telugu">చర్య</th>
          </tr></thead>
          <tbody id="catSubjectTableBody">
            <tr><td colspan="3" class="p-6 text-center text-slate-500">Loading…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </section>

  <section class="mcq-panel hidden" data-panel="super">
    <div class="admin-card p-5">
      <h2 class="text-sm font-bold">Pending Super-Approval</h2>
      <label class="text-xs block mt-3">Deploy to test ID<input type="number" id="superTestId" class="admin-input w-40 mt-1" min="1" /></label>
      <div id="stgSuperList" class="space-y-3 max-h-[28rem] overflow-y-auto mt-3"></div>
      <button type="button" id="stgDeploy" class="admin-btn admin-btn-primary text-xs mt-4">Approve & Deploy</button>
    </div>
  </section>

  <section class="mcq-panel hidden" data-panel="examiners">
    <div class="admin-card p-5">
      <h2 class="text-sm font-bold">Examiner RBAC</h2>
      <form id="examinerForm" class="grid sm:grid-cols-2 gap-3 mt-3">
        <input type="hidden" id="exId" />
        <label class="text-xs">Email<input type="email" id="exEmail" class="admin-input w-full mt-1" required /></label>
        <label class="text-xs">Password<input type="password" id="exPass" class="admin-input w-full mt-1" placeholder="Leave blank to keep" /></label>
        <label class="text-xs">Subject<select id="exSubject" class="admin-input w-full mt-1"><option value="">— Select subject —</option></select></label>
        <label class="text-xs">Status<select id="exStatus" class="admin-input w-full mt-1"><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
        <div class="sm:col-span-2"><button type="submit" class="admin-btn admin-btn-primary text-xs">Save examiner</button></div>
      </form>
      <tbody id="exTable" class="block mt-4 text-xs"></tbody>
    </div>
  </section>

  <section class="mcq-panel hidden" data-panel="apislots">
    <div class="admin-card p-5">
      <h2 class="text-sm font-bold">Multi-API Slots (AES-256 encrypted keys)</h2>
      <div id="apiSlotsGrid" class="grid sm:grid-cols-2 gap-3 mt-4"></div>
      <button type="button" id="apiSlotsSave" class="admin-btn admin-btn-primary text-xs mt-4">Save slots</button>
    </div>
  </section>
  <?php endif; ?>
</div>

<style>
.mcq-classic-nav {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 1.5rem;
  padding: 0.75rem;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
}
.mcq-classic-tab {
  appearance: none;
  font-family: inherit;
  font-size: 0.75rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  line-height: 1.25;
  padding: 0.625rem 1rem;
  border: 1px solid #94a3b8;
  border-radius: 0.375rem;
  background: #fff;
  color: #334155;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
}
.mcq-classic-tab:hover {
  background: #f8fafc;
  border-color: #64748b;
  color: #1e293b;
}
.mcq-classic-tab:focus-visible {
  outline: 2px solid #0d9488;
  outline-offset: 2px;
}
.mcq-classic-tab.is-active {
  background: #1e3a5f;
  border-color: #1e3a5f;
  color: #f8fafc;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.12), 0 1px 3px rgba(15, 23, 42, 0.2);
}
.mcq-classic-tab.is-active:hover {
  background: #243f66;
  border-color: #243f66;
  color: #fff;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>
<script>
(function () {
  const root = document.getElementById('mcqEngineRoot');
  if (!root) return;
  const API = root.dataset.api;
  const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const isAdmin = root.dataset.isAdmin === '1';
  let currentJobId = 0;
  let selectedStg = new Set();
  let segmentCache = [];
  let difficultyScales = [];
  let batchChunkSize = 12;
  let maxQuestionsPerPage = 500;

  function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

  function fetchApi(url, options) {
    options = options || {};
    options.credentials = 'same-origin';
    options.headers = options.headers || {};
    options.headers['Accept'] = 'application/json';
    if (options.body && !(options.body instanceof FormData)) {
      options.headers['Content-Type'] = 'application/json';
      options.headers['X-CSRF-Token'] = CSRF;
    }
    return fetch(url, options).then(function (res) {
      return res.text().then(function (text) {
        if (!res.ok) {
          console.error('[MCQ API] HTTP', res.status, url, text.slice(0, 500));
          var err = new Error('HTTP ' + res.status + ': ' + (text.slice(0, 200) || res.statusText));
          err.status = res.status;
          err.body = text;
          throw err;
        }
        if (!text || text.trim() === '') {
          console.error('[MCQ API] Empty body', res.status, url);
          throw new Error('Empty response from server');
        }
        try {
          return JSON.parse(text);
        } catch (e) {
          console.error('[MCQ API] Invalid JSON', res.status, url, text.slice(0, 500));
          throw new Error('Invalid JSON (login redirect or PHP error?)');
        }
      });
    }).catch(function (err) {
      if (err && err.status) throw err;
      console.error('[MCQ API] Network error', url, err);
      throw err;
    });
  }

  function post(action, body, isForm) {
    if (isForm) {
      body.append('action', action);
      body.append('_csrf', CSRF);
      return fetchApi(API, { method: 'POST', body: body });
    }
    body = body || {};
    body.action = action;
    body._csrf = CSRF;
    return fetchApi(API, { method: 'POST', body: JSON.stringify(body) });
  }
  function get(action, params) {
    const q = new URLSearchParams(params || {}); q.set('action', action);
    return fetchApi(API + '?' + q, { method: 'GET' });
  }

  function loadDashboardData() {
    console.log('[MCQ] loadDashboardData →', API);
    return get('bootstrap').then(function (d) {
      if (!d.ok) throw new Error(d.error || 'bootstrap failed');
      if (d.catalog_subjects) populateSubjectSelects(d.catalog_subjects);
      if (d.difficulty_scales) populateDifficultyScales(d.difficulty_scales);
      if (d.batch_chunk_size) batchChunkSize = d.batch_chunk_size;
      if (d.max_questions_per_page) maxQuestionsPerPage = d.max_questions_per_page;
      updateQppHint();
      return get('sub_courses');
    }).then(function (d) {
      if (d.ok) {
        subCourseItems = d.items || [];
        const sel = document.getElementById('segSubCourse');
        const opts = subCourseItems.map(function (r) {
          return '<option value="' + r.id + '">' + esc((r.course_name || '') + ' — ' + (r.name_te || r.name)) + '</option>';
        }).join('');
        if (sel) sel.innerHTML = '<option value="">—</option>' + opts;
        renderSubCourseChecks('catSubCourseChecks');
      }
      return loadSegments();
    }).then(function () {
      if (isAdmin) return loadCatalogSubjects();
    }).catch(function (err) {
      console.error('[MCQ] Dashboard load failed:', err.message, err.status || '', err.body ? err.body.slice(0, 300) : '');
      var tb = document.getElementById('segTableBody');
      if (tb) {
        tb.innerHTML = '<tr><td colspan="6" class="text-red-600 p-4 font-telugu">' + esc(err.message)
          + ' — <a href="/admin/login.php" class="underline">Sign in again</a></td></tr>';
      }
    });
  }

  function populateDifficultyScales(scales) {
    difficultyScales = scales || [];
    const sel = document.getElementById('genScale');
    if (!sel) return;
    sel.innerHTML = difficultyScales.map(function (s) {
      return '<option value="' + esc(s.code) + '">' + esc(s.label) + '</option>';
    }).join('') || '<option value="SGT">SGT</option><option value="SA">SA</option><option value="TGT">TGT</option><option value="PGT">PGT</option>';
    updateScaleDescription();
  }

  function updateScaleDescription() {
    const sel = document.getElementById('genScale');
    const box = document.getElementById('genScaleDesc');
    if (!sel || !box) return;
    const code = sel.value;
    const meta = difficultyScales.find(function (s) { return s.code === code; });
    if (meta && meta.exam_context) {
      box.textContent = meta.exam_context;
      box.classList.remove('hidden');
    } else {
      box.classList.add('hidden');
    }
  }

  function updateQppHint() {
    const hint = document.getElementById('genQppHint');
    if (hint) {
      hint.textContent = 'Unlimited entry (up to ' + maxQuestionsPerPage + ') · auto-chunked in batches of ' + batchChunkSize;
    }
    const qpp = document.getElementById('genQpp');
    if (qpp) qpp.removeAttribute('max');
  }

  document.getElementById('genScale')?.addEventListener('change', updateScaleDescription);

  document.querySelectorAll('.mcq-classic-tab').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('.mcq-classic-tab').forEach(function (b) {
        b.classList.remove('is-active');
        b.setAttribute('aria-selected', 'false');
      });
      btn.classList.add('is-active');
      btn.setAttribute('aria-selected', 'true');
      const tab = btn.dataset.tab;
      document.querySelectorAll('.mcq-panel').forEach(function (p) {
        p.classList.toggle('hidden', p.dataset.panel !== tab);
      });
      if (tab === 'staging') loadStaging('raw_ai', 'stgRawList');
      if (tab === 'super') loadStaging('examiner_approved', 'stgSuperList');
      if (tab === 'subjects') loadCatalogSubjects();
    });
  });

  let subCourseItems = [];
  let catalogSubjectNames = [];

  function populateSubjectSelects(names) {
    catalogSubjectNames = names || [];
    const opts = catalogSubjectNames.map(n => '<option value="' + esc(n) + '">' + esc(n) + '</option>').join('');
    const ex = document.getElementById('exSubject');
    const seg = document.getElementById('segSubject');
    const locked = root.dataset.examinerSubject || '';
    if (ex) ex.innerHTML = '<option value="">— Select subject —</option>' + opts;
    if (seg) {
      seg.innerHTML = '<option value="">—</option>' + opts;
      if (locked) seg.value = locked;
    }
  }

  function renderSubCourseChecks(containerId) {
    const box = document.getElementById(containerId);
    if (!box) return;
    box.innerHTML = subCourseItems.map(r => {
      const lab = esc((r.course_name || '') + ' — ' + (r.name_te || r.name));
      return '<label class="flex items-center gap-2 text-xs cursor-pointer"><input type="checkbox" class="cat-sc-cb rounded" value="' + r.id + '" /> ' + lab + '</label>';
    }).join('') || '<p class="text-slate-500 text-xs">No sub-courses</p>';
  }

  function loadSubCourses() {
    get('sub_courses').then(d => {
      if (!d.ok) return;
      subCourseItems = d.items || [];
      const sel = document.getElementById('segSubCourse');
      const opts = subCourseItems.map(r => '<option value="' + r.id + '">' + esc((r.course_name||'') + ' — ' + (r.name_te||r.name)) + '</option>').join('');
      if (sel) sel.innerHTML = '<option value="">—</option>' + opts;
      renderSubCourseChecks('catSubCourseChecks');
    });
  }

  function loadCatalogSubjects() {
    get('catalog_subjects_list').then(d => {
      const tb = document.getElementById('catSubjectTableBody');
      if (!d.ok) {
        if (tb) tb.innerHTML = '<tr><td colspan="3" class="text-red-600 p-4">' + esc(d.error) + '</td></tr>';
        return;
      }
      populateSubjectSelects(d.names || []);
      if (!tb) return;
      const items = d.items || [];
      tb.innerHTML = items.length ? items.map(s => {
        const maps = (s.sub_course_labels || []).join(', ') || '<span class="text-slate-400">— none —</span>';
        return '<tr class="align-top"><td class="font-semibold">' + esc(s.subject_name) + '</td><td class="text-slate-600 text-[11px] leading-relaxed">' + maps + '</td><td class="text-right whitespace-nowrap"><button type="button" class="text-red-600 text-xs font-telugu cat-sub-del" data-id="' + s.id + '" data-name="' + esc(s.subject_name) + '">Delete / Remove</button></td></tr>';
      }).join('') : '<tr><td colspan="3" class="p-4 text-slate-500 font-telugu">No subjects yet</td></tr>';
      tb.querySelectorAll('.cat-sub-del').forEach(btn => {
        btn.addEventListener('click', () => {
          const nm = btn.getAttribute('data-name') || '';
          const msg = 'Delete subject “' + nm + '”? This will unlink all sub-course mappings. Examiners using this subject must be reassigned first.';
          if (!confirm(msg)) return;
          post('catalog_subject_delete', { id: +btn.dataset.id }).then(r => {
            if (!r.ok) alert(r.error);
            else loadCatalogSubjects();
          });
        });
      });
    });
  }

  document.getElementById('catalogSubjectForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const ids = [];
    document.querySelectorAll('#catSubCourseChecks .cat-sc-cb:checked').forEach(cb => ids.push(+cb.value));
    const msg = document.getElementById('catSubjectMsg');
    if (msg) msg.textContent = 'Saving…';
    post('catalog_subject_save', { subject_name: document.getElementById('catSubjectName').value, sub_course_ids: ids }).then(d => {
      if (!d.ok) { if (msg) msg.textContent = d.error; return; }
      if (msg) msg.textContent = '✓ Added';
      document.getElementById('catSubjectName').value = '';
      document.querySelectorAll('#catSubCourseChecks .cat-sc-cb').forEach(cb => { cb.checked = false; });
      loadCatalogSubjects();
    });
  });

  function loadSegments() {
    get('segments_list').then(d => {
      const tb = document.getElementById('segTableBody');
      const gen = document.getElementById('genSegment');
      if (!d.ok) { tb.innerHTML = '<tr><td colspan="6" class="text-red-600 p-4">' + esc(d.error) + '</td></tr>'; return; }
      segmentCache = d.items || [];
      gen.innerHTML = segmentCache.map(s => '<option value="' + s.id + '">' + esc(s.topic_name) + ' (p' + s.start_page + '-' + s.end_page + ')</option>').join('');
      tb.innerHTML = segmentCache.length ? segmentCache.map(s => '<tr><td class="font-semibold">' + esc(s.topic_name) + '</td><td>' + esc(s.pdf_name) + '</td><td>' + s.start_page + '–' + s.end_page + '</td><td>' + esc(s.assigned_subject) + '</td><td>' + esc(s.sub_course_name||'—') + '</td><td class="text-right"><button type="button" class="text-indigo-600 text-xs seg-edit" data-id="' + s.id + '">Edit</button> <button type="button" class="text-red-600 text-xs seg-del" data-id="' + s.id + '">Del</button></td></tr>').join('') : '<tr><td colspan="6" class="p-4 text-slate-500">No segments</td></tr>';
      tb.querySelectorAll('.seg-del').forEach(b => b.addEventListener('click', () => { if (confirm('Delete?')) post('segment_delete', { id: +b.dataset.id }).then(() => loadSegments()); }));
      tb.querySelectorAll('.seg-edit').forEach(b => b.addEventListener('click', () => {
        const s = segmentCache.find(x => String(x.id) === String(b.dataset.id));
        if (!s) return;
        document.getElementById('segId').value = s.id;
        document.getElementById('segPdfName').value = s.pdf_name;
        document.getElementById('segTopic').value = s.topic_name;
        document.getElementById('segStart').value = s.start_page;
        document.getElementById('segEnd').value = s.end_page;
        document.getElementById('segStorage').value = s.storage_path || '';
        document.getElementById('segSubCourse').value = s.sub_course_id || '';
        if (!root.dataset.examinerSubject) document.getElementById('segSubject').value = s.assigned_subject;
      }));
    });
  }

  document.getElementById('mcqPdfUpload')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    fd.append('action', 'pdf_upload');
    fd.append('_csrf', CSRF);
    document.getElementById('mcqPdfUploadMsg').textContent = 'Uploading…';
    fetch(API, { method: 'POST', credentials: 'same-origin', body: fd }).then(r => r.json()).then(d => {
      if (!d.ok) throw new Error(d.error);
      document.getElementById('segPdfName').value = d.pdf_name;
      document.getElementById('segStorage').value = d.storage_path;
      document.getElementById('mcqPdfUploadMsg').textContent = '✓ Stored';
    }).catch(err => { document.getElementById('mcqPdfUploadMsg').textContent = err.message; });
  });

  document.getElementById('mcqSegmentForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const seg = {
      id: document.getElementById('segId').value || undefined,
      pdf_name: document.getElementById('segPdfName').value,
      topic_name: document.getElementById('segTopic').value,
      start_page: +document.getElementById('segStart').value,
      end_page: +document.getElementById('segEnd').value,
      assigned_subject: document.getElementById('segSubject').value,
      sub_course_id: document.getElementById('segSubCourse').value || null,
      storage_path: document.getElementById('segStorage').value || null,
    };
    post('segment_save', { segment: seg }).then(d => { if (!d.ok) alert(d.error); else { loadSegments(); document.getElementById('segReset').click(); } });
  });
  document.getElementById('segReset')?.addEventListener('click', () => {
    ['segId','segStorage','segPdfName','segTopic'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('segStart').value = 1;
    document.getElementById('segEnd').value = 11;
  });

  function renderPageStatus(p, total, inserted, status, batches, requested) {
    const el = document.getElementById('genProgress');
    const color = status === 'completed' ? 'text-emerald-700' : (status === 'failed' ? 'text-red-600' : 'text-amber-700');
    const icon = status === 'completed' ? '🟢' : (status === 'failed' ? '🔴' : '🟡');
    const row = document.createElement('div');
    row.className = color;
    let line = icon + ' Page ' + p + '/' + total + ' — ' + inserted + ' MCQs';
    if (requested && requested > batchChunkSize) {
      line += ' (' + (batches || 0) + ' API batch' + ((batches || 0) === 1 ? '' : 'es') + ', target ' + requested + ')';
    }
    line += ' — ' + status;
    row.textContent = line;
    el.appendChild(row);
    el.scrollTop = el.scrollHeight;
  }

  document.getElementById('genStart')?.addEventListener('click', () => {
    let excel = null;
    try { const t = document.getElementById('genExcelMap')?.value.trim(); if (t) excel = JSON.parse(t); } catch (e) { alert('Invalid Excel mapping JSON'); return; }
    post('job_start', {
      segment_id: +document.getElementById('genSegment').value,
      difficulty_scale: document.getElementById('genScale').value,
      questions_per_page: +document.getElementById('genQpp').value,
      excel_mapping: excel,
    }).then(d => {
      if (!d.ok) { alert(d.error); return; }
      currentJobId = d.job_id;
      document.getElementById('genProgress').innerHTML = '';
      document.getElementById('genResume').disabled = false;
      document.getElementById('genRetry').disabled = false;
      runChunk();
    });
  });

  function runChunk() {
    if (!currentJobId) return;
    post('job_chunk', { job_id: currentJobId }).then(d => {
      if (!d.ok) { alert(d.error || 'Chunk failed'); return; }
      const p = d.progress;
      renderPageStatus(p.page, p.total, p.inserted, p.status, p.batches, p.requested);
      if (p.status === 'processing') setTimeout(runChunk, 400);
      if (p.status === 'failed') alert(p.error || 'Failed');
    });
  }
  document.getElementById('genResume')?.addEventListener('click', runChunk);
  document.getElementById('genRetry')?.addEventListener('click', runChunk);

  function loadStaging(status, containerId) {
    get('staging_list', { status }).then(d => {
      const el = document.getElementById(containerId);
      if (!d.ok) { el.innerHTML = '<p class="text-red-600">' + esc(d.error) + '</p>'; return; }
      selectedStg = new Set();
      el.innerHTML = (d.items || []).map(q => '<label class="block border rounded-lg p-3 bg-white"><input type="checkbox" class="stg-cb mr-2" value="' + q.id + '" />'
        + '<span class="text-xs font-mono">#' + q.id + '</span><div class="mt-2 text-sm">' + esc(q.question_text) + '</div>'
        + (q.question_text_te ? '<div class="text-xs text-slate-600 mt-1">' + esc(q.question_text_te) + '</div>' : '')
        + '<div class="grid grid-cols-2 gap-1 mt-2 text-xs">A:' + esc(q.option_a) + ' B:' + esc(q.option_b) + '</div></label>').join('') || '<p class="text-slate-500">None</p>';
      el.querySelectorAll('.stg-cb').forEach(cb => cb.addEventListener('change', () => { if (cb.checked) selectedStg.add(+cb.value); else selectedStg.delete(+cb.value); }));
      if (window.MathJax?.typesetPromise) MathJax.typesetPromise([el]);
    });
  }
  document.getElementById('stgRefresh')?.addEventListener('click', () => loadStaging('raw_ai', 'stgRawList'));
  document.getElementById('stgApproveSection')?.addEventListener('click', () => {
    post('staging_examiner_approve', { ids: Array.from(selectedStg) }).then(d => { if (!d.ok) alert(d.error); else loadStaging('raw_ai', 'stgRawList'); });
  });
  document.getElementById('stgDeploy')?.addEventListener('click', () => {
    post('staging_super_deploy', { ids: Array.from(selectedStg), test_id: +document.getElementById('superTestId').value }).then(d => {
      if (!d.ok) alert(d.error); else { alert('Deployed: ' + d.deployed); loadStaging('examiner_approved', 'stgSuperList'); }
    });
  });

  if (isAdmin) {
    function loadExaminers() {
      get('examiners_list').then(d => {
        document.getElementById('exTable').innerHTML = (d.items||[]).map(e => '<div class="flex justify-between border-b py-2"><span>' + esc(e.email) + ' · ' + esc(e.assigned_subject) + ' · ' + e.status + '</span><button class="text-red-600 text-xs ex-del" data-id="'+e.id+'">Remove</button></div>').join('');
        document.querySelectorAll('.ex-del').forEach(b => b.addEventListener('click', () => post('examiner_delete', { id: +b.dataset.id }).then(loadExaminers)));
      });
    }
    document.getElementById('examinerForm')?.addEventListener('submit', function (e) {
      e.preventDefault();
      post('examiner_save', { examiner: {
        id: document.getElementById('exId').value || undefined,
        email: document.getElementById('exEmail').value,
        password: document.getElementById('exPass').value,
        assigned_subject: document.getElementById('exSubject').value,
        status: document.getElementById('exStatus').value,
      }}).then(d => { if (!d.ok) alert(d.error); else loadExaminers(); });
    });
    function loadApiSlots() {
      get('api_slots_list').then(d => {
        document.getElementById('apiSlotsGrid').innerHTML = (d.slots||[]).map(s => '<div class="border rounded-lg p-3"><p class="text-xs font-bold">Slot '+s.slot_index+'</p>'
          + '<label class="text-[10px]">Provider<select class="admin-input w-full mt-1 slot-prov" data-i="'+s.slot_index+'"><option>openai</option><option>anthropic</option><option>gemini</option></select></label>'
          + '<label class="text-[10px]">Model<input class="admin-input w-full mt-1 slot-model" data-i="'+s.slot_index+'" value="'+esc(s.model_name)+'" /></label>'
          + '<label class="text-[10px]">API key<input type="password" class="admin-input w-full mt-1 slot-key" data-i="'+s.slot_index+'" placeholder="'+(s.has_key?'••••':'key')+'" /></label>'
          + '<label class="text-[10px] flex items-center gap-1 mt-2"><input type="radio" name="slot_active" class="slot-active" value="'+s.slot_index+'" '+(s.is_active==1?'checked':'')+'> Active</label></div>').join('');
        d.slots.forEach(s => {
          const p = document.querySelector('.slot-prov[data-i="'+s.slot_index+'"]');
          if (p) p.value = s.provider;
        });
      });
    }
    document.getElementById('apiSlotsSave')?.addEventListener('click', () => {
      const slots = [];
      document.querySelectorAll('.slot-prov').forEach(p => {
        const i = +p.dataset.i;
        slots.push({
          slot_index: i,
          provider: p.value,
          model_name: document.querySelector('.slot-model[data-i="'+i+'"]').value,
          api_key: document.querySelector('.slot-key[data-i="'+i+'"]').value,
          is_active: document.querySelector('input.slot-active[value="'+i+'"]')?.checked ? 1 : 0,
        });
      });
      post('api_slots_save', { slots }).then(d => { if (!d.ok) alert(d.error); else alert('Saved'); });
    });
    loadExaminers();
    loadApiSlots();
  }

  loadDashboardData();
})();
</script>
