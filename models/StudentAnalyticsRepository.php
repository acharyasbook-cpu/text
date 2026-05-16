<?php

declare(strict_types=1);

class StudentAnalyticsRepository
{
    public function __construct(
        private TestRepository $tests = new TestRepository(),
        private SubscriptionRepository $subs = new SubscriptionRepository(),
        private CourseRepository $courses = new CourseRepository(),
    ) {
    }

    /** @return array<string,mixed> */
    public function dashboard(int $userId): array
    {
        return [
            'performance' => $this->tests->performanceSummary($userId),
            'recent_attempts' => $this->tests->recentAttempts($userId, 12),
            'subject_breakdown' => $this->tests->subjectBreakdown($userId),
            'subscriptions' => $this->subs->activePackagesForUser($userId),
            'sub_course_enrollments' => $this->enrolledSubCourses($userId),
            'study_progress' => $this->studyProgressSummary($userId),
            'exam_history_chart' => $this->examHistoryChart($userId),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function enrolledSubCourses(int $userId): array
    {
        $packages = $this->subs->activePackagesForUser($userId);
        $seen = [];
        $out = [];
        foreach ($packages as $p) {
            $key = ($p['course_name'] ?? '') . '|' . ($p['name'] ?? '');
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $p;
        }

        return $out;
    }

    /** @return array{topics_tracked:int,avg_progress:float,completed:int} */
    public function studyProgressSummary(int $userId): array
    {
        if ($userId < 1 || !SchemaHelper::hasTable('topic_study_progress')) {
            return ['topics_tracked' => 0, 'avg_progress' => 0.0, 'completed' => 0];
        }
        $tbl = SchemaHelper::topicsTable();
        $st = db()->prepare(
            "SELECT COUNT(*) AS topics_tracked,
                    ROUND(AVG(tsp.progress_pct), 1) AS avg_progress,
                    SUM(CASE WHEN tsp.progress_pct >= 100 THEN 1 ELSE 0 END) AS completed
             FROM topic_study_progress tsp
             INNER JOIN `{$tbl}` t ON t.id = tsp.topic_id
             WHERE tsp.user_id = ?"
        );
        $st->execute([$userId]);
        $row = $st->fetch() ?: [];

        return [
            'topics_tracked' => (int) ($row['topics_tracked'] ?? 0),
            'avg_progress' => (float) ($row['avg_progress'] ?? 0),
            'completed' => (int) ($row['completed'] ?? 0),
        ];
    }

    /** @return list<array{label:string,score:float,max:float,pct:float}> */
    public function examHistoryChart(int $userId, int $limit = 10): array
    {
        $rows = $this->tests->recentAttempts($userId, $limit);
        $out = [];
        foreach (array_reverse($rows) as $r) {
            $max = (float) ($r['max_score'] ?? 1);
            $score = (float) ($r['score'] ?? 0);
            $out[] = [
                'label' => mb_substr((string) ($r['title'] ?? 'Test'), 0, 18),
                'score' => $score,
                'max' => $max,
                'pct' => $max > 0 ? round($score / $max * 100, 1) : 0.0,
            ];
        }

        return $out;
    }

    public function markTopicRead(int $userId, int $topicId, int $progressPct = 100): void
    {
        if ($userId < 1 || $topicId < 1 || !SchemaHelper::hasTable('topic_study_progress')) {
            return;
        }
        $progressPct = max(0, min(100, $progressPct));
        $st = db()->prepare(
            'INSERT INTO topic_study_progress (user_id, topic_id, progress_pct, notes_opened, last_read_at)
             VALUES (?,?,?,1,NOW())
             ON DUPLICATE KEY UPDATE
               progress_pct = GREATEST(progress_pct, VALUES(progress_pct)),
               notes_opened = 1,
               last_read_at = NOW()'
        );
        $st->execute([$userId, $topicId, $progressPct]);
    }
}
