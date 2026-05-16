<?php

declare(strict_types=1);

final class SchemaHelper
{
    private static ?bool $courseStatus = null;
    private static ?bool $subjectStatus = null;
    private static ?bool $testStatus = null;

    public static function coursesHasStatus(): bool
    {
        if (self::$courseStatus === null) {
            self::$courseStatus = self::columnExists('courses', 'status');
        }

        return self::$courseStatus;
    }

    public static function subjectsHasStatus(): bool
    {
        if (self::$subjectStatus === null) {
            self::$subjectStatus = self::columnExists('subjects', 'status');
        }

        return self::$subjectStatus;
    }

    public static function testsHasStatus(): bool
    {
        if (self::$testStatus === null) {
            self::$testStatus = self::columnExists('tests', 'status');
        }

        return self::$testStatus;
    }

    public static function hasTable(string $table): bool
    {
        try {
            $db = db()->query('SELECT DATABASE()')->fetchColumn();
            $st = db()->prepare(
                'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?'
            );
            $st->execute([$db, $table]);

            return (int) $st->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function columnExists(string $table, string $column): bool
    {
        try {
            $db = db()->query('SELECT DATABASE()')->fetchColumn();
            $st = db()->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?'
            );
            $st->execute([$db, $table, $column]);

            return (int) $st->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** @return 'topics'|'lessons' */
    public static function topicsTable(): string
    {
        return self::hasTable('topics') ? 'topics' : 'lessons';
    }

    /** Material column linking to topic/lesson row */
    public static function materialsTopicFkColumn(): string
    {
        return self::columnExists('study_materials', 'topic_id') ? 'topic_id' : 'lesson_id';
    }

    public static function hierarchyFourTier(): bool
    {
        return self::hasTable('sub_courses') && self::hasTable('sub_course_subjects');
    }

    /** Unlimited exams linked to a topic row */
    public static function topicExamsEnabled(): bool
    {
        return self::hasTable('exams');
    }

    /** Division / revision / grand / model exams composed of smaller tests */
    public static function testBundleEnabled(): bool
    {
        return self::hasTable('test_bundle_items');
    }

    public static function contentManagerEnabled(): bool
    {
        $tbl = self::topicsTable();

        return self::columnExists($tbl, 'has_sub_topics')
            && self::columnExists($tbl, 'notes_content')
            && self::hasTable('sub_topics');
    }

    public static function topicExamSuiteEnabled(): bool
    {
        return self::hasTable('topic_exam_suite');
    }

    public static function topicNotesBindEnabled(): bool
    {
        return self::columnExists(self::topicsTable(), 'notes_bind_sub_topic_id');
    }

    public static function topicNotesEnabledColumn(): bool
    {
        return self::columnExists(self::topicsTable(), 'notes_enabled');
    }

    public static function imagePathEnabled(string $table): bool
    {
        return self::columnExists($table, 'image_path');
    }

    /** Writable table for main-course cover images (view-safe). */
    public static function mainCourseImageTable(): string
    {
        if (self::hasTable('main_courses') && self::imagePathEnabled('main_courses')) {
            return 'main_courses';
        }

        return 'courses';
    }
}
