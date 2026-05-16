  <section id="cmTermMatrixSection" class="cm-card mb-6 overflow-hidden hidden">
    <div class="px-5 py-3 border-b border-[#E3E6F0] bg-white">
      <h2 class="text-sm font-bold text-slate-900 font-telugu">సబ్జెక్ట్ కాలమ్ మ్యాట్రిక్స్ (షార్ట్ / లాంగ్ టర్మ్)</h2>
      <p class="text-xs text-slate-600 mt-0.5">ఫ్రంట్‌ఎండ్ బాక్స్ పేర్లు · టాగిల్ · 250-రోజు షెడ్యూల్</p>
    </div>
    <div class="p-5 bg-white space-y-5">
      <p id="cmTermMatrixMigrateHint" class="hidden text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
        Run: <code>php database/migrate_subject_term_matrix.php</code>
      </p>
      <div class="grid sm:grid-cols-2 gap-4 pb-4 border-b border-[#E3E6F0]">
        <div>
          <label class="cm-label text-xs font-telugu">గ్లోబల్ షార్ట్ టర్మ్ పేరు</label>
          <input type="text" id="cmGlobalShortTe" class="cm-input w-full rounded-lg px-3 py-2 text-sm font-telugu mt-1" />
          <label class="flex items-center gap-2 mt-2 text-xs font-telugu"><input type="checkbox" id="cmGlobalShortOn" class="rounded" checked /> బాక్స్ చూపించు</label>
        </div>
        <div>
          <label class="cm-label text-xs font-telugu">గ్లోబల్ లాంగ్ టర్మ్ పేరు</label>
          <input type="text" id="cmGlobalLongTe" class="cm-input w-full rounded-lg px-3 py-2 text-sm font-telugu mt-1" />
          <label class="flex items-center gap-2 mt-2 text-xs font-telugu"><input type="checkbox" id="cmGlobalLongOn" class="rounded" checked /> బాక్స్ చూపించు</label>
        </div>
      </div>
      <button type="button" id="cmSaveTermGlobals" class="text-xs font-semibold text-royal">గ్లోబల్ లేబుల్‌లు సేవ్</button>
      <div class="grid md:grid-cols-2 gap-4 pt-2">
        <div class="border border-[#E3E6F0] rounded-lg p-4">
          <p class="text-xs font-bold text-slate-800 mb-2 font-telugu">ఈ సబ్జెక్ట్ — షార్ట్ టర్మ్</p>
          <input type="text" id="cmShortLabelTe" class="cm-input w-full rounded-lg px-3 py-2 text-sm font-telugu" placeholder="షార్ట్ టర్మ్" />
          <label class="flex items-center gap-2 mt-3 text-xs font-telugu font-semibold">
            <input type="checkbox" id="cmShortEnabled" class="rounded" checked /> Box Matrix Toggle (ఎనేబుల్)
          </label>
        </div>
        <div class="border border-[#E3E6F0] rounded-lg p-4">
          <p class="text-xs font-bold text-slate-800 mb-2 font-telugu">ఈ సబ్జెక్ట్ — లాంగ్ టర్మ్</p>
          <input type="text" id="cmLongLabelTe" class="cm-input w-full rounded-lg px-3 py-2 text-sm font-telugu" placeholder="లాంగ్ టర్మ్" />
          <label class="flex items-center gap-2 mt-3 text-xs font-telugu font-semibold">
            <input type="checkbox" id="cmLongEnabled" class="rounded" checked /> Box Matrix Toggle (ఎనేబుల్)
          </label>
        </div>
      </div>
      <button type="button" id="cmSaveTermMatrix" class="px-6 py-2.5 rounded-lg bg-slate-900 text-white text-sm font-bold font-telugu" disabled>సబ్జెక్ట్ మ్యాట్రిక్స్ సేవ్</button>
      <p id="cmTermMatrixMsg" class="text-xs text-slate-500 min-h-[1rem]"></p>
    </div>
  </section>
