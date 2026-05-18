<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/SchemaHelper.php';
require_once dirname(__DIR__) . '/models/SubscriptionRepository.php';
require_once dirname(__DIR__) . '/models/SubjectScheduleService.php';

/**
 * 2-day free schedule access: schedule day_index 1–2 unlocked for logged-in users;
 * day 3+ requires active sub-course subscription. Uses per-(user,sub_course) demo anchor
 * when unpaid so programme day advances from first visit.
 */
final class SubCourseDemoAccess
{
    public const FREE_SCHEDULE_DAY_INDEX = 2;

    public static function tableReady(): bool
    {
        return SchemaHelper::hasTable('user_sub_course_demo');
    }

    public static function ensureDemoStart(int $userId, int $subCourseId): void
    {
        if ($userId < 1 || $subCourseId < 1 || !self::tableReady()) {
            return;
        }
        $sub = new SubscriptionRepository();
        if ($sub->userHasActivePlanForSubCourse($userId, $subCourseId)) {
            return;
        }
        db()->prepare(
            'INSERT IGNORE INTO user_sub_course_demo (user_id, sub_course_id, started_at) VALUES (?,?,NOW())'
        )->execute([$userId, $subCourseId]);
    }

    /** Purchase anchor if paid, else demo started_at (may be null until ensureDemoStart). */
    public static function scheduleAnchor(int $userId, int $subCourseId): ?string
    {
        if ($userId < 1 || $subCourseId < 1) {
            return null;
        }
        $sub = new SubscriptionRepository();
        if ($sub->userHasActivePlanForSubCourse($userId, $subCourseId)) {
            return $sub->enrollmentAnchorForSubCourse($userId, $subCourseId);
        }
        if (!self::tableReady()) {
            return null;
        }
        $st = db()->prepare(
            'SELECT started_at FROM user_sub_course_demo WHERE user_id = ? AND sub_course_id = ? LIMIT 1'
        );
        $st->execute([$userId, $subCourseId]);
        $v = $st->fetchColumn();

        return is_string($v) && $v !== '' ? $v : null;
    }

    public static function programmeDay(int $userId, int $subCourseId, int $scheduleMaxDays): int
    {
        $anchor = self::scheduleAnchor($userId, $subCourseId);
        if ($anchor === null) {
            return 1;
        }
        $svc = new SubjectScheduleService();

        return $svc->computeEnrollmentDay($anchor, $scheduleMaxDays);
    }

    /** True for logged-in users in first two programme days (anchor) or paid. */
    public static function canAccessProgrammeDay(?int $userId, int $subCourseId, int $programmeDayNumber): bool
    {
        if ($userId === null || $userId < 1 || $subCourseId < 1) {
            return false;
        }
        $sub = new SubscriptionRepository();
        if ($sub->userHasActivePlanForSubCourse($userId, $subCourseId)) {
            return true;
        }

        return $programmeDayNumber <= self::FREE_SCHEDULE_DAY_INDEX;
    }
}
