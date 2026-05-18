<?php

declare(strict_types=1);

final class ScheduleTestController
{
    private ScheduleTestRepository $repo;

    public function __construct()
    {
        $this->repo = new ScheduleTestRepository();
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        AdminAuthController::requireAdminApi(false);

        $data = $this->readBody();
        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? ($data['action'] ?? ''));

        try {
            if (!$this->repo->enabled() && $action !== 'status') {
                throw new RuntimeException('Run: php database/migrate_schedule_test_manager.php');
            }

            match ($action) {
                'status' => $this->status(),
                'context' => $this->context($data),
                'save_config' => $this->saveConfig($data),
                'calendar' => $this->calendar($data),
                'day' => $this->day($data),
                'dual_day' => $this->dualDay($data),
                'save_day' => $this->saveDay($data),
                'batch_save_dispatch' => $this->batchSaveDispatch($data),
                'delete_day' => $this->deleteDay($data),
                'copy_layout' => $this->copyLayout($data),
                'topics' => $this->topics($data),
                'create_custom_topic' => $this->createCustomTopic($data),
                'pool_mcqs' => $this->poolMcqs($data),
                'ai_pool' => $this->aiPool($data),
                'whatsapp' => $this->whatsapp($data),
                'compile_notification' => $this->compileNotification($data),
                'dispatch_whatsapp' => $this->dispatchWhatsapp($data),
                'dual_tracker' => $this->dualTracker($data),
                'day_preview' => $this->dayPreview($data),
                'toggle_lock' => $this->toggleLock($data),
                default => $this->fail('Unknown action', 400),
            };
        } catch (InvalidArgumentException $e) {
            $this->fail($e->getMessage(), 400);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    /** @return array<string,mixed> */
    private function readBody(): array
    {
        if (!empty($_POST['action'])) {
            return $_POST;
        }
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '{}', true);

        return is_array($decoded) ? $decoded : [];
    }

    private function status(): void
    {
        echo json_encode(['ok' => true, 'enabled' => $this->repo->enabled()], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function context(array $data): void
    {
        $subCourseId = (int) ($data['sub_course_id'] ?? $_GET['sub_course_id'] ?? 0);
        $termKey = (string) ($data['term_key'] ?? $_GET['term_key'] ?? ScheduleTestRepository::TERM_SHORT);
        if ($subCourseId < 1) {
            throw new InvalidArgumentException('sub_course_id required');
        }
        $matrixRepo = new SubjectTermMatrixRepository();
        $matrixRepo->syncSubCourseTermBoxesFromRecord($subCourseId);
        echo json_encode([
            'ok' => true,
            'config' => $this->repo->getConfig($subCourseId, $termKey),
            'days' => $this->repo->daysForSubCourse($subCourseId, $termKey),
            'subjects' => $this->subjectsForSubCourse($subCourseId),
            'globals' => $matrixRepo->globalDefaults(),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function saveConfig(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $subCourseId = (int) ($data['sub_course_id'] ?? 0);
        $termKey = (string) ($data['term_key'] ?? ScheduleTestRepository::TERM_SHORT);
        $mode = (string) ($data['planner_mode'] ?? 'day_wise');
        $this->repo->saveConfig($subCourseId, $termKey, $mode);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function calendar(array $data): void
    {
        $subCourseId = (int) ($data['sub_course_id'] ?? $_GET['sub_course_id'] ?? 0);
        $termKey = (string) ($data['term_key'] ?? $_GET['term_key'] ?? ScheduleTestRepository::TERM_SHORT);
        $year = (int) ($data['year'] ?? $_GET['year'] ?? (int) date('Y'));
        $month = (int) ($data['month'] ?? $_GET['month'] ?? (int) date('n'));
        echo json_encode([
            'ok' => true,
            'days' => $this->repo->calendarMonth($subCourseId, $termKey, $year, $month),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function day(array $data): void
    {
        $dayId = (int) ($data['day_id'] ?? $_GET['day_id'] ?? 0);
        if ($dayId < 1) {
            throw new InvalidArgumentException('day_id required');
        }
        echo json_encode([
            'ok' => true,
            'day' => $this->repo->dayById($dayId),
            'rows' => $this->repo->rowsForDay($dayId),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function saveDay(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $dayId = $this->repo->saveDayWithRows($data);
        echo json_encode([
            'ok' => true,
            'day_id' => $dayId,
            'rows' => $this->repo->rowsForDay($dayId),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function deleteDay(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $this->repo->deleteDay((int) ($data['day_id'] ?? 0));
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function copyLayout(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $target = (int) ($data['target_day_id'] ?? 0);
        $source = (int) ($data['source_day_id'] ?? 0);
        if ($source < 1 && $target > 0) {
            $day = $this->repo->dayById($target);
            if ($day) {
                $source = $this->repo->previousDayId(
                    (int) $day['sub_course_id'],
                    (string) $day['term_key'],
                    (int) ($day['day_index'] ?? 0)
                ) ?? 0;
            }
        }
        $layout = [];
        if ($source > 0) {
            $srcDay = $this->repo->dayById($source);
            if ($srcDay && !empty($srcDay['layout_snapshot'])) {
                $layout = json_decode((string) $srcDay['layout_snapshot'], true) ?: [];
            }
            if ($layout === []) {
                foreach ($this->repo->rowsForDay($source) as $r) {
                    $layout[] = [
                        'subject_id' => (int) $r['subject_id'],
                        'topic_ids' => $r['topic_ids'] ?? [],
                        'total_marks' => (int) $r['total_marks'],
                    ];
                }
            }
            if ($target > 0) {
                $this->repo->copyLayoutFromPreviousDay($target, $source);
            }
        }
        echo json_encode(['ok' => $source > 0, 'source_day_id' => $source, 'layout' => $layout], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function topics(array $data): void
    {
        $subjectId = (int) ($data['subject_id'] ?? $_GET['subject_id'] ?? 0);
        echo json_encode(['ok' => true, 'topics' => $this->repo->topicsForSubject($subjectId)], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function createCustomTopic(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $title = trim((string) ($data['title'] ?? ''));
        if ($subjectId < 1 || $title === '') {
            throw new InvalidArgumentException('subject_id and title required');
        }
        $adminId = (int) ($_SESSION['admin']['id'] ?? 0);
        $topicId = $this->repo->createCustomTopic($subjectId, $title, $adminId > 0 ? $adminId : null);
        echo json_encode([
            'ok' => true,
            'topic_id' => $topicId,
            'topics' => $this->repo->topicsForSubject($subjectId),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function dualDay(array $data): void
    {
        $subCourseId = (int) ($data['sub_course_id'] ?? $_GET['sub_course_id'] ?? 0);
        $dayIndex = isset($data['day_index']) ? (int) $data['day_index'] : (isset($_GET['day_index']) ? (int) $_GET['day_index'] : null);
        $scheduleDate = !empty($data['schedule_date']) ? (string) $data['schedule_date'] : (!empty($_GET['schedule_date']) ? (string) $_GET['schedule_date'] : null);
        if ($subCourseId < 1) {
            throw new InvalidArgumentException('sub_course_id required');
        }
        echo json_encode([
            'ok' => true,
            'workspace' => $this->repo->dualDayWorkspace($subCourseId, $dayIndex > 0 ? $dayIndex : null, $scheduleDate),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function batchSaveDispatch(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $result = $this->repo->batchSaveDualTermDispatch($data);
        echo json_encode(['ok' => true] + $result, JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function compileNotification(array $data): void
    {
        $subCourseId = (int) ($data['sub_course_id'] ?? $_GET['sub_course_id'] ?? 0);
        $dayIndex = isset($data['day_index']) ? (int) $data['day_index'] : (isset($_GET['day_index']) ? (int) $_GET['day_index'] : null);
        $scheduleDate = !empty($data['schedule_date']) ? (string) $data['schedule_date'] : (!empty($_GET['schedule_date']) ? (string) $_GET['schedule_date'] : null);
        if ($subCourseId < 1) {
            throw new InvalidArgumentException('sub_course_id required');
        }
        $compiler = new ScheduleDailyNotificationService($this->repo);
        $compiled = $compiler->compileDailyNotification(
            $subCourseId,
            $dayIndex > 0 ? $dayIndex : null,
            $scheduleDate
        );
        echo json_encode(['ok' => true, 'notification' => $compiled], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function dispatchWhatsapp(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $subCourseId = (int) ($data['sub_course_id'] ?? 0);
        if ($subCourseId < 1) {
            throw new InvalidArgumentException('sub_course_id required');
        }
        $dayIndex = isset($data['day_index']) && $data['day_index'] !== ''
            ? (int) $data['day_index'] : null;
        $scheduleDate = !empty($data['schedule_date']) ? (string) $data['schedule_date'] : null;

        if (!empty($data['hold_short'])) {
            $shortId = (int) ($data['short_day_id'] ?? 0);
            if ($shortId < 1 && $dayIndex) {
                $day = $this->repo->dayByIndex($subCourseId, ScheduleTestRepository::TERM_SHORT, $dayIndex);
                $shortId = $day ? (int) $day['id'] : 0;
            }
            if ($shortId > 0) {
                $this->repo->setDayStudentLock($shortId, true);
            }
        }
        if (!empty($data['hold_long'])) {
            $longId = (int) ($data['long_day_id'] ?? 0);
            if ($longId < 1 && $dayIndex) {
                $day = $this->repo->dayByIndex($subCourseId, ScheduleTestRepository::TERM_LONG, $dayIndex);
                $longId = $day ? (int) $day['id'] : 0;
            }
            if ($longId > 0) {
                $this->repo->setDayStudentLock($longId, true);
            }
        }

        $compiler = new ScheduleDailyNotificationService($this->repo);
        $compiled = $compiler->compileDailyNotification(
            $subCourseId,
            $dayIndex > 0 ? $dayIndex : null,
            $scheduleDate
        );
        $whatsapp = $this->repo->dispatchCompiledNotification($subCourseId, $compiled['text']);
        echo json_encode([
            'ok' => true,
            'notification' => $compiled,
            'whatsapp' => $whatsapp,
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function poolMcqs(array $data): void
    {
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $topicIds = $data['topic_ids'] ?? [];
        if (!is_array($topicIds)) {
            $topicIds = [];
        }
        $search = trim((string) ($data['q'] ?? ''));
        echo json_encode([
            'ok' => true,
            'items' => $this->repo->poolMcqsForTopics($subjectId, array_map('intval', $topicIds), $search),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function aiPool(array $data): void
    {
        $subjectId = (int) ($data['subject_id'] ?? 0);
        $topicIds = $data['topic_ids'] ?? [];
        $count = (int) ($data['count'] ?? 25);
        if (!is_array($topicIds)) {
            $topicIds = [];
        }
        $ids = $this->repo->aiPickQuestionIds($subjectId, array_map('intval', $topicIds), $count);
        echo json_encode(['ok' => true, 'question_ids' => $ids, 'count' => count($ids)], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function whatsapp(array $data): void
    {
        $dayId = (int) ($data['day_id'] ?? $_GET['day_id'] ?? 0);
        $text = $this->repo->buildWhatsAppDaySummary($dayId);
        $url = 'https://wa.me/?text=' . rawurlencode($text);
        echo json_encode(['ok' => true, 'text' => $text, 'url' => $url], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function dualTracker(array $data): void
    {
        $subCourseId = (int) ($data['sub_course_id'] ?? $_GET['sub_course_id'] ?? 0);
        if ($subCourseId < 1) {
            throw new InvalidArgumentException('sub_course_id required');
        }
        echo json_encode([
            'ok' => true,
            'tracker' => $this->repo->dualTrackerDashboard($subCourseId),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function dayPreview(array $data): void
    {
        $dayId = (int) ($data['day_id'] ?? $_GET['day_id'] ?? 0);
        if ($dayId < 1) {
            throw new InvalidArgumentException('day_id required');
        }
        echo json_encode([
            'ok' => true,
            'preview' => $this->repo->dayPreviewPanel($dayId),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function toggleLock(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $dayId = (int) ($data['day_id'] ?? 0);
        $locked = !empty($data['locked']);
        $this->repo->setDayStudentLock($dayId, $locked);
        echo json_encode(['ok' => true, 'is_locked' => $locked], JSON_UNESCAPED_UNICODE);
    }

    /** @return list<array<string,mixed>> */
    private function subjectsForSubCourse(int $subCourseId): array
    {
        $st = db()->prepare(
            'SELECT s.id, s.name, s.name_te, s.slug
             FROM subjects s
             INNER JOIN sub_course_subjects scs ON scs.subject_id = s.id
             WHERE scs.sub_course_id = ?
             ORDER BY scs.sort_order, s.sort_order, s.id'
        );
        $st->execute([$subCourseId]);

        return $st->fetchAll() ?: [];
    }

    private function fail(string $msg, int $code): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    }
}
