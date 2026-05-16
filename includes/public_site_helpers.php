<?php

declare(strict_types=1);

/** @return list<string> */
function public_five_tier_ordered_keys(): array
{
    return ['topic', 'division', 'revision', 'grand', 'model'];
}

/** @return array<string,string> */
function public_five_tier_labels_te(): array
{
    return [
        'topic' => 'టాపిక్ టెస్ట్‌లు',
        'division' => 'డివిజన్ టెస్ట్‌లు',
        'revision' => 'రివిజన్ టెస్ట్‌లు',
        'grand' => 'గ్రాండ్ టెస్ట్‌లు',
        'model' => 'మోడల్ పేపర్లు',
    ];
}

/** Subject workspace — respects programme path when four-tier links exist. */
function public_subject_workspace_url(string $courseSlug, ?string $subCourseSlug, string $subjectSlug): string
{
    $q = ['course' => $courseSlug, 'subject' => $subjectSlug];
    if ($subCourseSlug !== null && $subCourseSlug !== '') {
        $q['sub'] = $subCourseSlug;
    }

    return base_url('subject.php?' . http_build_query($q));
}

function public_sub_course_workspace_url(string $courseSlug, string $subCourseSlug): string
{
    return base_url('sub_course.php?' . http_build_query(['course' => $courseSlug, 'sub' => $subCourseSlug]));
}

function public_media_url(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    return base_url(ltrim($path, '/'));
}

function public_media_cache_version(?string $path): int
{
    if ($path === null || $path === '') {
        return 0;
    }
    $abs = ACHARYA_ROOT . '/' . ltrim($path, '/');

    return is_file($abs) ? (int) filemtime($abs) : 0;
}

function public_topic_notes_url(string $courseSlug, ?string $subCourseSlug, string $subjectSlug, string $topicSlug): string
{
    $q = ['course' => $courseSlug, 'subject' => $subjectSlug, 'topic' => $topicSlug];
    if ($subCourseSlug !== null && $subCourseSlug !== '') {
        $q['sub'] = $subCourseSlug;
    }

    return base_url('topic-notes.php?' . http_build_query($q));
}

function public_course_overview_url(string $courseSlug): string
{
    return base_url('course.php?' . http_build_query(['slug' => $courseSlug]));
}
