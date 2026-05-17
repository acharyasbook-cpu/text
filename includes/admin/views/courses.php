<?php
/** @var AdminRepository $repo */
require_once dirname(__DIR__, 3) . '/models/CourseRepository.php';

$courses = $repo->allCourses();
$subjects = $repo->allSubjectsWithCourse();
$categoriesFlat = $repo->allCategoriesFlat();
$packages = $repo->allPackages();
$subCourseSelect = SchemaHelper::hierarchyFourTier() ? $repo->allSubCoursesForSelect() : [];
$editSubCourse = !empty($_GET['edit_sub_course']) ? (int) $_GET['edit_sub_course'] : null;
$subCourseForm = ($editSubCourse && SchemaHelper::hierarchyFourTier()) ? $repo->getSubCourse($editSubCourse) : null;
$subCourseLinkedSubjectIds = ($editSubCourse && SchemaHelper::hierarchyFourTier())
    ? $repo->subjectIdsForSubCourse((int) $editSubCourse) : [];
$planRows = [];
if (($tabCheck = ($_GET['tab'] ?? '')) === 'pricing' && SchemaHelper::hasTable('sub_course_plans')) {
    $planRows = (new CourseRepository())->allPlansWithContext();
}

$topicsCatalog = SchemaHelper::topicExamsEnabled() ? $repo->topicsCatalogForAdmin() : [];
$topicExamRows = SchemaHelper::topicExamsEnabled() ? $repo->allTopicExamsAdmin() : [];

$tabs = ['courses' => 'Courses'];
if (SchemaHelper::hierarchyFourTier()) {
    $tabs['subcourses'] = 'Sub-courses';
}
$tabs['subjects'] = 'Subject Manager';
$tabs['topics'] = 'Topics & Material';
if (SchemaHelper::hasTable('sub_course_plans')) {
    $tabs['pricing'] = 'Pricing';
}
$tabs['packages'] = 'Legacy packages';

$editCourse = !empty($_GET['edit_course']) ? (int) $_GET['edit_course'] : null;
$editSubject = !empty($_GET['edit_subject']) ? (int) $_GET['edit_subject'] : null;
$courseForm = null;
if ($editCourse) {
    foreach ($courses as $c) {
        if ((int) $c['id'] === $editCourse) { $courseForm = $c; break; }
    }
}
$subjectForm = null;
if ($editSubject) {
    foreach ($subjects as $s) {
        if ((int)$s['id'] === $editSubject) { $subjectForm = $s; break; }
    }
}
$subjectLinkedSubIds = ($subjectForm && SchemaHelper::hierarchyFourTier()) ? $repo->subCourseSubjectIds((int) $subjectForm['id']) : [];
$tab = $_GET['tab'] ?? 'courses';
if ($tab === 'lessons') {
    $tab = 'topics';
}
$focusSubjectId = !empty($_GET['focus_subject']) ? (int) $_GET['focus_subject'] : 0;
?>

<div class="flex flex-wrap gap-2 mb-6 border-b border-slate-200 pb-4">
  <?php foreach ($tabs as $k => $l): ?>
  <a href="<?= admin_e(admin_dashboard_url(['view' => 'courses', 'tab' => $k])) ?>" class="px-4 py-2 text-sm font-medium rounded-lg <?= $tab===$k ? 'bg-brand text-white' : 'bg-white text-slate-600 border border-slate-200 hover:bg-slate-50' ?>"><?= admin_e($l) ?></a>
  <?php endforeach; ?>
</div>

<?php if ($tab === 'courses'): ?>
<div class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
      <h2 class="font-semibold text-slate-800">Main programmes — <span class="font-normal text-slate-500">AP DSC, TS DSC, TET…</span></h2>
      <a href="<?= admin_e(admin_dashboard_url(['view' => 'courses', 'tab' => 'courses'])) ?>" class="text-sm text-brand font-medium">+ New</a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
          <tr><th class="px-5 py-3">Name</th><th class="px-5 py-3">Region</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr>
        </thead>
        <tbody>
          <?php foreach ($courses as $c): ?>
          <tr class="border-t border-slate-100 hover:bg-slate-50/50">
            <td class="px-5 py-3">
              <span class="font-medium text-slate-800"><?= admin_e($c['name']) ?></span>
              <?php if ($c['name_te']): ?><span class="font-telugu block text-xs text-slate-500"><?= admin_e($c['name_te']) ?></span><?php endif; ?>
            </td>
            <td class="px-5 py-3 text-slate-600"><?= admin_e($c['region'] ?? '—') ?></td>
            <td class="px-5 py-3"><?php
              $cLive = !empty($c['is_active'])
                  && (!SchemaHelper::coursesHasStatus() || !empty($c['status']));
              ?><span class="px-2 py-0.5 text-xs rounded <?= $cLive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>"><?= $cLive ? 'Live' : 'Draft' ?></span></td>
            <td class="px-5 py-3 text-right space-x-2">
              <a href="<?= admin_e(admin_dashboard_url(['view' => 'courses', 'tab' => 'courses', 'edit_course' => (int)$c['id']])) ?>" class="text-brand hover:underline">Edit</a>
              <form method="post" class="inline" onsubmit="return confirm('Delete this course and all subjects?');">
                <input type="hidden" name="action" value="delete_course" />
                <input type="hidden" name="return_view" value="courses" />
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
                <button type="submit" class="text-red-600 hover:underline">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 h-fit">
    <h3 class="font-semibold text-slate-800 mb-4"><?= $courseForm ? 'Edit Course' : 'Add Course' ?></h3>
    <form method="post" class="space-y-3">
      <input type="hidden" name="action" value="save_course" />
      <input type="hidden" name="return_view" value="courses" />
      <?php if ($courseForm): ?><input type="hidden" name="id" value="<?= (int)$courseForm['id'] ?>" /><?php endif; ?>
      <div><label class="text-xs font-medium text-slate-600">Name *</label>
        <input name="name" required value="<?= admin_e($courseForm['name'] ?? '') ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium text-slate-600">Telugu Name</label>
        <input name="name_te" value="<?= admin_e($courseForm['name_te'] ?? '') ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm font-telugu" /></div>
      <div><label class="text-xs font-medium text-slate-600">Slug</label>
        <input name="slug" value="<?= admin_e($courseForm['slug'] ?? '') ?>" placeholder="auto from name" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium text-slate-600">Region</label>
        <input name="region" value="<?= admin_e($courseForm['region'] ?? '') ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium text-slate-600">Description</label>
        <textarea name="description" rows="3" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm"><?= admin_e($courseForm['description'] ?? '') ?></textarea></div>
      <div class="flex gap-3">
        <div class="flex-1"><label class="text-xs font-medium text-slate-600">Sort</label>
          <input type="number" name="sort_order" value="<?= (int)($courseForm['sort_order'] ?? 0) ?>" class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm" /></div>
        <label class="flex items-center gap-2 mt-6 text-sm"><input type="checkbox" name="is_active" <?= (!$courseForm || $courseForm['is_active']) ? 'checked' : '' /> <span>Live <span class="text-slate-400 font-normal">(visible on site)</span></span></label>
      </div>
      <button type="submit" class="w-full py-2.5 bg-brand text-white font-semibold rounded-lg hover:bg-brand-dark text-sm">Save Course</button>
    </form>
  </div>
</div>

<?php elseif ($tab === 'subcourses'): ?>
<?php if (!SchemaHelper::hierarchyFourTier()): ?>
<p class="text-slate-500 text-sm">Run database migration migrate_four_tier.php to enable sub-courses.</p>
<?php else: ?>
<div class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left">Sub-course</th><th class="px-5 py-3 text-left">Main course</th><th class="px-5 py-3 text-left">Slug</th><th class="px-5 py-3 text-left">Status</th><th class="px-5 py-3"></th></tr></thead>
      <tbody>
        <?php foreach ($subCourseSelect as $sc): ?>
        <tr class="border-t border-slate-100">
          <td class="px-5 py-3 font-medium"><?= admin_e($sc['name']) ?></td>
          <td class="px-5 py-3 text-slate-600"><?= admin_e($sc['course_name']) ?></td>
          <td class="px-5 py-3 text-xs text-slate-500"><?= admin_e($sc['slug']) ?></td>
          <td class="px-5 py-3">
            <?php $scLive = !empty($sc['is_active']) && (!SchemaHelper::columnExists('sub_courses', 'status') || !empty($sc['status'])); ?>
            <span class="px-2 py-0.5 text-xs rounded <?= $scLive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>"><?= $scLive ? 'Live' : 'Draft' ?></span>
          </td>
          <td class="px-5 py-3 text-right space-x-2">
            <a href="<?= admin_e(admin_dashboard_url(['view' => 'courses', 'tab' => 'subcourses', 'edit_sub_course' => (int)$sc['id']])) ?>" class="text-brand">Edit</a>
            <form method="post" class="inline" onsubmit="return confirm('Delete sub-course?');">
              <input type="hidden" name="action" value="delete_sub_course" />
              <input type="hidden" name="return_view" value="courses" />
              <input type="hidden" name="id" value="<?= (int)$sc['id'] ?>" />
              <button type="submit" class="text-red-600">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-5 h-fit">
    <h3 class="font-semibold mb-4"><?= $subCourseForm ? 'Edit Sub-course' : 'Add Sub-course' ?></h3>
    <form method="post" class="space-y-3">
      <input type="hidden" name="action" value="save_sub_course" />
      <input type="hidden" name="return_view" value="courses" />
      <?php if ($subCourseForm): ?><input type="hidden" name="id" value="<?= (int)$subCourseForm['id'] ?>" /><?php endif; ?>
      <div><label class="text-xs font-medium">Main course *</label>
        <select name="course_id" required class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
          <?php foreach ($courses as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ($subCourseForm && (int)$subCourseForm['course_id'] === (int)$c['id']) ? 'selected' : '' ?>><?= admin_e($c['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div><label class="text-xs font-medium">Name *</label><input name="name" required value="<?= admin_e($subCourseForm['name'] ?? '') ?>" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium">Telugu</label><input name="name_te" value="<?= admin_e($subCourseForm['name_te'] ?? '') ?>" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm font-telugu" /></div>
      <div><label class="text-xs font-medium">Slug</label><input name="slug" value="<?= admin_e($subCourseForm['slug'] ?? '') ?>" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium">Description</label><textarea name="description" rows="2" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"><?= admin_e($subCourseForm['description'] ?? '') ?></textarea></div>
      <div><label class="text-xs font-medium">Sort</label><input type="number" name="sort_order" value="<?= (int)($subCourseForm['sort_order'] ?? 0) ?>" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <?php if ($subCourseForm && SchemaHelper::hierarchyFourTier()): ?>
      <input type="hidden" name="sub_course_subjects_sent" value="1" />
      <div><label class="text-xs font-medium text-slate-700">Subjects in this programme</label>
        <p class="text-[11px] text-slate-500 mb-1">Tick papers or extras to attach. Create new subjects below (Subject Manager); visibility follows each subject’s <strong>Live</strong> checkbox.</p>
        <div class="max-h-52 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1.5 mt-1 bg-slate-50/50">
          <?php foreach ($subjects as $s): ?>
          <label class="flex gap-2 items-start text-xs cursor-pointer">
            <input type="checkbox" name="sub_course_subject_ids[]" value="<?= (int)$s['id'] ?>"
              <?= in_array((int)$s['id'], $subCourseLinkedSubjectIds, true) ? 'checked' : '' ?> class="mt-0.5" />
            <span class="font-telugu"><?= admin_e(($s['linked_summary'] ?? $s['course_name'] ?? '') . ' — ' . $s['name']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" <?= (!$subCourseForm || !empty($subCourseForm['is_active'])) ? 'checked' : '' ?> /> Live <span class="text-slate-400 font-normal">(visible)</span></label>
      <button type="submit" class="w-full py-2.5 bg-brand text-white font-semibold rounded-lg text-sm">Save Sub-course</button>
    </form>
  </div>
</div>
<?php endif; ?>

<?php elseif ($tab === 'subjects'): ?>
<div class="grid lg:grid-cols-3 gap-6">
  <div class="lg:col-span-2 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left">Subject</th>
      <?php if (SchemaHelper::hierarchyFourTier()): ?><th class="px-5 py-3 text-left">Linked sub-courses</th>
      <?php else: ?><th class="px-5 py-3 text-left">Course</th><?php endif; ?>
      <?php if ($categoriesFlat && !SchemaHelper::hierarchyFourTier()): ?><th class="px-5 py-3 text-left">Category</th><?php endif; ?>
      <th class="px-5 py-3 text-left">Status</th>
      <th class="px-5 py-3"></th></tr></thead>
      <tbody>
        <?php foreach ($subjects as $s): ?>
        <tr class="border-t border-slate-100">
          <td class="px-5 py-3 font-medium"><?= admin_e($s['name']) ?></td>
          <?php if (SchemaHelper::hierarchyFourTier()): ?>
          <td class="px-5 py-3 text-slate-500 text-xs"><?= admin_e($s['linked_summary'] ?? '—') ?></td>
          <?php else: ?>
          <td class="px-5 py-3 text-slate-600"><?= admin_e($s['course_name'] ?? '') ?></td>
          <?php endif; ?>
          <?php if ($categoriesFlat && !SchemaHelper::hierarchyFourTier()): ?>
          <td class="px-5 py-3 text-slate-500 text-xs"><?= admin_e($s['category_name'] ?? '—') ?></td>
          <?php endif; ?>
          <?php
            $subLive = !empty($s['is_active'])
              && (!SchemaHelper::subjectsHasStatus() || !isset($s['status']) || (int) $s['status'] === 1);
          ?>
          <td class="px-5 py-3"><span class="px-2 py-0.5 text-xs rounded <?= $subLive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' ?>"><?= $subLive ? 'Live' : 'Draft' ?></span></td>
          <td class="px-5 py-3 text-right">
            <a href="<?= admin_e(admin_dashboard_url(['view' => 'courses', 'tab' => 'subjects', 'edit_subject' => (int)$s['id']])) ?>" class="text-brand">Edit</a>
            <form method="post" class="inline" onsubmit="return confirm('Delete?');">
              <input type="hidden" name="action" value="delete_subject" /><input type="hidden" name="return_view" value="courses" />
              <input type="hidden" name="id" value="<?= (int)$s['id'] ?>" />
              <button type="submit" class="text-red-600 ml-2">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-5 h-fit">
    <h3 class="font-semibold mb-4"><?= $subjectForm ? 'Edit Subject' : 'Add Subject' ?></h3>
    <form method="post" class="space-y-3">
      <input type="hidden" name="action" value="save_subject" /><input type="hidden" name="return_view" value="courses" />
      <?php if ($subjectForm): ?><input type="hidden" name="id" value="<?= (int)$subjectForm['id'] ?>" /><?php endif; ?>
      <div><label class="text-xs font-medium">Legacy course (optional)</label>
        <select name="course_id" id="subjectCourseSelect" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"
          <?= SchemaHelper::hierarchyFourTier() ? '' : 'required' ?>>
          <?php if (SchemaHelper::hierarchyFourTier()): ?><option value="">— Global library —</option><?php endif; ?>
          <?php foreach ($courses as $c): ?>
          <option value="<?= (int)$c['id'] ?>" <?= ($subjectForm && isset($subjectForm['course_id']) && (int)$subjectForm['course_id']===(int)$c['id'])?'selected':'' ?>><?= admin_e($c['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <?php if (SchemaHelper::hierarchyFourTier() && $subCourseSelect): ?>
      <div><label class="text-xs font-medium text-slate-700">Assign to sub-courses</label>
        <p class="text-[11px] text-slate-500 mb-1">Independent subjects; tick programmes (SGT, SA/TGT/PGT tracks, …) where this subject applies — or edit a sub-course and attach subjects there.</p>
        <div class="max-h-52 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1.5 mt-1 bg-slate-50/50">
          <?php foreach ($subCourseSelect as $sc): ?>
          <label class="flex gap-2 items-start text-xs cursor-pointer">
            <input type="checkbox" name="sub_course_ids[]" value="<?= (int)$sc['id'] ?>"
              <?= in_array((int)$sc['id'], $subjectLinkedSubIds, true) ? 'checked' : '' ?> class="mt-0.5" />
            <span><?= admin_e(($sc['course_slug'] ?? '') . ' — ' . $sc['name']) ?></span>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($categoriesFlat): ?>
      <div><label class="text-xs font-medium text-slate-600">Category (SGT / SA / …)</label>
        <select name="category_id" id="subjectCategorySelect" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
          <option value="">— None —</option>
          <?php foreach ($categoriesFlat as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>" data-course-id="<?= (int)$cat['course_id'] ?>"
            <?= ($subjectForm && isset($subjectForm['category_id']) && (int)$subjectForm['category_id'] === (int)$cat['id']) ? 'selected' : '' ?>>
            <?= admin_e(($cat['course_name'] ?? '') . ' · ' . $cat['name']) ?>
          </option>
          <?php endforeach; ?>
        </select></div>
      <?php endif; ?>
      <div><label class="text-xs font-medium">Name *</label><input name="name" required value="<?= admin_e($subjectForm['name']??'') ?>" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium">Telugu</label><input name="name_te" value="<?= admin_e($subjectForm['name_te']??'') ?>" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm font-telugu" /></div>
      <?php
      $subjectId = $subjectForm ? (int) $subjectForm['id'] : 0;
      require __DIR__ . '/partials/subject_image_picker.php';
      ?>
      <div><label class="text-xs font-medium">Slug</label><input name="slug" value="<?= admin_e($subjectForm['slug']??'') ?>" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <?php if (SchemaHelper::columnExists('subjects', 'marks_allocated')): ?>
      <div><label class="text-xs font-medium">Marks allocated</label><input type="number" name="marks_allocated" min="0" value="<?= isset($subjectForm['marks_allocated']) ? (int)$subjectForm['marks_allocated'] : '' ?>" placeholder="e.g. 100" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <?php endif; ?>
      <div><label class="text-xs font-medium">Description</label><textarea name="description" rows="2" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"><?= admin_e($subjectForm['description'] ?? '') ?></textarea></div>
      <div><label class="text-xs font-medium">Sort order</label><input type="number" name="sort_order" value="<?= (int)($subjectForm['sort_order'] ?? 0) ?>" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" <?= (!$subjectForm || !empty($subjectForm['is_active'])) ? 'checked' : '' ?> /> Live <span class="text-slate-400 font-normal">(visible)</span></label>
      <button type="submit" class="w-full py-2.5 bg-brand text-white font-semibold rounded-lg text-sm">Save Subject</button>
    </form>
    <?php if ($categoriesFlat): ?>
    <script>
    (function () {
      var courseSel = document.getElementById('subjectCourseSelect');
      var catSel = document.getElementById('subjectCategorySelect');
      if (!courseSel || !catSel) return;
      function sync() {
        var cid = String(courseSel.value);
        Array.prototype.forEach.call(catSel.options, function (opt) {
          if (!opt.value) return;
          var oc = opt.getAttribute('data-course-id');
          opt.hidden = oc !== cid;
          if (opt.hidden && opt.selected) catSel.value = '';
        });
      }
      courseSel.addEventListener('change', sync);
      sync();
    })();
    </script>
    <?php endif; ?>
  </div>
</div>

<?php elseif ($tab === 'topics'): ?>
<?php if ($focusSubjectId): ?>
<div id="topicsFocusBanner" class="mb-4 rounded-lg border border-brand/30 bg-brand/5 px-4 py-3 text-sm text-slate-700 flex flex-wrap items-center justify-between gap-2">
  <span>Opening <strong>Topics &amp; Material</strong> with subject id <code class="text-xs bg-white/80 px-1 rounded"><?= (int) $focusSubjectId ?></code> pre-selected (from programme workspace).</span>
  <a href="<?= admin_e(admin_dashboard_url(['view' => 'courses', 'tab' => 'topics'])) ?>" class="text-brand font-medium hover:underline shrink-0">Clear focus</a>
</div>
<?php endif; ?>
<div class="grid lg:grid-cols-2 gap-6">
  <div class="bg-white rounded-xl border border-slate-200 p-5">
    <h3 class="font-semibold mb-4">Add Topic</h3>
    <form method="post" class="space-y-3">
      <input type="hidden" name="action" value="save_topic" /><input type="hidden" name="return_view" value="courses" />
      <div><label class="text-xs font-medium">Subject</label>
        <select name="subject_id" required class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
          <?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int) $s['id'] === $focusSubjectId ? 'selected' : '' ?>><?= admin_e(($s['linked_summary'] ?? $s['course_name'] ?? '') . ' — ' . $s['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div><label class="text-xs font-medium">Title *</label><input name="title" required class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium">Telugu Title</label><input name="title_te" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm font-telugu" /></div>
      <div><label class="text-xs font-medium">Summary</label><textarea name="summary" rows="2" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm"></textarea></div>
      <div><label class="text-xs font-medium">Duration (mins)</label><input type="number" name="duration_mins" value="30" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <label class="text-sm"><input type="checkbox" name="is_free_preview" /> Free preview</label>
      <label class="text-sm block mt-1"><input type="checkbox" name="topic_visible" checked /> Visible on site</label>
      <?php if (SchemaHelper::topicExamsEnabled()): ?>
      <p class="text-[11px] text-slate-500 border border-dashed border-slate-200 rounded-lg px-3 py-2 bg-slate-50/80">
        Optional single exam fields below — for <strong>multiple</strong> exams per topic use the Topic exams panel after saving the topic.
      </p>
      <?php endif; ?>
      <?php if (SchemaHelper::columnExists(SchemaHelper::topicsTable(), 'exam_link')): ?>
      <div><label class="text-xs font-medium">Exam link (URL)</label><input name="exam_link" placeholder="External test URL" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium">Linked test ID</label><input type="number" name="exam_test_id" placeholder="tests.id from Exam Manager" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <?php endif; ?>
      <button type="submit" class="w-full py-2.5 bg-brand text-white font-semibold rounded-lg text-sm">Save Topic</button>
    </form>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-5">
    <h3 class="font-semibold mb-4">Study material under a Topic</h3>
    <form method="post" enctype="multipart/form-data" class="space-y-3">
      <input type="hidden" name="action" value="save_material" /><input type="hidden" name="return_view" value="courses" />
      <div><label class="text-xs font-medium">Subject</label>
        <select name="subject_id" required class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
          <?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>" <?= (int) $s['id'] === $focusSubjectId ? 'selected' : '' ?>><?= admin_e(($s['linked_summary'] ?? $s['course_name'] ?? '') . ' — ' . $s['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div><label class="text-xs font-medium">Title</label><input name="title" required class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium">Topic ID (optional)</label><input type="number" name="topic_id" placeholder="topics.id — attach notes to topic" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <div><label class="text-xs font-medium">Type</label>
        <select name="material_type" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
          <option value="pdf">PDF</option><option value="video">Video</option><option value="notes">Notes</option><option value="link">Link</option>
        </select>
      </div>
      <div><label class="text-xs font-medium">Upload file</label><input type="file" name="material_file" class="mt-1 w-full text-sm" /></div>
      <div><label class="text-xs font-medium">Or URL</label><input name="file_url" placeholder="https://..." class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" /></div>
      <button type="submit" class="w-full py-2.5 bg-slate-800 text-white font-semibold rounded-lg text-sm">Upload Material</button>
    </form>
  </div>
</div>

<?php if (SchemaHelper::topicExamsEnabled()): ?>
<div class="mt-8 bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-slate-100">
    <h3 class="font-semibold text-slate-800">Topic exams</h3>
    <p class="text-xs text-slate-500 mt-1">Add any number of external links or platform tests per topic.</p>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 text-slate-600">
        <tr>
          <th class="text-left px-4 py-3">Subject</th>
          <th class="text-left px-4 py-3">Topic</th>
          <th class="text-left px-4 py-3">Exam title</th>
          <th class="text-left px-4 py-3">Link / Test</th>
          <th class="text-left px-4 py-3">Sort</th>
          <th class="text-left px-4 py-3">Live</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($topicExamRows as $ex): ?>
        <tr class="border-t border-slate-100">
          <td class="px-4 py-2"><?= admin_e($ex['subject_name'] ?? '') ?></td>
          <td class="px-4 py-2"><?= admin_e($ex['topic_title'] ?? '') ?></td>
          <td class="px-4 py-2 font-medium"><?= admin_e($ex['title']) ?></td>
          <td class="px-4 py-2 text-xs text-slate-600">
            <?php if (!empty($ex['external_url'])): ?>
              <span class="break-all"><?= admin_e($ex['external_url']) ?></span>
            <?php elseif (!empty($ex['test_id'])): ?>
              test id <?= (int) $ex['test_id'] ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="px-4 py-2"><?= (int) ($ex['sort_order'] ?? 0) ?></td>
          <td class="px-4 py-2"><?= !empty($ex['is_active']) ? 'Live' : 'Draft' ?></td>
          <td class="px-4 py-2 text-right">
            <form method="post" class="inline" onsubmit="return confirm('Remove this exam row?');">
              <input type="hidden" name="action" value="delete_topic_exam" />
              <input type="hidden" name="return_view" value="courses" />
              <input type="hidden" name="tab_redirect" value="topics" />
              <input type="hidden" name="id" value="<?= (int) $ex['id'] ?>" />
              <button type="submit" class="text-red-600 hover:underline text-xs">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$topicExamRows): ?>
        <tr><td colspan="7" class="px-4 py-6 text-center text-slate-500 text-sm">No topic exams yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="px-5 py-5 border-t border-slate-100">
    <h4 class="text-sm font-semibold text-slate-700 mb-3">Add exam</h4>
    <form method="post" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
      <input type="hidden" name="action" value="save_topic_exam" />
      <input type="hidden" name="return_view" value="courses" />
      <input type="hidden" name="tab_redirect" value="topics" />
      <div class="md:col-span-2 lg:col-span-3">
        <label class="text-xs font-medium text-slate-600">Topic *</label>
        <select name="topic_id" required class="mt-1 w-full border border-slate-300 rounded-lg px-3 py-2 text-sm">
          <option value="">— Select topic —</option>
          <?php foreach ($topicsCatalog as $tc): ?>
          <option value="<?= (int) $tc['id'] ?>"><?= admin_e(($tc['subject_name'] ?? '') . ' — ' . ($tc['title'] ?? '') . ' (id ' . (int) $tc['id'] . ')') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600">Exam title *</label>
        <input name="title" required class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" placeholder="Mock test 1" />
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600">Slug</label>
        <input name="slug" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" placeholder="auto from title" />
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600">Telugu title</label>
        <input name="title_te" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm font-telugu" />
      </div>
      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600">External URL</label>
        <input name="external_url" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" placeholder="https://..." />
      </div>
      <div>
        <label class="text-xs font-medium text-slate-600">Platform test ID</label>
        <input type="number" name="test_id" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" placeholder="tests.id" />
      </div>
      <?php if (SchemaHelper::columnExists('exams', 'test_type')): ?>
      <div>
        <label class="text-xs font-medium text-slate-600">Exam kind</label>
        <select name="exam_test_type" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm">
          <?php foreach (['topic' => 'Topic', 'division' => 'Division', 'revision' => 'Revision', 'grand' => 'Grand', 'model' => 'Model'] as $ek => $el): ?>
          <option value="<?= admin_e($ek) ?>"><?= admin_e($el) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <?php if (SchemaHelper::columnExists('exams', 'material_url')): ?>
      <div class="md:col-span-2">
        <label class="text-xs font-medium text-slate-600">Study material / PDF URL</label>
        <input name="material_url" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" placeholder="https://...pdf" />
      </div>
      <?php endif; ?>
      <div>
        <label class="text-xs font-medium text-slate-600">Sort</label>
        <input type="number" name="sort_order" value="0" class="mt-1 w-full border rounded-lg px-3 py-2 text-sm" />
      </div>
      <label class="flex items-center gap-2 text-sm pb-2"><input type="checkbox" name="is_active" checked /> Live</label>
      <div class="md:col-span-2 lg:col-span-3">
        <button type="submit" class="px-6 py-2.5 bg-brand text-white font-semibold rounded-lg text-sm">Save exam</button>
      </div>
    </form>
  </div>
</div>
<?php else: ?>
<p class="mt-6 text-sm text-slate-500 bg-white border border-slate-200 rounded-lg px-4 py-3">
  Run <code class="text-xs bg-slate-100 px-1 rounded">php database/migrate_topic_exams.php</code> (or reinstall setup) to enable unlimited exams per topic.
</p>
<?php endif; ?>

<?php if ($focusSubjectId): ?>
<script>
(function () {
  var b = document.getElementById('topicsFocusBanner');
  if (b) b.scrollIntoView({ behavior: 'smooth', block: 'start' });
})();
</script>
<?php endif; ?>

<?php elseif ($tab === 'pricing'): ?>
<div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden p-6">
  <h2 class="font-semibold text-slate-800 mb-2">Sub-course pricing</h2>
  <p class="text-sm text-slate-600 mb-6">Fixed slabs: default ₹499 / ₹699 / ₹999 — adjust per programme if needed.</p>
  <?php if (!$planRows): ?>
    <p class="text-sm text-slate-500">No plans loaded. Run migrate_four_tier.php.</p>
  <?php else: ?>
  <form method="post" class="space-y-6">
    <input type="hidden" name="action" value="save_pricing_bulk" />
    <input type="hidden" name="return_view" value="courses" />
    <div class="overflow-x-auto rounded-lg border border-slate-200">
      <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-600">
          <tr>
            <th class="text-left px-4 py-3">Course</th>
            <th class="text-left px-4 py-3">Sub-course</th>
            <th class="text-left px-4 py-3">Plan</th>
            <th class="text-left px-4 py-3">Price (₹)</th>
            <th class="text-left px-4 py-3">Live</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($planRows as $pl): ?>
          <tr class="border-t border-slate-100 hover:bg-slate-50/50">
            <td class="px-4 py-2"><?= admin_e($pl['course_name']) ?></td>
            <td class="px-4 py-2 font-medium"><?= admin_e($pl['sub_course_name']) ?></td>
            <td class="px-4 py-2"><?= admin_e($pl['label']) ?></td>
            <td class="px-4 py-2">
              <input type="number" step="1" name="plan[<?= (int)$pl['id'] ?>][price_inr]" value="<?= htmlspecialchars((string) $pl['price_inr'], ENT_QUOTES, 'UTF-8') ?>" class="w-28 border rounded px-2 py-1" />
            </td>
            <td class="px-4 py-2">
              <input type="checkbox" name="plan[<?= (int)$pl['id'] ?>][active]" value="1" <?= !empty($pl['is_active']) ? 'checked' : '' ?> />
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <button type="submit" class="px-6 py-2.5 bg-brand text-white font-semibold rounded-lg text-sm">Save all prices</button>
  </form>
  <?php endif; ?>
</div>

<?php elseif ($tab === 'packages'): ?>
  <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50"><tr><th class="px-5 py-3 text-left">Package</th><th class="px-5 py-3 text-left">Price</th><th class="px-5 py-3 text-left">Type</th></tr></thead>
      <tbody>
        <?php foreach ($packages as $p): ?>
        <tr class="border-t"><td class="px-5 py-3 font-medium"><?= admin_e($p['name']) ?></td>
          <td class="px-5 py-3">₹<?= number_format((float)$p['price_inr'],0) ?></td>
          <td class="px-5 py-3 text-slate-500 capitalize"><?= admin_e(str_replace('_',' ',$p['package_type'])) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="bg-white rounded-xl border border-slate-200 p-5">
    <h3 class="font-semibold mb-4">Add Sub-course Package</h3>
    <form method="post" class="space-y-3">
      <input type="hidden" name="action" value="save_package" /><input type="hidden" name="return_view" value="courses" />
      <input name="name" required placeholder="Package name" class="w-full border rounded-lg px-3 py-2 text-sm" />
      <select name="package_type" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="subject">Subject Access</option>
        <option value="division_tests">Division Test Pack</option>
        <option value="course_bundle">Course Bundle</option>
      </select>
      <select name="course_id" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">— Course —</option>
        <?php foreach ($courses as $c): ?><option value="<?= (int)$c['id'] ?>"><?= admin_e($c['name']) ?></option><?php endforeach; ?>
      </select>
      <select name="subject_id" class="w-full border rounded-lg px-3 py-2 text-sm">
        <option value="">— Subject (optional) —</option>
        <?php foreach ($subjects as $s): ?><option value="<?= (int)$s['id'] ?>"><?= admin_e($s['name']) ?></option><?php endforeach; ?>
      </select>
      <input type="number" step="0.01" name="price_inr" placeholder="Price INR" class="w-full border rounded-lg px-3 py-2 text-sm" />
      <label class="text-sm"><input type="checkbox" name="includes_division_tests" /> Includes division tests</label>
      <label class="text-sm"><input type="checkbox" name="is_active" checked /> Active</label>
      <button type="submit" class="w-full py-2.5 bg-brand text-white font-semibold rounded-lg text-sm">Save Package</button>
    </form>
  </div>
</div>
<?php endif; ?>
