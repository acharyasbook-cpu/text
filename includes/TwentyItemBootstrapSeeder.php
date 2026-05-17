<?php

declare(strict_types=1);

require_once __DIR__ . '/FreemiumAccess.php';

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?: 'item';

        return trim($text, '-');
    }
}

/**
 * Idempotent 20-topic + revision-exam bootstrap per subject (టాపిక్ 1…20 / టెస్ట్ 1…20).
 */
final class TwentyItemBootstrapSeeder
{
    public const SLOT_COUNT = 20;

    public static function ensureForSubject(int $subjectId): int
    {
        if ($subjectId < 1) {
            return 0;
        }

        require_once dirname(__DIR__) . '/models/AdminRepository.php';
        require_once dirname(__DIR__) . '/includes/admin/content_manager_defaults.php';

        $repo = new AdminRepository();
        $tbl = SchemaHelper::topicsTable();
        $created = 0;

        for ($slot = 1; $slot <= self::SLOT_COUNT; $slot++) {
            $existing = self::findTopicAtSlot($tbl, $subjectId, $slot);
            if ($existing) {
                continue;
            }
            $topicId = self::createPlaceholderTopic($repo, $subjectId, $slot);
            if ($topicId > 0) {
                self::seedNotesPlaceholder($tbl, $topicId, $slot);
                self::seedRevisionExam($repo, $topicId, $slot);
                $created++;
            }
        }

        return $created;
    }

    public static function ensureForSubCourse(int $subCourseId): int
    {
        if ($subCourseId < 1 || !SchemaHelper::hierarchyFourTier()) {
            return 0;
        }
        $st = db()->prepare(
            'SELECT subject_id FROM sub_course_subjects WHERE sub_course_id=? ORDER BY sort_order, id'
        );
        $st->execute([$subCourseId]);
        $total = 0;
        foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $sid) {
            $total += self::ensureForSubject((int) $sid);
        }

        return $total;
    }

    private static function findTopicAtSlot(string $tbl, int $subjectId, int $slot): ?array
    {
        $st = db()->prepare(
            "SELECT * FROM `{$tbl}` WHERE subject_id=? AND sort_order=? LIMIT 1"
        );
        $st->execute([$subjectId, $slot]);
        $row = $st->fetch();
        if ($row) {
            return $row;
        }

        $slug = 'topic-' . $subjectId . '-' . $slot;
        $st2 = db()->prepare("SELECT * FROM `{$tbl}` WHERE subject_id=? AND slug=? LIMIT 1");
        $st2->execute([$subjectId, $slug]);

        return $st2->fetch() ?: null;
    }

    private static function createPlaceholderTopic(AdminRepository $repo, int $subjectId, int $slot): int
    {
        $titleTe = 'టాపిక్ ' . $slot;
        $slug = 'topic-' . $subjectId . '-' . $slot;

        return $repo->saveTopic([
            'subject_id' => $subjectId,
            'slug' => $slug,
            'title' => 'Topic ' . $slot,
            'title_te' => $titleTe,
            'summary' => '',
            'duration_mins' => 30,
            'sort_order' => $slot,
            'is_free_preview' => $slot <= FreemiumAccess::FREE_PREVIEW_SLOTS ? 1 : 0,
            'is_active' => 1,
        ], null);
    }

    private static function seedNotesPlaceholder(string $tbl, int $topicId, int $slot): void
    {
        $placeholder = "📘 టాపిక్ {$slot}\n\nఈ టాపిక్ కోసం అడ్మిన్ ప్యానెల్ నుండి నిజమైన సిలబస్ నోట్స్ జోడించండి.";
        if (SchemaHelper::contentManagerEnabled()) {
            $sets = ['notes_content=?'];
            $params = [$placeholder];
            if (SchemaHelper::topicNotesEnabledColumn()) {
                $sets[] = 'notes_enabled=1';
            }
            if (SchemaHelper::columnExists($tbl, 'content')) {
                $sets[] = 'content=?';
                $params[] = $placeholder;
            }
            $params[] = $topicId;
            db()->prepare('UPDATE `' . $tbl . '` SET ' . implode(', ', $sets) . ' WHERE id=?')->execute($params);
        } elseif (SchemaHelper::columnExists($tbl, 'content')) {
            db()->prepare("UPDATE `{$tbl}` SET content=? WHERE id=?")->execute([$placeholder, $topicId]);
        }
    }

    private static function seedRevisionExam(AdminRepository $repo, int $topicId, int $slot): void
    {
        if (!SchemaHelper::topicExamSuiteEnabled()) {
            return;
        }
        $existing = $repo->getTopicExamSuite($topicId, null);
        foreach ($existing as $row) {
            if (($row['suite_key'] ?? '') === 'revision' && !empty($row['test_id'])) {
                return;
            }
        }

        $repo->saveTopicExamSuite($topicId, null, [[
            'suite_key' => 'revision',
            'custom_title' => 'Test ' . $slot,
            'custom_title_te' => 'టెస్ట్ ' . $slot,
            'question_count' => 50,
            'total_marks' => 50,
            'is_enabled' => 1,
            'is_required' => 1,
            'sort_order' => 0,
        ]]);
    }
}
