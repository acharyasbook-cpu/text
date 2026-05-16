<?php

declare(strict_types=1);

final class SchemaHelper
{
    private static ?bool $courseStatus = null;
    private static ?bool $subjectStatus = null;
    private static ?bool $testStatus = null;

    /** Safe ORDER BY for hierarchy lists (NULL/zero sort_order still visible). */
    public static function sqlOrderBySort(string $sortCol = 'sort_order', string $idCol = 'id'): string
    {
        return "COALESCE(NULLIF({$sortCol}, 0), {$idCol}) ASC, {$idCol} ASC";
    }

    /**
     * One-time per request: fix NULL sort_order and ensure flagship courses stay active/visible.
     */
    public static function ensureCatalogHealth(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        try {
            $pdo = db();
            $tables = ['main_courses', 'courses', 'sub_courses', 'subjects', 'topics', 'sub_topics', 'topic_exam_suite', 'exams', 'study_materials', 'sub_course_subjects'];
            foreach ($tables as $table) {
                if (!self::hasTable($table) || !self::columnExists($table, 'sort_order')) {
                    continue;
                }
                $pdo->exec("UPDATE `{$table}` SET sort_order = id WHERE sort_order IS NULL");
            }

            $flagship = ['ap-dsc', 'ts-dsc', 'ap-tet', 'ts-tet', 'ctet'];
            foreach (['main_courses', 'courses'] as $table) {
                if (!self::hasTable($table)) {
                    continue;
                }
                $hasStatus = self::columnExists($table, 'status');
                $hasActive = self::columnExists($table, 'is_active');
                foreach ($flagship as $slug) {
                    $sets = [];
                    if ($hasActive) {
                        $sets[] = 'is_active = 1';
                    }
                    if ($hasStatus) {
                        $sets[] = 'status = 1';
                    }
                    if ($sets === []) {
                        continue;
                    }
                    $pdo->prepare('UPDATE `' . $table . '` SET ' . implode(', ', $sets) . ' WHERE slug = ?')
                        ->execute([$slug]);
                }
            }
        } catch (Throwable $e) {
            // Non-fatal on connection/bootstrap edge cases.
        }
    }

    /**
     * Map main_courses.id (admin dropdown) → courses.id used by sub_courses.course_id.
     */
    public static function resolveCatalogCourseId(int $selectedId): int
    {
        if ($selectedId < 1) {
            return $selectedId;
        }

        if (self::hierarchyFourTier()) {
            try {
                $chk = db()->prepare('SELECT COUNT(*) FROM sub_courses WHERE course_id=?');
                $chk->execute([$selectedId]);
                if ((int) $chk->fetchColumn() > 0) {
                    return $selectedId;
                }
            } catch (Throwable $e) {
                return $selectedId;
            }
        }

        $slug = self::courseSlugByAnyId($selectedId);
        if ($slug === null) {
            return $selectedId;
        }

        foreach (['courses', 'main_courses'] as $table) {
            if (!self::hasTable($table)) {
                continue;
            }
            $st = db()->prepare("SELECT id FROM `{$table}` WHERE slug=? LIMIT 1");
            $st->execute([$slug]);
            $cid = (int) $st->fetchColumn();
            if ($cid < 1) {
                continue;
            }
            if (self::hierarchyFourTier()) {
                $chk = db()->prepare('SELECT COUNT(*) FROM sub_courses WHERE course_id=?');
                $chk->execute([$cid]);
                if ((int) $chk->fetchColumn() > 0) {
                    return $cid;
                }
            } else {
                return $cid;
            }
        }

        return $selectedId;
    }

    private static function courseSlugByAnyId(int $id): ?string
    {
        foreach (['main_courses', 'courses'] as $table) {
            if (!self::hasTable($table)) {
                continue;
            }
            $st = db()->prepare("SELECT slug FROM `{$table}` WHERE id=? LIMIT 1");
            $st->execute([$id]);
            $slug = $st->fetchColumn();
            if (is_string($slug) && $slug !== '') {
                return $slug;
            }
        }

        return null;
    }

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

    public static function topicMcqContentEnabled(): bool
    {
        return self::columnExists(self::topicsTable(), 'mcq_content');
    }

    public static function subCourseTermMatrixEnabled(): bool
    {
        return self::hasTable('sub_course_term_boxes')
            && self::hasTable('sub_course_term_schedule');
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

    /** Table/view the public site should read for main-course rows (includes image_path). */
    public static function publicMainCoursesTable(): string
    {
        if (self::hasTable('main_courses') && self::imagePathEnabled('main_courses')) {
            if (!self::imagePathEnabled('courses')) {
                return 'main_courses';
            }
        }

        return 'courses';
    }

    /**
     * Recreate `courses` view when main_courses gained columns (e.g. image_path) after view creation.
     */
    public static function ensureCoursesViewIncludesImagePath(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        if (!self::hasTable('main_courses') || !self::hasTable('courses')) {
            return;
        }
        if (!self::imagePathEnabled('main_courses') || self::imagePathEnabled('courses')) {
            return;
        }

        try {
            $type = db()->query(
                "SELECT TABLE_TYPE FROM information_schema.TABLES
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'courses' LIMIT 1"
            )->fetchColumn();
            if ($type !== 'VIEW') {
                return;
            }
            db()->exec('CREATE OR REPLACE VIEW `courses` AS SELECT * FROM `main_courses`');
            self::$courseStatus = null;
        } catch (Throwable $e) {
            // Non-fatal: publicMainCoursesTable() still reads main_courses directly.
        }
    }
}
