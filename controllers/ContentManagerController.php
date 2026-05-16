<?php

declare(strict_types=1);

/**
 * Content Manager + media upload API (admin.auth protected).
 */
final class ContentManagerController
{
    public function __construct(
        private AdminRepository $repo = new AdminRepository(),
    ) {
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'GET') {
            AdminAuthController::requireAdminApi(false);
            $this->handleGet();

            return;
        }

        if ($method !== 'POST') {
            $this->jsonError(405, 'Method not allowed');

            return;
        }

        if (!empty($_FILES['image_file']) && ($_POST['action'] ?? '') === 'upload_image') {
            AdminAuthController::requireAdminApi(true);
            $this->handleImageUpload();

            return;
        }

        if (!empty($_FILES['mcq_file']) && ($_POST['action'] ?? '') === 'import_mcq_file') {
            AdminAuthController::requireAdminApi(true);
            $this->handleMcqFileImport();

            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = $_POST;
        }

        AdminAuthController::requireAdminApi(true, $data);
        $this->handlePost($data);
    }

    private function handleGet(): void
    {
        $action = $_GET['action'] ?? '';
        switch ($action) {
            case 'main_courses':
                $this->jsonOk(['items' => $this->repo->contentManagerMainCourses()]);
                break;
            case 'sort_list':
                $entity = preg_replace('/[^a-z_]/', '', (string) ($_GET['entity'] ?? ''));
                $orderRepo = new ContentOrderRepository();
                if (!$orderRepo->isValidEntity($entity)) {
                    $this->jsonError(400, 'Invalid entity');
                    break;
                }
                $courseId = (int) ($_GET['course_id'] ?? 0);
                if ($courseId > 0) {
                    $courseId = $this->repo->resolveContentManagerCourseId($courseId);
                }
                $ctx = [
                    'course_id' => $courseId,
                    'sub_course_id' => (int) ($_GET['sub_course_id'] ?? 0),
                    'subject_id' => (int) ($_GET['subject_id'] ?? 0),
                    'topic_id' => (int) ($_GET['topic_id'] ?? 0),
                ];
                $this->jsonOk(['items' => $orderRepo->siblings($entity, $ctx)]);
                break;
            case 'sub_courses':
                $cid = (int) ($_GET['course_id'] ?? 0);
                $this->jsonOk(['items' => $this->repo->contentManagerSubCourses($cid)]);
                break;
            case 'subjects':
                $scid = (int) ($_GET['sub_course_id'] ?? 0);
                $this->jsonOk(['items' => $this->repo->contentManagerSubjects($scid)]);
                break;
            case 'topics':
                $sid = (int) ($_GET['subject_id'] ?? 0);
                $this->jsonOk(['items' => $this->repo->contentManagerTopics($sid)]);
                break;
            case 'topic':
                $tid = (int) ($_GET['topic_id'] ?? 0);
                $topic = $this->repo->getTopicContentManager($tid);
                if (!$topic) {
                    $this->jsonError(404, 'Topic not found');
                    break;
                }
                $this->jsonOk(['topic' => $topic]);
                break;
            case 'entity':
                $entity = preg_replace('/[^a-z_]/', '', (string) ($_GET['entity'] ?? ''));
                $eid = (int) ($_GET['id'] ?? 0);
                $row = $this->repo->cmEntityRow($entity, $eid);
                if (!$row) {
                    $this->jsonError(404, 'Not found');
                    break;
                }
                $this->jsonOk(['item' => $row]);
                break;
            case 'resolve_programme':
                $mc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['mc'] ?? ''));
                $sc = preg_replace('/[^a-z0-9-]/', '', strtolower($_GET['sc'] ?? ''));
                $row = $this->repo->resolveSubCourseByCourseAndSlug($mc, $sc);
                if (!$row) {
                    $this->jsonError(404, 'Programme not found');
                    break;
                }
                $this->jsonOk([
                    'course_id' => (int) ($row['parent_course_id'] ?? $row['course_id'] ?? 0),
                    'sub_course_id' => (int) ($row['id'] ?? 0),
                ]);
                break;
            case 'exam_templates':
                require_once ACHARYA_ROOT . '/includes/admin/content_manager_defaults.php';
                $this->jsonOk(['items' => content_manager_exam_suite_templates()]);
                break;
            case 'sub_course_term_matrix':
            case 'subject_term_matrix':
                $scid = (int) ($_GET['sub_course_id'] ?? 0);
                $matrixRepo = new SubjectTermMatrixRepository();
                if ($scid < 1) {
                    $this->jsonError(400, 'sub_course_id required');
                    break;
                }
                $autoInit = !isset($_GET['auto_init']) || $_GET['auto_init'] !== '0';
                $boxes = $autoInit
                    ? $matrixRepo->syncSubCourseTermBoxesFromRecord($scid)
                    : $matrixRepo->boxesForSubCourse($scid);
                $subMeta = $this->repo->cmEntityRow('sub_course', $scid);
                $this->jsonOk([
                    'global' => $matrixRepo->globalDefaults(),
                    'boxes' => $boxes,
                    'tables_ready' => $matrixRepo->subCourseTablesReady(),
                    'sub_course' => $subMeta ? [
                        'id' => $scid,
                        'slug' => (string) ($subMeta['slug'] ?? ''),
                        'name' => (string) ($subMeta['name'] ?? ''),
                        'name_te' => (string) ($subMeta['name_te'] ?? ''),
                    ] : null,
                    'routing_keys' => [
                        'short' => SubjectTermMatrixRepository::TERM_SHORT,
                        'long' => SubjectTermMatrixRepository::TERM_LONG,
                    ],
                ]);
                break;
            case 'term_matrix_globals':
                $matrixRepo = new SubjectTermMatrixRepository();
                $this->jsonOk(['global' => $matrixRepo->globalDefaults()]);
                break;
            case 'sub_topics':
                $tid = (int) ($_GET['topic_id'] ?? 0);
                $this->jsonOk(['items' => $tid > 0 ? $this->repo->listSubTopicsForTopic($tid) : []]);
                break;
            default:
                $this->jsonError(400, 'Unknown action');
        }
    }

    private function handleImageUpload(): void
    {
        $entity = preg_replace('/[^a-z_]/', '', (string) ($_POST['entity'] ?? ''));
        $eid = (int) ($_POST['id'] ?? 0);
        if ($eid < 1) {
            throw new InvalidArgumentException('Select a course, sub-course, or subject before uploading.');
        }
        if (!in_array($entity, ['course', 'sub_course', 'subject'], true)) {
            throw new InvalidArgumentException('Invalid image target entity.');
        }

        $path = $this->repo->handleUpload('image_file', $entity);
        if (!$path) {
            throw new InvalidArgumentException('No image file received.');
        }

        $this->repo->cmSetImagePath($entity, $eid, $path);
        $abs = ACHARYA_ROOT . '/' . ltrim($path, '/');
        $this->jsonOk([
            'image_path' => $path,
            'url' => acharya_media_url($path),
            'v' => is_file($abs) ? (int) filemtime($abs) : 0,
        ]);
    }

    /** @param array<string,mixed> $data */
    private function handlePost(array $data): void
    {
        $action = (string) ($data['action'] ?? '');

        switch ($action) {
            case 'save_topic_config':
                $topicId = (int) ($data['topic_id'] ?? 0);
                if ($topicId < 1) {
                    throw new InvalidArgumentException('topic_id required');
                }
                $this->repo->saveTopicContentManager($topicId, [
                    'has_sub_topics' => !empty($data['has_sub_topics']),
                    'notes_enabled' => !empty($data['notes_enabled']),
                    'question_count' => (int) ($data['question_count'] ?? 50),
                    'notes_content' => (string) ($data['notes_content'] ?? ''),
                    'mcq_content' => (string) ($data['mcq_content'] ?? ''),
                    'sub_topics' => $data['sub_topics'] ?? [],
                    'exam_suite' => $data['exam_suite'] ?? [],
                ]);
                $this->jsonOk(['topic_id' => $topicId]);
                break;

            case 'create_topic':
                $subjectId = (int) ($data['subject_id'] ?? 0);
                $title = trim((string) ($data['title'] ?? ''));
                $titleTe = trim((string) ($data['title_te'] ?? ''));
                $newId = $this->repo->createTopicQuick($subjectId, $title, $titleTe !== '' ? $titleTe : null);
                $this->jsonOk(['topic_id' => $newId]);
                break;

            case 'delete_topic':
                $this->repo->cmDeleteTopic((int) ($data['topic_id'] ?? 0));
                $this->jsonOk([]);
                break;

            case 'save_main_course':
                $id = isset($data['id']) && (int) $data['id'] > 0 ? (int) $data['id'] : null;
                $newId = $this->repo->cmSaveMainCourse($data, $id);
                $this->jsonOk(['id' => $newId]);
                break;

            case 'delete_main_course':
                $this->repo->deleteCourse((int) ($data['id'] ?? 0));
                $this->jsonOk([]);
                break;

            case 'save_sub_course':
                $id = isset($data['id']) && (int) $data['id'] > 0 ? (int) $data['id'] : null;
                $courseId = (int) ($data['course_id'] ?? 0);
                if ($courseId < 1) {
                    throw new InvalidArgumentException('Select a main course before saving sub-course');
                }
                $newId = $this->repo->cmSaveSubCourse($data, $id);
                $this->jsonOk(['id' => $newId, 'course_id' => $this->repo->resolveContentManagerCourseId($courseId)]);
                break;

            case 'delete_sub_course':
                $this->repo->deleteSubCourse((int) ($data['id'] ?? 0));
                $this->jsonOk([]);
                break;

            case 'save_subject':
                $id = isset($data['id']) && (int) $data['id'] > 0 ? (int) $data['id'] : null;
                $subCourseId = (int) ($data['sub_course_id'] ?? 0);
                if ($subCourseId < 1) {
                    throw new InvalidArgumentException('Select a sub-course before saving subject');
                }
                $newId = $this->repo->cmSaveSubjectForSubCourse($data, $id);
                $row = $this->repo->cmEntityRow('subject', $newId);
                $subjectSlug = (string) ($row['slug'] ?? '');
                $publicUrl = null;
                if ($subjectSlug !== '') {
                    $st = db()->prepare(
                        'SELECT sc.slug AS sub_slug, c.slug AS course_slug FROM sub_courses sc JOIN courses c ON c.id = sc.course_id WHERE sc.id = ? LIMIT 1'
                    );
                    $st->execute([$subCourseId]);
                    $path = $st->fetch();
                    if ($path) {
                        require_once dirname(__DIR__) . '/includes/public_site_helpers.php';
                        $publicUrl = public_subject_workspace_url(
                            (string) $path['course_slug'],
                            (string) $path['sub_slug'],
                            $subjectSlug
                        );
                    }
                }
                $this->jsonOk([
                    'id' => $newId,
                    'sub_course_id' => $subCourseId,
                    'slug' => $subjectSlug !== '' ? $subjectSlug : null,
                    'public_url' => $publicUrl,
                ]);
                break;

            case 'delete_subject':
                $this->repo->deleteSubject((int) ($data['id'] ?? 0));
                $this->jsonOk([]);
                break;

            case 'set_subject_live':
                $this->repo->cmSetSubjectLive(
                    (int) ($data['sub_course_id'] ?? 0),
                    (int) ($data['subject_id'] ?? 0),
                    !empty($data['is_live'])
                );
                $this->jsonOk([]);
                break;

            case 'save_sub_course_term_matrix':
            case 'save_subject_term_matrix':
                $scid = (int) ($data['sub_course_id'] ?? 0);
                if ($scid < 1) {
                    throw new InvalidArgumentException('sub_course_id required');
                }
                $matrixRepo = new SubjectTermMatrixRepository();
                $boxes = is_array($data['boxes'] ?? null) ? $data['boxes'] : [];
                $saved = $matrixRepo->applySubCourseTermToggles($scid, $boxes);
                $this->jsonOk([
                    'sub_course_id' => $scid,
                    'boxes' => $saved,
                    'routing_keys' => [
                        'short' => SubjectTermMatrixRepository::TERM_SHORT,
                        'long' => SubjectTermMatrixRepository::TERM_LONG,
                    ],
                ]);
                break;

            case 'save_term_matrix_globals':
                (new SubjectTermMatrixRepository())->saveGlobalDefaults($data);
                $this->jsonOk([]);
                break;

            case 'save_topic_meta':
                $topicId = (int) ($data['topic_id'] ?? 0);
                if ($topicId < 1) {
                    throw new InvalidArgumentException('topic_id required');
                }
                $this->repo->cmSaveTopicMeta($topicId, $data);
                $this->jsonOk(['topic_id' => $topicId]);
                break;

            case 'save_topic_notes':
                $topicId = (int) ($data['topic_id'] ?? 0);
                if ($topicId < 1) {
                    throw new InvalidArgumentException('topic_id required');
                }
                $this->repo->saveTopicNotesOnly($topicId, $data);
                $this->jsonOk(['topic_id' => $topicId]);
                break;

            case 'save_topic_mcq_text':
                $topicId = (int) ($data['topic_id'] ?? 0);
                if ($topicId < 1) {
                    throw new InvalidArgumentException('topic_id required');
                }
                $this->repo->saveTopicMcqTextOnly($topicId, (string) ($data['mcq_content'] ?? ''));
                $this->jsonOk(['topic_id' => $topicId]);
                break;

            case 'save_topic_exam_suite':
                $topicId = (int) ($data['topic_id'] ?? 0);
                if ($topicId < 1) {
                    throw new InvalidArgumentException('topic_id required');
                }
                $suites = $data['exam_suite'] ?? [];
                if (!is_array($suites)) {
                    $suites = [];
                }
                $this->repo->saveTopicExamSuiteOnly($topicId, $suites);
                $this->jsonOk(['topic_id' => $topicId]);
                break;

            case 'import_mcq_bank':
                $topicId = (int) ($data['topic_id'] ?? 0);
                $suiteKey = preg_replace('/[^a-z_]/', '', (string) ($data['suite_key'] ?? 'revision'));
                if ($topicId < 1 || $suiteKey === '') {
                    throw new InvalidArgumentException('topic_id and suite_key required');
                }
                require_once ACHARYA_ROOT . '/services/McqParserService.php';
                $parser = new McqParserService();
                $text = (string) ($data['mcq_content'] ?? '');
                $questions = $text !== '' ? $parser->parseFromText($text) : [];
                if ($questions === []) {
                    throw new InvalidArgumentException('No questions parsed from text.');
                }
                $suiteMeta = is_array($data['suite_meta'] ?? null) ? $data['suite_meta'] : null;
                $result = $this->repo->importMcqBankForTopicSuite($topicId, $suiteKey, $questions, $text, $suiteMeta);
                $this->jsonOk($result);
                break;

            case 'wizard_commit':
                $this->handleWizardCommit($data);
                break;

            default:
                $this->jsonError(400, 'Unknown action');
        }
    }

    /** @param array<string,mixed> $data */
    private function handleWizardCommit(array $data): void
    {
        $entity = preg_replace('/[^a-z_]/', '', (string) ($data['entity'] ?? ''));
        $orderRepo = new ContentOrderRepository();
        if (!$orderRepo->isValidEntity($entity)) {
            throw new InvalidArgumentException('Invalid sort entity');
        }

        $courseId = (int) ($data['course_id'] ?? 0);
        if ($courseId > 0) {
            $courseId = $this->repo->resolveContentManagerCourseId($courseId);
        }

        $ctx = [
            'course_id' => $courseId,
            'sub_course_id' => (int) ($data['sub_course_id'] ?? 0),
            'subject_id' => (int) ($data['subject_id'] ?? 0),
            'topic_id' => (int) ($data['topic_id'] ?? 0),
        ];

        $orderedIds = $data['ordered_ids'] ?? [];
        if (!is_array($orderedIds)) {
            $orderedIds = [];
        }
        if ($orderedIds === []) {
            $siblings = $orderRepo->siblings($entity, $ctx);
            $orderedIds = array_map(static fn (array $row): int => (int) ($row['id'] ?? 0), $siblings);
            $orderedIds = array_values(array_filter($orderedIds, static fn (int $id): bool => $id > 0));
        }

        $items = $orderedIds === [] ? [] : $orderRepo->reorderBatch($entity, $orderedIds, $ctx);
        $branch = $this->wizardBranchAfterCommit($entity, $ctx);

        $this->jsonOk([
            'items' => $items,
            'next_stage' => $branch['next_stage'],
            'has_sub_topics' => $branch['has_sub_topics'],
            'topic_id' => $branch['topic_id'],
            'skip_sub_topic' => $branch['skip_sub_topic'],
        ]);
    }

    /**
     * @param array<string,mixed> $ctx
     * @return array{next_stage:string,has_sub_topics:bool,topic_id:int,skip_sub_topic:bool}
     */
    private function wizardBranchAfterCommit(string $entity, array $ctx): array
    {
        $topicId = (int) ($ctx['topic_id'] ?? 0);

        return match ($entity) {
            'sub_course' => [
                'next_stage' => 'subject',
                'has_sub_topics' => false,
                'topic_id' => $topicId,
                'skip_sub_topic' => false,
            ],
            'subject' => [
                'next_stage' => 'topic',
                'has_sub_topics' => false,
                'topic_id' => $topicId,
                'skip_sub_topic' => false,
            ],
            'topic' => $this->wizardTopicBranch($topicId),
            'sub_topic' => [
                'next_stage' => 'notes_exam',
                'has_sub_topics' => true,
                'topic_id' => $topicId,
                'skip_sub_topic' => false,
            ],
            'exam_suite' => [
                'next_stage' => 'complete',
                'has_sub_topics' => false,
                'topic_id' => $topicId,
                'skip_sub_topic' => false,
            ],
            default => [
                'next_stage' => 'sub_course',
                'has_sub_topics' => false,
                'topic_id' => $topicId,
                'skip_sub_topic' => false,
            ],
        };
    }

    /** @return array{next_stage:string,has_sub_topics:bool,topic_id:int,skip_sub_topic:bool} */
    private function wizardTopicBranch(int $topicId): array
    {
        if ($topicId < 1) {
            return [
                'next_stage' => 'notes_exam',
                'has_sub_topics' => false,
                'topic_id' => 0,
                'skip_sub_topic' => true,
            ];
        }

        $topic = $this->repo->getTopicContentManager($topicId);
        $subs = $this->repo->listSubTopicsForTopic($topicId);
        $hasFlag = !empty($topic['has_sub_topics']);
        $hasRows = $subs !== [];

        if ($hasFlag || $hasRows) {
            return [
                'next_stage' => 'sub_topic',
                'has_sub_topics' => true,
                'topic_id' => $topicId,
                'skip_sub_topic' => false,
            ];
        }

        return [
            'next_stage' => 'notes_exam',
            'has_sub_topics' => false,
            'topic_id' => $topicId,
            'skip_sub_topic' => true,
        ];
    }

    private function handleMcqFileImport(): void
    {
        $topicId = (int) ($_POST['topic_id'] ?? 0);
        $suiteKey = preg_replace('/[^a-z_]/', '', (string) ($_POST['suite_key'] ?? 'revision'));
        if ($topicId < 1 || $suiteKey === '') {
            throw new InvalidArgumentException('topic_id and suite_key required');
        }
        if (empty($_FILES['mcq_file'])) {
            throw new InvalidArgumentException('mcq_file required');
        }
        require_once ACHARYA_ROOT . '/services/McqParserService.php';
        $parser = new McqParserService();
        $questions = $parser->parseUploadedFile($_FILES['mcq_file']);
        if ($questions === []) {
            throw new InvalidArgumentException('No questions found in uploaded file.');
        }
        $raw = null;
        $tmp = (string) ($_FILES['mcq_file']['tmp_name'] ?? '');
        $ext = strtolower(pathinfo((string) ($_FILES['mcq_file']['name'] ?? ''), PATHINFO_EXTENSION));
        if (in_array($ext, ['txt', 'csv', 'doc'], true) && $tmp !== '') {
            $raw = (string) file_get_contents($tmp);
        }
        $suiteMeta = [
            'custom_title' => trim((string) ($_POST['custom_title'] ?? '')),
            'custom_title_te' => trim((string) ($_POST['custom_title_te'] ?? '')),
            'is_required' => !empty($_POST['is_required']) ? 1 : 0,
        ];
        $result = $this->repo->importMcqBankForTopicSuite($topicId, $suiteKey, $questions, $raw, $suiteMeta);
        $this->jsonOk($result);
    }

    /** @param array<string,mixed> $payload */
    private function jsonOk(array $payload): never
    {
        echo json_encode(['ok' => true] + $payload, JSON_THROW_ON_ERROR);
        exit;
    }

    private function jsonError(int $code, string $message): never
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $message], JSON_THROW_ON_ERROR);
        exit;
    }
}
