<?php
require_once dirname(__DIR__) . '/content_manager_defaults.php';

$cmOpenTab = $cmOpenTab ?? ($_GET['cm_tab'] ?? 'hierarchy');
if (!in_array($cmOpenTab, ['hierarchy', 'schedule', 'content'], true)) {
    $cmOpenTab = 'hierarchy';
}
$brandingReturnView = $brandingReturnView ?? 'content';

$cmReady = SchemaHelper::contentManagerEnabled();
$cmV2Ready = SchemaHelper::topicExamSuiteEnabled();
$apiUrl = admin_url('content_api.php');
$examTemplates = content_manager_exam_suite_templates();
$deepMc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['mc'] ?? ''));
$deepSc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['sc'] ?? ''));
?>
<?php require __DIR__ . '/branding_logo_card.php'; ?>

<style>
.cm-tabs { display:flex; flex-wrap:wrap; gap:.5rem; margin-bottom:1.25rem; }
.cm-tab-btn { padding:.5rem 1rem; font-size:.8125rem; font-weight:700; border-radius:.5rem; border:1px solid #E3E6F0; background:#fff; color:#475569; }
.cm-tab-btn.is-active { background:#0f172a; color:#fff; border-color:#0f172a; }
.cm-tab-panel { display:none; }
.cm-tab-panel.is-active { display:block; }
#cmContextBar { position: sticky; top: 3.25rem; z-index: 30; }
.cm-save-btn { padding:.5rem 1.25rem; border-radius:.5rem; background:#0f172a; color:#fff; font-size:.8125rem; font-weight:700; }
.cm-save-btn:hover { background:#1e293b; }
.cm-save-btn:disabled { opacity:.5; cursor:not-allowed; }
.cm-notes-bind-row { display:flex; flex-wrap:wrap; gap:.75rem; margin-bottom:.75rem; }
.cm-sort-block { border:1px solid #E3E6F0; border-radius:.5rem; padding:.75rem; min-height:6rem; background:#fafbfc; }
.cm-sort-block-title { font-size:.75rem; font-weight:700; color:#0f172a; margin-bottom:.5rem; }
.cm-sort-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:.35rem; }
.cm-sort-row {
  display:flex; align-items:center; gap:.35rem; padding:.4rem .5rem; background:#fff;
  border:1px solid #E3E6F0; border-radius:.375rem; font-size:.8125rem; cursor:grab;
}
.cm-sort-row.is-dragging { opacity:.55; border-color:#1e3a8a; }
.cm-sort-row.is-selected { border-color:#1e3a8a; box-shadow:0 0 0 2px rgba(30,58,138,.15); }
.cm-sort-handle { color:#94a3b8; font-size:1rem; line-height:1; user-select:none; cursor:grab; padding:0 .2rem; }
.cm-sort-label { flex:1; min-width:0; font-family:"Noto Sans Telugu",Inter,sans-serif; font-weight:600; color:#0f172a; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.cm-sort-btn {
  width:1.65rem; height:1.65rem; border:1px solid #cbd5e1; border-radius:.25rem;
  background:#fff; font-size:.65rem; line-height:1; color:#1e3a8a; font-weight:700;
}
.cm-sort-btn:hover { background:#eff6ff; border-color:#1e3a8a; }
.cm-sort-btn--del { color:#b91c1c; border-color:#fecaca; }
.cm-sort-btn--del:hover { background:#fef2f2; }
.cm-sort-empty { font-size:.75rem; color:#94a3b8; font-family:"Noto Sans Telugu",Inter,sans-serif; padding:.5rem; }
.cm-wizard-progress { display:flex; flex-wrap:wrap; gap:.35rem; list-style:none; margin:0; padding:0; }
.cm-wizard-step {
  display:flex; align-items:center; gap:.35rem; padding:.35rem .65rem; border-radius:999px;
  font-size:.7rem; font-weight:700; color:#64748b; background:#f1f5f9; border:1px solid #e2e8f0;
  font-family:"Noto Sans Telugu",Inter,sans-serif;
}
.cm-wizard-step span { display:inline-flex; align-items:center; justify-content:center; width:1.25rem; height:1.25rem; border-radius:999px; background:#cbd5e1; color:#0f172a; font-size:.65rem; }
.cm-wizard-step.is-active { background:#1e3a8a; color:#fff; border-color:#1e3a8a; }
.cm-wizard-step.is-active span { background:#fff; color:#1e3a8a; }
.cm-wizard-step.is-done { background:#ecfdf5; color:#047857; border-color:#a7f3d0; }
.cm-wizard-step.is-done span { background:#10b981; color:#fff; }
.cm-wizard-step.is-skipped { opacity:.45; text-decoration:line-through; }
.cm-wizard-stage {
  border:1px solid #E3E6F0; border-radius:.625rem; padding:1rem; background:#fafbfc;
  transition:opacity .25s ease, max-height .3s ease;
}
.cm-wizard-stage.is-locked { display:none; }
.cm-wizard-stage.is-active { display:block; border-color:#1e3a8a; box-shadow:0 4px 14px rgba(30,58,138,.08); background:#fff; }
.cm-wizard-stage.is-complete { display:block; opacity:.72; }
.cm-wizard-stage-head { margin-bottom:.75rem; }
.cm-wizard-badge {
  display:inline-block; font-size:.65rem; font-weight:800; letter-spacing:.04em; text-transform:uppercase;
  color:#1e3a8a; background:#eff6ff; padding:.15rem .5rem; border-radius:.25rem; margin-bottom:.35rem;
  font-family:"Noto Sans Telugu",Inter,sans-serif;
}
.cm-wizard-stage-head h3 { font-size:.9375rem; font-weight:800; color:#0f172a; margin:0; font-family:"Noto Sans Telugu",Inter,sans-serif; }
.cm-wizard-stage-desc { font-size:.75rem; color:#64748b; margin:.25rem 0 0; }
.cm-wizard-stage-foot { margin-top:.75rem; padding-top:.75rem; border-top:1px dashed #e2e8f0; }
.cm-wizard-save-btn {
  padding:.55rem 1.25rem; border-radius:.5rem; background:#0f172a; color:#fff;
  font-size:.8125rem; font-weight:700; font-family:"Noto Sans Telugu",Inter,sans-serif;
}
.cm-wizard-save-btn:hover { background:#1e293b; }
.cm-wizard-save-btn:disabled { opacity:.5; cursor:not-allowed; }
.cm-wizard-save-btn--final { background:#047857; }
.cm-wizard-save-btn--final:hover { background:#065f46; }
.cm-wizard-secondary-btn {
  padding:.55rem 1rem; border-radius:.5rem; border:1px solid #cbd5e1; background:#fff;
  color:#1e3a8a; font-size:.8125rem; font-weight:700;
}
.cm-wizard-secondary-btn:hover { background:#eff6ff; }
#cmWizardHint.is-hidden { display:none; }
.cm-soc { position:relative; }
.cm-soc-field { display:flex; gap:.35rem; align-items:stretch; }
.cm-soc-search { flex:1; min-width:0; font-family:"Noto Sans Telugu",Inter,sans-serif; }
.cm-soc-create {
  flex-shrink:0; padding:.45rem .65rem; border-radius:.5rem; border:1px solid #cbd5e1;
  background:#fff; color:#1e3a8a; font-size:.7rem; font-weight:800;
  font-family:"Noto Sans Telugu",Inter,sans-serif; white-space:nowrap;
}
.cm-soc-create:hover:not(:disabled) { background:#eff6ff; border-color:#1e3a8a; }
.cm-soc-create:disabled { opacity:.45; cursor:not-allowed; }
.cm-soc-list {
  position:absolute; left:0; right:0; top:calc(100% + 2px); z-index:50; max-height:14rem;
  overflow-y:auto; margin:0; padding:.25rem; list-style:none; background:#fff;
  border:1px solid #E3E6F0; border-radius:.5rem; box-shadow:0 10px 28px rgba(15,23,42,.12);
}
.cm-soc-item {
  display:flex; align-items:center; justify-content:space-between; gap:.5rem;
  padding:.45rem .55rem; border-radius:.375rem; cursor:pointer; font-size:.8125rem;
}
.cm-soc-item:hover, .cm-soc-item.is-active { background:#eff6ff; }
.cm-soc-item-label { flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.cm-soc-badge { font-size:.6rem; font-weight:800; color:#047857; background:#ecfdf5; padding:.1rem .35rem; border-radius:.25rem; }
.cm-soc-empty { padding:.5rem; font-size:.75rem; color:#94a3b8; }
.cm-soc-native-hidden {
  position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden;
  clip:rect(0,0,0,0); white-space:nowrap; border:0;
}
</style>
<div class="font-telugu max-w-6xl mx-auto" id="contentManagerRoot"
     data-api="<?= admin_e($apiUrl) ?>"
     data-media-root="<?= admin_e(rtrim(admin_site_url(''), '/')) ?>"
     data-mc="<?= admin_e($deepMc) ?>"
     data-sc="<?= admin_e($deepSc) ?>"
     data-templates="<?= admin_e(json_encode($examTemplates, JSON_UNESCAPED_UNICODE)) ?>"
     data-csrf="<?= admin_e(admin_csrf_token()) ?>">

  <header class="mb-6 pb-4 border-b border-[#E3E6F0]">
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

  <section id="cmContextBar" class="cm-card mb-4 overflow-hidden sticky top-[3.25rem] z-30 shadow-md border-2 border-slate-200">
    <div class="px-5 py-3 border-b border-[#E3E6F0] bg-white">
      <h2 class="text-sm font-bold text-slate-900 font-telugu">హైరార్కీ కాంటెక్స్ట్ (అన్ని టాబ్‌లకు)</h2>
      <p class="text-[11px] text-slate-500 mt-0.5">మెయిన్ → సబ్ → సబ్జెక్ట్ → టాపిక్ · ఏ టాబ్‌లోనైనా సేవ్ చేయవచ్చు</p>
    </div>
    <div class="p-5 grid sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-white">
      <div>
        <label class="cm-label block text-xs mb-1.5">1. మెయిన్ కోర్స్</label>
        <select id="cmMainCourse" class="cm-input cm-select w-full rounded-lg px-3 py-2.5 text-sm font-medium text-slate-900"></select>
        <div class="mt-1 flex gap-2"><button type="button" id="cmAddMainCourse" class="text-[11px] font-semibold text-slate-700">+ Add</button><button type="button" id="cmDelMainCourse" class="text-[11px] font-semibold text-red-700 px-2 py-0.5 border border-red-200 rounded font-telugu">తొలగించు</button></div>
      </div>
      <div>
        <label class="cm-label block text-xs mb-1.5">2. సబ్ కోర్స్</label>
        <select id="cmSubCourse" class="cm-input cm-select w-full rounded-lg px-3 py-2.5 text-sm text-slate-900" disabled></select>
        <div class="mt-1 flex gap-2"><button type="button" id="cmAddSubCourse" class="text-[11px] font-semibold text-slate-700" disabled>+ Add</button><button type="button" id="cmDelSubCourse" class="text-[11px] font-semibold text-red-700 px-2 py-0.5 border border-red-200 rounded font-telugu" disabled>తొలగించు</button></div>
      </div>
      <div>
        <label class="cm-label block text-xs mb-1.5">3. సబ్జెక్ట్</label>
        <div id="cmSocSubjectMount"></div>
        <select id="cmSubject" class="cm-input cm-select cm-soc-native-hidden" disabled aria-hidden="true" tabindex="-1"><option value="">—</option></select>
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
        <div id="cmSocTopicMount"></div>
        <select id="cmTopic" class="cm-input cm-select cm-soc-native-hidden" disabled aria-hidden="true" tabindex="-1"><option value="">—</option></select>
        <div class="mt-1"><button type="button" id="cmDelTopic" class="text-[11px] font-semibold text-red-600" disabled>Delete topic</button></div>
      </div>
    </div>
    <?php require __DIR__ . '/partials/cm_cover_forms.php'; ?>
    <?php require __DIR__ . '/partials/cm_entity_blocks.php'; ?>
    <div class="px-5 pb-4 flex flex-wrap gap-2 items-center border-t border-[#E3E6F0] bg-white pt-3">
      <span class="text-[11px] text-slate-500 font-telugu">టాపిక్ ఎంపిక లేదా పైన + క్రియేట్ ఉపయోగించండి</span>
      <span id="cmCascadeStatus" class="text-xs text-slate-400 ml-auto"></span>
    </div>
    <p id="cmContextSummary" class="px-5 pb-3 text-xs text-slate-600 font-telugu border-t border-[#E3E6F0] pt-2 bg-slate-50/90 min-h-[1.25rem]"></p>
  </section>

  <nav class="cm-tabs" aria-label="Content manager sections">
    <button type="button" class="cm-tab-btn font-telugu<?= $cmOpenTab === 'hierarchy' ? ' is-active' : '' ?>" data-cm-tab="hierarchy">హైరార్కీ</button>
    <button type="button" class="cm-tab-btn font-telugu<?= $cmOpenTab === 'schedule' ? ' is-active' : '' ?>" data-cm-tab="schedule">షెడ్యూల్ టెస్ట్</button>
    <button type="button" class="cm-tab-btn font-telugu<?= $cmOpenTab === 'content' ? ' is-active' : '' ?>" data-cm-tab="content">నోట్స్ &amp; ఎగ్జామ్</button>
  </nav>

  <div class="cm-tab-panel<?= $cmOpenTab === 'hierarchy' ? ' is-active' : '' ?>" data-cm-panel="hierarchy">
  <?php require __DIR__ . '/partials/cm_sort_engine.php'; ?>
  <section class="cm-card mb-6 p-4 bg-white border border-[#E3E6F0]">
    <p class="text-sm text-slate-600 font-telugu">పైన ఎంచుకున్న హైరార్కీ · కవర్ ఫోటోలు · సబ్/సబ్జెక్ట్ సేవ్ · కొత్త టాపిక్ జోడింపు ఇక్కడే ఉపయోగించండి.</p>
  </section>
  </div>

  <div class="cm-tab-panel<?= $cmOpenTab === 'schedule' ? ' is-active' : '' ?>" data-cm-panel="schedule">
  <?php require __DIR__ . '/partials/cm_schedule_panel.php'; ?>
  </div>

  <div class="cm-tab-panel<?= $cmOpenTab === 'content' ? ' is-active' : '' ?>" data-cm-panel="content">
  <div id="cmWorkspace" class="hidden space-y-5">
  <?php require __DIR__ . '/partials/cm_ai_wizard_placeholder.php'; ?>
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
        <label class="cm-label block text-xs text-slate-800 font-telugu">సబ్-టాపిక్ (వెతకండి లేదా క్రియేట్)</label>
        <div id="cmSocSubTopicMount" class="max-w-lg"></div>
        <select id="cmSubTopicPick" class="cm-soc-native-hidden" aria-hidden="true" tabindex="-1"><option value="">—</option></select>
        <label class="cm-label block text-xs text-slate-600 mt-2">ప్రాథమిక సబ్-టాపిక్ పేరు (మాన్యువల్)</label>
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
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4 pb-4 border-b border-[#E3E6F0]">
        <div>
          <p class="text-sm font-bold text-slate-900 font-telugu">PDF డౌన్‌లోడ్ అనుమతి</p>
          <p class="text-xs text-slate-600 mt-0.5 font-telugu">చెల్లింపు విద్యార్థులు మాత్రమే · ఉచిత ప్రివ్యూ ఎప్పటికీ డౌన్‌లోడ్ చేయలేరు</p>
        </div>
        <label class="relative inline-flex items-center cursor-pointer shrink-0">
          <input type="checkbox" id="cmCanDownload" class="sr-only peer" />
          <span class="w-11 h-6 bg-[#E3E6F0] rounded-full peer peer-checked:bg-emerald-700 transition-colors after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-5"></span>
        </label>
      </div>
      <div id="cmNotesPanel">
      <p class="text-xs text-slate-600 mb-2 font-telugu">పూర్తి పాఠ్యం / సారాంశం — బైండ్ ఎంపిక చేసి సేవ్ చేయండి.</p>
      <div class="cm-notes-bind-row font-telugu text-xs mb-3">
        <label class="flex items-center gap-1"><input type="radio" name="cmNotesBindMode" value="topic" checked /> మెయిన్ టాపిక్</label>
        <label class="flex items-center gap-1"><input type="radio" name="cmNotesBindMode" value="existing_subtopic" /> ఉన్న సబ్-టాపిక్</label>
        <label class="flex items-center gap-1"><input type="radio" name="cmNotesBindMode" value="new_subtopic" /> కొత్త సబ్-టాపిక్ పేరు</label>
      </div>
      <div id="cmSocNotesSubMount" class="max-w-md mb-2 hidden"></div>
      <select id="cmNotesBindSubSelect" class="cm-input cm-soc-native-hidden hidden font-telugu" aria-hidden="true" tabindex="-1"><option value="">—</option></select>
      <input type="text" id="cmNotesNewSubName" class="cm-input w-full max-w-md rounded-lg px-3 py-2 text-sm mb-2 hidden font-telugu" placeholder="కొత్త సబ్-టాపిక్ పేరు..." />
      <p id="cmNotesTargetLabel" class="text-[11px] font-semibold text-slate-800 mb-2">బైండ్: మెయిన్ టాపిక్ ID</p>
      <textarea id="cmNotesContent" rows="12" class="cm-input w-full rounded-lg px-3 py-2.5 text-sm leading-relaxed resize-y min-h-[14rem] font-telugu text-slate-900 border-[#E3E6F0]" placeholder="ఈ టాపిక్‌కు సంబంధించిన నోట్స్ మొత్తం ఇక్కడ రాయండి..."></textarea>
      <button type="button" id="cmSaveNotesBtn" class="mt-4 px-8 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold font-telugu shadow-sm">
        నోట్స్ సేవ్
      </button>
      <p id="cmSaveNotesMsg" class="text-xs text-slate-600 mt-2 min-h-[1rem]"></p>
      </div>
    </section>

    <section class="cm-card p-5 bg-white border border-[#E3E6F0]">
      <h3 class="text-sm font-bold text-slate-900 font-telugu mb-1">MCQs (ఎగ్జామ్ డేటా ఇంజిన్)</h3>
      <p class="text-xs text-slate-600 mb-3 font-telugu">టెక్స్ట్ / .csv / .xlsx / .docx — పార్స్ చేసి ఇంటరాక్టివ్ టెస్ట్‌గా సేవ్.</p>
      <label class="cm-label text-[11px] font-telugu">ఎగ్జామ్ స్లాట్ (Suite)</label>
      <select id="cmMcqSuiteKey" class="cm-input w-full max-w-xs rounded-lg px-3 py-2 text-sm mb-3 font-telugu">
        <option value="revision">రివిజన్ టెస్ట్</option>
        <option value="division">డివిజన్ టెస్ట్</option>
        <option value="sub_grand">సబ్ గ్రాండ్ టెస్ట్</option>
        <option value="grand">గ్రాండ్ టెస్ట్</option>
      </select>
      <textarea id="cmMcqContent" rows="10" class="cm-input w-full rounded-lg px-3 py-2.5 text-sm leading-relaxed resize-y min-h-[12rem] font-telugu text-slate-900 border-[#E3E6F0]" placeholder="1. ప్రశ్న...&#10;A) ... B) ...&#10;సమాధానం: C) ..."></textarea>
      <form id="cmMcqUploadForm" class="mt-3 flex flex-wrap items-center gap-3" enctype="multipart/form-data">
        <input type="file" id="cmMcqFile" name="mcq_file" accept=".csv,.txt,.xlsx,.docx" class="text-xs" />
        <span class="text-[11px] text-slate-500 font-telugu">.csv · .txt · .xlsx · .docx</span>
      </form>
      <div class="flex flex-wrap gap-3 mt-4">
        <button type="button" id="cmSaveMcqBtn" class="cm-save-btn font-telugu">MCQ టెక్స్ట్ సేవ్</button>
        <button type="button" id="cmSaveExamBtn" class="cm-save-btn font-telugu bg-emerald-800 hover:bg-emerald-900">Save Exam (పార్స్ &amp; ఇంపోర్ట్)</button>
        <button type="button" id="cmSaveExamSuiteBtn" class="cm-save-btn font-telugu bg-slate-700">ఎగ్జామ్ సూట్ సేవ్</button>
      </div>
      <p id="cmSaveMcqMsg" class="text-xs text-slate-600 mt-2 min-h-[1rem]"></p>
      <p id="cmSaveExamMsg" class="text-xs text-slate-600 min-h-[1rem]"></p>
    </section>

    <section id="cmExamSection" class="cm-card overflow-hidden bg-white border border-[#E3E6F0]">
      <div class="px-5 py-3 border-b border-[#E3E6F0]">
        <h3 class="text-sm font-bold text-slate-900 font-telugu">ఎగ్జామ్ మేనేజ్మెంట్ సూట్</h3>
        <p class="text-xs text-slate-600 mt-0.5">ఈ టెస్ట్ అవసరమా? — ఆఫ్ చేస్తే disabled</p>
      </div>
      <div id="cmExamGrid" class="p-5 grid sm:grid-cols-2 gap-4"></div>
      <p id="cmSaveExamSuiteMsg" class="px-5 pb-4 text-xs text-slate-500 min-h-[1rem]"></p>
    </section>
  </div>
  </div>

<p id="cmPickTopicHint" class="text-center text-sm text-slate-400 py-16 border border-dashed border-[#E3E6F0] rounded-xl bg-white">
    పైన కోర్స్ → సబ్ కోర్స్ → సబ్జెక్ట్ → టాపిక్ ఎంచుకోండి.
  </p>

  <?php endif; ?>
</div>

<?php if ($cmReady): ?>
<script src="<?= admin_e(admin_site_url('assets/js/cm-search-or-create.js')) ?>"></script>
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
    mcq: document.getElementById('cmMcqContent'),
    saveNotesBtn: document.getElementById('cmSaveNotesBtn'),
    saveNotesMsg: document.getElementById('cmSaveNotesMsg'),
    saveMcqBtn: document.getElementById('cmSaveMcqBtn'),
    saveMcqMsg: document.getElementById('cmSaveMcqMsg'),
    notesTarget: document.getElementById('cmNotesTargetLabel'),
    notesBindHint: document.getElementById('cmNotesBindHint'),
    status: document.getElementById('cmCascadeStatus'),
    newTopic: document.getElementById('cmNewTopicTitle'),
    addTopicBtn: document.getElementById('cmAddTopicBtn'),
    examSection: document.getElementById('cmExamSection'),
    examGrid: document.getElementById('cmExamGrid'),
    notesEnabled: document.getElementById('cmNotesEnabled'),
    canDownload: document.getElementById('cmCanDownload'),
    notesPanel: document.getElementById('cmNotesPanel'),
    coverRow: document.getElementById('cmCoverRow'),
    subjectLive: document.getElementById('cmSubjectLive'),
    subjectLiveWrap: document.getElementById('cmSubjectLiveWrap'),
    termSection: document.getElementById('cmTermMatrixSection'),
    termMsg: document.getElementById('cmTermMatrixMsg'),
    saveTermMatrix: document.getElementById('cmSaveTermMatrix'),
    schedulePickHint: document.getElementById('cmScheduleSubPickHint'),
    scheduleMatrixBody: document.getElementById('cmScheduleMatrixBody'),
    scheduleSubTitle: document.getElementById('cmScheduleSubTitle'),
    shortLabelDisplay: document.getElementById('cmShortLabelDisplay'),
    longLabelDisplay: document.getElementById('cmLongLabelDisplay'),
    shortEnabled: document.getElementById('cmShortEnabled'),
    longEnabled: document.getElementById('cmLongEnabled'),
    termMigrateHint: document.getElementById('cmTermMatrixMigrateHint'),
    notesBindSub: document.getElementById('cmNotesBindSubSelect'),
    notesNewSub: document.getElementById('cmNotesNewSubName'),
    mcqSuite: document.getElementById('cmMcqSuiteKey'),
    mcqFile: document.getElementById('cmMcqFile'),
    saveExamBtn: document.getElementById('cmSaveExamBtn'),
    saveExamMsg: document.getElementById('cmSaveExamMsg'),
    saveExamSuiteBtn: document.getElementById('cmSaveExamSuiteBtn'),
    saveExamSuiteMsg: document.getElementById('cmSaveExamSuiteMsg'),
    blockMain: document.getElementById('cmBlockMain'),
    blockSub: document.getElementById('cmBlockSub'),
    blockSubject: document.getElementById('cmBlockSubject'),
    blockTopic: document.getElementById('cmBlockTopic'),
    mainName: document.getElementById('cmMainName'),
    mainNameTe: document.getElementById('cmMainNameTe'),
    subNameEdit: document.getElementById('cmSubNameEdit'),
    subNameTeEdit: document.getElementById('cmSubNameTeEdit'),
    subjectNameEdit: document.getElementById('cmSubjectNameEdit'),
    subjectNameTeEdit: document.getElementById('cmSubjectNameTeEdit'),
    topicTitleEdit: document.getElementById('cmTopicTitleEdit'),
    topicTitleTeEdit: document.getElementById('cmTopicTitleTeEdit'),
    saveMainBtn: document.getElementById('cmSaveMainCourseBtn'),
    saveSubBtn: document.getElementById('cmSaveSubCourseBtn'),
    saveSubjectBtn: document.getElementById('cmSaveSubjectBtn'),
    saveTopicMetaBtn: document.getElementById('cmSaveTopicMetaBtn'),
    saveMainMsg: document.getElementById('cmSaveMainMsg'),
    saveSubMsg: document.getElementById('cmSaveSubMsg'),
    saveSubjectMsg: document.getElementById('cmSaveSubjectMsg'),
    saveTopicMetaMsg: document.getElementById('cmSaveTopicMetaMsg'),
    contextSummary: document.getElementById('cmContextSummary'),
  };

  var state = { examSuite: [], subTopics: [], subjects: [], termBoxes: [] };
  var csrf = document.getElementById('contentManagerRoot').getAttribute('data-csrf') || '';

  function adminHeaders(extra) {
    var h = { 'X-CSRF-Token': csrf, 'X-Requested-With': 'XMLHttpRequest' };
    if (extra) { for (var k in extra) { h[k] = extra[k]; } }
    return h;
  }

  function postJson(body) {
    body._csrf = csrf;
    return fetch(api, {
      method: 'POST', credentials: 'same-origin',
      headers: adminHeaders({ 'Content-Type': 'application/json' }),
      body: JSON.stringify(body),
    }).then(function (r) { return r.json(); });
  }

  function mediaUrl(path) {
    if (!path) return '';
    var p = String(path);
    if (p.indexOf('http://') === 0 || p.indexOf('https://') === 0) return p;
    if (p.charAt(0) === '/') return p;
    var base = root.getAttribute('data-media-root') || '';
    if (base) {
      return base.replace(/\/$/, '') + '/' + p.replace(/^\//, '');
    }
    return (window.location.pathname.indexOf('/admin/') >= 0 ? '../' : '') + p;
  }

  function syncCoverFormIds() {
    var idMap = { course: el.main.value, sub_course: el.sub.value, subject: el.subject.value };
    document.querySelectorAll('.cm-cover-form').forEach(function (form) {
      var entity = form.getAttribute('data-entity');
      var idField = form.querySelector('.cm-cover-id-field');
      if (idField) idField.value = idMap[entity] || '';
    });
  }

  var coverUi = {
    course: { img: 'cmCoverCourseImg', avatar: 'cmCoverCourseAvatar', initials: 'cmCoverCourseInitials', label: 'cmCoverCourseLabel' },
    sub_course: { img: 'cmCoverSubImg', avatar: 'cmCoverSubAvatar', initials: 'cmCoverSubInitials', label: 'cmCoverSubLabel' },
    subject: { img: 'cmCoverSubjectImg', avatar: 'cmCoverSubjectAvatar', initials: 'cmCoverSubjectInitials', label: 'cmCoverSubjectLabel' }
  };

  function setCoverPreview(entity, item) {
    var ui = coverUi[entity];
    if (!ui) return;
    var imgEl = document.getElementById(ui.img);
    var avEl = document.getElementById(ui.avatar);
    if (!imgEl || !avEl) return;
    if (item) {
      var iniEl = document.getElementById(ui.initials);
      var labEl = document.getElementById(ui.label);
      var label = item.display_label || item.name_te || item.name || '—';
      if (labEl) labEl.textContent = label;
      if (iniEl) iniEl.textContent = item.avatar_initials || '—';
      avEl.style.background = item.avatar_bg || '#f8fafc';
      avEl.style.color = item.avatar_color || '#334155';
    }
    var url = item && item.image_url ? String(item.image_url) : '';
    if (url) {
      imgEl.src = url;
      imgEl.classList.remove('hidden');
      avEl.classList.add('hidden');
    } else {
      imgEl.removeAttribute('src');
      imgEl.classList.add('hidden');
      avEl.classList.remove('hidden');
    }
  }

  function toggleNotesUi() {
    if (!el.notesPanel || !el.notesEnabled) return;
    el.notesPanel.classList.toggle('hidden', !el.notesEnabled.checked);
  }

  function updateCovers() {
    if (!el.coverRow) return;
    var mc = el.main.value, sc = el.sub.value, su = el.subject.value;
    syncCoverFormIds();
    el.coverRow.classList.toggle('hidden', !mc);
    var cBox = document.getElementById('cmCoverCourse');
    var sBox = document.getElementById('cmCoverSub');
    var uBox = document.getElementById('cmCoverSubject');
    if (cBox) cBox.hidden = !mc;
    if (sBox) sBox.hidden = !sc;
    if (uBox) uBox.hidden = !su;
    if (!mc) setCoverPreview('course', null);
    else {
      fetchJson(api + '?action=entity&entity=course&id=' + mc).then(function (d) {
        setCoverPreview('course', d.ok && d.item ? d.item : null);
      });
    }
    if (!sc) setCoverPreview('sub_course', null);
    else {
      fetchJson(api + '?action=entity&entity=sub_course&id=' + sc).then(function (d) {
        setCoverPreview('sub_course', d.ok && d.item ? d.item : null);
      });
    }
    if (!su) setCoverPreview('subject', null);
    else {
      fetchJson(api + '?action=entity&entity=subject&id=' + su).then(function (d) {
        setCoverPreview('subject', d.ok && d.item ? d.item : null);
      });
    }
  }

  function fetchJson(url) {
    return fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
  }

  var socSubject, socTopic, socSubTopic, socNotesSub;
  var elSubTopicPick = document.getElementById('cmSubTopicPick');

  function cmSubjectLabel(s) {
    return (s.name_te && String(s.name_te).trim()) ? String(s.name_te).trim() : (s.name || '—');
  }
  function cmTopicLabel(t) {
    return (t.title_te && String(t.title_te).trim()) ? String(t.title_te).trim() : (t.title || '—');
  }
  function cmSubTopicLabel(st) {
    return (st.sub_topic_name_te && String(st.sub_topic_name_te).trim())
      ? String(st.sub_topic_name_te).trim() : (st.sub_topic_name || '—');
  }

  function syncSocCascade(level) {
    if (level <= 2 && socSubject) { socSubject.setDisabled(true, 'ముందు సబ్ కోర్స్ ఎంచుకోండి'); socSubject.clearSelection(false); }
    if (level <= 3 && socTopic) { socTopic.setDisabled(true, 'ముందు సబ్జెక్ట్ ఎంచుకోండి'); socTopic.clearSelection(false); }
    if (level <= 4) {
      if (socSubTopic) { socSubTopic.setDisabled(true, 'ముందు టాపిక్ ఎంచుకోండి'); socSubTopic.clearSelection(false); }
      if (socNotesSub) { socNotesSub.setDisabled(true); socNotesSub.clearSelection(false); }
    }
  }

  function initSearchOrCreate() {
    if (typeof CmSearchOrCreate === 'undefined') return;
    var mountS = document.getElementById('cmSocSubjectMount');
    var mountT = document.getElementById('cmSocTopicMount');
    var mountSt = document.getElementById('cmSocSubTopicMount');
    var mountN = document.getElementById('cmSocNotesSubMount');

    if (mountS && el.subject) {
      socSubject = new CmSearchOrCreate({
        mount: mountS,
        selectEl: el.subject,
        type: 'subject',
        fetchJson: fetchJson,
        getParentId: function () { return parseInt(el.sub.value, 10) || 0; },
        fetchUrl: function (q) {
          var scid = parseInt(el.sub.value, 10) || 0;
          return api + '?action=search_subjects&sub_course_id=' + scid + '&q=' + encodeURIComponent(q) + '&limit=50';
        },
        labelFn: cmSubjectLabel,
        onSelected: function (item) {
          var scid = parseInt(el.sub.value, 10);
          if (!scid || !item.id || parseInt(item.is_linked, 10) === 1) return Promise.resolve();
          return postJson({ action: 'link_subject', sub_course_id: scid, subject_id: item.id }).then(function (d) {
            if (!d.ok) throw new Error(d.error || 'Link failed');
            item.is_linked = 1;
          });
        },
        onCreate: function (name) {
          var scid = parseInt(el.sub.value, 10);
          if (!scid) throw new Error('సబ్ కోర్స్ ఎంచుకోండి');
          return postJson({ action: 'save_subject', sub_course_id: scid, name: name, is_active: 1 }).then(function (d) {
            if (!d.ok) throw new Error(d.error || 'Create failed');
            return { id: d.id, name: name, name_te: '', is_linked: 1 };
          });
        },
      });
      socSubject.setDisabled(true);
    }

    if (mountT && el.topic) {
      socTopic = new CmSearchOrCreate({
        mount: mountT,
        selectEl: el.topic,
        type: 'topic',
        fetchJson: fetchJson,
        getParentId: function () { return parseInt(el.subject.value, 10) || 0; },
        fetchUrl: function (q) {
          var sid = parseInt(el.subject.value, 10) || 0;
          return api + '?action=search_topics&subject_id=' + sid + '&q=' + encodeURIComponent(q) + '&limit=50';
        },
        labelFn: cmTopicLabel,
        onCreate: function (name) {
          var sid = parseInt(el.subject.value, 10);
          if (!sid) throw new Error('సబ్జెక్ట్ ఎంచుకోండి');
          return postJson({ action: 'create_topic', subject_id: sid, title: name }).then(function (d) {
            if (!d.ok) throw new Error(d.error || 'Create failed');
            return { id: d.topic_id, title: name, title_te: '' };
          });
        },
      });
      socTopic.setDisabled(true);
    }

    if (mountSt && elSubTopicPick) {
      socSubTopic = new CmSearchOrCreate({
        mount: mountSt,
        selectEl: elSubTopicPick,
        type: 'subtopic',
        fetchJson: fetchJson,
        getParentId: function () { return parseInt(el.topic.value, 10) || 0; },
        fetchUrl: function (q) {
          var tid = parseInt(el.topic.value, 10) || 0;
          return api + '?action=search_sub_topics&topic_id=' + tid + '&q=' + encodeURIComponent(q) + '&limit=50';
        },
        labelFn: cmSubTopicLabel,
        onSelected: function (item) {
          if (el.subName) el.subName.value = cmSubTopicLabel(item);
        },
        onCreate: function (name) {
          var tid = parseInt(el.topic.value, 10);
          if (!tid) throw new Error('టాపిక్ ఎంచుకోండి');
          return postJson({ action: 'create_sub_topic', topic_id: tid, name: name }).then(function (d) {
            if (!d.ok) throw new Error(d.error || 'Create failed');
            if (el.hasSub && !el.hasSub.checked) { el.hasSub.checked = true; toggleSubUi(); }
            if (el.subName) el.subName.value = name;
            return { id: d.id, sub_topic_name: name, sub_topic_name_te: name };
          });
        },
      });
      socSubTopic.setDisabled(true);
    }

    if (mountN && el.notesBindSub) {
      socNotesSub = new CmSearchOrCreate({
        mount: mountN,
        selectEl: el.notesBindSub,
        type: 'subtopic',
        fetchJson: fetchJson,
        getParentId: function () { return parseInt(el.topic.value, 10) || 0; },
        fetchUrl: function (q) {
          var tid = parseInt(el.topic.value, 10) || 0;
          return api + '?action=search_sub_topics&topic_id=' + tid + '&q=' + encodeURIComponent(q) + '&limit=50';
        },
        labelFn: cmSubTopicLabel,
        onCreate: function (name) {
          var tid = parseInt(el.topic.value, 10);
          if (!tid) throw new Error('టాపిక్ ఎంచుకోండి');
          return postJson({ action: 'create_sub_topic', topic_id: tid, name: name }).then(function (d) {
            if (!d.ok) throw new Error(d.error || 'Create failed');
            return { id: d.id, sub_topic_name: name, sub_topic_name_te: name };
          });
        },
      });
      socNotesSub.setDisabled(true);
    }
  }

  initSearchOrCreate();

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

  document.querySelectorAll('.cm-tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tab = btn.getAttribute('data-cm-tab');
      document.querySelectorAll('.cm-tab-btn').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
      document.querySelectorAll('.cm-tab-panel').forEach(function (p) {
        p.classList.toggle('is-active', p.getAttribute('data-cm-panel') === tab);
      });
    });
  });

  function notesBindMode() {
    var m = document.querySelector('input[name="cmNotesBindMode"]:checked');
    return m ? m.value : 'topic';
  }

  function syncNotesBindUi() {
    var mode = notesBindMode();
    var notesMount = document.getElementById('cmSocNotesSubMount');
    if (notesMount) notesMount.classList.toggle('hidden', mode !== 'existing_subtopic');
    if (el.notesBindSub) el.notesBindSub.classList.toggle('hidden', true);
    if (el.notesNewSub) el.notesNewSub.classList.toggle('hidden', mode !== 'new_subtopic');
    if (el.notesTarget) {
      el.notesTarget.textContent = mode === 'topic' ? 'బైండ్: మెయిన్ టాపిక్' : (mode === 'new_subtopic' ? 'బైండ్: కొత్త సబ్-టాపిక్' : 'బైండ్: ఎంచుకున్న సబ్-టాపిక్');
    }
  }

  document.querySelectorAll('input[name="cmNotesBindMode"]').forEach(function (inp) {
    inp.addEventListener('change', syncNotesBindUi);
  });
  syncNotesBindUi();

  function loadEntityBlocks() {
    var mc = parseInt(el.main.value, 10);
    var sc = parseInt(el.sub.value, 10);
    var su = parseInt(el.subject.value, 10);
    var tp = parseInt(el.topic.value, 10);
    if (el.blockMain) el.blockMain.hidden = !mc;
    if (el.blockSub) el.blockSub.hidden = !sc;
    if (el.blockSubject) el.blockSubject.hidden = !su;
    if (el.blockTopic) el.blockTopic.hidden = !tp;
    if (mc && el.mainName) {
      fetchJson(api + '?action=entity&entity=course&id=' + mc).then(function (d) {
        if (d.ok && d.item) {
          el.mainName.value = d.item.name || '';
          if (el.mainNameTe) el.mainNameTe.value = d.item.name_te || '';
        }
      });
    }
    if (sc && el.subNameEdit) {
      fetchJson(api + '?action=entity&entity=sub_course&id=' + sc).then(function (d) {
        if (d.ok && d.item) {
          el.subNameEdit.value = d.item.name || '';
          if (el.subNameTeEdit) el.subNameTeEdit.value = d.item.name_te || '';
        }
      });
    }
    if (su && el.subjectNameEdit) {
      fetchJson(api + '?action=entity&entity=subject&id=' + su).then(function (d) {
        if (d.ok && d.item) {
          el.subjectNameEdit.value = d.item.name || '';
          if (el.subjectNameTeEdit) el.subjectNameTeEdit.value = d.item.name_te || '';
        }
      });
    }
    if (tp && el.topicTitleEdit) {
      fetchJson(api + '?action=topic&topic_id=' + tp).then(function (d) {
        if (d.ok && d.topic) {
          el.topicTitleEdit.value = d.topic.title || '';
          if (el.topicTitleTeEdit) el.topicTitleTeEdit.value = d.topic.title_te || '';
        }
      });
    }
  }

  function loadSubCourseTermMatrix(subCourseId) {
    if (!el.termSection) return Promise.resolve();
    if (!subCourseId) {
      if (el.schedulePickHint) el.schedulePickHint.classList.remove('hidden');
      if (el.scheduleMatrixBody) el.scheduleMatrixBody.classList.add('hidden');
      return Promise.resolve();
    }
    return fetchJson(api + '?action=sub_course_term_matrix&sub_course_id=' + subCourseId + '&auto_init=1').then(function (d) {
      if (!d.ok) throw new Error(d.error || 'Load failed');
      if (el.termMigrateHint) el.termMigrateHint.classList.toggle('hidden', !!d.tables_ready);
      state.termBoxes = d.boxes || [];
      var short = state.termBoxes.find(function (b) { return b.term_key === 'short_term'; });
      var long = state.termBoxes.find(function (b) { return b.term_key === 'long_term'; });
      if (el.schedulePickHint) el.schedulePickHint.classList.add('hidden');
      if (el.scheduleMatrixBody) el.scheduleMatrixBody.classList.remove('hidden');
      if (el.scheduleSubTitle && d.sub_course) {
        var sc = d.sub_course;
        el.scheduleSubTitle.textContent = (sc.name_te || sc.name || '') + (sc.slug ? ' · ' + sc.slug : '');
      }
      if (el.shortLabelDisplay) el.shortLabelDisplay.textContent = short ? (short.label_te || short.label_en || '—') : '—';
      if (el.longLabelDisplay) el.longLabelDisplay.textContent = long ? (long.label_te || long.label_en || '—') : '—';
      if (el.shortEnabled) el.shortEnabled.checked = short ? !!parseInt(short.is_enabled, 10) : true;
      if (el.longEnabled) el.longEnabled.checked = long ? !!parseInt(long.is_enabled, 10) : true;
      if (el.termMsg) el.termMsg.textContent = 'షెడ్యూల్ మ్యాట్రిక్స్ సిద్ధం (ఆటో మ్యాపింగ్).';
    }).catch(function (e) {
      if (el.termMsg) el.termMsg.textContent = e.message;
    });
  }

  window.cmOpenScheduleTab = function () {
    var tabBtn = document.querySelector('.cm-tab-btn[data-cm-tab="schedule"]');
    if (tabBtn) tabBtn.click();
    var scid = parseInt(el.sub.value, 10) || 0;
    if (scid) loadSubCourseTermMatrix(scid);
  };

  function resetFrom(level) {
    if (level <= 1) { el.sub.innerHTML = '<option value="">—</option>'; el.sub.disabled = true; }
    if (level <= 1) { loadSubCourseTermMatrix(0); }
    if (level <= 2) { el.subject.innerHTML = '<option value="">—</option>'; el.subject.disabled = true; }
    if (level <= 3) {
      el.topic.innerHTML = '<option value="">—</option>'; el.topic.disabled = true;
      if (el.newTopic) el.newTopic.disabled = true;
      if (el.addTopicBtn) el.addTopicBtn.disabled = true;
    }
    syncSocCascade(level);
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
      if (el.canDownload) {
        el.canDownload.checked = parseInt(t.can_download, 10) === 1;
      }
      el.notes.value = t.notes_content || '';
      if (el.mcq) el.mcq.value = t.mcq_content || '';
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
      if (el.notesBindSub) {
        fetchJson(api + '?action=sub_topics&topic_id=' + topicId).then(function (sd) {
          fillSelect(el.notesBindSub, sd.items || [], 'id', function (st) {
            return st.sub_topic_name_te || st.sub_topic_name;
          }, '— సబ్-టాపిక్ —');
          if (t.active_sub_topic_id) el.notesBindSub.value = String(t.active_sub_topic_id);
          if (socNotesSub) socNotesSub.syncFromSelect();
        });
      }
      if (socSubTopic) socSubTopic.reload();
      loadEntityBlocks();
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
    loadEntityBlocks();
    updateContextSummary();
    persistCascade();
    if (!id) {
      if (typeof window.cmResetWizard === 'function') window.cmResetWizard();
      return;
    }
    chainSelect(el.sub, api + '?action=sub_courses&course_id=' + id, el.sub, function (s) { return s.name_te || s.name; })
      .then(function () {
        if (typeof window.bootWizardFromMain === 'function') window.bootWizardFromMain();
      });
  });

  el.sub.addEventListener('change', function () {
    resetFrom(2);
    var id = el.sub.value;
    document.getElementById('cmAddSubject').disabled = !id;
    document.getElementById('cmDelSubCourse').disabled = !id;
    updateCovers();
    updateContextSummary();
    persistCascade();
    if (!id) return;
    loadSubCourseTermMatrix(parseInt(id, 10) || 0);
    loadEntityBlocks();
    chainSelect(el.subject, api + '?action=subjects&sub_course_id=' + id, el.subject, function (s) { return s.name_te || s.name; }, function (d) {
      state.subjects = d.items || [];
      if (socSubject) {
        socSubject.setDisabled(false);
        socSubject.syncFromSelect();
      }
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
    loadEntityBlocks();
    updateContextSummary();
    persistCascade();
    if (!id) return;
    chainSelect(el.topic, api + '?action=topics&subject_id=' + id, el.topic, function (t) { return t.title_te || t.title; }, function () {
      if (el.newTopic) el.newTopic.disabled = false;
      if (el.addTopicBtn) el.addTopicBtn.disabled = false;
      if (socTopic) {
        socTopic.setDisabled(false);
        socTopic.syncFromSelect();
      }
    });
  });

  el.topic.addEventListener('change', function () {
    updateContextSummary();
    persistCascade();
  });

  if (el.saveTermMatrix) {
    el.saveTermMatrix.addEventListener('click', function () {
      var scid = parseInt(el.sub.value, 10);
      if (!scid) return;
      var boxes = [
        { term_key: 'short_term', is_enabled: el.shortEnabled && el.shortEnabled.checked ? 1 : 0 },
        { term_key: 'long_term', is_enabled: el.longEnabled && el.longEnabled.checked ? 1 : 0 },
      ];
      postJson({ action: 'save_sub_course_term_matrix', sub_course_id: scid, boxes: boxes }).then(function (d) {
        if (!d.ok) throw new Error(d.error);
        if (el.termMsg) el.termMsg.textContent = '✓ షెడ్యూల్ టెస్ట్ సేవ్ అయ్యింది.';
        loadSubCourseTermMatrix(scid);
      }).catch(function (e) { if (el.termMsg) el.termMsg.textContent = e.message; });
    });
  }

  var cmPreferScheduleTab = <?= json_encode($cmOpenTab === 'schedule') ?>;

  el.topic.addEventListener('change', function () {
    var id = el.topic.value;
    if (!id) {
      syncSocCascade(4);
      resetFrom(4);
      return;
    }
    if (socSubTopic) { socSubTopic.setDisabled(false); socSubTopic.reload(); }
    if (socNotesSub) { socNotesSub.setDisabled(false); socNotesSub.reload(); }
    loadTopicConfig(id);
  });

  el.hasSub.addEventListener('change', toggleSubUi);
  if (el.notesEnabled) el.notesEnabled.addEventListener('change', toggleNotesUi);
  toggleNotesUi();

  document.querySelectorAll('.cm-cover-hitbox').forEach(function (box) {
    function openCoverUpload() {
      var entity = box.getAttribute('data-cover-entity');
      var idMap = { course: el.main.value, sub_course: el.sub.value, subject: el.subject.value };
      if (!entity || !idMap[entity]) {
        alert('ముందుగా కోర్స్ / సబ్-కోర్స్ / సబ్జెక్ట్ ఎంచుకోండి.');
        return;
      }
      var wrapIds = { course: 'cmCoverCourseUploader', sub_course: 'cmCoverSubUploader', subject: 'cmCoverSubjectUploader' };
      var wrap = document.getElementById(wrapIds[entity] || '');
      if (wrap) wrap.classList.remove('hidden');
      var fileInp = wrap && wrap.querySelector('.cm-cover-file');
      if (fileInp) fileInp.click();
    }
    box.addEventListener('click', openCoverUpload);
    box.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openCoverUpload(); }
    });
  });

  document.querySelectorAll('.cm-cover-file').forEach(function (inp) {
    inp.addEventListener('change', function () {
      var form = inp.closest('.cm-cover-form');
      if (!form) return;
      syncCoverFormIds();
      var entity = form.getAttribute('data-entity');
      var idField = form.querySelector('.cm-cover-id-field');
      var eid = idField ? idField.value : '';
      if (!eid) {
        alert('ముందుగా కోర్స్ / సబ్-కోర్స్ / సబ్జెక్ట్ ఎంచుకోండి.');
        inp.value = '';
        return;
      }
      if (!inp.files || !inp.files[0]) return;
      var fd = new FormData(form);
      fd.set('image_file', inp.files[0]);
      fetch(api, { method: 'POST', credentials: 'same-origin', headers: adminHeaders(), body: fd })
        .then(function (r) { return r.json(); })
        .then(function (d) {
          if (!d.ok) throw new Error(d.error || 'Upload failed');
          if (entity && d.url) {
            setCoverPreview(entity, {
              image_url: d.url + (d.v ? (d.url.indexOf('?') >= 0 ? '&' : '?') + 'v=' + d.v : ''),
              display_label: '',
              avatar_initials: '—',
              avatar_bg: '#f8fafc',
              avatar_color: '#334155'
            });
          }
          var wrapIds = { course: 'cmCoverCourseUploader', sub_course: 'cmCoverSubUploader', subject: 'cmCoverSubjectUploader' };
          var wrap = document.getElementById(wrapIds[entity] || '');
          if (wrap) wrap.classList.add('hidden');
          updateCovers();
        }).catch(function (e) { alert(e.message); inp.value = ''; });
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
    if (!id || !confirm('మెయిన్ కోర్స్‌ను తొలగించాలా? ఇందులో ఉన్న సబ్-కోర్స్‌లు, సబ్జెక్ట్‌లు మరియు టాపిక్‌లు కూడా తొలగించబడతాయి.')) return;
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
    if (!id || !confirm('సబ్ కోర్స్‌ను తొలగించాలా? లింక్ చేసిన సబ్జెక్ట్ మ్యాపింగ్‌లు తొలగించబడతాయి.')) return;
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
    if (!id || !confirm('సబ్జెక్ట్‌ను తొలగించాలా? అన్ని టాపిక్‌లు, సబ్-టాపిక్‌లు మరియు ఎగ్జామ్ డేటా తొలగించబడతాయి.')) return;
    postJson({ action: 'delete_subject', id: id }).then(function () { el.sub.dispatchEvent(new Event('change')); });
  });
  document.getElementById('cmDelTopic').addEventListener('click', function () {
    var id = parseInt(el.topic.value, 10);
    if (!id || !confirm('టాపిక్‌ను తొలగించాలా? సబ్-టాపిక్‌లు మరియు ఎగ్జామ్ సూట్ కూడా తొలగించబడతాయి.')) return;
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

  if (el.addTopicBtn && el.newTopic) {
    el.addTopicBtn.addEventListener('click', function () {
      var sid = el.subject.value, title = el.newTopic.value.trim();
      if (!sid || !title) return;
      postJson({ action: 'create_topic', subject_id: parseInt(sid, 10), title: title }).then(function (d) {
        if (!d.ok) throw new Error(d.error);
        el.newTopic.value = '';
        return fetchJson(api + '?action=topics&subject_id=' + sid).then(function (td) {
          fillSelect(el.topic, td.items, 'id', function (t) { return t.title_te || t.title; });
          el.topic.value = String(d.topic_id);
          if (socTopic) socTopic.syncFromSelect();
          loadTopicConfig(d.topic_id);
        });
      }).catch(function (e) { alert(e.message); });
    });
  }

  function buildTopicSavePayload(topicId) {
    return {
      action: 'save_topic_config',
      topic_id: topicId,
      has_sub_topics: el.hasSub.checked ? 1 : 0,
      notes_enabled: el.notesEnabled && el.notesEnabled.checked ? 1 : 0,
      can_download: el.canDownload && el.canDownload.checked ? 1 : 0,
      question_count: 50,
      notes_content: el.notesEnabled && el.notesEnabled.checked ? el.notes.value : '',
      mcq_content: el.mcq ? el.mcq.value : '',
      sub_topics: el.hasSub.checked ? collectSubTopics() : [],
      exam_suite: collectExamSuite(),
    };
  }

  function runTopicSave(btn, msgEl) {
    var topicId = parseInt(el.topic.value, 10);
    if (!topicId) return;
    if (el.hasSub.checked && !el.subName.value.trim()) {
      if (msgEl) msgEl.textContent = 'సబ్-టాపిక్ పేరు నమోదు చేయండి.';
      return;
    }
    if (btn) btn.disabled = true;
    if (msgEl) msgEl.textContent = 'సేవ్…';
    return postJson(buildTopicSavePayload(topicId)).then(function (d) {
      if (btn) btn.disabled = false;
      if (!d.ok) throw new Error(d.error);
      if (msgEl) msgEl.textContent = '✓ సేవ్ పూర్తయింది';
      setTimeout(function () { if (msgEl) msgEl.textContent = ''; }, 4000);
      return loadTopicConfig(topicId);
    }).catch(function (e) {
      if (btn) btn.disabled = false;
      if (msgEl) msgEl.textContent = e.message;
    });
  }

  function buildNotesPayload(topicId) {
    var mode = notesBindMode();
    return {
      action: 'save_topic_notes',
      topic_id: topicId,
      has_sub_topics: mode !== 'topic' || el.hasSub.checked ? 1 : 0,
      notes_enabled: el.notesEnabled && el.notesEnabled.checked ? 1 : 0,
      can_download: el.canDownload && el.canDownload.checked ? 1 : 0,
      notes_content: el.notesEnabled && el.notesEnabled.checked ? el.notes.value : '',
      notes_bind_mode: mode,
      bind_sub_topic_id: mode === 'existing_subtopic' && el.notesBindSub ? parseInt(el.notesBindSub.value, 10) : 0,
      new_sub_topic_name: mode === 'new_subtopic' && el.notesNewSub ? el.notesNewSub.value.trim() : '',
      sub_topics: el.hasSub.checked ? collectSubTopics() : [],
      question_count: 50,
    };
  }

  if (el.saveNotesBtn) {
    el.saveNotesBtn.addEventListener('click', function () {
      var topicId = parseInt(el.topic.value, 10);
      if (!topicId) return;
      el.saveNotesBtn.disabled = true;
      el.saveNotesMsg.textContent = 'సేవ్…';
      postJson(buildNotesPayload(topicId)).then(function (d) {
        el.saveNotesBtn.disabled = false;
        if (!d.ok) throw new Error(d.error);
        el.saveNotesMsg.textContent = '✓ నోట్స్ సేవ్';
        return loadTopicConfig(topicId);
      }).catch(function (e) {
        el.saveNotesBtn.disabled = false;
        el.saveNotesMsg.textContent = e.message;
      });
    });
  }
  if (el.saveMcqBtn) {
    el.saveMcqBtn.addEventListener('click', function () {
      var topicId = parseInt(el.topic.value, 10);
      if (!topicId) return;
      el.saveMcqBtn.disabled = true;
      el.saveMcqMsg.textContent = 'సేవ్…';
      postJson({ action: 'save_topic_mcq_text', topic_id: topicId, mcq_content: el.mcq ? el.mcq.value : '' }).then(function (d) {
        el.saveMcqBtn.disabled = false;
        if (!d.ok) throw new Error(d.error);
        el.saveMcqMsg.textContent = '✓ MCQ టెక్స్ట్ సేవ్';
      }).catch(function (e) {
        el.saveMcqBtn.disabled = false;
        el.saveMcqMsg.textContent = e.message;
      });
    });
  }
  if (el.saveExamBtn) {
    el.saveExamBtn.addEventListener('click', function () {
      var topicId = parseInt(el.topic.value, 10);
      if (!topicId) return;
      var suiteKey = el.mcqSuite ? el.mcqSuite.value : 'revision';
      el.saveExamBtn.disabled = true;
      if (el.saveExamMsg) el.saveExamMsg.textContent = 'పార్స్ & ఇంపోర్ట్…';
      var file = el.mcqFile && el.mcqFile.files && el.mcqFile.files[0];
      if (file) {
        var fd = new FormData();
        fd.append('action', 'import_mcq_file');
        fd.append('topic_id', String(topicId));
        fd.append('suite_key', suiteKey);
        fd.append('mcq_file', file);
        fd.append('_csrf', csrf);
        fetch(api, { method: 'POST', credentials: 'same-origin', headers: adminHeaders(), body: fd })
          .then(function (r) { return r.json(); })
          .then(function (d) {
            el.saveExamBtn.disabled = false;
            if (!d.ok) throw new Error(d.error);
            if (el.saveExamMsg) el.saveExamMsg.textContent = '✓ ' + (d.imported || 0) + ' ప్రశ్నలు ఇంపోర్ట్';
            return loadTopicConfig(topicId);
          }).catch(function (e) {
            el.saveExamBtn.disabled = false;
            if (el.saveExamMsg) el.saveExamMsg.textContent = e.message;
          });
        return;
      }
      postJson({
        action: 'import_mcq_bank',
        topic_id: topicId,
        suite_key: suiteKey,
        mcq_content: el.mcq ? el.mcq.value : '',
      }).then(function (d) {
        el.saveExamBtn.disabled = false;
        if (!d.ok) throw new Error(d.error);
        if (el.saveExamMsg) el.saveExamMsg.textContent = '✓ ' + (d.imported || 0) + ' ప్రశ్నలు ఇంపోర్ట్';
        return loadTopicConfig(topicId);
      }).catch(function (e) {
        el.saveExamBtn.disabled = false;
        if (el.saveExamMsg) el.saveExamMsg.textContent = e.message;
      });
    });
  }
  if (el.saveExamSuiteBtn) {
    el.saveExamSuiteBtn.addEventListener('click', function () {
      var topicId = parseInt(el.topic.value, 10);
      if (!topicId) return;
      el.saveExamSuiteBtn.disabled = true;
      if (el.saveExamSuiteMsg) el.saveExamSuiteMsg.textContent = 'సేవ్…';
      postJson({ action: 'save_topic_exam_suite', topic_id: topicId, exam_suite: collectExamSuite() }).then(function (d) {
        el.saveExamSuiteBtn.disabled = false;
        if (!d.ok) throw new Error(d.error);
        if (el.saveExamSuiteMsg) el.saveExamSuiteMsg.textContent = '✓ ఎగ్జామ్ సూట్ సేవ్';
        return loadTopicConfig(topicId);
      }).catch(function (e) {
        el.saveExamSuiteBtn.disabled = false;
        if (el.saveExamSuiteMsg) el.saveExamSuiteMsg.textContent = e.message;
      });
    });
  }

  if (el.saveMainBtn) {
    el.saveMainBtn.addEventListener('click', function () {
      var id = parseInt(el.main.value, 10);
      if (!id) return;
      postJson({ action: 'save_main_course', id: id, name: el.mainName.value.trim(), name_te: el.mainNameTe ? el.mainNameTe.value.trim() : '' }).then(function (d) {
        if (!d.ok) throw new Error(d.error);
        if (el.saveMainMsg) el.saveMainMsg.textContent = '✓ సేవ్';
        loadMainCourses();
      }).catch(function (e) { if (el.saveMainMsg) el.saveMainMsg.textContent = e.message; });
    });
  }
  function updateContextSummary() {
    if (!el.contextSummary) return;
    var parts = [];
    var mo = el.main.options[el.main.selectedIndex];
    var so = el.sub.options[el.sub.selectedIndex];
    var ju = el.subject.options[el.subject.selectedIndex];
    var to = el.topic.options[el.topic.selectedIndex];
    if (mo && mo.value) parts.push('మెయిన్: ' + mo.textContent);
    if (so && so.value) parts.push('సబ్: ' + so.textContent);
    if (ju && ju.value) parts.push('సబ్జెక్ట్: ' + ju.textContent);
    if (to && to.value) parts.push('టాపిక్: ' + to.textContent);
    el.contextSummary.textContent = parts.length ? '✓ ' + parts.join(' · ') : 'మెయిన్ కోర్స్ ఎంచుకోండి — అన్ని టాబ్‌లలో ఈ సెలెక్షన్ వర్తిస్తుంది.';
  }

  function persistCascade() {
    try {
      sessionStorage.setItem('cmCascade', JSON.stringify({
        main: el.main.value || '',
        sub: el.sub.value || '',
        subject: el.subject.value || '',
        topic: el.topic.value || '',
      }));
    } catch (e) {}
  }

  function requireMainCourseId() {
    var cid = parseInt(el.main.value, 10);
    if (!cid) throw new Error('ముందుగా మెయిన్ కోర్స్ ఎంచుకోండి (పైన హైరార్కీ బార్).');
    return cid;
  }
  function requireSubCourseId() {
    var scid = parseInt(el.sub.value, 10);
    if (!scid) throw new Error('ముందుగా సబ్ కోర్స్ ఎంచుకోండి.');
    return scid;
  }

  if (el.saveSubBtn) {
    el.saveSubBtn.addEventListener('click', function () {
      try {
        var cid = requireMainCourseId();
        var id = parseInt(el.sub.value, 10);
        if (!id) throw new Error('సబ్ కోర్స్ ఎంచుకోండి లేదా క్రొత్తది సృష్టించండి.');
        var name = el.subNameEdit.value.trim();
        if (!name) throw new Error('సబ్ కోర్స్ పేరు నమోదు చేయండి.');
        el.saveSubBtn.disabled = true;
        if (el.saveSubMsg) el.saveSubMsg.textContent = 'సేవ్…';
        postJson({ action: 'save_sub_course', id: id, course_id: cid, name: name, name_te: el.subNameTeEdit ? el.subNameTeEdit.value.trim() : '', is_active: 1 }).then(function (d) {
          el.saveSubBtn.disabled = false;
          if (!d.ok) throw new Error(d.error || 'Save failed');
          if (el.saveSubMsg) el.saveSubMsg.textContent = '✓ సబ్ కోర్స్ సేవ్ అయ్యింది';
          loadSubCourseTermMatrix(id);
        }).catch(function (e) {
          el.saveSubBtn.disabled = false;
          if (el.saveSubMsg) el.saveSubMsg.textContent = e.message;
        });
      } catch (e) {
        if (el.saveSubMsg) el.saveSubMsg.textContent = e.message;
      }
    });
  }
  if (el.saveSubjectBtn) {
    el.saveSubjectBtn.addEventListener('click', function () {
      try {
        var scid = requireSubCourseId();
        requireMainCourseId();
        var id = parseInt(el.subject.value, 10);
        if (!id) throw new Error('సబ్జెక్ట్ ఎంచుకోండి లేదా క్రొత్తది సృష్టించండి.');
        var name = el.subjectNameEdit.value.trim();
        if (!name) throw new Error('సబ్జెక్ట్ పేరు నమోదు చేయండి.');
        el.saveSubjectBtn.disabled = true;
        if (el.saveSubjectMsg) el.saveSubjectMsg.textContent = 'సేవ్…';
        postJson({ action: 'save_subject', id: id, sub_course_id: scid, name: name, name_te: el.subjectNameTeEdit ? el.subjectNameTeEdit.value.trim() : '', is_active: 1 }).then(function (d) {
          el.saveSubjectBtn.disabled = false;
          if (!d.ok) throw new Error(d.error || 'Save failed');
          if (el.saveSubjectMsg) {
            el.saveSubjectMsg.textContent = '✓ సబ్జెక్ట్ సేవ్ · slug: ' + (d.slug || '');
            if (d.public_url) el.saveSubjectMsg.innerHTML += ' <a href="' + esc(d.public_url) + '" target="_blank" rel="noopener" class="text-royal underline">పబ్లిక్ లింక్</a>';
          }
          return fetchJson(api + '?action=subjects&sub_course_id=' + scid);
        }).then(function (td) {
          if (td.ok) fillSelect(el.subject, td.items, 'id', function (s) { return s.name_te || s.name; });
          el.subject.value = String(id);
        }).catch(function (e) {
          el.saveSubjectBtn.disabled = false;
          if (el.saveSubjectMsg) el.saveSubjectMsg.textContent = e.message;
        });
      } catch (e) {
        if (el.saveSubjectMsg) el.saveSubjectMsg.textContent = e.message;
      }
    });
  }
  if (el.saveTopicMetaBtn) {
    el.saveTopicMetaBtn.addEventListener('click', function () {
      var id = parseInt(el.topic.value, 10);
      if (!id) return;
      postJson({ action: 'save_topic_meta', topic_id: id, title: el.topicTitleEdit.value.trim(), title_te: el.topicTitleTeEdit ? el.topicTitleTeEdit.value.trim() : '' }).then(function (d) {
        if (!d.ok) throw new Error(d.error);
        if (el.saveTopicMetaMsg) el.saveTopicMetaMsg.textContent = '✓ సేవ్';
        el.subject.dispatchEvent(new Event('change'));
        el.topic.value = String(id);
        loadTopicConfig(id);
      }).catch(function (e) { if (el.saveTopicMetaMsg) el.saveTopicMetaMsg.textContent = e.message; });
    });
  }

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
            loadSubCourseTermMatrix(parseInt(d.sub_course_id, 10) || 0);
            return chainSelect(el.subject, api + '?action=subjects&sub_course_id=' + d.sub_course_id, el.subject, function (s) { return s.name_te || s.name; })
              .then(function () {
                if (socSubject) { socSubject.setDisabled(false); socSubject.syncFromSelect(); }
              });
          });
      });
  }

  function restoreCascade() {
    try {
      var raw = sessionStorage.getItem('cmCascade');
      if (!raw) return Promise.resolve();
      var saved = JSON.parse(raw);
      if (!saved.main) return Promise.resolve();
      el.main.value = saved.main;
      return chainSelect(el.sub, api + '?action=sub_courses&course_id=' + saved.main, el.sub, function (s) { return s.name_te || s.name; })
        .then(function () {
          if (saved.sub) {
            el.sub.value = saved.sub;
            loadSubCourseTermMatrix(parseInt(saved.sub, 10) || 0);
            return chainSelect(el.subject, api + '?action=subjects&sub_course_id=' + saved.sub, el.subject, function (s) { return s.name_te || s.name; });
          }
        })
        .then(function () {
          if (saved.subject) {
            el.subject.value = saved.subject;
            return chainSelect(el.topic, api + '?action=topics&subject_id=' + saved.subject, el.topic, function (t) { return t.title_te || t.title; });
          }
        })
        .then(function () {
          if (saved.topic) {
            el.topic.value = saved.topic;
            if (socTopic) socTopic.syncFromSelect();
          }
          if (socSubject && el.subject.value) socSubject.syncFromSelect();
          loadEntityBlocks();
          updateContextSummary();
        });
    } catch (e) {
      return Promise.resolve();
    }
  }

  loadMainCourses().then(function () {
    if (deepMc && deepSc) return bootDeepLink();
    return restoreCascade();
  }).then(function () {
    updateContextSummary();
    if (el.main.value && typeof window.bootWizardFromMain === 'function') window.bootWizardFromMain();
  }).catch(function (e) { if (el.status) el.status.textContent = e.message; });

<?php require __DIR__ . '/partials/cm_wizard_script.php'; ?>
})();
</script>
<?php endif; ?>
