<?php
require_once dirname(__DIR__, 4) . '/includes/FreemiumAccess.php';
/**
 * @var array<string,mixed> $subject
 * @var list<array<string,mixed>> $topicsWorkspace
 * @var bool $programmeHasAccess
 * @var list<array<string,mixed>> $plans
 * @var string $checkoutReturn
 * @var ?array $user
 */
$courseSlug = (string) ($subject['course_slug'] ?? '');
$subSlug = (string) ($subject['sub_course_slug'] ?? '');
$subjectSlug = (string) ($subject['slug'] ?? '');
$backSubUrl = $subSlug !== ''
    ? public_sub_course_workspace_url($courseSlug, $subSlug)
    : base_url('learn.php?course=' . rawurlencode($courseSlug));
$backCourseUrl = base_url('learn.php?course=' . rawurlencode($courseSlug));
$backHomeUrl = base_url('index.php');
$initialPanel = (string) ($_GET['panel'] ?? 'notes');
if (!in_array($initialPanel, ['notes', 'exam'], true)) {
    $initialPanel = 'notes';
}
$plans = $plans ?? [];
$checkoutReturn = $checkoutReturn !== '' ? $checkoutReturn : public_subject_workspace_url(
    $courseSlug,
    $subSlug !== '' ? $subSlug : null,
    $subjectSlug
);
$user = $user ?? current_user();
$examReturnPath = public_subject_exam_return_path(
    $courseSlug,
    $subSlug !== '' ? $subSlug : null,
    $subjectSlug
);
?>
<link rel="stylesheet" href="<?= e(base_url('assets/css/subject-workspace.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 4) . '/assets/css/subject-workspace.css') ?>" />
<link rel="stylesheet" href="<?= e(base_url('assets/css/freemium-gate.css')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 4) . '/assets/css/freemium-gate.css') ?>" />

<section class="subject-workspace-hub mb-10" aria-label="Notes and exams"
         data-initial-panel="<?= e($initialPanel) ?>"
         data-freemium-gate="1">

  <div class="subject-workspace-nav">
    <a href="<?= e($backSubUrl) ?>" class="subject-workspace-back font-telugu" title="విషయాల జాబితాకు">← వెనుకకు / విషయాలు</a>
  </div>

  <div class="subject-tab-bar" role="tablist">
    <button type="button" class="subject-tab-btn font-telugu<?= $initialPanel === 'notes' ? ' is-active' : '' ?>"
            data-subject-tab="notes" role="tab" aria-selected="<?= $initialPanel === 'notes' ? 'true' : 'false' ?>">
      నోట్స్ (Notes)
    </button>
    <button type="button" class="subject-tab-btn font-telugu<?= $initialPanel === 'exam' ? ' is-active' : '' ?>"
            data-subject-tab="exam" role="tab" aria-selected="<?= $initialPanel === 'exam' ? 'true' : 'false' ?>">
      ఆన్‌లైన్ ఎగ్జామ్స్ (Online Exams)
    </button>
  </div>

  <div id="subjectPanelNotes" class="subject-tab-panel<?= $initialPanel === 'notes' ? ' is-active' : '' ?>" role="tabpanel">
    <?php if ($topicsWorkspace === []): ?>
    <p class="font-telugu text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-white">ఈ విషయానికి టాపిక్‌లు త్వరలో జోడించబడతాయి.</p>
    <?php else: ?>
    <p class="font-telugu text-xs text-slate-600 mb-4">
      మొదటి <?= (int) FreemiumAccess::FREE_PREVIEW_SLOTS ?> అంశాలు ఉచిత ప్రివ్యూ — మిగతా <?= max(0, count($topicsWorkspace) - FreemiumAccess::FREE_PREVIEW_SLOTS) ?> 🔒
    </p>
    <div class="subject-topic-list">
      <?php foreach ($topicsWorkspace as $topic):
          $rank = (int) ($topic['freemium_rank'] ?? 0);
          $labelRank = $rank > 0 ? $rank : 1;
          $locked = !empty($topic['notes_locked']);
          $preview = (string) ($topic['notes_preview'] ?? '');
          if ($preview === '') {
              $preview = 'ఈ టాపిక్ కోసం సంక్షిప్త స్టడీ మెటీరియల్.';
          }
      ?>
      <article class="subject-topic-row font-telugu<?= $locked ? ' subject-topic-row--locked' : '' ?>">
        <div class="subject-topic-row__meta">
          <p class="subject-topic-row__num">
            టాపిక్ <?= $labelRank ?>
            <?php if ($locked): ?><span class="freemium-lock">🔒</span><?php endif; ?>
            <?php if (!empty($topic['freemium_free_preview']) && !$programmeHasAccess): ?>
            <span class="freemium-badge">ఉచిత</span>
            <?php endif; ?>
          </p>
          <p class="subject-topic-row__title"><?= e((string) ($topic['title_te'] ?: $topic['title'])) ?></p>
          <p class="text-xs text-slate-500 mt-1 line-clamp-2"><?= e($preview) ?></p>
        </div>
        <div class="subject-topic-row__cta">
          <?php if ($locked): ?>
          <button type="button" class="study-card-cta freemium-locked-cta" data-freemium-action="checkout">🔒 అన్‌లాక్</button>
          <?php elseif (!empty($topic['notes_url'])): ?>
          <a href="<?= e((string) $topic['notes_url']) ?>" class="classical-btn-primary py-2 px-4 text-sm">చదవండి →</a>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div id="subjectPanelExam" class="subject-tab-panel<?= $initialPanel === 'exam' ? ' is-active' : '' ?>" role="tabpanel">
    <?php if (!$programmeHasAccess): ?>
    <p class="font-telugu text-sm text-amber-900 bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
      మొదటి <?= (int) FreemiumAccess::FREE_PREVIEW_SLOTS ?> టెస్టులు ఉచిత — మిగతా Razorpay ద్వారా అన్‌లాక్.
    </p>
    <?php endif; ?>
    <?php if ($topicsWorkspace === []): ?>
    <p class="font-telugu text-sm text-slate-500 border border-[#E3E6F0] rounded-xl p-6 bg-white">ఎగ్జామ్ డేటా త్వరలో జోడించబడుతుంది.</p>
    <?php else: ?>
    <div class="subject-topic-list">
      <?php foreach ($topicsWorkspace as $topic):
          $rank = (int) ($topic['freemium_rank'] ?? 0);
          $labelRank = $rank > 0 ? $rank : 1;
          $locked = !empty($topic['workspace_locked']);
          $suite = $topic['exam_suite'] ?? [];
          $row = $suite[0] ?? null;
          $examUrl = $row && !empty($row['exam_url']) ? (string) $row['exam_url'] : '';
          $labelTe = $row
              ? (string) ($row['custom_title_te'] ?? $row['test_title_te'] ?? 'టెస్ట్ ' . $labelRank)
              : 'టెస్ట్ ' . $labelRank;
      ?>
      <article class="subject-topic-row font-telugu<?= $locked ? ' subject-topic-row--locked' : '' ?>">
        <div class="subject-topic-row__meta">
          <p class="subject-topic-row__num">
            టెస్ట్ <?= $labelRank ?>
            <?php if ($locked): ?><span class="freemium-lock">🔒</span><?php endif; ?>
          </p>
          <p class="subject-topic-row__title"><?= e($labelTe) ?></p>
          <p class="text-xs text-slate-500 mt-1">25 మార్క్ · <?= (int) ($row['question_count'] ?? 25) ?> ప్రశ్నలు (ఇన్‌స్టంట్ ఎవాల్యుషన్)</p>
        </div>
        <div class="subject-topic-row__cta">
          <?php if ($locked): ?>
          <button type="button" class="classical-btn-primary py-2 px-4 text-sm freemium-locked-cta" data-freemium-action="checkout">🔒 అన్‌లాక్</button>
          <?php elseif ($examUrl !== ''): ?>
          <a href="<?= e($examUrl) ?>" class="classical-btn-primary py-2 px-4 text-sm">పరీక్ష ప్రారంభించు →</a>
          <?php else: ?>
          <span class="text-xs text-slate-400">టెస్ట్ సిద్ధమవుతోంది</span>
          <?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/freemium_checkout_modal.php'; ?>

<script src="<?= e(base_url('assets/js/freemium-gate.js')) ?>?v=<?= (int) @filemtime(dirname(__DIR__, 4) . '/assets/js/freemium-gate.js') ?>"></script>
<script>
(function () {
  var hub = document.querySelector('.subject-workspace-hub');
  if (!hub) return;

  var tabs = hub.querySelectorAll('[data-subject-tab]');
  var panels = {
    notes: document.getElementById('subjectPanelNotes'),
    exam: document.getElementById('subjectPanelExam'),
  };

  function activate(name) {
    tabs.forEach(function (btn) {
      var on = btn.getAttribute('data-subject-tab') === name;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-selected', on ? 'true' : 'false');
    });
    Object.keys(panels).forEach(function (key) {
      if (panels[key]) panels[key].classList.toggle('is-active', key === name);
    });
    if (history.replaceState) {
      var url = new URL(window.location.href);
      url.searchParams.set('panel', name);
      history.replaceState({}, '', url.toString());
    }
  }

  tabs.forEach(function (btn) {
    btn.addEventListener('click', function () {
      activate(btn.getAttribute('data-subject-tab'));
    });
  });

  var initial = hub.getAttribute('data-initial-panel') || 'notes';
  activate(initial === 'exam' ? 'exam' : 'notes');
})();
</script>
