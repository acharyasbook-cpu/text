<?php
/** Schedule Test tab — auto sub-course term matrix (no manual slug typing). */
?>
  <section id="cmTermMatrixSection" class="cm-card mb-6 overflow-hidden">
    <div class="px-5 py-3 border-b border-[#E3E6F0] bg-white">
      <h2 class="text-sm font-bold text-slate-900 font-telugu">షెడ్యూల్ టెస్ట్ (Schedule Test)</h2>
      <p class="text-xs text-slate-600 mt-0.5 font-telugu">పై కాంటెక్స్ట్ బార్ నుండి సబ్-కోర్స్ ఎంచుకోండి — లేబుల్ &amp; రూటింగ్ కీలు ఆటోమేటిక్</p>
    </div>
    <div class="p-5 bg-white space-y-5">
      <p id="cmTermMatrixMigrateHint" class="hidden text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 font-telugu">
        Run: <code>php database/migrate_structural_refactor.php</code>
      </p>
      <p id="cmScheduleSubPickHint" class="text-sm text-slate-500 font-telugu border border-dashed border-[#E3E6F0] rounded-lg px-4 py-3 text-center">
        మెయిన్ కోర్స్ + సబ్ కోర్స్ (ఉదా. AP SA Telugu) ఎంచుకోండి — షార్ట్/లాంగ్ టర్మ్ మ్యాట్రిక్స్ ఆటో-లోడ్ అవుతాయి.
      </p>
      <div id="cmScheduleMatrixBody" class="hidden space-y-5">
        <p id="cmScheduleSubTitle" class="text-sm font-bold text-slate-900 font-telugu"></p>
        <p class="text-[11px] text-slate-500 font-telugu">రూటింగ్: <code>short</code> → <code>short_term</code> · <code>long</code> → <code>long_term</code></p>
        <div class="grid md:grid-cols-2 gap-4">
          <div class="border border-[#E3E6F0] rounded-lg p-4 bg-slate-50/50">
            <p class="text-xs font-bold text-slate-800 mb-1 font-telugu">షార్ట్ టర్మ్ (ఆటో)</p>
            <p id="cmShortLabelDisplay" class="text-sm font-semibold text-slate-900 font-telugu min-h-[1.25rem]">—</p>
            <label class="flex items-center gap-2 mt-3 text-xs font-telugu font-semibold">
              <input type="checkbox" id="cmShortEnabled" class="rounded" checked /> మ్యాట్రిక్స్ బాక్స్ ఎనేబుల్
            </label>
          </div>
          <div class="border border-[#E3E6F0] rounded-lg p-4 bg-slate-50/50">
            <p class="text-xs font-bold text-slate-800 mb-1 font-telugu">లాంగ్ టర్మ్ (ఆటో)</p>
            <p id="cmLongLabelDisplay" class="text-sm font-semibold text-slate-900 font-telugu min-h-[1.25rem]">—</p>
            <label class="flex items-center gap-2 mt-3 text-xs font-telugu font-semibold">
              <input type="checkbox" id="cmLongEnabled" class="rounded" checked /> మ్యాట్రిక్స్ బాక్స్ ఎనేబుల్
            </label>
          </div>
        </div>
        <button type="button" id="cmSaveTermMatrix" class="px-6 py-2.5 rounded-lg bg-slate-900 text-white text-sm font-bold font-telugu">షెడ్యూల్ టెస్ట్ సేవ్</button>
      </div>
      <p id="cmTermMatrixMsg" class="text-xs text-slate-500 min-h-[1rem] font-telugu"></p>
    </div>
  </section>
