<?php

declare(strict_types=1);

/**
 * Swap / batch reorder for Content Manager hierarchy nodes.
 */
final class ContentOrderRepository
{
    private const ENTITIES = [
        'main_course',
        'sub_course',
        'subject',
        'topic',
        'sub_topic',
        'exam_suite',
    ];

    public function isValidEntity(string $entity): bool
    {
        return in_array($entity, self::ENTITIES, true);
    }

    /**
     * @param array<string,mixed> $ctx course_id, sub_course_id, subject_id, topic_id
     * @return list<array<string,mixed>>
     */
    public function siblings(string $entity, array $ctx): array
    {
        return match ($entity) {
            'main_course' => $this->repo()->contentManagerMainCoursesForSort(),
            'sub_course' => $this->repo()->contentManagerSubCoursesForSort((int) ($ctx['course_id'] ?? 0)),
            'subject' => $this->repo()->contentManagerSubjects((int) ($ctx['sub_course_id'] ?? 0)),
            'topic' => $this->repo()->contentManagerTopics((int) ($ctx['subject_id'] ?? 0)),
            'sub_topic' => $this->listSubTopics((int) ($ctx['topic_id'] ?? 0)),
            'exam_suite' => $this->listExamSuite((int) ($ctx['topic_id'] ?? 0)),
            default => [],
        };
    }

    /**
     * @param array<string,mixed> $ctx
     * @return array{ok:bool, items:list<array<string,mixed>>}
     */
    public function move(string $entity, int $id, string $direction, array $ctx, ?int $siblingId = null): array
    {
        if (!$this->isValidEntity($entity) || $id < 1) {
            throw new InvalidArgumentException('Invalid entity or id');
        }
        $items = $this->siblings($entity, $ctx);
        if ($items === []) {
            return ['ok' => true, 'items' => []];
        }
        $ids = array_map(static fn ($r) => (int) $r['id'], $items);
        $idx = array_search($id, $ids, true);
        if ($idx === false) {
            throw new InvalidArgumentException('Item not in scope');
        }
        $swapIdx = null;
        if ($siblingId !== null && $siblingId > 0) {
            $swapIdx = array_search($siblingId, $ids, true);
            if ($swapIdx === false) {
                throw new InvalidArgumentException('Sibling not in scope');
            }
        } elseif ($direction === 'up' && $idx > 0) {
            $swapIdx = $idx - 1;
        } elseif ($direction === 'down' && $idx < count($ids) - 1) {
            $swapIdx = $idx + 1;
        } else {
            return ['ok' => true, 'items' => $items];
        }
        $this->swapSortValues($entity, $items[$idx], $items[$swapIdx], $ctx);
        $reordered = $this->siblings($entity, $ctx);
        $this->renumberSequential($entity, $reordered, $ctx);

        return ['ok' => true, 'items' => $this->siblings($entity, $ctx)];
    }

    /**
     * @param list<int> $orderedIds
     * @param array<string,mixed> $ctx
     * @return list<array<string,mixed>>
     */
    public function reorderBatch(string $entity, array $orderedIds, array $ctx): array
    {
        if (!$this->isValidEntity($entity)) {
            throw new InvalidArgumentException('Invalid entity');
        }
        $orderedIds = array_values(array_filter(array_map('intval', $orderedIds), static fn ($i) => $i > 0));
        if ($orderedIds === []) {
            return [];
        }
        $sort = 0;
        foreach ($orderedIds as $itemId) {
            $this->setSortOrder($entity, $itemId, $sort++, $ctx);
        }

        return $this->siblings($entity, $ctx);
    }

    /** @param array<string,mixed> $ctx */
    private function swapSortValues(string $entity, array $a, array $b, array $ctx): void
    {
        $sortA = (int) ($a['sort_order'] ?? 0);
        $sortB = (int) ($b['sort_order'] ?? 0);
        $this->setSortOrder($entity, (int) $a['id'], $sortB, $ctx);
        $this->setSortOrder($entity, (int) $b['id'], $sortA, $ctx);
    }

    /**
     * @param list<array<string,mixed>> $items
     * @param array<string,mixed> $ctx
     */
    private function renumberSequential(string $entity, array $items, array $ctx): void
    {
        $i = 0;
        foreach ($items as $row) {
            $this->setSortOrder($entity, (int) $row['id'], $i++, $ctx);
        }
    }

    /** @param array<string,mixed> $ctx */
    private function setSortOrder(string $entity, int $id, int $sortOrder, array $ctx): void
    {
        switch ($entity) {
            case 'main_course':
                $tbl = SchemaHelper::mainCourseImageTable();
                db()->prepare("UPDATE `{$tbl}` SET sort_order=? WHERE id=?")->execute([$sortOrder, $id]);
                if ($tbl === 'main_courses' && SchemaHelper::hasTable('courses')) {
                    $st = db()->prepare('SELECT slug FROM main_courses WHERE id=?');
                    $st->execute([$id]);
                    $slug = $st->fetchColumn();
                    if ($slug) {
                        db()->prepare('UPDATE courses SET sort_order=? WHERE slug=?')->execute([$sortOrder, $slug]);
                    }
                }
                break;
            case 'sub_course':
                db()->prepare('UPDATE sub_courses SET sort_order=? WHERE id=?')->execute([$sortOrder, $id]);
                break;
            case 'subject':
                $scid = (int) ($ctx['sub_course_id'] ?? 0);
                if ($scid > 0 && SchemaHelper::hierarchyFourTier()) {
                    db()->prepare(
                        'UPDATE sub_course_subjects SET sort_order=? WHERE sub_course_id=? AND subject_id=?'
                    )->execute([$sortOrder, $scid, $id]);
                }
                db()->prepare('UPDATE subjects SET sort_order=? WHERE id=?')->execute([$sortOrder, $id]);
                break;
            case 'topic':
                $tbl = SchemaHelper::topicsTable();
                db()->prepare("UPDATE `{$tbl}` SET sort_order=? WHERE id=?")->execute([$sortOrder, $id]);
                break;
            case 'sub_topic':
                db()->prepare('UPDATE sub_topics SET sort_order=? WHERE id=?')->execute([$sortOrder, $id]);
                break;
            case 'exam_suite':
                db()->prepare('UPDATE topic_exam_suite SET sort_order=? WHERE id=?')->execute([$sortOrder, $id]);
                break;
        }
    }

    /** @return list<array<string,mixed>> */
    private function listSubTopics(int $topicId): array
    {
        if ($topicId < 1 || !SchemaHelper::hasTable('sub_topics')) {
            return [];
        }
        $order = SchemaHelper::sqlOrderBySort('sort_order', 'id');
        $st = db()->prepare(
            "SELECT id, sub_topic_name AS name, sub_topic_name_te AS name_te, sort_order
             FROM sub_topics WHERE topic_id=? ORDER BY {$order}"
        );
        $st->execute([$topicId]);

        return $st->fetchAll();
    }

    /** @return list<array<string,mixed>> */
    private function listExamSuite(int $topicId): array
    {
        if ($topicId < 1 || !SchemaHelper::topicExamSuiteEnabled()) {
            return [];
        }
        $order = SchemaHelper::sqlOrderBySort('sort_order', 'id');
        $st = db()->prepare(
            "SELECT id, suite_key, custom_title AS name, custom_title_te AS name_te, sort_order
             FROM topic_exam_suite WHERE topic_id=? AND (sub_topic_id IS NULL OR sub_topic_id=0)
             ORDER BY {$order}"
        );
        $st->execute([$topicId]);

        return $st->fetchAll();
    }

    private function repo(): AdminRepository
    {
        return new AdminRepository();
    }
}
