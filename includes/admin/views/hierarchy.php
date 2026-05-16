<?php
/** @var AdminRepository $repo */
$tree = $repo->adminHierarchyTree();
?>

<div class="mb-6">
  <h2 class="text-lg font-semibold text-slate-800">Visibility & Hierarchy</h2>
  <p class="text-sm text-slate-500 mt-1">Turn items <strong class="text-emerald-600">Live</strong> or <strong class="text-amber-600">Draft</strong>. Changes apply to the public site immediately. Toggles save via AJAX (no reload).</p>
</div>

<div class="space-y-8">
<?php foreach ($tree as $course):
    $cLive = isset($course['status']) ? (int) $course['status'] : (int) ($course['is_active'] ?? 1);
?>
  <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-4 px-5 py-4 bg-slate-50 border-b border-slate-200">
      <div>
        <h3 class="font-semibold text-slate-900"><?= admin_e($course['name']) ?></h3>
        <?php if (!empty($course['name_te'])): ?>
          <p class="font-telugu text-sm text-brand mt-0.5"><?= admin_e($course['name_te']) ?></p>
        <?php endif; ?>
      </div>
      <div class="flex items-center gap-3">
        <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Course</span>
        <button type="button" role="switch" aria-checked="<?= $cLive ? 'true' : 'false' ?>"
          class="ab-toggle relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand <?= $cLive ? 'bg-emerald-500' : 'bg-slate-300' ?>"
          data-entity="course" data-id="<?= (int) $course['id'] ?>">
          <span class="sr-only">Toggle course live</span>
          <span class="pointer-events-none inline-block mt-0.5 h-7 w-7 transform rounded-full bg-white shadow transition <?= $cLive ? 'translate-x-6' : 'translate-x-0.5' ?>"></span>
        </button>
        <span class="text-xs w-14 <?= $cLive ? 'text-emerald-700' : 'text-amber-700' ?>"><?= $cLive ? 'Live' : 'Draft' ?></span>
      </div>
    </div>

    <div class="p-5 space-y-6">
      <?php if (SchemaHelper::hierarchyFourTier() && !empty($course['sub_courses_list'])): ?>
        <?php foreach ($course['sub_courses_list'] as $sc):
            $scLive = isset($sc['status']) ? (int) $sc['status'] : (int) ($sc['is_active'] ?? 1);
        ?>
        <div class="border border-slate-100 rounded-lg p-4">
          <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <h4 class="text-sm font-semibold text-brand"><?= admin_e($sc['name']) ?>
              <?php if (!empty($sc['name_te'])): ?>
                <span class="font-telugu font-normal text-slate-600"> — <?= admin_e($sc['name_te']) ?></span>
              <?php endif; ?>
            </h4>
            <div class="flex items-center gap-2">
              <span class="text-[10px] uppercase text-slate-400">Sub-course</span>
              <button type="button" class="ab-toggle relative inline-flex h-7 w-12 rounded-full <?= $scLive ? 'bg-emerald-500' : 'bg-slate-300' ?>" data-entity="sub_course" data-id="<?= (int) $sc['id'] ?>">
                <span class="pointer-events-none inline-block mt-0.5 h-6 w-6 rounded-full bg-white shadow <?= $scLive ? 'translate-x-[1.375rem]' : 'translate-x-0.5' ?>"></span>
              </button>
            </div>
          </div>
          <div class="pl-3 border-l-2 border-gold/30 space-y-3">
            <?php foreach (($sc['linked_subjects'] ?? []) as $sub):
              $lnkLive = isset($sub['scs_status']) ? (int) $sub['scs_status'] : 1;
              $subLive = isset($sub['status']) ? (int) $sub['status'] : (int) ($sub['is_active'] ?? 1);
            ?>
            <div class="bg-slate-50/80 rounded-lg p-3 space-y-2">
              <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="flex items-center gap-3 flex-wrap">
                  <button type="button" class="ab-toggle relative inline-flex h-7 w-12 rounded-full <?= $lnkLive ? 'bg-sky-600' : 'bg-slate-300' ?>" data-entity="scs" data-id="<?= (int) $sub['scs_row_id'] ?>" title="Assignment live (this sub-course)">
                    <span class="pointer-events-none inline-block mt-0.5 h-6 w-6 rounded-full bg-white shadow <?= $lnkLive ? 'translate-x-[1.375rem]' : 'translate-x-0.5' ?>"></span>
                  </button>
                  <span class="text-[10px] uppercase text-slate-400">Map</span>
                  <span class="text-sm font-medium text-slate-800"><?= admin_e($sub['name']) ?></span>
                  <button type="button" class="ab-toggle ml-2 relative inline-flex h-7 w-12 rounded-full <?= $subLive ? 'bg-emerald-500' : 'bg-slate-300' ?>" data-entity="subject" data-id="<?= (int) $sub['id'] ?>" title="Subject global">
                    <span class="pointer-events-none inline-block mt-0.5 h-6 w-6 rounded-full bg-white shadow <?= $subLive ? 'translate-x-[1.375rem]' : 'translate-x-0.5' ?>"></span>
                  </button>
                </div>
              </div>
              <?php foreach (($sub['modules_list'] ?? []) as $mod):
                $modLive = (int) ($mod['status'] ?? 1);
                $labels = ['exam' => 'Exams', 'revision_test' => 'Revision Tests', 'division_test' => 'Division Tests'];
              ?>
              <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs pl-2">
                <span class="text-slate-600"><strong><?= admin_e($labels[$mod['module_type']] ?? $mod['module_type']) ?>:</strong> <?= admin_e($mod['title']) ?></span>
                <button type="button" class="ab-toggle relative inline-flex h-6 w-10 rounded-full <?= $modLive ? 'bg-blue-600' : 'bg-slate-300' ?>" data-entity="module" data-id="<?= (int) $mod['id'] ?>">
                  <span class="pointer-events-none inline-block mt-0.5 h-5 w-5 rounded-full bg-white shadow <?= $modLive ? 'translate-x-[1.125rem]' : 'translate-x-0.5' ?>"></span>
                </button>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
      <?php foreach (($course['categories_list'] ?? []) as $cat):
          $catLive = (int) ($cat['status'] ?? 1);
      ?>
      <div class="border border-slate-100 rounded-lg p-4">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
          <h4 class="text-sm font-semibold text-brand"><?= admin_e($cat['name']) ?>
            <?php if (!empty($cat['name_te'])): ?>
              <span class="font-telugu font-normal text-slate-600"> — <?= admin_e($cat['name_te']) ?></span>
            <?php endif; ?>
          </h4>
          <div class="flex items-center gap-2">
            <span class="text-[10px] uppercase text-slate-400">Category</span>
            <button type="button" role="switch" aria-checked="<?= $catLive ? 'true' : 'false' ?>"
              class="ab-toggle relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full transition-colors <?= $catLive ? 'bg-emerald-500' : 'bg-slate-300' ?>"
              data-entity="category" data-id="<?= (int) $cat['id'] ?>">
              <span class="pointer-events-none inline-block mt-0.5 h-6 w-6 rounded-full bg-white shadow transition <?= $catLive ? 'translate-x-[1.375rem]' : 'translate-x-0.5' ?>"></span>
            </button>
          </div>
        </div>
        <div class="pl-3 border-l-2 border-gold/30 space-y-3">
          <?php foreach (($cat['subjects_list'] ?? []) as $sub):
              $subLive = isset($sub['status']) ? (int) $sub['status'] : (int) ($sub['is_active'] ?? 1);
          ?>
          <div class="bg-slate-50/80 rounded-lg p-3">
            <div class="flex flex-wrap items-start justify-between gap-2">
              <div>
                <p class="text-sm font-medium text-slate-800"><?= admin_e($sub['name']) ?>
                  <?php if (!empty($sub['marks_allocated'])): ?>
                    <span class="text-xs text-slate-500 font-normal">· <?= (int) $sub['marks_allocated'] ?> marks</span>
                  <?php endif; ?>
                </p>
                <?php if (!empty($sub['name_te'])): ?>
                  <p class="font-telugu text-xs text-slate-600"><?= admin_e($sub['name_te']) ?></p>
                <?php endif; ?>
              </div>
              <div class="flex items-center gap-2">
                <span class="text-[10px] uppercase text-slate-400">Subject</span>
                <button type="button" role="switch"
                  class="ab-toggle relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full <?= $subLive ? 'bg-emerald-500' : 'bg-slate-300' ?>"
                  data-entity="subject" data-id="<?= (int) $sub['id'] ?>">
                  <span class="pointer-events-none inline-block mt-0.5 h-6 w-6 rounded-full bg-white shadow <?= $subLive ? 'translate-x-[1.375rem]' : 'translate-x-0.5' ?>"></span>
                </button>
              </div>
            </div>
            <?php foreach (($sub['modules_list'] ?? []) as $mod):
                $modLive = (int) ($mod['status'] ?? 1);
                $labels = ['exam' => 'Exams', 'revision_test' => 'Revision Tests', 'division_test' => 'Division Tests'];
            ?>
              <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs pl-2">
                <span class="text-slate-600">
                  <strong class="text-slate-700"><?= admin_e($labels[$mod['module_type']] ?? $mod['module_type']) ?>:</strong>
                  <?= admin_e($mod['title']) ?>
                </span>
                <button type="button" role="switch"
                  class="ab-toggle relative inline-flex h-6 w-10 shrink-0 cursor-pointer rounded-full <?= $modLive ? 'bg-blue-600' : 'bg-slate-300' ?>"
                  data-entity="module" data-id="<?= (int) $mod['id'] ?>">
                  <span class="pointer-events-none inline-block mt-0.5 h-5 w-5 rounded-full bg-white shadow text-[0] <?= $modLive ? 'translate-x-[1.125rem]' : 'translate-x-0.5' ?>"></span>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <?php if (!SchemaHelper::hierarchyFourTier() && !empty($course['orphan_subjects'])): ?>
      <div class="rounded-lg border border-amber-200 bg-amber-50/60 p-4">
        <p class="text-xs font-semibold text-amber-800 mb-2">Subjects without category (assign category in Course Manager)</p>
        <?php foreach ($course['orphan_subjects'] as $sub):
            $subLive = isset($sub['status']) ? (int) $sub['status'] : (int) ($sub['is_active'] ?? 1);
        ?>
          <div class="flex justify-between items-center py-2 border-b border-amber-100 last:border-0">
            <span class="text-sm"><?= admin_e($sub['name']) ?></span>
            <button type="button" class="ab-toggle relative inline-flex h-7 w-12 rounded-full <?= $subLive ? 'bg-emerald-500' : 'bg-slate-300' ?>" data-entity="subject" data-id="<?= (int) $sub['id'] ?>">
              <span class="pointer-events-none inline-block mt-0.5 h-6 w-6 rounded-full bg-white shadow <?= $subLive ? 'translate-x-[1.375rem]' : 'translate-x-0.5' ?>"></span>
            </button>
          </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>

<script>
(function () {
  var apiUrl = <?= json_encode(admin_url('admin_api.php')) ?>;

  function setToggleVisual(btn, on) {
    var knob = btn.querySelector('span:last-child');
    btn.setAttribute('aria-checked', on ? 'true' : 'false');
    if (btn.classList.contains('h-8')) {
      btn.classList.toggle('bg-emerald-500', on);
      btn.classList.toggle('bg-slate-300', !on);
      if (knob) knob.classList.toggle('translate-x-6', on);
      if (knob) knob.classList.toggle('translate-x-0.5', !on);
    } else if (btn.classList.contains('h-6')) {
      btn.classList.toggle('bg-blue-600', on);
      btn.classList.toggle('bg-slate-300', !on);
      if (knob) knob.classList.toggle('translate-x-[1.125rem]', on);
      if (knob) knob.classList.toggle('translate-x-0.5', !on);
    } else {
      btn.classList.toggle('bg-emerald-500', on);
      btn.classList.toggle('bg-slate-300', !on);
      if (knob) knob.classList.toggle('translate-x-[1.375rem]', on);
      if (knob) knob.classList.toggle('translate-x-0.5', !on);
    }
  }

  document.querySelectorAll('.ab-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var on = btn.getAttribute('aria-checked') !== 'true';
      var entity = btn.getAttribute('data-entity');
      var id = parseInt(btn.getAttribute('data-id'), 10);

      btn.disabled = true;

      fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ entity: entity, id: id, status: on }),
        credentials: 'same-origin'
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          btn.disabled = false;
          if (j.ok) setToggleVisual(btn, on);
          else alert(j.error || 'Failed to update');
        })
        .catch(function () {
          btn.disabled = false;
          alert('Network error');
        });
    });
  });
})();
</script>
