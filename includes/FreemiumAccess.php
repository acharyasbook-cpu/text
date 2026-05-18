<?php

declare(strict_types=1);

/**
 * Counter-based freemium: first two topics (by sort_order) are free preview; 3–20 require paid access.
 */
final class FreemiumAccess
{
    public const FREE_PREVIEW_SLOTS = 2;

    /**
     * @param list<array<string,mixed>> $topics
     * @return array<int,int> topic_id => 1-based rank
     */
    public static function topicRanksBySort(array $topics): array
    {
        $sorted = $topics;
        usort($sorted, static function (array $a, array $b): int {
            $so = ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
            if ($so !== 0) {
                return $so;
            }

            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        $ranks = [];
        foreach ($sorted as $i => $row) {
            $id = (int) ($row['id'] ?? 0);
            if ($id > 0) {
                $ranks[$id] = $i + 1;
            }
        }

        return $ranks;
    }

    public static function rankForTopic(array $topicRanks, int $topicId): int
    {
        return $topicRanks[$topicId] ?? 99;
    }

    public static function isTopicUnlocked(bool $programmeHasAccess, int $freemiumRank, array $topic): bool
    {
        if ($programmeHasAccess) {
            return true;
        }
        if (!empty($topic['is_free_preview'])) {
            return true;
        }

        return $freemiumRank <= self::FREE_PREVIEW_SLOTS;
    }

    public static function canViewTopicNotes(
        ?array $user,
        array $topic,
        bool $programmeHasAccess,
        int $freemiumRank
    ): bool {
        if (!$user) {
            return false;
        }

        return self::isTopicUnlocked($programmeHasAccess, $freemiumRank, $topic);
    }

    public static function canDownloadNotes(
        ?array $user,
        array $topic,
        bool $programmeHasAccess,
        int $freemiumRank
    ): bool {
        if (!$user || !$programmeHasAccess) {
            return false;
        }
        if (!self::isTopicUnlocked($programmeHasAccess, $freemiumRank, $topic)) {
            return false;
        }
        if (!SchemaHelper::topicCanDownloadEnabled()) {
            return false;
        }

        return !empty($topic['can_download']);
    }

    public static function canAccessTest(
        int $userId,
        array $test,
        bool $programmeHasAccess,
        ?array $topic,
        int $freemiumRank
    ): bool {
        if ($programmeHasAccess) {
            return true;
        }
        if ($topic === null) {
            return !empty($test['package_id']) ? false : true;
        }

        return self::isTopicUnlocked(false, $freemiumRank, $topic);
    }

    public static function resolveTopicForTest(int $testId): ?array
    {
        if ($testId < 1) {
            return null;
        }
        $st = db()->prepare('SELECT * FROM tests WHERE id=? LIMIT 1');
        $st->execute([$testId]);
        $test = $st->fetch();
        if (!$test) {
            return null;
        }
        $topicId = (int) ($test['topic_id'] ?? 0);
        if ($topicId < 1 && SchemaHelper::topicExamSuiteEnabled()) {
            $st2 = db()->prepare(
                'SELECT topic_id FROM topic_exam_suite WHERE test_id=? ORDER BY id ASC LIMIT 1'
            );
            $st2->execute([$testId]);
            $topicId = (int) $st2->fetchColumn();
        }
        if ($topicId < 1) {
            return null;
        }
        $tbl = SchemaHelper::topicsTable();
        $st3 = db()->prepare("SELECT * FROM `{$tbl}` WHERE id=? LIMIT 1");
        $st3->execute([$topicId]);

        $row = $st3->fetch();

        return $row ?: null;
    }

    public static function programmeAccessForSubject(array $subject, int $userId): bool
    {
        if ($userId < 1 || empty($subject['sub_course_slug'])) {
            return false;
        }
        $courseRepo = new CourseRepository();
        $scRow = $courseRepo->findSubCourseBySlugs(
            (string) ($subject['course_slug'] ?? ''),
            (string) $subject['sub_course_slug']
        );
        if (!$scRow) {
            return false;
        }

        return (new SubscriptionRepository())->userHasActivePlanForSubCourse(
            $userId,
            (int) $scRow['id']
        );
    }

    public static function assertTopicNotesAccess(
        array $user,
        array $subject,
        array $topic,
        CourseRepository $courseRepo
    ): void {
        $topics = $courseRepo->topicsForSubject((int) $subject['id']);
        $ranks = self::topicRanksBySort($topics);
        $rank = self::rankForTopic($ranks, (int) $topic['id']);
        $paid = self::programmeAccessForSubject($subject, (int) $user['id']);
        if (!self::canViewTopicNotes($user, $topic, $paid, $rank)) {
            require_once dirname(__DIR__) . '/includes/public_site_helpers.php';
            flash('error', 'ఈ టాపిక్ యాక్సెస్ కోసం సబ్-కోర్స్ ప్లాన్ అవసరం.');
            redirect(ltrim(
                public_subject_workspace_url(
                    (string) ($subject['course_slug'] ?? ''),
                    !empty($subject['sub_course_slug']) ? (string) $subject['sub_course_slug'] : null,
                    (string) ($subject['slug'] ?? ''),
                    'notes'
                ),
                '/'
            ));
        }
    }

    public static function assertTestAccess(array $user, array $test, array $subject, ?int $scheduleRowId = null): void
    {
        $subRepo = new SubscriptionRepository();
        $uid = (int) $user['id'];
        $testId = (int) $test['id'];
        $schedId = ($scheduleRowId !== null && $scheduleRowId > 0) ? $scheduleRowId : null;

        if (!empty($test['package_id']) && !$subRepo->userMayAccessScheduledTest($uid, $testId, $schedId)) {
            flash('error', 'You need an active package to access this test.');
            redirect('exams.php');
        }

        $topic = self::resolveTopicForTest($testId);
        $courseRepo = new CourseRepository();
        $topics = $topic
            ? $courseRepo->topicsForSubject((int) $topic['subject_id'])
            : [];
        $ranks = self::topicRanksBySort($topics);
        $rank = $topic ? self::rankForTopic($ranks, (int) $topic['id']) : 99;
        $paid = self::programmeAccessForSubject($subject, $uid);
        $scheduleWindow = $schedId !== null && $subRepo->userMayAccessScheduledTest($uid, $testId, $schedId);
        $programmeHasAccess = $paid || $scheduleWindow;

        if (!self::canAccessTest($uid, $test, $programmeHasAccess, $topic, $rank)) {
            flash('error', 'ఈ టెస్ట్ యాక్సెస్ కోసం సబ్-కోర్స్ ప్లాన్ అవసరం.');
            $return = trim((string) ($_GET['return'] ?? ''));
            if ($return !== '' && !preg_match('#^https?://#i', $return)) {
                redirect(ltrim($return, '/'));
            }
            redirect('exams.php');
        }
    }
}
