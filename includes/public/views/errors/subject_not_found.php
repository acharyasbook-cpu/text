<?php
/** @var string $courseSlug */
/** @var string $subSlug */
$courseSlug = $courseSlug ?? '';
$subSlug = $subSlug ?? '';
$backHref = $subSlug !== '' && $courseSlug !== ''
    ? public_sub_course_workspace_url($courseSlug, $subSlug)
    : ($courseSlug !== '' ? base_url('learn.php?course=' . rawurlencode($courseSlug)) : base_url('index.php'));
$backLabel = '← వెనుకకు / Back';
?>
<main class="max-w-lg mx-auto px-4 py-16 font-telugu">
  <?php require __DIR__ . '/../partials/public_back_bar.php'; ?>
  <div class="text-center mt-6">
    <h1 class="text-2xl font-bold text-royal">విషయం కనుగొనబడలేదు</h1>
    <p class="text-sm text-slate-600 mt-3">Subject not found — స్లగ్ మారినట్లయితే Admin లో Content Manager నుండి సబ్జెక్ట్‌ను మళ్లీ సేవ్ చేయండి (pivot లింక్ స్వయంచాలకంగా పునరుద్ధరించబడుతుంది).</p>
  </div>
</main>
