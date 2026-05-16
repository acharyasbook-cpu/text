<?php
/** Linear cascade ordering wizard — step-by-step hierarchy sort. */
$reorderApi = admin_url('ajax/reorder_content.php');
?>
<section id="cmWizard" class="cm-card mb-6 overflow-hidden border-2 border-slate-200 font-telugu"
         data-reorder-api="<?= admin_e($reorderApi) ?>">
  <div class="px-5 py-4 border-b border-[#E3E6F0] bg-gradient-to-r from-slate-50 to-white">
    <h2 class="text-base font-bold text-slate-900 font-telugu">లీనియర్ కంటెంట్ విజార్డ్</h2>
    <p class="text-[11px] text-slate-600 mt-1 font-telugu">మెయిన్ కోర్స్ ఎంచుకోండి → దశలవారీ క్రమం సేవ్ చేయండి · పేజీ రీలోడ్ అవ్వదు</p>
    <ol id="cmWizardProgress" class="cm-wizard-progress mt-4" aria-label="Wizard progress">
      <li class="cm-wizard-step" data-step="sub_course"><span>1</span> సబ్ కోర్స్</li>
      <li class="cm-wizard-step" data-step="subject"><span>2</span> సబ్జెక్ట్</li>
      <li class="cm-wizard-step" data-step="topic"><span>3</span> టాపిక్</li>
      <li class="cm-wizard-step cm-wizard-step--branch" data-step="sub_topic"><span>4</span> సబ్-టాపిక్</li>
      <li class="cm-wizard-step" data-step="notes_exam"><span>5</span> నోట్స్ &amp; ఎగ్జామ్</li>
    </ol>
  </div>

  <div class="p-5 space-y-4 bg-white" id="cmWizardStages">
    <p id="cmWizardHint" class="text-sm text-slate-500 font-telugu text-center py-6 border border-dashed border-[#E3E6F0] rounded-lg">
      పైన మెయిన్ కోర్స్ (ఉదా. AP DSC) ఎంచుకోండి — సబ్ కోర్స్‌ల జాబితా ఇక్కడ తెరుచుకుంటుంది.
    </p>

    <article class="cm-wizard-stage is-locked" data-wizard-stage="sub_course" id="cmWizardStageSub">
      <header class="cm-wizard-stage-head">
        <span class="cm-wizard-badge">దశ 1</span>
        <h3 class="font-telugu">సబ్ కోర్స్ క్రమం</h3>
        <p class="cm-wizard-stage-desc font-telugu">మెయిన్ కోర్స్‌కు చెందిన అన్ని సబ్ కోర్స్‌లు · ⋮⋮ లాగి లేదా ▲▼</p>
      </header>
      <ul id="cmWizardListSub" class="cm-sort-list" aria-label="Sub courses order"></ul>
      <footer class="cm-wizard-stage-foot">
        <button type="button" class="cm-wizard-save-btn" data-wizard-commit="sub_course">సబ్ కోర్స్ క్రమం సేవ్</button>
      </footer>
    </article>

    <article class="cm-wizard-stage is-locked" data-wizard-stage="subject" id="cmWizardStageSubject">
      <header class="cm-wizard-stage-head">
        <span class="cm-wizard-badge">దశ 2</span>
        <h3 class="font-telugu">సబ్జెక్ట్ క్రమం</h3>
        <p class="cm-wizard-stage-desc font-telugu">ఎంచుకున్న సబ్ కోర్స్‌కు సంబంధించిన సబ్జెక్ట్‌లు</p>
      </header>
      <ul id="cmWizardListSubject" class="cm-sort-list" aria-label="Subjects order"></ul>
      <footer class="cm-wizard-stage-foot">
        <button type="button" class="cm-wizard-save-btn" data-wizard-commit="subject">సబ్జెక్ట్ క్రమం సేవ్</button>
      </footer>
    </article>

    <article class="cm-wizard-stage is-locked" data-wizard-stage="topic" id="cmWizardStageTopic">
      <header class="cm-wizard-stage-head">
        <span class="cm-wizard-badge">దశ 3</span>
        <h3 class="font-telugu">టాపిక్ క్రమం</h3>
        <p class="cm-wizard-stage-desc font-telugu">ఎంచుకున్న సబ్జెక్ట్‌కు చెందిన టాపిక్‌లు</p>
      </header>
      <ul id="cmWizardListTopic" class="cm-sort-list" aria-label="Topics order"></ul>
      <footer class="cm-wizard-stage-foot">
        <button type="button" class="cm-wizard-save-btn" data-wizard-commit="topic">టాపిక్ క్రమం సేవ్</button>
      </footer>
    </article>

    <article class="cm-wizard-stage is-locked cm-wizard-stage--optional" data-wizard-stage="sub_topic" id="cmWizardStageSubTopic">
      <header class="cm-wizard-stage-head">
        <span class="cm-wizard-badge">దశ 4</span>
        <h3 class="font-telugu">సబ్-టాపిక్ క్రమం</h3>
        <p class="cm-wizard-stage-desc font-telugu" id="cmWizardSubTopicDesc">ఎంచుకున్న టాపిక్‌కు సబ్-టాపిక్‌లు ఉన్నప్పుడు మాత్రమే</p>
      </header>
      <ul id="cmWizardListSubTopic" class="cm-sort-list" aria-label="Sub topics order"></ul>
      <footer class="cm-wizard-stage-foot">
        <button type="button" class="cm-wizard-save-btn" data-wizard-commit="sub_topic">సబ్-టాపిక్ క్రమం సేవ్</button>
      </footer>
    </article>

    <article class="cm-wizard-stage is-locked" data-wizard-stage="notes_exam" id="cmWizardStageNotesExam">
      <header class="cm-wizard-stage-head">
        <span class="cm-wizard-badge">దశ 5</span>
        <h3 class="font-telugu">నోట్స్ &amp; ఎగ్జామ్ హబ్</h3>
        <p class="cm-wizard-stage-desc font-telugu" id="cmWizardNotesExamDesc">ఎగ్జామ్ సూట్ క్రమం · నోట్స్/ప్రశ్నల సంపాదన</p>
      </header>
      <p id="cmWizardActiveTopicLabel" class="text-xs font-semibold text-slate-700 mb-2 font-telugu min-h-[1rem]"></p>
      <ul id="cmWizardListExam" class="cm-sort-list mb-4" aria-label="Exam suite order"></ul>
      <footer class="cm-wizard-stage-foot">
        <div class="flex flex-wrap gap-2">
          <button type="button" id="cmWizardOpenContentTab" class="cm-wizard-secondary-btn font-telugu">నోట్స్ &amp; ఎగ్జామ్ ఎడిటర్ తెరవండి</button>
          <button type="button" class="cm-wizard-save-btn cm-wizard-save-btn--final" data-wizard-commit="exam_suite">ఫైనల్ క్రమం సేవ్ &amp; పూర్తి</button>
        </div>
      </footer>
    </article>
  </div>

  <p id="cmSortMsg" class="px-5 pb-4 text-xs text-slate-600 font-telugu min-h-[1.5rem] border-t border-[#E3E6F0] pt-3 bg-slate-50"></p>
</section>
