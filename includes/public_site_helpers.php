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
function public_subject_workspace_url(string $courseSlug, ?string $subCourseSlug, string $subjectSlug, ?string $panel = null): string
{
    $q = ['course' => $courseSlug, 'subject' => $subjectSlug];
    if ($subCourseSlug !== null && $subCourseSlug !== '') {
        $q['sub'] = $subCourseSlug;
    }
    if ($panel === 'notes' || $panel === 'exam') {
        $q['panel'] = $panel;
    }

    return base_url('subject.php?' . http_build_query($q));
}

function public_sub_course_workspace_url(string $courseSlug, string $subCourseSlug): string
{
    return base_url('sub_course.php?' . http_build_query(['course' => $courseSlug, 'sub' => $subCourseSlug]));
}

/**
 * Web path prefix for project root (no trailing slash), e.g. '' or '/acharya-books'.
 * Pure PHP — does not require init.php or base_url().
 */
function acharya_site_base_path(): string
{
    $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
    if (str_ends_with($scriptDir, '/admin')) {
        $root = rtrim(dirname($scriptDir), '/');
    } else {
        $root = $scriptDir;
    }
    if ($root === '/' || $root === '.' || $root === '') {
        return '';
    }

    return $root;
}

/**
 * Resolve a stored relative media path to a browser URL (admin panel + public site).
 */
function acharya_media_url(?string $path): string
{
    if ($path === null || $path === '') {
        return '';
    }
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $rel = ltrim(str_replace('\\', '/', $path), '/');

    if (function_exists('admin_media_url') && function_exists('admin_in_panel') && admin_in_panel()) {
        return admin_media_url($rel);
    }

    if (function_exists('base_url')) {
        return base_url($rel);
    }

    $base = acharya_site_base_path();

    return $base === '' ? '/' . $rel : $base . '/' . $rel;
}

function public_media_url(?string $path): string
{
    return acharya_media_url($path);
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

    return base_url('note_viewer.php?' . http_build_query($q));
}

/** Relative return path for exam → subject workspace (exam panel). */
function public_subject_exam_return_path(string $courseSlug, ?string $subCourseSlug, string $subjectSlug): string
{
    $q = ['course' => $courseSlug, 'subject' => $subjectSlug, 'panel' => 'exam'];
    if ($subCourseSlug !== null && $subCourseSlug !== '') {
        $q['sub'] = $subCourseSlug;
    }

    return 'subject.php?' . http_build_query($q);
}

function public_exam_start_url(string $courseSlug, string $testSlug, ?string $returnPath = null): string
{
    $q = ['course' => $courseSlug, 'test' => $testSlug];
    if ($returnPath !== null && $returnPath !== '') {
        $q['return'] = $returnPath;
    }

    return base_url('exam_running.php?' . http_build_query($q));
}

function public_course_overview_url(string $courseSlug): string
{
    return base_url('course.php?' . http_build_query(['slug' => $courseSlug]));
}
