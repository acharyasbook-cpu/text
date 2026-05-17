<?php

declare(strict_types=1);

require __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/public_site_helpers.php';
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

$topics = $courseRepo->topicsForSubject((int) $subject['id']);
$ranks = FreemiumAccess::topicRanksBySort($topics);
$rank = FreemiumAccess::rankForTopic($ranks, (int) $topic['id']);
$paid = FreemiumAccess::programmeAccessForSubject($subject, (int) $user['id']);

if (!FreemiumAccess::canDownloadNotes($user, $topic, $paid, $rank)) {
    http_response_code(403);
    exit('Download not permitted for this topic.');
}

$notes = $courseRepo->topicNotesForDisplay($topic);
if ($notes === '') {
    $notes = $courseRepo->topicNotesPlaceholder($topic);
}

$filename = preg_replace('/[^a-z0-9\-]+/i', '-', (string) ($topic['slug'] ?? 'notes')) . '.txt';
header('Content-Type: text/plain; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

echo $notes;
