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
        $subCourseMetrics = $this->subCourseMetricsDashboard($from, $to);

        return [
            'total_students' => $totalStudents,
            'paid_students' => $paidStudents,
            'free_students' => $freeStudents,
            'total_exam_attempts' => $totalAttempts,
            'revenue_total_inr' => $revenue['total_inr'],
            'revenue_by_plan' => $revenue['by_plan'],
            'course_popularity' => $popularity,
            'sub_course_metrics' => $subCourseMetrics,
        ];
    }

    /**
     * Per sub-course: registrations (demo + plan purchases in range), plan mix, practice active vs idle, revenue.
     *
     * @return list<array<string,mixed>>
     */
    public function subCourseMetricsDashboard(?string $from, ?string $to): array
    {
        if (!SchemaHelper::hasTable('sub_courses')) {
            return [];
        }

        $hasDemo = SchemaHelper::hasTable('user_sub_course_demo');
        $hasPlans = SchemaHelper::hasTable('sub_course_plans')
            && SchemaHelper::columnExists('user_subscriptions', 'sub_course_plan_id');
        $hasScSubjects = SchemaHelper::hasTable('sub_course_subjects');
        $payDateCol = SchemaHelper::hasTable('payments')
            ? (SchemaHelper::columnExists('payments', 'paid_at') ? 'pay.paid_at' : 'pay.created_at')
            : null;
        $hasPayPlan = SchemaHelper::hasTable('payments')
            && SchemaHelper::columnExists('payments', 'sub_course_plan_id')
            && SchemaHelper::hasTable('sub_course_plans');

        [$demoWhere, $demoParams] = $this->sqlDateRangePredicates('d.started_at', $from, $to);
        [$subWhere, $subParams] = $this->sqlDateRangePredicates('us.purchased_at', $from, $to);
        [$attWhere, $attParams] = $this->sqlDateRangePredicates('ta.submitted_at', $from, $to);
        [$payWhere, $payParams] = $payDateCol !== null
            ? $this->sqlDateRangePredicates($payDateCol, $from, $to)
            : ['1', []];

        $regBySc = [];
        if ($hasDemo || $hasPlans) {
            $parts = [];
            $params = [];
            if ($hasDemo) {
                $parts[] = "SELECT d.sub_course_id AS sc_id, d.user_id AS user_id FROM user_sub_course_demo d WHERE {$demoWhere}";
                $params = array_merge($params, $demoParams);
            }
            if ($hasPlans) {
                $parts[] = "SELECT sp.sub_course_id AS sc_id, us.user_id AS user_id
                    FROM user_subscriptions us
                    INNER JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id
                    WHERE {$subWhere}";
                $params = array_merge($params, $subParams);
            }
            $union = implode(' UNION ', $parts);
            $sql = "SELECT sc_id, COUNT(DISTINCT user_id) AS n FROM ({$union}) reg GROUP BY sc_id";
            $st = db()->prepare($sql);
            $st->execute($params);
            foreach ($st->fetchAll() ?: [] as $r) {
                $regBySc[(int) $r['sc_id']] = (int) $r['n'];
            }
        }

        $planBySc = [];
        if ($hasPlans) {
            $st = db()->prepare(
                "SELECT sp.sub_course_id AS sc_id, sp.plan_code AS plan_code, COUNT(*) AS n
                 FROM user_subscriptions us
                 INNER JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id
                 WHERE {$subWhere}
                 GROUP BY sp.sub_course_id, sp.plan_code"
            );
            $st->execute($subParams);
            foreach ($st->fetchAll() ?: [] as $r) {
                $sid = (int) $r['sc_id'];
                if (!isset($planBySc[$sid])) {
                    $planBySc[$sid] = ['6_months' => 0, '1_year' => 0, 'until_exam' => 0, 'other' => 0];
                }
                $code = (string) ($r['plan_code'] ?? '');
                $bucket = in_array($code, ['6_months', '1_year', 'until_exam'], true) ? $code : 'other';
                $planBySc[$sid][$bucket] += (int) $r['n'];
            }
        }

        $activeBySc = [];
        if ($hasScSubjects) {
            $st = db()->prepare(
                "SELECT scs.sub_course_id AS sc_id, COUNT(DISTINCT ta.user_id) AS n
                 FROM test_attempts ta
                 INNER JOIN tests t ON t.id = ta.test_id
                 INNER JOIN subjects s ON s.id = t.subject_id
                 INNER JOIN sub_course_subjects scs ON scs.subject_id = s.id
                 WHERE ta.status = 'submitted' AND {$attWhere}
                 GROUP BY scs.sub_course_id"
            );
            $st->execute($attParams);
            foreach ($st->fetchAll() ?: [] as $r) {
                $activeBySc[(int) $r['sc_id']] = (int) $r['n'];
            }
        }

        $revBySc = [];
        if ($hasPayPlan) {
            $st = db()->prepare(
                "SELECT sp.sub_course_id AS sc_id, COALESCE(SUM(pay.amount_inr), 0) AS rev
                 FROM payments pay
                 INNER JOIN sub_course_plans sp ON sp.id = pay.sub_course_plan_id
                 WHERE pay.status = 'completed' AND {$payWhere}
                 GROUP BY sp.sub_course_id"
            );
            $st->execute($payParams);
            foreach ($st->fetchAll() ?: [] as $r) {
                $revBySc[(int) $r['sc_id']] = (float) $r['rev'];
            }
        }

        $st = db()->query(
            'SELECT sc.id, sc.name, sc.name_te, c.name AS course_name
             FROM sub_courses sc
             INNER JOIN courses c ON c.id = sc.course_id
             ORDER BY sc.name ASC'
        );
        $courses = $st->fetchAll() ?: [];

        $rows = [];
        foreach ($courses as $row) {
            $id = (int) $row['id'];
            $reg = $regBySc[$id] ?? 0;
            $active = $activeBySc[$id] ?? 0;
            $idle = max(0, $reg - $active);
            $rev = $revBySc[$id] ?? 0.0;
            $plans = $planBySc[$id] ?? ['6_months' => 0, '1_year' => 0, 'until_exam' => 0, 'other' => 0];
            $rows[] = [
                'sub_course_id' => $id,
                'name' => $row['name'],
                'name_te' => $row['name_te'],
                'course_name' => $row['course_name'],
                'registrations' => $reg,
                'plan_6_months' => (int) ($plans['6_months'] ?? 0),
                'plan_1_year' => (int) ($plans['1_year'] ?? 0),
                'plan_until_exam' => (int) ($plans['until_exam'] ?? 0),
                'plan_other' => (int) ($plans['other'] ?? 0),
                'active_practice_students' => $active,
                'idle_students' => $idle,
                'revenue_inr' => $rev,
                'trending_score' => $reg + $active * 2 + (int) round($rev / 500.0),
            ];
        }

        $maxRev = 0.0;
        foreach ($rows as $r) {
            $maxRev = max($maxRev, (float) ($r['revenue_inr'] ?? 0));
        }
        foreach ($rows as &$r) {
            $r['is_top_revenue'] = $maxRev > 0 && (float) $r['revenue_inr'] >= $maxRev - 0.001;
        }
        unset($r);

        usort($rows, static function (array $a, array $b): int {
            return ((int) ($b['trending_score'] ?? 0)) <=> ((int) ($a['trending_score'] ?? 0));
        });

        return $rows;
    }

    /**
     * @return array{0:string,1:list<mixed>}
     */
    private function sqlDateRangePredicates(string $column, ?string $from, ?string $to): array
    {
        $parts = [];
        $params = [];
        if ($from !== null && $from !== '') {
            $parts[] = "DATE({$column}) >= ?";
            $params[] = $from;
        }
        if ($to !== null && $to !== '') {
            $parts[] = "DATE({$column}) <= ?";
            $params[] = $to;
        }

        return [$parts === [] ? '1' : implode(' AND ', $parts), $params];
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
        $loginCol = SchemaHelper::columnExists('users', 'last_login_at') ? 'u.last_login_at' : 'NULL';
        $enrollSql = $this->activeEnrollmentsSql();
        $deviceSql = $this->deviceCountSql();
        $subSql = $this->activeSubscriptionSql();

        $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at,
                       {$loginCol} AS last_login_at,
                       {$lastActiveSql} AS last_active_at,
                       {$paidFlagSql} AS is_paid,
                       {$enrollSql} AS active_enrollments,
                       {$subSql['plan_code']} AS active_plan_code,
                       {$subSql['plan_label']} AS active_plan_label,
                       {$subSql['purchased_at']} AS subscription_started_at,
                       {$subSql['expires_at']} AS subscription_expires_at,
                       (SELECT COUNT(*) FROM test_attempts ta WHERE ta.user_id=u.id AND ta.status='submitted') AS exams_taken,
                       (SELECT ROUND(AVG(ta.score/NULLIF(ta.max_score,0)*100),1) FROM test_attempts ta
                        WHERE ta.user_id=u.id AND ta.status='submitted' AND ta.max_score>0) AS avg_score_pct,
                       {$deviceSql} AS device_count
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
        $loginCol = SchemaHelper::columnExists('users', 'last_login_at') ? 'u.last_login_at,' : '';
        $st = db()->prepare(
            "SELECT u.id, u.name, u.email, u.phone, u.created_at, {$loginCol} {$lastActiveSql} AS last_active_at
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

    private function activeEnrollmentsSql(): string
    {
        if (!SchemaHelper::hasTable('user_subscriptions')) {
            return '0';
        }

        return "(SELECT COUNT(*) FROM user_subscriptions us WHERE us.user_id=u.id AND us.status='active')";
    }

    private function deviceCountSql(): string
    {
        if (!SchemaHelper::hasTable('user_login_events')) {
            return '1';
        }

        return "(SELECT COUNT(DISTINCT COALESCE(ule.user_agent_hash, ule.id))
                 FROM user_login_events ule
                 WHERE ule.user_id=u.id AND ule.logged_in_at >= DATE_SUB(NOW(), INTERVAL 90 DAY))";
    }

    /** @return array{plan_code:string,plan_label:string,purchased_at:string,expires_at:string} */
    private function activeSubscriptionSql(): array
    {
        if (!SchemaHelper::hasTable('user_subscriptions') || !SchemaHelper::hasTable('sub_course_plans')) {
            return [
                'plan_code' => 'NULL',
                'plan_label' => 'NULL',
                'purchased_at' => 'NULL',
                'expires_at' => 'NULL',
            ];
        }

        $teCol = SchemaHelper::columnExists('sub_course_plans', 'label_te')
            ? "COALESCE(NULLIF(sp.label_te,''), sp.label)"
            : 'sp.label';
        $order = 'ORDER BY us.expires_at IS NULL DESC, us.expires_at DESC, us.purchased_at DESC LIMIT 1';
        $join = 'FROM user_subscriptions us INNER JOIN sub_course_plans sp ON sp.id = us.sub_course_plan_id WHERE us.user_id = u.id AND us.status = \'active\'';

        return [
            'plan_code' => "(SELECT sp.plan_code {$join} {$order})",
            'plan_label' => "(SELECT {$teCol} {$join} {$order})",
            'purchased_at' => "(SELECT us.purchased_at {$join} {$order})",
            'expires_at' => "(SELECT us.expires_at {$join} {$order})",
        ];
    }

    public static function formatPlanCode(?string $code): string
    {
        return match ((string) $code) {
            '6_months' => '6 Months',
            '1_year' => '1 Year',
            'until_exam' => 'Up to Exam',
            default => $code !== '' ? $code : '—',
        };
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
