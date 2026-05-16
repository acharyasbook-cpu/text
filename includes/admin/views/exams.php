<?php
/** @var AdminRepository $repo */
$tests = $repo->allTests();
$courses = $repo->allCourses();
$subjects = $repo->allSubjectsWithCourse();
$packages = $repo->allPackages();
$topicsCatalog = SchemaHelper::columnExists('tests', 'topic_id') ? $repo->topicsCatalogForAdmin() : [];
$editTest = !empty($_GET['edit_test']) ? (int) $_GET['edit_test'] : null;
$testForm = null;
if ($editTest) {
    foreach ($tests as $t) {
        if ((int) $t['id'] === $editTest) {
            $testForm = $t;
            break;
        }
    }
}

$manageTestId = !empty($_GET['test_id']) ? (int) $_GET['test_id'] : null;
$questions = $manageTestId ? $repo->questionsForTest($manageTestId) : [];

$firstCourseId = $courses[0]['id'] ?? 0;
$pickerCourseId = $testForm ? (int) $testForm['course_id'] : (int) $firstCourseId;
$pickerSubjectId = $testForm && !empty($testForm['subject_id']) ? (int) $testForm['subject_id'] : null;
$pickerExclude = $testForm ? (int) $testForm['id'] : null;
$bundleTests = $pickerCourseId ? $repo->testsForBundlePicker($pickerCourseId, $pickerSubjectId, $pickerExclude) : [];
$bundleSelected = $testForm && SchemaHelper::testBundleEnabled() ? $repo->bundleComponentIds((int) $testForm['id']) : [];

$typeBadgeClass = static function (string $tt): string {
    return match ($tt) {
        'topic' => 'bg-blue-100 text-blue-800',
        'division' => 'bg-amber-100 text-amber-800',
        'revision' => 'bg-orange-100 text-orange-800',
        'grand' => 'bg-purple-100 text-purple-800',
        'model' => 'bg-emerald-100 text-emerald-800',
        default => 'bg-slate-100 text-slate-700',
    };
};
?>

<?php if ($manageTestId): ?>
<div class="mb-6">
  <a href="<?= admin_e(admin_dashboard_url(['view' => 'exams'])) ?>" class="text-sm text-brand hover:underline">← Back to exams</a>
  <h2 class="text-xl font-semibold text-slate-800 mt-2">MCQ Manager — Test #<?= $manageTestId ?></h2>
</div>
<div class="grid lg:grid-cols-2 gap-6">
  <div class="bg-white rounded-xl border border-slate-200 p-5">
    <h3 class="font-semibold mb-4">Add / Upload MCQ</h3>
    <form method="post" class="space-y-3">
      <input type="hidden" name="action" value="save_question" />
      <input type="hidden" name="return_view" value="exams" />
      <input type="hidden" name="test_id" value="<?= $manageTestId ?>" />
      <div><label class="text-xs font-medium">Question *</label>
        <textarea name="question_text" required rows="3" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"></textarea></div>
      <div><label class="text-xs font-medium">Telugu (optional)</label>
        <textarea name="question_text_te" rows="2" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm font-telugu"></textarea></div>
      <div class="grid grid-cols-2 gap-2">
        <input name="option_a" required placeholder="Option A" class="border rounded-lg px-3 py-2 text-sm" />
        <input name="option_b" required placeholder="Option B" class="border rounded-lg px-3 py-2 text-sm" />
        <input name="option_c" required placeholder="Option C" class="border rounded-lg px-3 py-2 text-sm" />
        <input name="option_d" required placeholder="Option D" class="border rounded-lg px-3 py-2 text-sm" />
      </div>
      <div class="flex gap-3">
        <div><label class="text-xs font-medium">Correct</label>
          <select name="correct_option" class="mt-1 border rounded-lg px-3 py-2 text-sm">
            <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="D">D</option>
          </select></div>
        <div><label class="text-xs font-medium">Order</label>
          <input type="number" name="question_order" value="<?= count($questions) + 1 ?>" class="mt-1 border rounded-lg px-3 py-2 text-sm w-20" /></div>
        <div><label class="text-xs font-medium">Marks</label>
          <input type="number" name="marks" value="1" class="mt-1 border rounded-lg px-3 py-2 text-sm w-20" /></div>
      </div>
      <div><label class="text-xs font-medium">Explanation</label>
        <textarea name="explanation" rows="2" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" placeholder="Why this answer is correct"></textarea></div>
      <div><label class="text-xs font-medium">Topic tag</label>
        <input name="topic_tag" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <button type="submit" class="w-full py-2.5 bg-brand text-white font-semibold rounded-lg text-sm">Save Question</button>
    </form>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-3 border-b font-semibold text-sm"><?= count($questions) ?> Questions</div>
    <div class="max-h-[32rem] overflow-y-auto divide-y">
      <?php foreach ($questions as $q): ?>
      <div class="p-4 text-sm">
        <p class="font-medium text-slate-800">Q<?= (int)$q['question_order'] ?>. <?= admin_e(mb_substr($q['question_text'],0,120)) ?>…</p>
        <p class="text-xs text-emerald-700 mt-1">Answer: <?= admin_e($q['correct_option']) ?></p>
        <form method="post" class="mt-2" onsubmit="return confirm('Delete?');">
          <input type="hidden" name="action" value="delete_question" /><input type="hidden" name="return_view" value="exams" />
          <input type="hidden" name="id" value="<?= (int)$q['id'] ?>" />
          <button type="submit" class="text-xs text-red-600">Remove</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php else: ?>
<div class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 overflow-hidden">
    <div class="px-5 py-4 border-b flex justify-between">
      <h2 class="font-semibold">Exam Manager</h2>
      <span class="text-xs text-slate-500">Topic → Division → Revision → Grand → Model</span>
    </div>
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-left text-slate-500">
        <tr><th class="px-5 py-3">Title</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Topic</th><th class="px-5 py-3">Timer</th><th class="px-5 py-3">Q's</th><th class="px-5 py-3"></th></tr>
      </thead>
      <tbody>
        <?php foreach ($tests as $t): ?>
        <?php $tt = $t['test_type'] ?? 'topic'; ?>
        <tr class="border-t border-slate-100">
          <td class="px-5 py-3">
            <span class="font-medium"><?= admin_e($t['title']) ?></span>
            <span class="block text-xs text-slate-500"><?= admin_e($t['course_name']) ?></span>
          </td>
          <td class="px-5 py-3"><span class="px-2 py-0.5 text-xs rounded capitalize <?= admin_e($typeBadgeClass($tt)) ?>"><?= admin_e($tt) ?></span></td>
          <td class="px-5 py-3 text-xs text-slate-600"><?= !empty($t['topic_title']) ? admin_e($t['topic_title']) : '—' ?></td>
          <td class="px-5 py-3"><?= (int)$t['duration_mins'] ?>m</td>
          <td class="px-5 py-3"><?= (int)$t['question_count'] ?></td>
          <td class="px-5 py-3 text-right space-x-2 whitespace-nowrap">
            <a href="<?= admin_e(admin_dashboard_url(['view' => 'exams', 'test_id' => (int)$t['id']])) ?>" class="text-brand font-medium">MCQs</a>
            <a href="<?= admin_e(admin_dashboard_url(['view' => 'exams', 'edit_test' => (int)$t['id']])) ?>" class="text-slate-600">Edit</a>
            <form method="post" class="inline" onsubmit="return confirm('Delete exam?');">
              <input type="hidden" name="action" value="delete_test" /><input type="hidden" name="return_view" value="exams" />
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
              <button type="submit" class="text-red-600">Del</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 p-5 h-fit">
    <h3 class="font-semibold mb-4"><?= $testForm ? 'Edit Exam' : 'Create Online Exam' ?></h3>
    <form method="post" class="space-y-3" id="exam-editor-form">
      <input type="hidden" name="action" value="save_test" /><input type="hidden" name="return_view" value="exams" />
      <?php if ($testForm): ?><input type="hidden" name="id" value="<?= (int)$testForm['id'] ?>" /><?php endif; ?>
      <input name="title" required placeholder="Exam title" value="<?= admin_e($testForm['title'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
      <input name="title_te" placeholder="Telugu title" value="<?= admin_e($testForm['title_te'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm font-telugu" />
      <select name="course_id" id="exam-course-id" required class="w-full border rounded-lg px-3 py-2 text-sm">
        <?php foreach ($courses as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= ($testForm && (int)$testForm['course_id']===(int)$c['id'])?'selected':'' ?>><?= admin_e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="subject_id" id="exam-subject-id" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">— Subject (optional) —</option>
        <?php foreach ($subjects as $s): ?>
        <option value="<?= (int)$s['id'] ?>" <?= ($testForm && (int)($testForm['subject_id']??0)===(int)$s['id'])?'selected':'' ?>><?= admin_e($s['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="test_type" id="exam-test-type" required class="w-full border rounded-lg px-3 py-2 text-sm">
        <?php
        $curT = $testForm['test_type'] ?? 'topic';
        foreach ([
          'topic' => 'Topic test (single topic)',
          'division' => 'Division (2–3 topics combined)',
          'revision' => 'Revision (divisions combined)',
          'grand' => 'Grand (revision / syllabus-wide)',
          'model' => 'Model paper (full-length)',
        ] as $tk => $tl):
        ?>
        <option value="<?= admin_e($tk) ?>" <?= $curT === $tk ? 'selected' : '' ?>><?= admin_e($tl) ?></option>
        <?php endforeach; ?>
      </select>
      <div id="topic-link-wrap">
        <label class="text-xs text-slate-600">Link to syllabus topic (optional)</label>
        <select name="topic_id" id="exam-topic-id" class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
          <option value="">— No topic row —</option>
          <?php foreach ($topicsCatalog as $tc): ?>
          <option value="<?= (int)$tc['id'] ?>"
            data-subject-id="<?= (int)($tc['subject_id'] ?? 0) ?>"
            <?= ($testForm && !empty($testForm['topic_id']) && (int)$testForm['topic_id'] === (int)$tc['id']) ? 'selected' : '' ?>>
            <?= admin_e(($tc['subject_name'] ?? '') . ' — ' . ($tc['title'] ?? '')) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if (SchemaHelper::testBundleEnabled()): ?>
      <div id="bundle-components-wrap" class="space-y-1">
        <label class="text-xs text-slate-600">Include tests (for division / revision / grand / model)</label>
        <select name="component_test_ids[]" multiple size="8" class="w-full border rounded-lg px-3 py-2 text-sm">
          <?php foreach ($bundleTests as $bt): ?>
          <option value="<?= (int)$bt['id'] ?>" <?= in_array((int)$bt['id'], $bundleSelected, true) ? 'selected' : '' ?>>
            [<?= admin_e($bt['test_type'] ?? '') ?>] <?= admin_e($bt['title']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <p class="text-xs text-slate-500">Hold Ctrl/Cmd to pick multiple. Save subject & course first; edit again if the list is empty.</p>
      </div>
      <?php endif; ?>
      <input name="division_label" placeholder="Division unit label" value="<?= admin_e($testForm['division_label'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
      <div class="grid grid-cols-2 gap-2">
        <div><label class="text-xs">Duration (min)</label><input type="number" name="duration_mins" value="<?= (int)($testForm['duration_mins']??60) ?>" class="w-full border rounded-lg px-2 py-1.5 text-sm" /></div>
        <div><label class="text-xs">Pass marks</label><input type="number" name="passing_marks" value="<?= (int)($testForm['passing_marks']??25) ?>" class="w-full border rounded-lg px-2 py-1.5 text-sm" /></div>
        <div><label class="text-xs">Total Q</label><input type="number" name="total_questions" value="<?= (int)($testForm['total_questions']??50) ?>" class="w-full border rounded-lg px-2 py-1.5 text-sm" /></div>
        <div><label class="text-xs">Total marks</label><input type="number" name="total_marks" value="<?= (int)($testForm['total_marks']??50) ?>" class="w-full border rounded-lg px-2 py-1.5 text-sm" /></div>
      </div>
      <input type="number" step="0.01" name="negative_marking" value="<?= admin_e((string)($testForm['negative_marking']??0.25)) ?>" placeholder="Negative marking" class="w-full border rounded-lg px-3 py-2 text-sm" />
      <select name="package_id" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">— No package lock —</option>
        <?php foreach ($packages as $p): ?>
        <option value="<?= (int)$p['id'] ?>" <?= ($testForm && (int)($testForm['package_id']??0)===(int)$p['id'])?'selected':'' ?>><?= admin_e($p['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <input name="slug" placeholder="slug (auto)" value="<?= admin_e($testForm['slug'] ?? '') ?>" class="w-full border rounded-lg px-3 py-2 text-sm" />
      <label class="text-sm"><input type="checkbox" name="is_active" <?= (!$testForm || $testForm['is_active']) ? 'checked' : '' ?> /> Active / Live</label>
      <button type="submit" class="w-full py-2.5 bg-brand text-white font-semibold rounded-lg text-sm">Save Exam</button>
    </form>
  </div>
</div>
<script>
(function () {
  var form = document.getElementById('exam-editor-form');
  if (!form) return;
  var typeSel = form.querySelector('#exam-test-type');
  var bundleWrap = document.getElementById('bundle-components-wrap');
  var topicWrap = document.getElementById('topic-link-wrap');
  var subjSel = form.querySelector('#exam-subject-id');
  var topicSel = form.querySelector('#exam-topic-id');
  function syncType() {
    var tt = typeSel && typeSel.value;
    if (topicWrap) topicWrap.style.display = tt === 'topic' ? 'block' : 'none';
    if (bundleWrap) bundleWrap.style.display = ['division','revision','grand','model'].indexOf(tt) >= 0 ? 'block' : 'none';
  }
  function filterTopics() {
    if (!topicSel || !subjSel) return;
    var sid = subjSel.value;
    Array.prototype.forEach.call(topicSel.querySelectorAll('option'), function (o) {
      if (!o.value) { o.hidden = false; return; }
      var ds = o.getAttribute('data-subject-id');
      o.hidden = !!(sid && ds && ds !== sid);
    });
  }
  if (typeSel) typeSel.addEventListener('change', syncType);
  if (subjSel) subjSel.addEventListener('change', filterTopics);
  syncType();
  filterTopics();
})();
</script>
<?php endif; ?>
