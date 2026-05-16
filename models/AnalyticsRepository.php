<?php

declare(strict_types=1);

final class AnalyticsRepository
{
    private const PLAN_LABELS = [
        '6_months' => '6 Months Plan',
        '1_year' => '1 Year Plan',
        'until_exam' => 'Until / After Exam Plan',
    ];

    /** @return array<string,mixed> */
    public function platformOverview(?string $from = null, ?string $to = null): array
    {
        $regFilter = $this->dateFilterSql('u.created_at', $from, $to);
        $payFilter = $this->dateFilterSql('pay.paid_at', $from, $to, 'pay.created_at');

        $totalStudents = (int) db()->query(
            "SELECT COUNT(*) FROM users u WHERE u.role='student' {$regFilter['sql']}"
        )->fetchColumn();

        $paidStudents = 0;
        if (SchemaHelper::hasTable('payments')) {
            $paidStudents = (int) db()->query(
                "SELECT COUNT(DISTINCT pay.user_id) FROM payments pay
                 WHERE pay.status='completed' {$payFilter['sql']}"
            )->fetchColumn();
        } elseif (SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            $paidStudents = (int) db()->query(
                "SELECT COUNT(DISTINCT us.user_id) FROM user_subscriptions us
                 WHERE us.sub_course_plan_id IS NOT NULL"
            )->fetchColumn();
        }

        $freeStudents = max(0, $totalStudents - $paidStudents);

        $attemptFilter = $this->dateFilterSql('ta.submitted_at', $from, $to);
        $totalAttempts = (int) db()->query(
            "SELECT COUNT(*) FROM test_attempts ta WHERE ta.status='submitted' {$attemptFilter['sql']}"
        )->fetchColumn();

        $revenue = $this->revenueByPlan($from, $to);
        $popularity = $this->coursePopularityRanking($from, $to);

        return [
            'total_students' => $totalStudents,
            'paid_students' => $paidStudents,
            'free_students' => $freeStudents,
            'total_exam_attempts' => $totalAttempts,
            'revenue_total_inr' => $revenue['total_inr'],
            'revenue_by_plan' => $revenue['by_plan'],
            'course_popularity' => $popularity,
        ];
    }

    /**
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public function searchStudents(
        ?string $query,
        ?string $from,
        ?string $to,
        int $limit = 50,
        int $offset = 0,
    ): array {
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $regFilter = $this->dateFilterSql('u.created_at', $from, $to);

        $where = ["u.role = 'student'"];
        $params = [];
        if ($regFilter['sql'] !== '') {
            $where[] = ltrim($regFilter['sql'], 'AND ');
            $params = array_merge($params, $regFilter['params']);
        }

        $q = trim((string) $query);
        if ($q !== '') {
            $where[] = '(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $whereSql = implode(' AND ', $where);

        $countSt = db()->prepare("SELECT COUNT(*) FROM users u WHERE {$whereSql}");
        $countSt->execute($params);
        $total = (int) $countSt->fetchColumn();

        $lastActiveSql = $this->lastActiveSql();
        $paidFlagSql = $this->isPaidStudentSql();

        $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at,
                       {$lastActiveSql} AS last_active_at,
                       {$paidFlagSql} AS is_paid,
                       (SELECT COUNT(*) FROM test_attempts ta WHERE ta.user_id=u.id AND ta.status='submitted') AS exams_taken,
                       (SELECT ROUND(AVG(ta.score/NULLIF(ta.max_score,0)*100),1) FROM test_attempts ta
                        WHERE ta.user_id=u.id AND ta.status='submitted' AND ta.max_score>0) AS avg_score_pct
                FROM users u
                WHERE {$whereSql}
                ORDER BY last_active_at DESC, u.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        $st = db()->prepare($sql);
        $st->execute($params);

        return ['items' => $st->fetchAll() ?: [], 'total' => $total];
    }

    /** @return array<string,mixed>|null */
    public function studentProfile(int $userId): ?array
    {
        if ($userId < 1) {
            return null;
        }

        $lastActiveSql = $this->lastActiveSql();
        $st = db()->prepare(
            "SELECT u.id, u.name, u.email, u.phone, u.created_at, {$lastActiveSql} AS last_active_at
             FROM users u WHERE u.id=? AND u.role='student' LIMIT 1"
        );
        $st->execute([$userId]);
        $user = $st->fetch();
        if (!$user) {
            return null;
        }

        $testRepo = new TestRepository();
        $performance = $testRepo->performanceSummary($userId);
        $subjectRows = $testRepo->subjectBreakdown($userId);

        return [
            'user' => $user,
            'subscriptions' => $this->studentCommercialMatrix($userId),
            'practice' => $this->studentPracticeEngine($userId),
            'performance' => $performance,
            'subjects' => $subjectRows,
            'weak_subjects' => $this->weakSubjects($subjectRows),
            'weak_topics' => $this->weakTopics($userId),
            'recent_attempts' => $testRepo->recentAttempts($userId, 8),
            'study_stats' => $this->studyTimeStats($userId),
        ];
    }

    /** @return list<array<string,mixed>> */
    public function exportStudentRows(?string $query, ?string $from, ?string $to): array
    {
        $all = [];
        $offset = 0;
        do {
            $batch = $this->searchStudents($query, $from, $to, 500, $offset);
            foreach ($batch['items'] as $row) {
                $all[] = $row;
            }
            $offset += 500;
        } while (count($batch['items']) === 500);

        return $all;
    }

    /** @return list<array<string,mixed>> */
    private function coursePopularityRanking(?string $from, ?string $to): array
    {
        if (!SchemaHelper::hasTable('sub_courses')) {
            return [];
        }

        $filter = $this->dateFilterSql('us.purchased_at', $from, $to);
        $planJoin = '';
        if (SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id') && SchemaHelper::hasTable('sub_course_plans')) {
            $planJoin = 'INNER JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id';
        } elseif (SchemaHelper::hasTable('sub_course_packages')) {
            $planJoin = 'LEFT JOIN sub_course_packages p ON p.id = us.package_id LEFT JOIN sub_courses sc ON sc.course_id = p.course_id';
        }

        if (SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            $sql = "SELECT sc.id, sc.name, sc.name_te, c.name AS course_name,
                           COUNT(DISTINCT us.user_id) AS enrollments
                    FROM user_subscriptions us
                    {$planJoin}
                    INNER JOIN sub_courses sc ON sc.id = sp.sub_course_id
                    INNER JOIN courses c ON c.id = sc.course_id
                    WHERE us.status IN ('active','expired') {$filter['sql']}
                    GROUP BY sc.id, sc.name, sc.name_te, c.name
                    ORDER BY enrollments DESC, sc.name ASC
                    LIMIT 15";
        } else {
            return [];
        }

        $st = db()->prepare($sql);
        $st->execute($filter['params']);

        return $st->fetchAll() ?: [];
    }

    /** @return array{total_inr:float,by_plan:list<array<string,mixed>>} */
    private function revenueByPlan(?string $from, ?string $to): array
    {
        $byPlan = [];
        foreach (self::PLAN_LABELS as $code => $label) {
            $byPlan[] = ['plan_code' => $code, 'label' => $label, 'amount_inr' => 0.0, 'transactions' => 0];
        }

        if (!SchemaHelper::hasTable('payments')) {
            return ['total_inr' => 0.0, 'by_plan' => $byPlan];
        }

        $dateCol = SchemaHelper::columnExists('payments', 'paid_at') ? 'pay.paid_at' : 'pay.created_at';
        $filter = $this->dateFilterSql($dateCol, $from, $to);
        $total = (float) db()->query(
            "SELECT COALESCE(SUM(pay.amount_inr),0) FROM payments pay
             WHERE pay.status='completed' {$filter['sql']}"
        )->fetchColumn();

        if (SchemaHelper::columnExists('payments', 'sub_course_plan_id') && SchemaHelper::hasTable('sub_course_plans')) {
            $st = db()->prepare(
                "SELECT sp.plan_code,
                        COALESCE(SUM(pay.amount_inr),0) AS amount_inr,
                        COUNT(*) AS transactions
                 FROM payments pay
                 LEFT JOIN sub_course_plans sp ON sp.id = pay.sub_course_plan_id
                 WHERE pay.status='completed' {$filter['sql']}
                 GROUP BY sp.plan_code"
            );
            $st->execute($filter['params']);
            $rows = $st->fetchAll() ?: [];
            $indexed = [];
            foreach ($rows as $r) {
                $code = (string) ($r['plan_code'] ?? 'other');
                if ($code === '') {
                    $code = 'other';
                }
                $indexed[$code] = $r;
            }
            $byPlan = [];
            foreach (self::PLAN_LABELS as $code => $label) {
                $row = $indexed[$code] ?? null;
                $byPlan[] = [
                    'plan_code' => $code,
                    'label' => $label,
                    'amount_inr' => (float) ($row['amount_inr'] ?? 0),
                    'transactions' => (int) ($row['transactions'] ?? 0),
                ];
            }
            if (isset($indexed['other'])) {
                $byPlan[] = [
                    'plan_code' => 'other',
                    'label' => 'Other',
                    'amount_inr' => (float) $indexed['other']['amount_inr'],
                    'transactions' => (int) $indexed['other']['transactions'],
                ];
            }
        }

        return ['total_inr' => $total, 'by_plan' => $byPlan];
    }

    /** @return list<array<string,mixed>> */
    private function studentCommercialMatrix(int $userId): array
    {
        if (!SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id') || !SchemaHelper::hasTable('sub_course_plans')) {
            return $this->legacySubscriptions($userId);
        }

        $st = db()->prepare(
            "SELECT us.id, us.status, us.purchased_at, us.expires_at,
                    sp.plan_code, sp.label AS plan_label, sp.duration_months,
                    sc.id AS sub_course_id, sc.name AS sub_course_name, sc.name_te AS sub_course_name_te,
                    c.name AS course_name
             FROM user_subscriptions us
             INNER JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id
             INNER JOIN sub_courses sc ON sc.id = sp.sub_course_id
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE us.user_id = ?
             ORDER BY us.purchased_at DESC"
        );
        $st->execute([$userId]);
        $rows = $st->fetchAll() ?: [];

        foreach ($rows as &$row) {
            $code = (string) ($row['plan_code'] ?? '');
            $row['plan_display'] = self::PLAN_LABELS[$code] ?? ($row['plan_label'] ?? $code);
            $row['is_active'] = ($row['status'] === 'active')
                && (empty($row['expires_at']) || strtotime((string) $row['expires_at']) > time());
        }
        unset($row);

        return $rows;
    }

    /** @return list<array<string,mixed>> */
    private function legacySubscriptions(int $userId): array
    {
        $st = db()->prepare(
            'SELECT us.*, p.name AS package_name FROM user_subscriptions us
             LEFT JOIN sub_course_packages p ON p.id = us.package_id WHERE us.user_id=?'
        );
        $st->execute([$userId]);

        return $st->fetchAll() ?: [];
    }

    /** @return array<string,mixed> */
    private function studentPracticeEngine(int $userId): array
    {
        $scheduleDays = 250;
        if (SchemaHelper::hasTable('sub_course_term_boxes')) {
            $mx = db()->query('SELECT MAX(schedule_days) FROM sub_course_term_boxes')->fetchColumn();
            if ($mx) {
                $scheduleDays = max(100, (int) $mx);
            }
        }
        try {
            $globals = (new SubjectTermMatrixRepository())->globalDefaults();
            $scheduleDays = max(100, min(365, (int) ($globals['schedule_days'] ?? $scheduleDays)));
        } catch (Throwable $e) {
            // keep default
        }

        $subRepo = new SubscriptionRepository();
        $primarySubCourseId = 0;
        $anchor = null;
        if (SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            $st = db()->prepare(
                'SELECT sp.sub_course_id, us.purchased_at FROM user_subscriptions us
                 INNER JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id
                 WHERE us.user_id=? AND us.status="active"
                 ORDER BY us.purchased_at ASC LIMIT 1'
            );
            $st->execute([$userId]);
            $row = $st->fetch();
            if ($row) {
                $primarySubCourseId = (int) $row['sub_course_id'];
                $anchor = (string) $row['purchased_at'];
            }
        }

        $enrollmentDay = 1;
        if ($anchor) {
            $start = new DateTimeImmutable($anchor);
            $now = new DateTimeImmutable('today');
            $enrollmentDay = max(1, (int) $start->diff($now)->days + 1);
        }

        $frameworkLabel = $scheduleDays >= 200 ? '250 Days Sequential' : '100 Days Intensive';
        if ($scheduleDays < 200) {
            $frameworkLabel = '100 Days Intensive';
        }

        return [
            'schedule_days' => $scheduleDays,
            'framework_label' => $frameworkLabel,
            'enrollment_day' => min($enrollmentDay, $scheduleDays),
            'progress_label' => 'Day ' . min($enrollmentDay, $scheduleDays) . ' of ' . $scheduleDays,
            'progress_percent' => $scheduleDays > 0 ? round(min($enrollmentDay, $scheduleDays) / $scheduleDays * 100, 1) : 0,
            'primary_sub_course_id' => $primarySubCourseId,
        ];
    }

    /** @return array<string,mixed> */
    private function studyTimeStats(int $userId): array
    {
        $st = db()->prepare(
            "SELECT
                COALESCE(SUM(ta.time_taken_secs),0) AS total_secs,
                COALESCE(SUM(CASE WHEN DATE(ta.submitted_at)=CURDATE() THEN ta.time_taken_secs ELSE 0 END),0) AS today_secs,
                COUNT(DISTINCT DATE(ta.submitted_at)) AS active_days
             FROM test_attempts ta
             WHERE ta.user_id=? AND ta.status='submitted'"
        );
        $st->execute([$userId]);
        $row = $st->fetch() ?: ['total_secs' => 0, 'today_secs' => 0, 'active_days' => 0];

        return [
            'total_hours' => round(((int) $row['total_secs']) / 3600, 1),
            'today_minutes' => round(((int) $row['today_secs']) / 60, 0),
            'active_days' => (int) $row['active_days'],
        ];
    }

    /** @param list<array<string,mixed>> $subjects */
    /** @return list<array<string,mixed>> */
    private function weakSubjects(array $subjects): array
    {
        $weak = array_filter($subjects, static function ($s) {
            return isset($s['avg_percent']) && (float) $s['avg_percent'] < 55 && (int) ($s['attempts'] ?? 0) >= 2;
        });
        usort($weak, static fn ($a, $b) => ((float) $a['avg_percent']) <=> ((float) $b['avg_percent']));

        return array_values(array_slice($weak, 0, 5));
    }

    /** @return list<array<string,mixed>> */
    private function weakTopics(int $userId): array
    {
        $st = db()->prepare(
            "SELECT COALESCE(NULLIF(TRIM(tq.topic_tag),''), t.title, 'General') AS topic_name,
                    ROUND(AVG(CASE WHEN taa.is_correct=1 THEN 100 ELSE 0 END), 1) AS accuracy_pct,
                    COUNT(*) AS answers
             FROM test_attempt_answers taa
             INNER JOIN test_attempts ta ON ta.id = taa.attempt_id
             INNER JOIN test_questions tq ON tq.id = taa.question_id
             INNER JOIN tests t ON t.id = ta.test_id
             WHERE ta.user_id=? AND ta.status='submitted' AND tq.topic_tag IS NOT NULL AND tq.topic_tag != ''
             GROUP BY topic_name
             HAVING answers >= 5
             ORDER BY accuracy_pct ASC
             LIMIT 8"
        );
        $st->execute([$userId]);
        $rows = $st->fetchAll() ?: [];

        return array_values(array_filter($rows, static fn ($r) => (float) ($r['accuracy_pct'] ?? 100) < 55));
    }

    private function lastActiveSql(): string
    {
        if (SchemaHelper::columnExists('users', 'last_login_at')) {
            return 'GREATEST(COALESCE(u.last_login_at, u.created_at), COALESCE((SELECT MAX(ta.submitted_at) FROM test_attempts ta WHERE ta.user_id=u.id), u.created_at))';
        }

        return 'COALESCE((SELECT MAX(ta.submitted_at) FROM test_attempts ta WHERE ta.user_id=u.id), u.updated_at, u.created_at)';
    }

    private function isPaidStudentSql(): string
    {
        if (SchemaHelper::hasTable('payments')) {
            return "EXISTS (SELECT 1 FROM payments pay WHERE pay.user_id=u.id AND pay.status='completed')";
        }

        if (SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id')) {
            return 'EXISTS (SELECT 1 FROM user_subscriptions us WHERE us.user_id=u.id AND us.sub_course_plan_id IS NOT NULL)';
        }

        return '0';
    }

    /**
     * @return array{sql:string,params:list<mixed>}
     */
    private function dateFilterSql(string $column, ?string $from, ?string $to, ?string $fallbackColumn = null): array
    {
        $params = [];
        $parts = [];
        if ($from !== null && $from !== '') {
            $parts[] = "DATE({$column}) >= ?";
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $parts[] = "DATE({$column}) <= ?";
            $params[] = $to;
        }
        if ($parts === [] && $fallbackColumn !== null) {
            return $this->dateFilterSql($fallbackColumn, $from, $to);
        }

        return [
            'sql' => $parts === [] ? '' : ' AND ' . implode(' AND ', $parts),
            'params' => $params,
        ];
    }

    public static function planLabel(string $code): string
    {
        return self::PLAN_LABELS[$code] ?? $code;
    }
}
