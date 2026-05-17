<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';
require_once __DIR__ . '/includes/SecureContentGuard.php';
require_once __DIR__ . '/includes/FreemiumAccess.php';

$user = require_login();

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

FreemiumAccess::assertTopicNotesAccess($user, $subject, $topic, $courseRepo);

$topics = $courseRepo->topicsForSubject((int) $subject['id']);
$ranks = FreemiumAccess::topicRanksBySort($topics);
$rank = FreemiumAccess::rankForTopic($ranks, (int) $topic['id']);
$paid = FreemiumAccess::programmeAccessForSubject($subject, (int) $user['id']);
$canDownload = FreemiumAccess::canDownloadNotes($user, $topic, $paid, $rank);

$notes = $courseRepo->topicNotesForDisplay($topic);
if ($notes === '') {
    $notes = $courseRepo->topicNotesPlaceholder($topic);
}

$backUrl = public_subject_workspace_url($courseSlug, $subSlug !== '' ? $subSlug : null, $subjectSlug, 'notes');
$downloadUrl = $canDownload
    ? base_url('notes_download.php?' . http_build_query(array_filter([
        'course' => $courseSlug,
        'sub' => $subSlug !== '' ? $subSlug : null,
        'subject' => $subjectSlug,
        'topic' => $topicSlug,
    ])))
    : null;

(new StudentAnalyticsRepository())->markTopicRead((int) $user['id'], (int) $topic['id'], $notes !== '' ? 100 : 25);

$pageTitle = ($topic['title_te'] ?: $topic['title']) . ' | Notes';
$backHref = $backUrl;
$backLabel = '← వెనుకకు / Back to Notes';
$watermarkStyle = SecureContentGuard::watermarkPatternStyle($user);

require __DIR__ . '/includes/secure/secure_shell_start.php';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
  <div class="secure-viewport rounded-xl border border-[#E3E6F0] bg-white shadow-sm overflow-hidden"
       data-watermark="<?= SecureContentGuard::watermarkLabelEscaped($user) ?>"
       style="<?= $watermarkStyle ?>">
    <div class="secure-content-body p-6 sm:p-8">
      <header class="mb-6 pb-4 border-b border-slate-100">
        <h1 class="font-serif text-2xl font-bold text-royal"><?= e($topic['title']) ?></h1>
        <?php if (!empty($topic['title_te'])): ?>
        <p class="font-telugu text-lg text-gold mt-1 font-semibold"><?= e($topic['title_te']) ?></p>
        <?php endif; ?>
        <p class="font-telugu text-xs text-slate-500 mt-2">స్టడీ మెటీరియల్ · కాపీ / ప్రింట్ నిషేధం</p>
        <?php if ($downloadUrl): ?>
        <p class="mt-3">
          <a href="<?= e($downloadUrl) ?>"
             class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-telugu font-semibold hover:bg-slate-800">
            📥 Download PDF / Notes
          </a>
        </p>
        <?php endif; ?>
      </header>
      <article class="font-telugu text-slate-800 leading-relaxed whitespace-pre-wrap text-sm sm:text-base">
        <?= nl2br(e($notes)) ?>
      </article>
    </div>
  </div>
</main>

<?php require __DIR__ . '/includes/secure/secure_shell_end.php'; ?>
