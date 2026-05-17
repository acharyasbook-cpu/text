<?php

declare(strict_types=1);

/**
 * Student-facing daily schedule workspace (multi-subject rows per day).
 */
final class ScheduleTestStudentService
{
    public function __construct(
        private ScheduleTestRepository $repo = new ScheduleTestRepository(),
        private SubjectScheduleService $legacy = new SubjectScheduleService(),
        private SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {
    }

    /**
     * @return array<string,mixed>|null
     */
    public function buildDailyWorkspace(
        int $subCourseId,
        ?int $userId,
        string $courseSlug,
        string $subSlug = '',
        string $termKey = ScheduleTestRepository::TERM_SHORT,
        ?int $dayIndexOverride = null
    ): ?array {
        if (!$this->repo->enabled() || $subCourseId < 1) {
            return null;
        }

        $matrix = $this->legacy->buildSubCourseMatrixView($subCourseId, $userId, $courseSlug);
        $enrollmentDay = $dayIndexOverride ?? (int) ($matrix['enrollment_day'] ?? 1);
        $hasAccess = !empty($matrix['programme_access']);

        $day = $this->repo->dayByIndex($subCourseId, $termKey, $enrollmentDay);
        if (!$day) {
            return null;
        }

        $dayId = (int) $day['id'];
        $rows = $this->repo->rowsForDay($dayId);
        if ($rows === []) {
            return null;
        }

        $completedIds = $userId && $userId > 0
            ? $this->completedRowIds($userId, array_map(static fn (array $r): int => (int) $r['id'], $rows))
            : [];

        $enriched = [];
        foreach ($rows as $row) {
            $rowId = (int) $row['id'];
            $done = in_array($rowId, $completedIds, true);
            $examHref = '';
            if (!empty($row['test_slug']) && $courseSlug !== '') {
                $returnPath = 'sub_course.php?course=' . rawurlencode($courseSlug);
                if ($subSlug !== '') {
                    $returnPath .= '&sub=' . rawurlencode($subSlug);
                }
                $examHref = base_url(
                    'exam.php?course=' . rawurlencode($courseSlug)
                    . '&test=' . rawurlencode((string) $row['test_slug'])
                    . '&schedule_row=' . $rowId
                    . '&return=' . rawurlencode($returnPath)
                );
            }
            $enriched[] = $row + [
                'completed' => $done,
                'exam_href' => $examHref,
                'schedule_row_id' => $rowId,
            ];
        }

        $total = count($enriched);
        $doneCount = count(array_filter($enriched, static fn (array $r): bool => !empty($r['completed'])));
        $pct = $total > 0 ? (int) round($doneCount / $total * 100) : 0;

        return [
            'has_access' => $hasAccess,
            'enrollment_day' => $enrollmentDay,
            'term_key' => $termKey,
            'day' => $day,
            'rows' => $enriched,
            'progress_percent' => $pct,
            'progress_label_te' => $doneCount . ' / ' . $total . ' పరీక్షలు పూర్తి',
            'planner_mode' => $this->repo->getConfig($subCourseId, $termKey)['planner_mode'],
        ];
    }

    public function markRowComplete(int $userId, int $scheduleRowId): void
    {
        if ($userId < 1 || $scheduleRowId < 1 || !SchemaHelper::scheduleTestManagerEnabled()) {
            return;
        }
        db()->prepare(
            'INSERT IGNORE INTO st_schedule_completions (user_id, schedule_row_id) VALUES (?,?)'
        )->execute([$userId, $scheduleRowId]);
    }

    /** @param list<int> $rowIds @return list<int> */
    private function completedRowIds(int $userId, array $rowIds): array
    {
        if ($rowIds === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($rowIds), '?'));
        $st = db()->prepare(
            "SELECT schedule_row_id FROM st_schedule_completions WHERE user_id=? AND schedule_row_id IN ({$in})"
        );
        $st->execute(array_merge([$userId], $rowIds));

        return array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }
}
