<?php
/**
 * @var array<string,mixed> $subject
 * @var list<array<string,mixed>> $topicsWorkspace
 * @var bool $programmeHasAccess
 */
$courseSlug = (string) ($subject['course_slug'] ?? '');
$subSlug = (string) ($subject['sub_course_slug'] ?? '');
$subjectSlug = (string) ($subject['slug'] ?? '');
$backSubUrl = $subSlug !== ''
    ? public_sub_course_workspace_url($courseSlug, $subSlug)
    : base_url('learn.php?course=' . rawurlencode($courseSlug));
$initialPanel = (string) ($_GET['panel'] ?? '');
if (!in_array($initialPanel, ['notes', 'exam'], true)) {
    $initialPanel = '';
}

$notesTopics = [];
$examTopics = [];
foreach ($topicsWorkspace as $topic) {
    if (!empty($topic['workspace_locked'])) {
        continue;
    }
    $notesTopics[] = $topic;
    if (!empty($topic['has_exam']) || !empty($topic['exam_suite']) || !empty($topic['mcq_preview'])) {
        $examTopics[] = $topic;
    }
}
?>
<section class="subject-workspace-hub mb-10" aria-label="Notes and exams" data-initial-panel="<?= e($initialPanel) ?>">
  <div class="grid lg:grid-cols-2 gap-6 lg:gap-8 items-start">
    <!-- Notes Hub column -->
    <div id="subjectWorkspaceNotes" class="subject-workspace-column subject-workspace-column--notes">
      <header class="subject-workspace-column-head">
        <span class="subject-hub-box-icon" aria-hidden="true">📖</span>
        <div>
          <h2 class="font-telugu text-xl font-bold text-slate-900">నోట్స్ హబ్</h2>
          <p class="text-[10px] uppercase tracking-widest text-slate-500">Notes · Study Material</p>
        </div>
        <a href="<?= e($backSubUrl) ?>" class="public-back-bar font-telugu text-xs py-2 px-3 ml-auto shrink-0">← వెనుకకు</a>
      </header>
      <?php if ($notesTopics === []): ?>
      <p class="font-telugu text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-white mt-4">ఈ విషయానికి టాపిక్‌లు త్వరలో జోడించబడతాయి.</p>
      <?php else: ?>
      <div class="grid gap-4 mt-4">
        <?php foreach ($notesTopics as $i => $topic):
            $preview = (string) ($topic['notes_preview'] ?? '');
            if ($preview === '') {
                $preview = 'ఈ టాపిక్ కోసం సంక్షిప్త స్టడీ మెటీరియల్ త్వరలో జోడించబడుతుంది.';
            }
        ?>
        <article class="study-card font-telugu">
          <p class="study-card-num">టాపిక్ <?= $i + 1 ?></p>
          <h3 class="study-card-title"><?= e((string) $topic['title']) ?></h3>
          <?php if (!empty($topic['title_te'])): ?>
          <p class="study-card-title-te"><?= e((string) $topic['title_te']) ?></p>
          <?php endif; ?>
          <p class="study-card-preview"><?= e($preview) ?></p>
          <?php if (!empty($topic['notes_url'])): ?>
          <a href="<?= e((string) $topic['notes_url']) ?>" class="study-card-cta">చదవండి →</a>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Exam Hub column -->
    <div id="subjectWorkspaceExam" class="subject-workspace-column subject-workspace-column--exam">
      <header class="subject-workspace-column-head">
        <span class="subject-hub-box-icon" aria-hidden="true">✅</span>
        <div>
          <h2 class="font-telugu text-xl font-bold text-slate-900">ఎగ్జామ్ హబ్</h2>
          <p class="text-[10px] uppercase tracking-widest text-slate-500">Exam · MCQs & Tests</p>
        </div>
        <a href="<?= e($backSubUrl) ?>" class="public-back-bar font-telugu text-xs py-2 px-3 ml-auto shrink-0">← వెనుకకు</a>
      </header>
      <?php if (!$programmeHasAccess): ?>
      <p class="font-telugu text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-xl p-4 mt-4">పూర్తి ఎగ్జామ్ యాక్సెస్ కోసం సబ్-కోర్స్ ప్లాన్ అవసరం.</p>
      <?php endif; ?>
      <?php if ($examTopics === []): ?>
      <p class="font-telugu text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-white mt-4">ఈ విషయానికి ఎగ్జామ్ డేటా త్వరలో జోడించబడతాయి.</p>
      <?php else: ?>
      <ol class="space-y-4 mt-4">
        <?php foreach ($examTopics as $i => $topic):
            $suite = $topic['exam_suite'] ?? [];
            $returnPath = (string) ($topic['exam_return_path'] ?? public_subject_exam_return_path(
                $courseSlug,
                $subSlug !== '' ? $subSlug : null,
                $subjectSlug
            ));
        ?>
        <li class="programme-topic-card">
          <div class="flex gap-3 items-start mb-2">
            <span class="programme-topic-num"><?= $i + 1 ?></span>
            <div>
              <h3 class="font-semibold text-royal"><?= e((string) $topic['title']) ?></h3>
              <?php if (!empty($topic['title_te'])): ?>
              <p class="font-telugu text-sm text-gold font-semibold"><?= e((string) $topic['title_te']) ?></p>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($suite !== []): ?>
          <div class="grid gap-2 pl-0 sm:pl-10">
            <?php foreach ($suite as $row):
                $testSlug = (string) ($row['test_slug'] ?? '');
                $labelTe = (string) ($row['custom_title_te'] ?? $row['test_title_te'] ?? '');
                $labelEn = (string) ($row['custom_title'] ?? $row['test_title'] ?? 'పరీక్ష');
                $examUrl = $testSlug !== ''
                    ? public_exam_start_url($courseSlug, $testSlug, $returnPath)
                    : '';
            ?>
            <article class="topic-exam-suite-box">
              <h4 class="font-telugu text-sm font-bold text-royal"><?= e($labelTe !== '' ? $labelTe : $labelEn) ?></h4>
              <p class="font-telugu text-xs text-slate-600"><?= (int) ($row['question_count'] ?? 50) ?> ప్రశ్నలు</p>
              <?php if ($examUrl !== ''): ?>
              <a href="<?= e($examUrl) ?>" class="classical-btn-primary w-full mt-2 py-2 text-xs font-telugu text-center block">పరీక్ష ప్రారంభించు →</a>
              <?php endif; ?>
            </article>
            <?php endforeach; ?>
          </div>
          <?php elseif (!empty($topic['mcq_preview'])): ?>
          <p class="font-telugu text-xs text-slate-600 pl-10">MCQ బ్యాంక్ అందుబాటులో ఉంది.</p>
          <?php endif; ?>
        </li>
        <?php endforeach; ?>
      </ol>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
(function () {
  var hub = document.querySelector('.subject-workspace-hub');
  if (!hub) return;
  var panel = hub.getAttribute('data-initial-panel');
  if (panel === 'notes') {
    var n = document.getElementById('subjectWorkspaceNotes');
    if (n) n.scrollIntoView({ behavior: 'smooth', block: 'start' });
  } else if (panel === 'exam') {
    var e = document.getElementById('subjectWorkspaceExam');
    if (e) e.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
})();
</script>
