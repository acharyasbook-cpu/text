<?php

declare(strict_types=1);

final class ScheduleTestRepository
{
    public const TERM_SHORT = 'short_term';

    public const TERM_LONG = 'long_term';

    public const STATUS_COMPLETE = 'complete';

    public const STATUS_MISSING_MCQ = 'missing_mcq';

    public const STATUS_PENDING = 'pending';

    public function enabled(): bool
    {
        return SchemaHelper::scheduleTestManagerEnabled();
    }

    /** @return array{planner_mode:string,term_key:string} */
    public function getConfig(int $subCourseId, string $termKey): array
    {
        if (!$this->enabled() || $subCourseId < 1) {
            return ['planner_mode' => 'day_wise', 'term_key' => $termKey];
        }
        $st = db()->prepare(
            'SELECT planner_mode FROM st_schedule_config WHERE sub_course_id=? AND term_key=?'
        );
        $st->execute([$subCourseId, $termKey]);
        $mode = $st->fetchColumn();

        return [
            'planner_mode' => $mode ? (string) $mode : 'day_wise',
            'term_key' => $termKey,
        ];
    }

    public function saveConfig(int $subCourseId, string $termKey, string $plannerMode): void
    {
        if (!$this->enabled()) {
            return;
        }
        $mode = $plannerMode === 'date_wise' ? 'date_wise' : 'day_wise';
        db()->prepare(
            'INSERT INTO st_schedule_config (sub_course_id, term_key, planner_mode) VALUES (?,?,?)
             ON DUPLICATE KEY UPDATE planner_mode=VALUES(planner_mode)'
        )->execute([$subCourseId, $termKey, $mode]);
    }

    /**
     * Calendar badges for a month.
     *
     * @return list<array<string,mixed>>
     */
    public function calendarMonth(int $subCourseId, string $termKey, int $year, int $month): array
    {
        if (!$this->enabled() || $subCourseId < 1) {
            return [];
        }
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end = date('Y-m-t', strtotime($start));

        $st = db()->prepare(
            'SELECT d.id, d.day_index, d.schedule_date, d.title_te,
                    COUNT(r.id) AS row_count,
                    COALESCE(SUM(r.total_marks), 0) AS total_marks
             FROM st_schedule_days d
             LEFT JOIN st_schedule_rows r ON r.schedule_day_id = d.id
             WHERE d.sub_course_id = ? AND d.term_key = ? AND d.is_active = 1
               AND (
                 (d.schedule_date IS NOT NULL AND d.schedule_date BETWEEN ? AND ?)
                 OR d.schedule_date IS NULL
               )
             GROUP BY d.id
             ORDER BY COALESCE(d.schedule_date, "1970-01-01"), d.day_index'
        );
        $st->execute([$subCourseId, $termKey, $start, $end]);
        $rows = $st->fetchAll() ?: [];
        $config = $this->getConfig($subCourseId, $termKey);
        $out = [];
        foreach ($rows as $r) {
            $label = $config['planner_mode'] === 'date_wise' && !empty($r['schedule_date'])
                ? 'తేదీ ' . $r['schedule_date']
                : 'రోజు ' . (int) ($r['day_index'] ?? 0);
            $rc = (int) ($r['row_count'] ?? 0);
            $status = $this->dayStatusById((int) $r['id']);
            $out[] = $r + [
                'badge_label' => $label . ' — ' . $rc . ' విషయాలు',
                'badge_short' => ($config['planner_mode'] === 'date_wise' ? (string) $r['schedule_date'] : 'D' . (int) $r['day_index']) . ' · ' . $rc,
                'status' => $status,
                'status_color' => $this->statusColorClass($status),
                'is_locked' => !((int) ($r['is_active'] ?? 1)),
            ];
        }

        return $out;
    }

    /** @return list<array<string,mixed>> */
    public function daysForSubCourse(int $subCourseId, string $termKey): array
    {
        if (!$this->enabled()) {
            return [];
        }
        $st = db()->prepare(
            'SELECT d.*, COUNT(r.id) AS row_count, COALESCE(SUM(r.total_marks), 0) AS total_marks
             FROM st_schedule_days d
             LEFT JOIN st_schedule_rows r ON r.schedule_day_id = d.id
             WHERE d.sub_course_id = ? AND d.term_key = ? AND d.is_active = 1
             GROUP BY d.id
             ORDER BY COALESCE(d.day_index, 9999), d.schedule_date'
        );
        $st->execute([$subCourseId, $termKey]);

        return $st->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function dayById(int $dayId): ?array
    {
        $st = db()->prepare('SELECT * FROM st_schedule_days WHERE id=? LIMIT 1');
        $st->execute([$dayId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    /** @return array{sub_course_id:int,day_index:int}|null */
    public function rowScheduleGate(int $rowId): ?array
    {
        if (!$this->enabled() || $rowId < 1) {
            return null;
        }
        $st = db()->prepare(
            'SELECT d.sub_course_id, d.day_index
             FROM st_schedule_rows r
             INNER JOIN st_schedule_days d ON d.id = r.schedule_day_id
             WHERE r.id = ? LIMIT 1'
        );
        $st->execute([$rowId]);
        $row = $st->fetch();
        if (!$row) {
            return null;
        }

        return [
            'sub_course_id' => (int) ($row['sub_course_id'] ?? 0),
            'day_index' => (int) ($row['day_index'] ?? 0),
        ];
    }

    /** @return array<string,mixed>|null */
    public function dayByIndex(int $subCourseId, string $termKey, int $dayIndex): ?array
    {
        $st = db()->prepare(
            'SELECT * FROM st_schedule_days WHERE sub_course_id=? AND term_key=? AND day_index=? LIMIT 1'
        );
        $st->execute([$subCourseId, $termKey, $dayIndex]);

        return $st->fetch() ?: null;
    }

    /** @return list<array<string,mixed>> */
    public function rowsForDay(int $dayId): array
    {
        $st = db()->prepare(
            'SELECT r.*, s.name AS subject_name, s.name_te AS subject_name_te, s.slug AS subject_slug,
                    t.slug AS test_slug, t.title AS test_title
             FROM st_schedule_rows r
             JOIN subjects s ON s.id = r.subject_id
             LEFT JOIN tests t ON t.id = r.test_id
             WHERE r.schedule_day_id = ?
             ORDER BY r.sort_order, r.id'
        );
        $st->execute([$dayId]);
        $rows = $st->fetchAll() ?: [];
        foreach ($rows as &$row) {
            $row['topic_ids'] = $this->decodeTopicIds($row['topic_ids'] ?? '[]');
            $row['topics'] = $this->topicsMeta($row['topic_ids']);
            $row['row_meta'] = $this->decodeRowMeta($row['row_meta'] ?? null);
            $row['question_count'] = $this->questionCountForRow($row);
        }
        unset($row);

        return $rows;
    }

    /**
     * Both term planners for the same day slot (day index or calendar date).
     *
     * @return array{short_term:array<string,mixed>,long_term:array<string,mixed>}
     */
    public function dualDayWorkspace(int $subCourseId, ?int $dayIndex, ?string $scheduleDate): array
    {
        $out = ['short_term' => ['day' => null, 'rows' => []], 'long_term' => ['day' => null, 'rows' => []]];
        foreach ([self::TERM_SHORT => 'short_term', self::TERM_LONG => 'long_term'] as $term => $key) {
            $day = null;
            if ($scheduleDate !== null && $scheduleDate !== '') {
                $st = db()->prepare(
                    'SELECT * FROM st_schedule_days WHERE sub_course_id=? AND term_key=? AND schedule_date=? LIMIT 1'
                );
                $st->execute([$subCourseId, $term, $scheduleDate]);
                $day = $st->fetch() ?: null;
            } elseif ($dayIndex !== null && $dayIndex > 0) {
                $day = $this->dayByIndex($subCourseId, $term, $dayIndex);
            }
            $out[$key]['day'] = $day;
            $out[$key]['rows'] = $day ? $this->rowsForDay((int) $day['id']) : [];
        }

        return $out;
    }

    public function createCustomTopic(int $subjectId, string $title, ?int $adminUserId = null): int
    {
        if ($subjectId < 1 || trim($title) === '') {
            throw new InvalidArgumentException('subject_id and title required');
        }
        $tbl = SchemaHelper::topicsTable();
        $adminRepo = new AdminRepository();
        $topicId = $adminRepo->createTopicQuick($subjectId, trim($title), trim($title));
        if (SchemaHelper::columnExists($tbl, 'is_custom')) {
            $sql = 'UPDATE `' . $tbl . '` SET is_custom=1';
            $params = [];
            if (SchemaHelper::columnExists($tbl, 'created_by_admin') && $adminUserId !== null && $adminUserId > 0) {
                $sql .= ', created_by_admin=?';
                $params[] = $adminUserId;
            }
            $sql .= ' WHERE id=?';
            $params[] = $topicId;
            db()->prepare($sql)->execute($params);
        }

        return $topicId;
    }

    /**
     * Batch-save short + long term rows for one day slot; optionally dispatch WhatsApp.
     *
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    public function batchSaveDualTermDispatch(array $payload): array
    {
        $subCourseId = (int) ($payload['sub_course_id'] ?? 0);
        if ($subCourseId < 1) {
            throw new InvalidArgumentException('sub_course_id required');
        }
        $dayIndex = isset($payload['day_index']) && $payload['day_index'] !== ''
            ? (int) $payload['day_index'] : null;
        $scheduleDate = !empty($payload['schedule_date']) ? (string) $payload['schedule_date'] : null;

        $shortPayload = [
            'id' => (int) ($payload['short_day_id'] ?? 0) ?: null,
            'sub_course_id' => $subCourseId,
            'term_key' => self::TERM_SHORT,
            'day_index' => $dayIndex,
            'schedule_date' => $scheduleDate,
            'rows' => $payload['short_term']['rows'] ?? $payload['short_rows'] ?? [],
        ];
        $longPayload = [
            'id' => (int) ($payload['long_day_id'] ?? 0) ?: null,
            'sub_course_id' => $subCourseId,
            'term_key' => self::TERM_LONG,
            'day_index' => $dayIndex,
            'schedule_date' => $scheduleDate,
            'rows' => $payload['long_term']['rows'] ?? $payload['long_rows'] ?? [],
        ];

        if (!$shortPayload['id'] && $dayIndex) {
            $existing = $this->dayByIndex($subCourseId, self::TERM_SHORT, $dayIndex);
            if ($existing) {
                $shortPayload['id'] = (int) $existing['id'];
            }
        }
        if (!$longPayload['id'] && $dayIndex) {
            $existing = $this->dayByIndex($subCourseId, self::TERM_LONG, $dayIndex);
            if ($existing) {
                $longPayload['id'] = (int) $existing['id'];
            }
        }

        $shortDayId = is_array($shortPayload['rows']) && $shortPayload['rows'] !== []
            ? $this->saveDayWithRows($shortPayload) : (int) ($shortPayload['id'] ?? 0);
        $longDayId = is_array($longPayload['rows']) && $longPayload['rows'] !== []
            ? $this->saveDayWithRows($longPayload) : (int) ($longPayload['id'] ?? 0);

        foreach (
            [
                ['id' => $shortDayId, 'locked' => !empty($payload['hold_short'])],
                ['id' => $longDayId, 'locked' => !empty($payload['hold_long'])],
            ] as $lock
        ) {
            if ($lock['id'] > 0 && $lock['locked']) {
                $this->setDayStudentLock((int) $lock['id'], true);
            }
        }

        $compiler = new ScheduleDailyNotificationService($this);
        $compiled = $compiler->compileDailyNotification($subCourseId, $dayIndex, $scheduleDate);

        $whatsapp = ['ok' => false, 'url' => '', 'text' => $compiled['text'], 'gateway' => null];
        if (!empty($payload['dispatch_whatsapp'])) {
            $whatsapp = $this->dispatchCompiledNotification($subCourseId, $compiled['text']);
        } else {
            $whatsapp['url'] = 'https://wa.me/?text=' . rawurlencode($compiled['text']);
            $whatsapp['ok'] = $compiled['text'] !== '';
        }

        return [
            'short_day_id' => $shortDayId,
            'long_day_id' => $longDayId,
            'short_rows' => $shortDayId > 0 ? $this->rowsForDay($shortDayId) : [],
            'long_rows' => $longDayId > 0 ? $this->rowsForDay($longDayId) : [],
            'notification' => $compiled,
            'whatsapp' => $whatsapp,
        ];
    }

    /** @return array{ok:bool,url:string,text:string,gateway:array<string,mixed>|null} */
    public function dispatchCompiledNotification(int $subCourseId, string $message): array
    {
        $message = trim($message);
        $waMe = 'https://wa.me/?text=' . rawurlencode($message);
        if ($message === '') {
            return ['ok' => false, 'url' => $waMe, 'text' => '', 'gateway' => null];
        }

        $hub = new WhatsAppHubRepository();
        $row = $hub->subCourseRow($subCourseId);
        if (!$row) {
            return ['ok' => false, 'url' => $waMe, 'text' => $message, 'gateway' => ['error' => 'Sub-course not found']];
        }

        $gateway = new WhatsAppMobileGatewayService();
        $group = $gateway->groupNameFromSubCourse($row);
        $result = $gateway->fireTrigger($group, $message);

        return [
            'ok' => !empty($result['success']),
            'url' => $waMe,
            'text' => $message,
            'gateway' => $result,
        ];
    }

    public function questionCountForRow(array $row): int
    {
        return $this->questionCountForTest((int) ($row['test_id'] ?? 0));
    }

    /** @param array<string,mixed> $payload */
    public function saveDayWithRows(array $payload): int
    {
        if (!$this->enabled()) {
            throw new RuntimeException('Schedule tables not migrated.');
        }
        $subCourseId = (int) ($payload['sub_course_id'] ?? 0);
        $termKey = (string) ($payload['term_key'] ?? self::TERM_SHORT);
        if ($subCourseId < 1) {
            throw new InvalidArgumentException('sub_course_id required');
        }

        $dayId = !empty($payload['id']) ? (int) $payload['id'] : 0;
        $dayIndex = isset($payload['day_index']) && $payload['day_index'] !== ''
            ? (int) $payload['day_index'] : null;
        $scheduleDate = !empty($payload['schedule_date']) ? (string) $payload['schedule_date'] : null;
        $titleTe = trim((string) ($payload['title_te'] ?? ''));
        $plannerSlot = $this->plannerSlot($dayIndex, $scheduleDate);

        if ($dayId > 0) {
            $sql = 'UPDATE st_schedule_days SET day_index=?, schedule_date=?, title_te=?';
            $params = [$dayIndex, $scheduleDate, $titleTe !== '' ? $titleTe : null];
            if (SchemaHelper::columnExists('st_schedule_days', 'planner_slot')) {
                $sql .= ', planner_slot=?';
                $params[] = $plannerSlot;
            }
            $sql .= ' WHERE id=?';
            $params[] = $dayId;
            db()->prepare($sql)->execute($params);
        } else {
            $cols = 'sub_course_id, term_key, day_index, schedule_date, title_te';
            $vals = '?,?,?,?,?';
            $params = [$subCourseId, $termKey, $dayIndex, $scheduleDate, $titleTe !== '' ? $titleTe : null];
            if (SchemaHelper::columnExists('st_schedule_days', 'planner_slot')) {
                $cols .= ', planner_slot';
                $vals .= ',?';
                $params[] = $plannerSlot;
            }
            db()->prepare("INSERT INTO st_schedule_days ({$cols}) VALUES ({$vals})")->execute($params);
            $dayId = (int) db()->lastInsertId();
        }

        $rows = $payload['rows'] ?? [];
        if (!is_array($rows)) {
            $rows = [];
        }

        $existingIds = [];
        $sort = 0;
        $courseId = $this->courseIdForSubCourse($subCourseId);
        foreach ($rows as $rowData) {
            if (!is_array($rowData)) {
                continue;
            }
            ++$sort;
            $rowId = $this->saveScheduleRow($dayId, $subCourseId, $courseId, $rowData, $sort);
            if ($rowId > 0) {
                $existingIds[] = $rowId;
            }
        }

        if ($existingIds !== []) {
            $ph = implode(',', array_fill(0, count($existingIds), '?'));
            $del = db()->prepare(
                "DELETE FROM st_schedule_rows WHERE schedule_day_id=? AND id NOT IN ({$ph})"
            );
            $del->execute(array_merge([$dayId], $existingIds));
        } else {
            db()->prepare('DELETE FROM st_schedule_rows WHERE schedule_day_id=?')->execute([$dayId]);
        }

        $layout = array_map(static function (array $r): array {
            return [
                'subject_id' => (int) ($r['subject_id'] ?? 0),
                'topic_ids' => $r['topic_ids'] ?? [],
                'total_marks' => (int) ($r['total_marks'] ?? 25),
            ];
        }, $rows);
        db()->prepare('UPDATE st_schedule_days SET layout_snapshot=? WHERE id=?')->execute([
            json_encode($layout, JSON_UNESCAPED_UNICODE),
            $dayId,
        ]);

        return $dayId;
    }

    public function copyLayoutFromPreviousDay(int $targetDayId, int $sourceDayId): bool
    {
        $src = $this->dayById($sourceDayId);
        if (!$src || empty($src['layout_snapshot'])) {
            return false;
        }
        db()->prepare('UPDATE st_schedule_days SET layout_snapshot=? WHERE id=?')->execute([
            $src['layout_snapshot'],
            $targetDayId,
        ]);

        return true;
    }

    public function previousDayId(int $subCourseId, string $termKey, int $currentDayIndex): ?int
    {
        $st = db()->prepare(
            'SELECT id FROM st_schedule_days
             WHERE sub_course_id=? AND term_key=? AND day_index < ? AND day_index IS NOT NULL
             ORDER BY day_index DESC LIMIT 1'
        );
        $st->execute([$subCourseId, $termKey, $currentDayIndex]);
        $id = $st->fetchColumn();

        return $id ? (int) $id : null;
    }

    public function deleteDay(int $dayId): void
    {
        db()->prepare('DELETE FROM st_schedule_days WHERE id=?')->execute([$dayId]);
    }

    /** @return list<array<string,mixed>> */
    public function topicsForSubject(int $subjectId): array
    {
        return (new AdminRepository())->topicsForSubjectAdmin($subjectId);
    }

    /**
     * MCQs for subject + topic set.
     *
     * @param list<int> $topicIds
     * @return list<array<string,mixed>>
     */
    public function poolMcqsForTopics(int $subjectId, array $topicIds, string $search = '', int $limit = 200): array
    {
        if ($subjectId < 1) {
            return [];
        }
        $topicIds = array_values(array_filter(array_map('intval', $topicIds)));
        $tpTbl = SchemaHelper::topicsTable();
        $where = ['t.subject_id = ?'];
        $params = [$subjectId];

        if ($topicIds !== []) {
            $in = implode(',', array_fill(0, count($topicIds), '?'));
            if (SchemaHelper::columnExists('tests', 'topic_id')) {
                $where[] = "(t.topic_id IN ({$in}) OR tp.id IN ({$in}))";
                $params = array_merge($params, $topicIds, $topicIds);
            } else {
                $where[] = "tp.id IN ({$in})";
                $params = array_merge($params, $topicIds);
            }
        }

        $q = trim($search);
        if ($q !== '') {
            $where[] = '(tq.question_text LIKE ? OR tq.question_text_te LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $topicJoin = SchemaHelper::columnExists('tests', 'topic_id')
            ? "LEFT JOIN `{$tpTbl}` tp ON tp.id = t.topic_id"
            : "LEFT JOIN `{$tpTbl}` tp ON 1=0";

        $sql = "SELECT tq.id, tq.question_text, tq.correct_option, tq.marks,
                       t.title AS source_test_title, tp.title AS topic_title
                FROM test_questions tq
                INNER JOIN tests t ON t.id = tq.test_id
                {$topicJoin}
                WHERE " . implode(' AND ', $where) . "
                ORDER BY tq.id DESC
                LIMIT " . max(1, min(500, $limit));

        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll() ?: [];
    }

    /**
     * @param list<int> $topicIds
     * @return list<int> question ids
     */
    public function aiPickQuestionIds(int $subjectId, array $topicIds, int $count): array
    {
        $pool = $this->poolMcqsForTopics($subjectId, $topicIds, '', 500);
        if ($pool === []) {
            return [];
        }
        shuffle($pool);
        $ids = [];
        foreach (array_slice($pool, 0, max(1, $count)) as $row) {
            $ids[] = (int) $row['id'];
        }

        return $ids;
    }

    public function buildWhatsAppDaySummary(int $dayId): string
    {
        $day = $this->dayById($dayId);
        if (!$day) {
            return '';
        }
        $compiler = new ScheduleDailyNotificationService($this);

        return $compiler->compileDailyNotification(
            (int) ($day['sub_course_id'] ?? 0),
            isset($day['day_index']) ? (int) $day['day_index'] : null,
            !empty($day['schedule_date']) ? (string) $day['schedule_date'] : null
        )['text'];
    }

    /** @param array<string,mixed> $rowData */
    private function saveScheduleRow(int $dayId, int $subCourseId, int $courseId, array $rowData, int $sort): int
    {
        $rowId = (int) ($rowData['id'] ?? 0);
        $subjectId = (int) ($rowData['subject_id'] ?? 0);
        $topicIds = $rowData['topic_ids'] ?? [];
        if (!is_array($topicIds)) {
            $topicIds = [];
        }
        $topicIds = array_values(array_filter(array_map('intval', $topicIds)));
        $totalMarks = max(1, (int) ($rowData['total_marks'] ?? 25));
        $mode = (string) ($rowData['question_mode'] ?? 'manual');
        if ($subjectId < 1) {
            return 0;
        }

        $topicJson = json_encode($topicIds, JSON_UNESCAPED_UNICODE);
        $testId = (int) ($rowData['test_id'] ?? 0);
        $rowMetaJson = $this->encodeRowMeta($rowData['row_meta'] ?? null);

        if ($rowId > 0) {
            $st = db()->prepare('SELECT test_id FROM st_schedule_rows WHERE id=?');
            $st->execute([$rowId]);
            $testId = (int) $st->fetchColumn() ?: $testId;
            if (SchemaHelper::columnExists('st_schedule_rows', 'row_meta')) {
                db()->prepare(
                    'UPDATE st_schedule_rows SET subject_id=?, sort_order=?, topic_ids=?, total_marks=?, question_mode=?, row_meta=? WHERE id=?'
                )->execute([$subjectId, $sort, $topicJson, $totalMarks, $mode, $rowMetaJson, $rowId]);
            } else {
                db()->prepare(
                    'UPDATE st_schedule_rows SET subject_id=?, sort_order=?, topic_ids=?, total_marks=?, question_mode=? WHERE id=?'
                )->execute([$subjectId, $sort, $topicJson, $totalMarks, $mode, $rowId]);
            }
        } else {
            if (SchemaHelper::columnExists('st_schedule_rows', 'row_meta')) {
                db()->prepare(
                    'INSERT INTO st_schedule_rows (schedule_day_id, subject_id, sort_order, topic_ids, total_marks, question_mode, row_meta)
                     VALUES (?,?,?,?,?,?,?)'
                )->execute([$dayId, $subjectId, $sort, $topicJson, $totalMarks, $mode, $rowMetaJson]);
            } else {
                db()->prepare(
                    'INSERT INTO st_schedule_rows (schedule_day_id, subject_id, sort_order, topic_ids, total_marks, question_mode)
                     VALUES (?,?,?,?,?,?)'
                )->execute([$dayId, $subjectId, $sort, $topicJson, $totalMarks, $mode]);
            }
            $rowId = (int) db()->lastInsertId();
        }

        $examRepo = new ExamManagerRepository();
        $subName = $this->subjectName($subjectId);
        $title = 'Schedule D' . $dayId . ' · ' . $subName;
        $testPayload = [
            'course_id' => $courseId,
            'subject_id' => $subjectId,
            'slug' => slugify($title . '-' . $rowId),
            'title' => $title,
            'title_te' => $title,
            'test_type' => 'topic',
            'duration_mins' => 0,
            'total_questions' => $totalMarks,
            'total_marks' => $totalMarks,
            'passing_marks' => (int) ceil($totalMarks / 2),
            'negative_marking' => 0.25,
            'negative_enabled' => 1,
            'is_active' => 1,
            'pool_question_ids' => [],
            'external_mcq_text' => '',
        ];

        if ($mode === 'ai_pool') {
            $count = max(1, (int) ($rowData['ai_pool_count'] ?? $totalMarks));
            $testPayload['pool_question_ids'] = $this->aiPickQuestionIds($subjectId, $topicIds, $count);
        } elseif ($mode === 'manual' || $mode === 'hybrid') {
            $raw = $rowData['pool_question_ids'] ?? [];
            $testPayload['pool_question_ids'] = is_array($raw) ? array_map('intval', $raw) : [];
            if ($mode === 'hybrid' || $mode === 'external') {
                $testPayload['external_mcq_text'] = (string) ($rowData['external_mcq_text'] ?? '');
            }
        } elseif ($mode === 'external') {
            $testPayload['external_mcq_text'] = (string) ($rowData['external_mcq_text'] ?? '');
        }

        if ($testId > 0) {
            db()->prepare('DELETE FROM test_questions WHERE test_id=?')->execute([$testId]);
            $examRepo->saveExam($testPayload + ['unlimited_time' => 1], $testId);
        } else {
            $testId = $examRepo->saveExam($testPayload + ['unlimited_time' => 1]);
        }

        db()->prepare('UPDATE st_schedule_rows SET test_id=? WHERE id=?')->execute([$testId, $rowId]);

        return $rowId;
    }

    private function plannerSlot(?int $dayIndex, ?string $scheduleDate): ?string
    {
        if ($scheduleDate !== null && $scheduleDate !== '') {
            return 'date:' . $scheduleDate;
        }
        if ($dayIndex !== null && $dayIndex > 0) {
            return 'd:' . $dayIndex;
        }

        return null;
    }

    private function courseIdForSubCourse(int $subCourseId): int
    {
        $st = db()->prepare(
            'SELECT c.id FROM sub_courses sc JOIN courses c ON c.id = sc.course_id WHERE sc.id=? LIMIT 1'
        );
        $st->execute([$subCourseId]);

        return (int) ($st->fetchColumn() ?: 0);
    }

    private function subjectName(int $subjectId): string
    {
        $st = db()->prepare('SELECT name FROM subjects WHERE id=?');
        $st->execute([$subjectId]);

        return (string) ($st->fetchColumn() ?: 'Subject');
    }

    /** @return list<int> */
    private function decodeTopicIds(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_map('intval', $raw);
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? array_map('intval', $decoded) : [];
    }

    /** @return array<string,mixed> */
    private function decodeRowMeta(mixed $raw): array
    {
        if (is_array($raw)) {
            return $raw;
        }
        if ($raw === null || $raw === '') {
            return [];
        }
        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /** @param mixed $meta */
    private function encodeRowMeta(mixed $meta): ?string
    {
        if (!SchemaHelper::columnExists('st_schedule_rows', 'row_meta')) {
            return null;
        }
        if ($meta === null || $meta === '') {
            return null;
        }
        if (is_string($meta)) {
            return $meta;
        }
        if (!is_array($meta)) {
            return null;
        }
        $clean = array_filter([
            'topic_label' => isset($meta['topic_label']) ? trim((string) $meta['topic_label']) : null,
            'notes' => isset($meta['notes']) ? trim((string) $meta['notes']) : null,
        ], static fn ($v) => $v !== null && $v !== '');

        return $clean === [] ? null : json_encode($clean, JSON_UNESCAPED_UNICODE);
    }

    /** @param list<int> $ids @return list<array<string,mixed>> */
    private function topicsMeta(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $tbl = SchemaHelper::topicsTable();
        $in = implode(',', array_fill(0, count($ids), '?'));
        $st = db()->prepare("SELECT id, title, slug FROM `{$tbl}` WHERE id IN ({$in})");
        $st->execute($ids);

        return $st->fetchAll() ?: [];
    }

    /** @return array{short_term:array<string,mixed>,long_term:array<string,mixed>} */
    public function dualTrackerDashboard(int $subCourseId): array
    {
        return [
            'short_term' => $this->termTrackerBoard($subCourseId, self::TERM_SHORT),
            'long_term' => $this->termTrackerBoard($subCourseId, self::TERM_LONG),
        ];
    }

    /** @return array<string,mixed> */
    public function termTrackerBoard(int $subCourseId, string $termKey): array
    {
        $scheduleDays = $this->termScheduleDaysCap($subCourseId, $termKey);
        $totalTopics = $this->totalTopicsCount($subCourseId);
        $scheduledTopics = $this->scheduledTopicIdsForTerm($subCourseId, $termKey);
        $coverage = $totalTopics > 0
            ? round(count($scheduledTopics) / $totalTopics * 100, 1)
            : 0.0;

        $byIndex = $this->indexedDaysByNumber($subCourseId, $termKey);
        $matrixLimit = min($scheduleDays, 120);
        $slots = [];
        for ($i = 1; $i <= $matrixLimit; ++$i) {
            $day = $byIndex[$i] ?? null;
            $dayId = $day ? (int) $day['id'] : null;
            $status = $dayId ? $this->dayStatusById($dayId) : self::STATUS_PENDING;
            $slots[] = [
                'day_index' => $i,
                'day_id' => $dayId,
                'status' => $status,
                'label' => 'D' . $i,
                'is_locked' => $day ? !((int) ($day['is_active'] ?? 1)) : false,
            ];
        }

        $counts = ['complete' => 0, 'missing_mcq' => 0, 'pending' => 0];
        foreach ($slots as $slot) {
            $k = (string) ($slot['status'] ?? self::STATUS_PENDING);
            if (isset($counts[$k])) {
                ++$counts[$k];
            }
        }

        $box = $this->termBoxMeta($subCourseId, $termKey);

        return [
            'term_key' => $termKey,
            'label_te' => (string) ($box['label_te'] ?? ''),
            'label_en' => (string) ($box['label_en'] ?? ''),
            'schedule_days' => $scheduleDays,
            'matrix_limit' => $matrixLimit,
            'coverage_percent' => $coverage,
            'total_topics' => $totalTopics,
            'scheduled_topics' => count($scheduledTopics),
            'slots' => $slots,
            'counts' => $counts,
        ];
    }

    /** @return array<string,mixed> */
    public function dayPreviewPanel(int $dayId): array
    {
        $day = $this->dayById($dayId);
        if (!$day) {
            throw new InvalidArgumentException('Day not found');
        }

        $rows = $this->rowsForDay($dayId);
        $totalQuestions = 0;
        $totalMarks = 0;
        foreach ($rows as &$row) {
            $qc = $this->questionCountForTest((int) ($row['test_id'] ?? 0));
            $row['question_count'] = $qc;
            $totalQuestions += $qc;
            $totalMarks += (int) ($row['total_marks'] ?? 0);
        }
        unset($row);

        $termKey = (string) ($day['term_key'] ?? '');
        $routing = $termKey === self::TERM_SHORT ? 'short → short_term' : 'long → long_term';
        $plannerMode = $this->getConfig((int) $day['sub_course_id'], $termKey)['planner_mode'];
        $scheduleType = $plannerMode === 'date_wise' ? 'తేదీవారీ' : 'రోజువారీ';
        $hasValidSchedule = $rows !== [] && $this->dayHasTopics($rows);
        $status = $this->dayStatusFromRows($rows);
        $fatigue = $totalQuestions > 100;

        $wa = ['ok' => false, 'url' => '', 'text' => ''];
        if ($hasValidSchedule) {
            $text = $this->buildWhatsAppDaySummary($dayId);
            $wa = ['ok' => $text !== '', 'url' => 'https://wa.me/?text=' . rawurlencode($text), 'text' => $text];
        }

        return [
            'day' => $day,
            'rows' => $rows,
            'status' => $status,
            'schedule_type' => $scheduleType,
            'schedule_type_en' => $plannerMode,
            'routing' => $routing,
            'total_questions' => $totalQuestions,
            'total_marks' => $totalMarks,
            'fatigue_warning' => $fatigue,
            'is_locked' => !((int) ($day['is_active'] ?? 1)),
            'has_valid_schedule' => $hasValidSchedule,
            'whatsapp' => $wa,
        ];
    }

    public function setDayStudentLock(int $dayId, bool $locked): void
    {
        if ($dayId < 1) {
            return;
        }
        db()->prepare('UPDATE st_schedule_days SET is_active=? WHERE id=?')->execute([
            $locked ? 0 : 1,
            $dayId,
        ]);
    }

    public function dayStatusById(int $dayId): string
    {
        return $this->dayStatusFromRows($this->rowsForDay($dayId));
    }

    public function enrichDayWithStatus(array $dayRow): array
    {
        $status = $this->dayStatusById((int) ($dayRow['id'] ?? 0));

        return $dayRow + [
            'status' => $status,
            'status_color' => $this->statusColorClass($status),
        ];
    }

    public function statusColorClass(string $status): string
    {
        return match ($status) {
            self::STATUS_COMPLETE => 'complete',
            self::STATUS_MISSING_MCQ => 'missing_mcq',
            default => 'pending',
        };
    }

    private function dayStatusFromRows(array $rows): string
    {
        if ($rows === []) {
            return self::STATUS_PENDING;
        }
        $hasTopics = false;
        $allQuestions = true;
        foreach ($rows as $row) {
            $topicIds = $row['topic_ids'] ?? [];
            if (!is_array($topicIds)) {
                $topicIds = $this->decodeTopicIds($topicIds);
            }
            if ($topicIds === []) {
                continue;
            }
            $hasTopics = true;
            $qc = $this->questionCountForTest((int) ($row['test_id'] ?? 0));
            if ($qc < 1) {
                $allQuestions = false;
            }
        }
        if (!$hasTopics) {
            return self::STATUS_PENDING;
        }

        return $allQuestions ? self::STATUS_COMPLETE : self::STATUS_MISSING_MCQ;
    }

    /** @param list<array<string,mixed>> $rows */
    private function dayHasTopics(array $rows): bool
    {
        foreach ($rows as $row) {
            $ids = $row['topic_ids'] ?? [];
            if (is_array($ids) && $ids !== []) {
                return true;
            }
        }

        return false;
    }

    private function questionCountForTest(int $testId): int
    {
        if ($testId < 1) {
            return 0;
        }
        $st = db()->prepare('SELECT COUNT(*) FROM test_questions WHERE test_id=?');
        $st->execute([$testId]);

        return (int) $st->fetchColumn();
    }

    private function termScheduleDaysCap(int $subCourseId, string $termKey): int
    {
        $box = $this->termBoxMeta($subCourseId, $termKey);
        $days = (int) ($box['schedule_days'] ?? 0);
        if ($days < 1) {
            $globals = (new SubjectTermMatrixRepository())->globalDefaults();
            $days = (int) ($globals['schedule_days'] ?? SubjectTermMatrixRepository::DEFAULT_SCHEDULE_DAYS);
        }

        return max(1, min(365, $days));
    }

    /** @return array<string,mixed> */
    private function termBoxMeta(int $subCourseId, string $termKey): array
    {
        if (!SchemaHelper::subCourseTermMatrixEnabled()) {
            return [];
        }
        $st = db()->prepare(
            'SELECT label_en, label_te, schedule_days, is_enabled FROM sub_course_term_boxes
             WHERE sub_course_id=? AND term_key=? LIMIT 1'
        );
        $st->execute([$subCourseId, $termKey]);

        return $st->fetch() ?: [];
    }

    public function totalTopicsCount(int $subCourseId): int
    {
        $tbl = SchemaHelper::topicsTable();
        $st = db()->prepare(
            "SELECT COUNT(DISTINCT t.id) FROM `{$tbl}` t
             INNER JOIN subjects s ON s.id = t.subject_id
             INNER JOIN sub_course_subjects scs ON scs.subject_id = s.id
             WHERE scs.sub_course_id = ?"
        );
        $st->execute([$subCourseId]);

        return (int) $st->fetchColumn();
    }

    /** @return list<int> */
    public function scheduledTopicIdsForTerm(int $subCourseId, string $termKey): array
    {
        $st = db()->prepare(
            'SELECT r.topic_ids FROM st_schedule_rows r
             INNER JOIN st_schedule_days d ON d.id = r.schedule_day_id
             WHERE d.sub_course_id = ? AND d.term_key = ?'
        );
        $st->execute([$subCourseId, $termKey]);
        $ids = [];
        foreach ($st->fetchAll() ?: [] as $row) {
            foreach ($this->decodeTopicIds($row['topic_ids'] ?? '[]') as $tid) {
                $ids[$tid] = $tid;
            }
        }

        return array_values($ids);
    }

    /** @return array<int,array<string,mixed>> */
    private function indexedDaysByNumber(int $subCourseId, string $termKey): array
    {
        $out = [];
        foreach ($this->daysForSubCourse($subCourseId, $termKey) as $day) {
            $idx = (int) ($day['day_index'] ?? 0);
            if ($idx > 0) {
                $out[$idx] = $day;
            }
        }

        return $out;
    }
}
