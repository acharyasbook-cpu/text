<?php
/** Atomic save blocks for hierarchy entities. */
?>
<div class="cm-entity-grid grid lg:grid-cols-2 gap-4 mt-4 pt-4 border-t border-[#E3E6F0]">
  <div class="cm-entity-block border border-[#E3E6F0] rounded-lg p-4" id="cmBlockMain" hidden>
    <h3 class="text-xs font-bold text-slate-900 font-telugu mb-3">మెయిన్ కోర్స్</h3>
    <label class="cm-label text-[11px]">పేరు (EN)</label>
    <input type="text" id="cmMainName" class="cm-input w-full rounded-lg px-3 py-2 text-sm mb-2" />
    <label class="cm-label text-[11px]">పేరు (TE)</label>
    <input type="text" id="cmMainNameTe" class="cm-input w-full rounded-lg px-3 py-2 text-sm font-telugu mb-3" />
    <button type="button" id="cmSaveMainCourseBtn" class="cm-save-btn">Save Main Course</button>
    <p id="cmSaveMainMsg" class="text-xs text-slate-500 mt-2 min-h-[1rem]"></p>
  </div>
  <div class="cm-entity-block border border-[#E3E6F0] rounded-lg p-4" id="cmBlockSub" hidden>
    <h3 class="text-xs font-bold text-slate-900 font-telugu mb-3">సబ్ కోర్స్</h3>
    <label class="cm-label text-[11px]">పేరు (EN)</label>
    <input type="text" id="cmSubNameEdit" class="cm-input w-full rounded-lg px-3 py-2 text-sm mb-2" />
    <label class="cm-label text-[11px]">పేరు (TE)</label>
    <input type="text" id="cmSubNameTeEdit" class="cm-input w-full rounded-lg px-3 py-2 text-sm font-telugu mb-3" />
    <button type="button" id="cmSaveSubCourseBtn" class="cm-save-btn">Save Sub-Course</button>
    <p id="cmSaveSubMsg" class="text-xs text-slate-500 mt-2 min-h-[1rem]"></p>
  </div>
  <div class="cm-entity-block border border-[#E3E6F0] rounded-lg p-4" id="cmBlockSubject" hidden>
    <h3 class="text-xs font-bold text-slate-900 font-telugu mb-3">సబ్జెక్ట్</h3>
    <label class="cm-label text-[11px]">పేరు (EN)</label>
    <input type="text" id="cmSubjectNameEdit" class="cm-input w-full rounded-lg px-3 py-2 text-sm mb-2" />
    <label class="cm-label text-[11px]">పేరు (TE)</label>
    <input type="text" id="cmSubjectNameTeEdit" class="cm-input w-full rounded-lg px-3 py-2 text-sm font-telugu mb-3" />
    <button type="button" id="cmSaveSubjectBtn" class="cm-save-btn">Save Subject</button>
    <p id="cmSaveSubjectMsg" class="text-xs text-slate-500 mt-2 min-h-[1rem]"></p>
  </div>
  <div class="cm-entity-block border border-[#E3E6F0] rounded-lg p-4" id="cmBlockTopic" hidden>
    <h3 class="text-xs font-bold text-slate-900 font-telugu mb-3">టాపిక్</h3>
    <label class="cm-label text-[11px]">టాపిక్ పేరు</label>
    <input type="text" id="cmTopicTitleEdit" class="cm-input w-full rounded-lg px-3 py-2 text-sm mb-2" />
    <label class="cm-label text-[11px]">టాపిక్ (TE)</label>
    <input type="text" id="cmTopicTitleTeEdit" class="cm-input w-full rounded-lg px-3 py-2 text-sm font-telugu mb-3" />
    <button type="button" id="cmSaveTopicMetaBtn" class="cm-save-btn">Save Topic</button>
    <p id="cmSaveTopicMetaMsg" class="text-xs text-slate-500 mt-2 min-h-[1rem]"></p>
  </div>
</div>
