<?php

declare(strict_types=1);

/**
 * 250-day sequential exam release tied to sub-course enrolment purchased_at.
 */
final class SubjectScheduleService
{
    public function __construct(
        private SubjectTermMatrixRepository $matrix = new SubjectTermMatrixRepository(),
        private SubscriptionRepository $subscriptions = new SubscriptionRepository(),
    ) {
    }

    /**
     * @return array{
     *   programme_access:bool,
     *   enrollment_day:int,
     *   schedule_days:int,
     *   enrollment_started_at:?string,
     *   boxes:list<array<string,mixed>>
     * }
     */
    public function buildSubCourseMatrixView(int $subCourseId, ?int $userId, string $courseSlug = ''): array
    {
        $globals = $this->matrix->globalDefaults();
        $scheduleDays = max(1, (int) ($globals['schedule_days'] ?? SubjectTermMatrixRepository::DEFAULT_SCHEDULE_DAYS));
        $programmeAccess = $userId !== null && $userId > 0 && $subCourseId > 0
            && $this->subscriptions->userHasActivePlanForSubCourse($userId, $subCourseId);

        $enrollmentDay = 1;
        $startedAt = null;
        if ($programmeAccess) {
            $anchor = $this->subscriptions->enrollmentAnchorForSubCourse($userId, $subCourseId);
            if ($anchor !== null) {
                $startedAt = $anchor;
                $enrollmentDay = $this->computeEnrollmentDay($anchor, $scheduleDays);
            }
        }

        $boxes = [];
        foreach ($this->matrix->boxesForSubCourse($subCourseId) as $box) {
            if (!(int) ($box['is_enabled'] ?? 0)) {
                continue;
            }
            $termKey = (string) $box['term_key'];
            $termDays = max(1, (int) ($box['schedule_days'] ?? $scheduleDays));
            $this->matrix->ensureSubCourseScheduleSeeded($subCourseId, $termKey);

            $todaySlot = null;
            $todayHref = null;
            if ($programmeAccess && $enrollmentDay <= $termDays) {
                $slot = $this->matrix->subCourseScheduleSlot($subCourseId, $termKey, $enrollmentDay);
                if ($slot) {
                    $todaySlot = $slot;
                    if (!empty($slot['test_id']) && !empty($slot['test_slug'])) {
                        $q = 'exam.php?test=' . rawurlencode((string) $slot['test_slug']);
                        if ($courseSlug !== '') {
                            $q .= '&course=' . rawurlencode($courseSlug);
                        }
                        $todayHref = $q;
                    }
                }
            }

            $boxes[] = $box + [
                'enrollment_day' => $enrollmentDay,
                'schedule_days' => $termDays,
                'today_slot' => $todaySlot,
                'today_exam_href' => $todayHref,
                'can_take_today' => $programmeAccess && $todaySlot !== null && $todayHref !== null,
                'locked' => !$programmeAccess,
            ];
        }

        return [
            'programme_access' => $programmeAccess,
            'enrollment_day' => $enrollmentDay,
            'schedule_days' => $scheduleDays,
            'enrollment_started_at' => $startedAt,
            'boxes' => $boxes,
        ];
    }

    /** @deprecated Use buildSubCourseMatrixView */
    public function buildSubjectMatrixView(int $subjectId, ?int $userId, int $subCourseId, string $courseSlug = ''): array
    {
        if ($subCourseId > 0 && $this->matrix->subCourseTablesReady()) {
            return $this->buildSubCourseMatrixView($subCourseId, $userId, $courseSlug);
        }

        return $this->buildLegacySubjectMatrixView($subjectId, $userId, $subCourseId, $courseSlug);
    }

    /**
     * @return array{
     *   programme_access:bool,
     *   enrollment_day:int,
     *   schedule_days:int,
     *   enrollment_started_at:?string,
     *   boxes:list<array<string,mixed>>
     * }
     */
    private function buildLegacySubjectMatrixView(int $subjectId, ?int $userId, int $subCourseId, string $courseSlug = ''): array
    {
        $globals = $this->matrix->globalDefaults();
        $scheduleDays = max(1, (int) ($globals['schedule_days'] ?? SubjectTermMatrixRepository::DEFAULT_SCHEDULE_DAYS));
        $programmeAccess = $userId !== null && $userId > 0 && $subCourseId > 0
            && $this->subscriptions->userHasActivePlanForSubCourse($userId, $subCourseId);

        $enrollmentDay = 1;
        $startedAt = null;
        if ($programmeAccess) {
            $anchor = $this->subscriptions->enrollmentAnchorForSubCourse($userId, $subCourseId);
            if ($anchor !== null) {
                $startedAt = $anchor;
                $enrollmentDay = $this->computeEnrollmentDay($anchor, $scheduleDays);
            }
        }

        $boxes = [];
        foreach ($this->matrix->boxesForSubject($subjectId) as $box) {
            if (!(int) ($box['is_enabled'] ?? 0)) {
                continue;
            }
            $termKey = (string) $box['term_key'];
            $termDays = max(1, (int) ($box['schedule_days'] ?? $scheduleDays));
            $this->matrix->ensureScheduleSeeded($subjectId, $termKey);

            $todaySlot = null;
            $todayHref = null;
            if ($programmeAccess && $enrollmentDay <= $termDays) {
                $slot = $this->matrix->scheduleSlot($subjectId, $termKey, $enrollmentDay);
                if ($slot) {
                    $todaySlot = $slot;
                    if (!empty($slot['test_id']) && !empty($slot['test_slug'])) {
                        $q = 'exam.php?test=' . rawurlencode((string) $slot['test_slug']);
                        if ($courseSlug !== '') {
                            $q .= '&course=' . rawurlencode($courseSlug);
                        }
                        $todayHref = $q;
                    }
                }
            }

            $boxes[] = $box + [
                'enrollment_day' => $enrollmentDay,
                'schedule_days' => $termDays,
                'today_slot' => $todaySlot,
                'today_exam_href' => $todayHref,
                'can_take_today' => $programmeAccess && $todaySlot !== null && $todayHref !== null,
                'locked' => !$programmeAccess,
            ];
        }

        return [
            'programme_access' => $programmeAccess,
            'enrollment_day' => $enrollmentDay,
            'schedule_days' => $scheduleDays,
            'enrollment_started_at' => $startedAt,
            'boxes' => $boxes,
        ];
    }

    public function computeEnrollmentDay(string $purchasedAt, int $maxDays): int
    {
        try {
            $start = new DateTimeImmutable(substr($purchasedAt, 0, 19));
            $today = new DateTimeImmutable('today');
            $diff = (int) $start->diff($today)->days + 1;
        } catch (Throwable $e) {
            $diff = 1;
        }

        return max(1, min($maxDays, $diff));
    }
}
