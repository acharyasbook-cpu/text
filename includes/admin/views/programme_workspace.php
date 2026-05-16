<?php
/** @var AdminRepository $repo */
/** @var array<string,mixed>|null $programmeRow */
/** @var array<int,array<string,mixed>> $workspaceSubjects */
/** @var string|null $workspaceError */

$tierTe = [
    'topic' => 'టాపిక్ టెస్ట్‌లు',
    'division' => 'డివిజన్ టెస్ట్‌లు',
    'revision' => 'రివిజన్ టెస్ట్‌లు',
    'grand' => 'గ్రాండ్ టెస్ట్‌లు',
    'model' => 'మోడల్ పేపర్లు',
];
$tierOrder = ['topic', 'division', 'revision', 'grand', 'model'];

$testsLive = static function (array $t): bool {
    if (SchemaHelper::testsHasStatus()) {
        return !empty($t['status']) && !empty($t['is_active']);
    }

    return !empty($t['is_active']);
};
$subjectLive = static function (array $s): bool {
    if (SchemaHelper::subjectsHasStatus()) {
        return !empty($s['status']) && !empty($s['is_active']);
    }

    return !empty($s['is_active']);
};
$pivotLive = static function (array $s): bool {
    if (SchemaHelper::columnExists('sub_course_subjects', 'status')) {
        return !empty($s['scs_status']) && !empty($s['scs_is_active']);
    }

    return !empty($s['scs_is_active']);
};
$subCourseLive = static function (array $sc): bool {
    if (SchemaHelper::columnExists('sub_courses', 'status')) {
        return !empty($sc['status']) && !empty($sc['is_active']);
    }

    return !empty($sc['is_active']);
};

$coursesUrl = admin_dashboard_url(['view' => 'courses', 'tab' => 'subjects']);
$topicsUrl = admin_dashboard_url(['view' => 'courses', 'tab' => 'topics']);
$examsUrl = admin_dashboard_url(['view' => 'exams']);
$pr = $programmeRow ?? [];
$mcSlug = (string) ($pr['course_slug'] ?? '');
$scSlug = (string) ($pr['slug'] ?? '');
$addSubjectUrl = admin_dashboard_url(['view' => 'courses', 'tab' => 'subjects']);
$isTsDscMc = ($mcSlug === 'ts-dsc' && $programmeRow !== null);
$isApTetMc = ($mcSlug === 'ap-tet' && $programmeRow !== null);
$isTsTetMc = ($mcSlug === 'ts-tet' && $programmeRow !== null);
$isCtetMc = ($mcSlug === 'ctet' && $programmeRow !== null);
$breadcrumbProgTitle = ($pr['name_te'] ?? '') ?: (($pr['name'] ?? '') ?: 'Programme');
?>

<div class="mb-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
  <a href="<?= admin_e(admin_dashboard_url(['view' => 'overview'])) ?>" class="hover:text-brand">Dashboard</a>
  <span>/</span>
  <span class="font-medium text-slate-700"><?= admin_e(strtoupper(str_replace('-', ' ', $mcSlug ?: '—'))) ?></span>
  <span>/</span>
  <span class="font-telugu font-medium text-brand"><?= admin_e($breadcrumbProgTitle) ?></span>
</div>

<?php if ($isTsDscMc): ?>
<p class="font-telugu text-sm text-slate-600 mb-4 -mt-2 max-w-3xl leading-relaxed">తెలంగాణ డిఎస్సి ప్రొగ్రామ్ వర్క్‌స్పేస్ — కంటెంట్ నిర్వహణకు <strong>టాపిక్‌లు</strong> (Topics) పేరే వాడండి.</p>
<?php elseif ($isApTetMc): ?>
<p class="font-telugu text-sm text-slate-600 mb-4 -mt-2 max-w-3xl leading-relaxed">ఏపీ టెట్ ప్రొగ్రామ్ వర్క్‌స్పేస్ — ప్రతి పేపర్‌కు <strong>సబ్జెక్ట్ టైటిలర్లు</strong>, ఐదు స్థాయిల పరీక్షలు (టాపిక్ / డివిజన్ / రివిజన్ / గ్రాండ్ / మోడల్) మరియు కంటెంట్‌కు <strong>టాపిక్‌లు</strong> నిర్వహణ.</p>
<?php elseif ($isTsTetMc): ?>
<p class="font-telugu text-sm text-slate-600 mb-4 -mt-2 max-w-3xl leading-relaxed">టీఎస్ టెట్ — పేపర్ 2 రెండు స్ట్రీమ్‌లు: <strong>మ్యాథమెటిక్స్ &amp; సైన్స్</strong>, <strong>సోషల్ స్టడీస్</strong>. ప్రతి సబ్జెక్టుకు ఐదు స్థాయిల పరీక్షలు.</p>
<?php elseif ($isCtetMc): ?>
<p class="font-telugu text-sm text-slate-600 mb-4 -mt-2 max-w-3xl leading-relaxed">సీటెట్ — పేపర్ 2 రెండు స్ట్రీమ్‌లు: <strong>మ్యాథ్స్ &amp; సైన్స్</strong>, <strong>సోషల్ స్టడీస్</strong>. ప్రతి సబ్జెక్టుకు ఐదు స్థాయిల పరీక్షలు.</p>
<?php endif; ?>

<?php if (!empty($workspaceError)): ?>
  <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-amber-900 text-sm"><?= admin_e($workspaceError) ?></div>
<?php elseif (!$programmeRow): ?>
  <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-red-800 text-sm">Programme not found.</div>
<?php else: ?>

<div class="flex flex-col xl:flex-row gap-8 items-start">
  <div class="flex-1 min-w-0 space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Subject Titlers</h2>
        <p class="text-sm text-slate-500 mt-1 font-telugu">ప్రతి పేపర్ కింద ఐదు స్థాయిల పరీక్షల నిర్వహణ — లైవ్ / డ్రాఫ్ట్</p>
      </div>
      <div class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 shadow-sm">
          <span class="text-[10px] uppercase tracking-wide text-slate-500">Programme</span>
          <button type="button" class="ab-pro-toggle relative inline-flex h-8 w-14 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-400 <?= $subCourseLive($programmeRow) ? 'bg-emerald-500' : 'bg-slate-300' ?>"
            data-entity="sub_course" data-id="<?= (int) $programmeRow['id'] ?>" aria-checked="<?= $subCourseLive($programmeRow) ? 'true' : 'false' ?>">
            <span class="pointer-events-none inline-block mt-0.5 h-7 w-7 transform rounded-full bg-white shadow transition <?= $subCourseLive($programmeRow) ? 'translate-x-6' : 'translate-x-0.5' ?>"></span>
          </button>
          <span class="text-xs font-medium <?= $subCourseLive($programmeRow) ? 'text-emerald-700' : 'text-amber-700' ?>"><?= $subCourseLive($programmeRow) ? 'Live' : 'Draft' ?></span>
        </div>
        <label class="flex items-center gap-2 text-xs text-slate-600 cursor-pointer select-none">
          <input type="checkbox" id="wsCascadeDefault" checked class="rounded border-slate-300 text-brand focus:ring-brand" />
          <span>Cascade tests when toggling subject</span>
        </label>
        <a href="<?= admin_e($addSubjectUrl) ?>" class="inline-flex items-center justify-center rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-white shadow hover:bg-brand-dark">Add Extra Subject</a>
      </div>
    </div>

    <?php if (!$workspaceSubjects): ?>
      <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center text-slate-600 text-sm">
        No subjects linked to this programme yet. Use <strong>Add Extra Subject</strong> or <a class="text-brand font-medium hover:underline" href="<?= admin_e($coursesUrl) ?>">Subject Manager</a> to attach papers.
      </div>
    <?php endif; ?>

    <div class="space-y-10">
      <?php foreach ($workspaceSubjects as $sub): ?>
        <?php
          $sid = (int) $sub['id'];
          $sOn = $subjectLive($sub);
          $mapOn = $pivotLive($sub);
        ?>
        <section class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <div class="grid lg:grid-cols-3 gap-4 p-5 bg-gradient-to-br from-slate-50 to-white border-b border-slate-100">
            <div class="lg:col-span-2 flex flex-wrap items-center gap-4">
              <div class="rounded-xl border border-brand/20 bg-brand/5 px-5 py-4 min-w-[200px] flex-1">
                <p class="font-telugu text-lg font-semibold text-slate-900 leading-snug"><?= admin_e($sub['name_te'] ?: $sub['name']) ?></p>
                <?php if (!empty($sub['name_te']) && $sub['name'] !== $sub['name_te']): ?>
                  <p class="text-xs text-slate-500 mt-1"><?= admin_e($sub['name']) ?></p>
                <?php endif; ?>
              </div>
              <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                  <span class="text-[10px] uppercase text-slate-400">Map</span>
                  <button type="button" class="ab-pro-toggle relative inline-flex h-8 w-14 rounded-full <?= $mapOn ? 'bg-sky-600' : 'bg-slate-300' ?>"
                    data-entity="scs" data-id="<?= (int) $sub['scs_row_id'] ?>" aria-checked="<?= $mapOn ? 'true' : 'false' ?>">
                    <span class="pointer-events-none inline-block mt-0.5 h-7 w-7 rounded-full bg-white shadow <?= $mapOn ? 'translate-x-6' : 'translate-x-0.5' ?>"></span>
                  </button>
                </div>
                <div class="flex items-center gap-2">
                  <span class="text-[10px] uppercase text-slate-400">Subject</span>
                  <button type="button" class="ab-pro-toggle ab-subject-master relative inline-flex h-8 w-14 rounded-full <?= $sOn ? 'bg-emerald-500' : 'bg-slate-300' ?>"
                    data-entity="subject" data-id="<?= $sid ?>" data-cascade="1" aria-checked="<?= $sOn ? 'true' : 'false' ?>">
                    <span class="pointer-events-none inline-block mt-0.5 h-7 w-7 rounded-full bg-white shadow <?= $sOn ? 'translate-x-6' : 'translate-x-0.5' ?>"></span>
                  </button>
                  <span class="text-xs <?= $sOn ? 'text-emerald-700' : 'text-amber-700' ?>"><?= $sOn ? 'Live' : 'Draft' ?></span>
                </div>
              </div>
            </div>
            <div class="flex lg:justify-end gap-2 flex-wrap">
              <a href="<?= admin_e(admin_dashboard_url(['view' => 'content', 'mc' => $navProgramme['mc'] ?? '', 'sc' => $navProgramme['sc'] ?? ''])) ?>" class="text-xs font-medium px-3 py-2 rounded-lg bg-slate-800 text-white hover:bg-slate-900">Content Manager</a>
              <a href="<?= admin_e(admin_dashboard_url(['view' => 'courses', 'tab' => 'topics'])) ?>" class="text-xs font-medium px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">Material</a>
              <a href="<?= admin_e(admin_dashboard_url(['view' => 'exams'])) ?>" class="text-xs font-medium px-3 py-2 rounded-lg border border-slate-200 text-slate-700 hover:bg-slate-50">Exam Links</a>
            </div>
          </div>

          <div class="p-4 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-3">
            <?php foreach ($tierOrder as $tierKey): ?>
              <?php
                $bucket = $sub['tests_by_tier'][$tierKey] ?? [];
                $tierTestsOn = $bucket === [] ? false : !array_filter($bucket, static fn ($t) => !$testsLive($t));
              ?>
              <div class="rounded-xl border border-slate-100 bg-slate-50/80 min-h-[140px] flex flex-col">
                <div class="flex items-center justify-between gap-2 px-3 py-2 border-b border-slate-200/80 bg-white/90">
                  <span class="font-telugu text-xs font-semibold text-slate-800 leading-tight"><?= admin_e($tierTe[$tierKey]) ?></span>
                  <button type="button" class="ab-tier-toggle relative inline-flex h-7 w-12 shrink-0 rounded-full <?= ($bucket !== [] && $tierTestsOn) ? 'bg-emerald-500' : 'bg-slate-300' ?> <?= $bucket === [] ? 'opacity-40 cursor-not-allowed' : '' ?>"
                    data-subject-id="<?= $sid ?>" data-test-type="<?= admin_e($tierKey) ?>"
                    <?= $bucket === [] ? 'disabled' : '' ?>
                    aria-checked="<?= ($bucket !== [] && $tierTestsOn) ? 'true' : 'false' ?>">
                    <span class="pointer-events-none inline-block mt-0.5 h-6 w-6 rounded-full bg-white shadow <?= ($bucket !== [] && $tierTestsOn) ? 'translate-x-[1.375rem]' : 'translate-x-0.5' ?>"></span>
                  </button>
                </div>
                <ul class="flex-1 p-2 space-y-1.5 overflow-y-auto max-h-52">
                  <?php if (!$bucket): ?>
                    <li class="text-[11px] text-slate-400 px-1 py-2 font-telugu">ఇంకా టెస్టులు లేవు</li>
                  <?php endif; ?>
                  <?php foreach ($bucket as $test): ?>
                    <?php $tid = (int) $test['id']; $tlive = $testsLive($test); ?>
                    <li class="flex items-center justify-between gap-2 rounded-lg bg-white border border-slate-100 px-2 py-1.5">
                      <span class="text-[11px] text-slate-700 truncate" title="<?= admin_e($test['title'] ?? '') ?>"><?= admin_e($test['title'] ?? $test['slug'] ?? ('Test ' . $tid)) ?></span>
                      <button type="button" class="ab-pro-toggle relative inline-flex h-6 w-10 shrink-0 rounded-full <?= $tlive ? 'bg-blue-600' : 'bg-slate-300' ?>"
                        data-entity="test" data-id="<?= $tid ?>" data-owner-subject="<?= $sid ?>" aria-checked="<?= $tlive ? 'true' : 'false' ?>">
                        <span class="pointer-events-none inline-block mt-0.5 h-5 w-5 rounded-full bg-white shadow <?= $tlive ? 'translate-x-[1.125rem]' : 'translate-x-0.5' ?>"></span>
                      </button>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endforeach; ?>
    </div>
  </div>

  <aside class="w-full xl:w-72 shrink-0 xl:sticky xl:top-24 space-y-4">
    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm p-5">
      <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wide">Manage Content</h3>
      <p class="text-[11px] text-slate-500 mt-1">టాపిక్లు, మెటీరియల్, పరీక్ష లింక్‌లు</p>
      <?php if ($isTsDscMc): ?>
      <p class="text-[10px] text-slate-400 mt-1 font-telugu leading-snug">టీఎస్ డీఎస్‌సీ విభాగంలో లెసన్స్ కోసం “టాపిక్‌లు” మెనూనే వాడండి.</p>
      <?php elseif ($isApTetMc): ?>
      <p class="text-[10px] text-slate-400 mt-1 font-telugu leading-snug">ఏపీ టెట్ విభాగంలో కూడా అడ్మిన్‌లో “టాపిక్‌లు” మెనూనే కంటెంట్ అటాచ్ చేయండి.</p>
      <?php elseif ($isTsTetMc || $isCtetMc): ?>
      <p class="text-[10px] text-slate-400 mt-1 font-telugu leading-snug">టెట్ పేపర్ వర్క్‌స్పేస్ — కంటెంట్‌కు “టాపిక్‌లు” మెనూనే వాడండి.</p>
      <?php endif; ?>
      <div class="mt-4 space-y-2">
        <a href="<?= admin_e($topicsUrl) ?>" class="flex items-center justify-center w-full rounded-lg bg-brand py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-dark">Add Topics</a>
        <a href="<?= admin_e(admin_dashboard_url(['view' => 'content'])) ?>" class="flex items-center justify-center w-full rounded-lg bg-brand py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-dark">Material (Notes)</a>
        <a href="<?= admin_e($examsUrl) ?>" class="flex items-center justify-center w-full rounded-lg bg-brand py-2.5 text-sm font-semibold text-white shadow hover:bg-brand-dark">Exam Links</a>
      </div>
      <a href="<?= admin_e($addSubjectUrl) ?>" class="mt-4 flex items-center justify-center w-full rounded-lg border-2 border-brand text-brand py-2.5 text-sm font-semibold hover:bg-brand/5">Add Extra Subject</a>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-[11px] text-slate-600">
      Programme slug <code class="text-slate-800"><?= admin_e($scSlug) ?></code> · Course <code class="text-slate-800"><?= admin_e($mcSlug) ?></code>
    </div>
  </aside>
</div>

<script>
(function () {
  var apiUrl = <?= json_encode(admin_url('admin_api.php')) ?>;

  function paint(btn, on) {
    btn.setAttribute('aria-checked', on ? 'true' : 'false');
    var kn = btn.querySelector('span.pointer-events-none');
    var ent = btn.getAttribute('data-entity');
    btn.classList.remove('bg-emerald-500', 'bg-slate-300', 'bg-sky-600', 'bg-blue-600');
    if (ent === 'scs') {
      btn.classList.add(on ? 'bg-sky-600' : 'bg-slate-300');
    } else if (ent === 'test') {
      btn.classList.add(on ? 'bg-blue-600' : 'bg-slate-300');
    } else {
      btn.classList.add(on ? 'bg-emerald-500' : 'bg-slate-300');
    }
    if (!kn) return;
    var base = 'pointer-events-none inline-block rounded-full bg-white shadow ';
    if (btn.classList.contains('h-8') && btn.classList.contains('w-14')) {
      kn.className = base + 'mt-0.5 h-7 w-7 transform transition ' + (on ? 'translate-x-6' : 'translate-x-0.5');
    } else if (btn.classList.contains('h-6')) {
      kn.className = base + 'mt-0.5 h-5 w-5 ' + (on ? 'translate-x-[1.125rem]' : 'translate-x-0.5');
    } else {
      kn.className = base + 'mt-0.5 h-6 w-6 ' + (on ? 'translate-x-[1.375rem]' : 'translate-x-0.5');
    }
  }

  function paintTier(btn, on) {
    btn.setAttribute('aria-checked', on ? 'true' : 'false');
    btn.classList.toggle('bg-emerald-500', on);
    btn.classList.toggle('bg-slate-300', !on);
    var kn = btn.querySelector('span.pointer-events-none');
    if (kn) {
      kn.className = 'pointer-events-none inline-block mt-0.5 h-6 w-6 rounded-full bg-white shadow ' + (on ? 'translate-x-[1.375rem]' : 'translate-x-0.5');
    }
  }

  function postJSON(body) {
    return fetch(apiUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
      credentials: 'same-origin'
    }).then(function (r) { return r.json(); });
  }

  document.querySelectorAll('.ab-pro-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      var on = btn.getAttribute('aria-checked') !== 'true';
      var entity = btn.getAttribute('data-entity');
      var id = parseInt(btn.getAttribute('data-id'), 10);
      var cascade = btn.classList.contains('ab-subject-master') && document.getElementById('wsCascadeDefault') && document.getElementById('wsCascadeDefault').checked;
      var body = { entity: entity, id: id, status: on };
      if (entity === 'subject' && cascade) body.cascade_tests = true;
      btn.disabled = true;
      postJSON(body).then(function (j) {
        btn.disabled = false;
        if (!j.ok) { alert(j.error || 'Update failed'); return; }
        paint(btn, on);
        if (entity === 'subject' && cascade && btn.classList.contains('ab-subject-master')) {
          document.querySelectorAll('.ab-pro-toggle[data-entity="test"][data-owner-subject="' + id + '"]').forEach(function (tb) {
            paint(tb, on);
          });
          document.querySelectorAll('.ab-tier-toggle[data-subject-id="' + id + '"]').forEach(function (tb) {
            if (!tb.disabled) paintTier(tb, on);
          });
        }
      }).catch(function () { btn.disabled = false; alert('Network error'); });
    });
  });

  document.querySelectorAll('.ab-tier-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (btn.disabled) return;
      var on = btn.getAttribute('aria-checked') !== 'true';
      var sid = parseInt(btn.getAttribute('data-subject-id'), 10);
      var tt = btn.getAttribute('data-test-type');
      btn.disabled = true;
      postJSON({ entity: 'test_tier', subject_id: sid, test_type: tt, status: on }).then(function (j) {
        btn.disabled = false;
        if (!j.ok) { alert(j.error || 'Update failed'); return; }
        paintTier(btn, on);
        var col = btn.closest('.rounded-xl');
        if (!col) return;
        var sidStr = String(sid);
        col.querySelectorAll('.ab-pro-toggle[data-entity="test"]').forEach(function (tb) {
          if (tb.getAttribute('data-owner-subject') === sidStr) paint(tb, on);
        });
      }).catch(function () { btn.disabled = false; alert('Network error'); });
    });
  });
})();
</script>

<?php endif; ?>
