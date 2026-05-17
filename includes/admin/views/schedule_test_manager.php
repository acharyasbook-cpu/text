<?php
/** @var list<array<string,mixed>> $courses */
/** @var list<array<string,mixed>> $subCourses */
$apiUrl = admin_url('schedule_api.php');
$csrf = admin_csrf_token();
?>
<div id="stm-root" class="font-telugu stm-dashboard" data-api="<?= admin_e($apiUrl) ?>" data-csrf="<?= admin_e($csrf) ?>">

  <!-- Permanent color legend -->
  <div class="admin-card p-4 mb-5 stm-legend-card">
    <h2 class="text-sm font-bold text-slate-900 font-telugu mb-3">షెడ్యూల్ కలర్ సూచిక (Schedule Color Guide)</h2>
    
    
    <div class="grid md:grid-cols-3 gap-3">
      
      <div class="stm-legend-item stm-legend-item--green flex gap-3 items-start p-3 rounded-xl border border-emerald-200 bg-emerald-50/80">
        <span class="stm-swatch stm-swatch--green shrink-0" aria-hidden="true">🟢</span>
        <div>
          <p class="text-xs font-bold text-emerald-900">ప్యూర్ గ్రీన్ / Pure Green</p>
          <p class="text-xs text-emerald-800 mt-0.5 leading-relaxed">విజయవంతంగా క్వశ్చన్స్ మరియు టాపిక్స్ అన్నీ రెడీ అయిన రోజులు (Fully Configured)</p>
        </div>
      </div>
      <div class="stm-legend-item stm-legend-item--orange flex gap-3 items-start p-3 rounded-xl border border-orange-200 bg-orange-50/80">
        <span class="stm-swatch stm-swatch--orange shrink-0 stm-flash" aria-hidden="true">🟠</span>
        
        <div>
          <p class="text-xs font-bold text-orange-900">ఫ్లాషింగ్ ఆరెంజ్ / Flashing Orange</p>
          <p class="text-xs text-orange-800 mt-0.5 leading-relaxed">టాపిక్స్ సేవ్ అయ్యాయి కానీ లోపల ప్రశ్నలు లేవు (Missing Bits Alert — Add MCQs)</p>
        </div>
      </div>
      <div class="stm-legend-item stm-legend-item--red flex gap-3 items-start p-3 rounded-xl border border-red-200 bg-red-50/60">
        <span class="stm-swatch stm-swatch--red shrink-0" aria-hidden="true">🔴</span>
        <div>
          <p class="text-xs font-bold text-red-900">మ్యూటెడ్ రెడ్ / Muted Red</p>
          <p class="text-xs text-red-800/90 mt-0.5 leading-relaxed">సిలబస్ ప్రకారం ఇంకా షెడ్యూల్ చేయవలసిన పెండింగ్ రోజులు (Pending / Unscheduled)</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Context bar -->
  <div class="admin-card p-4 mb-5">
    <div class="grid md:grid-cols-4 gap-3">
      <div>
        <label class="admin-label text-xs">Course</label>
        <select id="stm-course" class="admin-input w-full mt-1 text-sm">
          <option value="">—</option>
          <?php foreach ($courses as $c): ?>
          <option value="<?= (int) $c['id'] ?>"><?= admin_e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="admin-label text-xs font-telugu">సబ్-కోర్స్</label>
        <select id="stm-subcourse" class="admin-input w-full mt-1 text-sm">
          <option value="">—</option>
          <?php foreach ($subCourses as $sc): ?>
          <option value="<?= (int) $sc['id'] ?>" data-course="<?= (int) ($sc['course_id'] ?? 0) ?>">
            <?= admin_e($sc['name'] ?? '') ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="admin-label text-xs font-telugu">సక్రియ టర్మ్ (Active Term)</label>
        <select id="stm-term" class="admin-input w-full mt-1 text-sm">
          <option value="short_term">షార్ట్ టర్మ్</option>
          <option value="long_term">లాంగ్ టర్మ్</option>
        </select>
      </div>
      <div class="flex flex-wrap gap-3 items-end">
        <label class="flex items-center gap-2 text-sm cursor-pointer">
          <input type="radio" name="stm-mode" value="day_wise" checked class="text-brand" /> రోజువారీ
        </label>
        <label class="flex items-center gap-2 text-sm cursor-pointer">
          <input type="radio" name="stm-mode" value="date_wise" class="text-brand" /> తేదీవారీ
        </label>
      </div>
    </div>
    <p id="stm-msg" class="text-xs text-slate-500 mt-3 min-h-[1rem] font-telugu"></p>
  </div>

  <!-- Dual tracker boards -->
  
  <div id="stm-dual-tracker" class="grid lg:grid-cols-2 gap-5 mb-5 hidden">
    <div class="admin-card p-4" id="stm-board-short">
      <h3 class="admin-card-title text-base font-telugu mb-1">Short-Term Planner Status</h3>
      <p class="text-xs text-slate-500 mb-3" id="stm-short-label">—</p>
      <div class="mb-2 flex justify-between text-xs font-telugu">
        <span>సిలబస్ కవరేజ్</span>
        <span id="stm-short-coverage-pct" class="font-bold text-brand">0%</span>
      </div>
      <div class="h-2 bg-slate-100 rounded-full overflow-hidden mb-3">
        
        <div id="stm-short-coverage-bar" class="h-full bg-[#4F46E5] rounded-full transition-all" style="width:0%"></div>
      </div>
      <p class="text-[10px] text-slate-500 mb-2 font-telugu" id="stm-short-coverage-detail">0 / 0 టాపిక్‌లు</p>
      <div id="stm-matrix-short" class="stm-status-matrix"></div>
    </div>
    <div class="admin-card p-4" id="stm-board-long">
      <h3 class="admin-card-title text-base font-telugu mb-1">Long-Term Planner Status</h3>
      <p class="text-xs text-slate-500 mb-3" id="stm-long-label">—</p>
      <div class="mb-2 flex justify-between text-xs font-telugu">
        <span>సిలబస్ కవరేజ్</span>
        <span id="stm-long-coverage-pct" class="font-bold text-brand">0%</span>
      </div>
      <div class="h-2 bg-slate-100 rounded-full overflow-hidden mb-3">
        <div id="stm-long-coverage-bar" class="h-full bg-[#4F46E5] rounded-full transition-all" style="width:0%"></div>
      </div>
      <p class="text-[10px] text-slate-500 mb-2 font-telugu" id="stm-long-coverage-detail">0 / 0 టాపిక్‌లు</p>
      <div id="stm-matrix-long" class="stm-status-matrix"></div>
    </div>
  </div>

  <!-- Main planner: calendar | editor | preview -->
  <div class="grid xl:grid-cols-12 gap-5">
    <div class="xl:col-span-3 admin-card p-4">
      <div class="flex items-center justify-between mb-3">
        <h2 class="admin-card-title text-base font-telugu">క్యాలెండర్</h2>
        <input type="month" id="stm-month" class="admin-input text-xs py-1" />
      </div>
      <div id="stm-calendar" class="stm-calendar grid grid-cols-7 gap-1 text-xs"></div>
    </div>

    <div class="xl:col-span-5 space-y-4">
      <div class="admin-card p-4">
        <div class="flex flex-wrap gap-3 items-center justify-between mb-4">
          <h2 class="admin-card-title text-base font-telugu" id="stm-day-title">రోజు ఎంచుకోండి</h2>
          <div class="flex flex-wrap gap-2">
            <button type="button" id="stm-copy-layout" class="admin-btn admin-btn-secondary text-xs font-telugu" disabled>మునుపటి రోజు నుండి కాపీ</button>
            <button type="button" id="stm-save-day" class="admin-btn admin-btn--primary text-xs font-telugu" disabled>షెడ్యూల్ సేవ్</button>
          </div>
        </div>
        <div id="stm-day-meta" class="grid sm:grid-cols-2 gap-3 mb-4 hidden">
          <div id="stm-wrap-day-index">
            <label class="admin-label text-xs font-telugu">రోజు సంఖ్య</label>
            <input type="number" id="stm-day-index" min="1" class="admin-input w-full mt-1" />
          </div>
          <div id="stm-wrap-schedule-date" class="hidden">
            <label class="admin-label text-xs font-telugu">తేదీ</label>
            <input type="date" id="stm-schedule-date" class="admin-input w-full mt-1" />
          </div>
        </div>
        <p class="text-sm font-semibold text-brand mb-2 font-telugu">
          మొత్తం మార్కులు: <span id="stm-total-marks">0</span>
          <span class="text-slate-500 font-normal text-xs ml-2">(<span id="stm-staged-count">0</span> విషయాలు)</span>
        </p>
        <div id="stm-composer" class="border border-slate-200 rounded-xl p-4 mb-4 bg-slate-50/60">
          <p class="text-xs font-bold text-slate-800 mb-3 font-telugu">విషయం → టాపిక్‌లు ఎంపిక (Multi-Subject)</p>
          <label class="admin-label text-xs font-telugu">విషయం (Subject)</label>
          <select id="stm-composer-subject" class="admin-input w-full text-sm mb-3 font-telugu" disabled>
            <option value="">— విషయం —</option>
          </select>
          <label class="admin-label text-xs font-telugu">టాపిక్‌లు (Topics)</label>
          <div id="stm-composer-topics" class="stm-topic-picker border border-slate-200 rounded-lg p-2 mb-3 max-h-40 overflow-y-auto text-xs bg-white">
            <p class="text-slate-400 font-telugu">ముందుగా విషయం ఎంచుకోండి</p>
          </div>
          <div class="flex flex-wrap gap-3 items-end">
            <label class="text-xs font-telugu">మార్కులు
              <input type="number" id="stm-composer-marks" class="admin-input w-20 ml-1" min="1" value="25" />
            </label>
            <button type="button" id="stm-add-to-list" class="admin-btn admin-btn-secondary text-xs font-telugu" disabled>
              + జాబితాకు జోడించండి (Add to List)
            </button>
          </div>
          <p id="stm-composer-msg" class="text-xs text-slate-500 mt-2 min-h-[1rem]"></p>
        </div>
        <div id="stm-staged-list" class="space-y-2 mb-2"></div>
      </div>
    </div>

    <div class="xl:col-span-4">
      <div class="admin-card p-4 stm-preview-panel sticky top-24" id="stm-preview-panel">
        <h2 class="admin-card-title text-base font-telugu mb-3">త్వరిత ప్రివ్యూ (Instant Preview)</h2>
        <div id="stm-staged-preview" class="mb-4 hidden">
          <h3 class="text-xs font-bold text-brand mb-2 font-telugu">ఈ రోజు షెడ్యూల్ జాబితా (Staging)</h3>
          <div id="stm-staged-preview-body"></div>
        </div>
        <hr id="stm-preview-divider" class="border-slate-200 my-3 hidden" />
        <div id="stm-preview-body" class="text-sm text-slate-500 font-telugu min-h-[8rem]">
          రోజు బ్లాక్ పై క్లిక్ చేయండి లేదా హోవర్ చేయండి — వివరాలు ఇక్కడ లోడ్ అవుతాయి.
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.stm-dashboard { --stm-green: #10b981; --stm-orange: #f97316; --stm-red: #f87171; }
.stm-swatch--green { color: var(--stm-green); }
.stm-swatch--orange { color: var(--stm-orange); }
.stm-swatch--red { color: var(--stm-red); opacity: .85; }
@keyframes stm-flash-pulse {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: .55; transform: scale(1.08); }
}
.stm-flash { animation: stm-flash-pulse 1.4s ease-in-out infinite; }
.stm-status-matrix {
  display: flex; flex-wrap: wrap; gap: 4px;
  max-height: 10rem; overflow-y: auto; padding: 4px;
  background: #f8fafc; border-radius: .5rem; border: 1px solid #e2e8f0;
}
.stm-slot {
  width: 1.65rem; height: 1.65rem; font-size: 9px; font-weight: 700;
  border-radius: .35rem; border: 1px solid rgba(0,0,0,.06);
  cursor: pointer; color: #fff; display: flex; align-items: center; justify-content: center;
  transition: transform .12s, box-shadow .12s;
}
.stm-slot:hover { transform: scale(1.12); box-shadow: 0 2px 8px rgba(79,70,229,.25); z-index: 2; }
.stm-slot.is-selected { outline: 2px solid #4F46E5; outline-offset: 1px; }
.stm-slot--complete { background: var(--stm-green); }
.stm-slot--missing_mcq { background: var(--stm-orange); animation: stm-flash-pulse 1.4s ease-in-out infinite; }
.stm-slot--pending { background: var(--stm-red); opacity: .72; }
.stm-calendar .stm-cell {
  min-height: 2.5rem; border: 1px solid #e2e8f0; border-radius: .5rem;
  padding: .25rem; background: #fff; cursor: pointer; position: relative;
}
.stm-calendar .stm-cell.is-selected { border-color: #4F46E5; background: #eef2ff; }
.stm-calendar .stm-cell.stm-cell--complete { border-color: #10b981; background: #ecfdf5; }
.stm-calendar .stm-cell.stm-cell--missing_mcq { border-color: #f97316; background: #fff7ed; }
.stm-calendar .stm-cell.stm-cell--pending { border-color: #fecaca; background: #fef2f2; }
.stm-calendar .stm-badge { font-size: 9px; border-radius: 4px; padding: 1px 4px; display: block; margin-top: 2px; color: #fff; }
.stm-calendar .stm-badge--complete { background: var(--stm-green); }
.stm-calendar .stm-badge--missing_mcq { background: var(--stm-orange); }
.stm-calendar .stm-badge--pending { background: var(--stm-red); opacity: .8; }
.stm-row { border: 1px solid #e2e8f0; border-radius: .75rem; padding: 1rem; background: #fff; cursor: grab; }
.stm-row.is-dragging { opacity: .5; }
.stm-tabs { display: flex; gap: .5rem; margin: .75rem 0; flex-wrap: wrap; }
.stm-tab { padding: .35rem .75rem; font-size: 12px; border-radius: .5rem; border: 1px solid #e2e8f0; cursor: pointer; background: #fff; }
.stm-tab.is-active { background: #4F46E5; color: #fff; border-color: #4F46E5; }
.stm-preview-table { width: 100%; font-size: 12px; border-collapse: collapse; }
.stm-preview-table th, .stm-preview-table td { border-bottom: 1px solid #e2e8f0; padding: .5rem .35rem; text-align: left; vertical-align: top; }
.stm-preview-table th { color: #64748b; font-weight: 600; }
.stm-fatigue-badge {
  background: #fff7ed; border: 1px solid #fdba74; color: #c2410c;
  font-size: 11px; padding: .5rem .75rem; border-radius: .5rem; margin-bottom: .75rem;
}
.stm-lock-toggle { display: flex; align-items: center; gap: .5rem; font-size: 12px; margin: .75rem 0; }
</style>

<script src="<?= admin_e(admin_site_url('assets/js/schedule-test-manager.js')) ?>?v=4"></script>
