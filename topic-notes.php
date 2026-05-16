<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';

$courseSlug = (string) ($_GET['course'] ?? '');
$subSlug = (string) ($_GET['sub'] ?? '');
$subjectSlug = (string) ($_GET['subject'] ?? '');
$topicSlug = (string) ($_GET['topic'] ?? '');

$courseRepo = new CourseRepository();
$subject = $courseRepo->findSubjectByPath($courseSlug !== '' ? $courseSlug : null, $subSlug !== '' ? $subSlug : null, $subjectSlug);
if (!$subject) {
    http_response_code(404);
    exit('Subject not found');
}

$topic = $courseRepo->findTopicBySlug((int) $subject['id'], $topicSlug);
if (!$topic) {
    http_response_code(404);
    exit('Topic not found');
}

$notes = $courseRepo->topicNotesForDisplay($topic);
$backUrl = public_subject_workspace_url($courseSlug, $subSlug !== '' ? $subSlug : null, $subjectSlug, 'notes');

$user = current_user();
if ($user) {
    (new StudentAnalyticsRepository())->markTopicRead((int) $user['id'], (int) $topic['id'], $notes !== '' ? 100 : 25);
}

$pageTitle = ($topic['title_te'] ?: $topic['title']) . ' | Notes';
$activeNav = 'courses';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
  <?php
  $backHref = $backUrl;
  $backLabel = '← వెనుకకు / Back to Notes list';
  require __DIR__ . '/includes/public/views/partials/public_back_bar.php';
  ?>

  <header class="bg-white border border-slate-200 rounded-lg p-6 mb-6">
    <h1 class="font-serif text-2xl font-bold text-royal"><?= e($topic['title']) ?></h1>
    <?php if (!empty($topic['title_te'])): ?>
      <p class="font-telugu text-lg text-gold mt-1"><?= e($topic['title_te']) ?></p>
    <?php endif; ?>
    <p class="text-xs text-slate-500 mt-2 font-telugu">స్టడీ మెటీరియల్ / Notes</p>
  </header>

  <article class="bg-white border border-slate-200 rounded-lg p-6 font-telugu text-slate-800 leading-relaxed whitespace-pre-wrap text-sm">
    <?php
    if ($notes === '') {
        $notes = $courseRepo->topicNotesPlaceholder($topic);
    }
    echo nl2br(e($notes));
    ?>
  </article>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
