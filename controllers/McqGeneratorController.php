<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/controllers/McqAuth.php';
require_once dirname(__DIR__) . '/models/PdfSegmentRepository.php';
require_once dirname(__DIR__) . '/models/ExaminerRepository.php';
require_once dirname(__DIR__) . '/models/AiApiSlotRepository.php';
require_once dirname(__DIR__) . '/models/QuestionsStagingRepository.php';
require_once dirname(__DIR__) . '/models/McqGenerationJobRepository.php';
require_once dirname(__DIR__) . '/services/McqAiGeneratorService.php';
require_once dirname(__DIR__) . '/services/McqDifficultyProfiles.php';
require_once dirname(__DIR__) . '/models/StSubjectRepository.php';

final class McqGeneratorController
{
    private PdfSegmentRepository $segments;
    private ExaminerRepository $examiners;
    private StSubjectRepository $catalogSubjects;
    private AiApiSlotRepository $slots;
    private QuestionsStagingRepository $staging;
    private McqGenerationJobRepository $jobs;
    private McqAiGeneratorService $generator;

    public function __construct()
    {
        $this->segments = new PdfSegmentRepository();
        $this->examiners = new ExaminerRepository();
        $this->slots = new AiApiSlotRepository();
        $this->staging = new QuestionsStagingRepository();
        $this->jobs = new McqGenerationJobRepository();
        $this->generator = new McqAiGeneratorService();
        $this->catalogSubjects = new StSubjectRepository();
    }

    public function handle(): void
    {
        $data = $this->readBody();
        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? ($data['action'] ?? ''));

        try {
            match ($action) {
                'bootstrap' => $this->bootstrap(),
                'segments_list' => $this->segmentsList(),
                'segment_save' => $this->segmentSave($data, true),
                'segment_delete' => $this->segmentDelete($data, true),
                'pdf_upload' => $this->pdfUpload(),
                'examiners_list' => $this->examinersList(),
                'examiner_save' => $this->examinerSave($data, true),
                'examiner_delete' => $this->examinerDelete($data, true),
                'api_slots_list' => $this->apiSlotsList(),
                'api_slots_save' => $this->apiSlotsSave($data, true),
                'job_start' => $this->jobStart($data, true),
                'job_chunk' => $this->jobChunk($data),
                'staging_list' => $this->stagingList(),
                'staging_update' => $this->stagingUpdate($data, true),
                'staging_examiner_approve' => $this->stagingExaminerApprove($data, true),
                'staging_super_deploy' => $this->stagingSuperDeploy($data, true),
                'sub_courses' => $this->subCourses(),
                'catalog_subjects_list' => $this->catalogSubjectsList(),
                'catalog_subject_save' => $this->catalogSubjectSave($data, true),
                'catalog_subject_delete' => $this->catalogSubjectDelete($data, true),
                default => $this->bad('Unknown action'),
            };
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    private function bootstrap(): void
    {
        McqAuth::requireMcqApi();
        echo json_encode([
            'ok' => true,
            'role' => McqAuth::isSuperAdmin() ? 'admin' : 'examiner',
            'assigned_subject' => McqAuth::assignedSubject(),
            'tables_ready' => PdfSegmentRepository::ready() && ExaminerRepository::ready(),
            'catalog_subjects' => $this->catalogSubjects->subjectNames(),
            'catalog_ready' => StSubjectRepository::ready(),
            'difficulty_scales' => McqDifficultyProfiles::listForUi(),
            'max_questions_per_page' => McqDifficultyProfiles::MAX_QUESTIONS_PER_PAGE,
            'batch_chunk_size' => McqDifficultyProfiles::BATCH_CHUNK_SIZE,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function catalogSubjectsList(): void
    {
        McqAuth::requireMcqApi();
        echo json_encode([
            'ok' => true,
            'items' => $this->catalogSubjects->listAllWithMappings(),
            'names' => $this->catalogSubjects->subjectNames(),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function catalogSubjectSave(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        if (!McqAuth::isSuperAdmin()) {
            throw new RuntimeException('Admin only');
        }
        $name = trim((string) ($data['subject_name'] ?? ($data['name'] ?? '')));
        $subCourseIds = array_map('intval', (array) ($data['sub_course_ids'] ?? []));
        $id = $this->catalogSubjects->create($name, $subCourseIds);
        echo json_encode(['ok' => true, 'id' => $id], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function catalogSubjectDelete(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        if (!McqAuth::isSuperAdmin()) {
            throw new RuntimeException('Admin only');
        }
        $this->catalogSubjects->delete((int) ($data['id'] ?? 0));
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    private function segmentsList(): void
    {
        McqAuth::requireMcqApi();
        $sub = McqAuth::subjectFilterForUser();
        $scId = (int) ($_GET['sub_course_id'] ?? 0);
        echo json_encode([
            'ok' => true,
            'items' => $this->segments->listForSubject($sub, $scId > 0 ? $scId : null),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function segmentSave(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        $seg = $data['segment'] ?? $data;
        if (!is_array($seg)) {
            throw new InvalidArgumentException('segment required');
        }
        if (McqAuth::assignedSubject()) {
            $seg['assigned_subject'] = McqAuth::assignedSubject();
        }
        $id = !empty($seg['id']) ? (int) $seg['id'] : null;
        $newId = $this->segments->save($seg, $id);
        echo json_encode(['ok' => true, 'id' => $newId], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function segmentDelete(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        $this->segments->delete((int) ($data['id'] ?? 0));
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    private function pdfUpload(): void
    {
        McqAuth::requireMcqApi(true, $_POST);
        $cfg = require dirname(__DIR__) . '/config/mcq_ai.php';
        $dir = (string) $cfg['storage_pdf'];
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        if (!isset($_FILES['pdf']) || !is_array($_FILES['pdf'])) {
            throw new InvalidArgumentException('PDF field missing — use form field name "pdf"');
        }
        $file = $_FILES['pdf'];
        $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($err === UPLOAD_ERR_NO_FILE) {
            throw new InvalidArgumentException('No PDF file selected');
        }
        if ($err !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('Upload error code ' . $err . ' (check upload_max_filesize / post_max_size)');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new InvalidArgumentException('Invalid upload temp file');
        }
        $name = preg_replace('/[^a-zA-Z0-9._-]+/', '_', (string) ($file['name'] ?? 'book.pdf'));
        if ($name === '' || $name === '_') {
            $name = 'book.pdf';
        }
        $dest = $dir . '/' . date('Ymd_His') . '_' . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new RuntimeException('Could not store PDF');
        }
        $rel = 'storage/mcq_pdfs/' . basename($dest);
        echo json_encode([
            'ok' => true,
            'storage_path' => $dest,
            'pdf_name' => $name,
            'relative' => $rel,
        ], JSON_UNESCAPED_UNICODE);
    }

    private function examinersList(): void
    {
        McqAuth::requireMcqApi();
        if (!McqAuth::canManageExaminers()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Admin only']);
            return;
        }
        echo json_encode(['ok' => true, 'items' => $this->examiners->listAll()], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function examinerSave(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        if (!McqAuth::canManageExaminers()) {
            throw new RuntimeException('Forbidden');
        }
        $ex = $data['examiner'] ?? $data;
        if (!is_array($ex)) {
            throw new InvalidArgumentException('examiner required');
        }
        $id = !empty($ex['id']) ? (int) $ex['id'] : null;
        echo json_encode(['ok' => true, 'id' => $this->examiners->save($ex, $id)], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function examinerDelete(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        if (!McqAuth::canManageExaminers()) {
            throw new RuntimeException('Forbidden');
        }
        $this->examiners->delete((int) ($data['id'] ?? 0));
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    private function apiSlotsList(): void
    {
        McqAuth::requireMcqApi();
        if (!McqAuth::isSuperAdmin()) {
            throw new RuntimeException('Admin only');
        }
        echo json_encode(['ok' => true, 'slots' => $this->slots->listSlots()], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function apiSlotsSave(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        if (!McqAuth::isSuperAdmin()) {
            throw new RuntimeException('Admin only');
        }
        $slots = $data['slots'] ?? [];
        if (!is_array($slots)) {
            throw new InvalidArgumentException('slots array required');
        }
        $this->slots->saveSlots($slots);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function jobStart(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        $segmentId = (int) ($data['segment_id'] ?? 0);
        $segment = $this->segments->find($segmentId);
        if (!$segment) {
            throw new InvalidArgumentException('Invalid segment');
        }
        $subFilter = McqAuth::subjectFilterForUser();
        if ($subFilter !== null && strcasecmp($subFilter, (string) $segment['assigned_subject']) !== 0) {
            throw new RuntimeException('Subject access denied');
        }
        $pages = max(1, (int) $segment['end_page'] - (int) $segment['start_page'] + 1);
        $slot = $this->slots->activeSlot();
        $jobId = $this->jobs->create([
            'segment_id' => $segmentId,
            'api_slot_id' => $slot['id'] ?? null,
            'subject_key' => $segment['assigned_subject'],
            'difficulty_scale' => McqDifficultyProfiles::normalize((string) ($data['difficulty_scale'] ?? 'SGT')),
            'language_mode' => $data['language_mode'] ?? 'bilingual_en_te',
            'total_pages' => $pages,
            'questions_per_page' => McqDifficultyProfiles::normalizeQuestionCount((int) ($data['questions_per_page'] ?? 3)),
            'excel_mapping' => $data['excel_mapping'] ?? null,
            'created_by' => admin_user()['email'] ?? null,
        ]);
        echo json_encode(['ok' => true, 'job_id' => $jobId, 'total_pages' => $pages], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function jobChunk(array $data): void
    {
        McqAuth::requireMcqApi();
        $jobId = (int) ($data['job_id'] ?? $_GET['job_id'] ?? 0);
        $slot = $this->slots->activeSlot();
        if (!$slot || trim((string) ($slot['api_key'] ?? '')) === '') {
            throw new RuntimeException('Configure and activate an API slot with a valid key');
        }
        $result = $this->generator->processPageChunk($jobId, $slot);
        echo json_encode(['ok' => $result['ok'], 'progress' => $result], JSON_UNESCAPED_UNICODE);
    }

    private function stagingList(): void
    {
        McqAuth::requireMcqApi();
        $status = (string) ($_GET['status'] ?? QuestionsStagingRepository::STATUS_RAW);
        $sub = McqAuth::subjectFilterForUser() ?? (string) ($_GET['subject'] ?? '');
        echo json_encode([
            'ok' => true,
            'items' => $this->staging->listByStatus($status, $sub !== '' ? $sub : null),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function stagingUpdate(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        $id = (int) ($data['id'] ?? 0);
        $patch = $data['patch'] ?? $data;
        if (!is_array($patch)) {
            throw new InvalidArgumentException('patch required');
        }
        $this->staging->update($id, $patch);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function stagingExaminerApprove(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        $ids = array_map('intval', (array) ($data['ids'] ?? []));
        $n = $this->staging->examinerApprove($ids, McqAuth::examinerId() ?: (int) (admin_user()['id'] ?? 0));
        echo json_encode(['ok' => true, 'approved' => $n], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function stagingSuperDeploy(array $data, bool $csrf): void
    {
        McqAuth::requireMcqApi($csrf, $data);
        if (!McqAuth::canSuperApprove()) {
            throw new RuntimeException('Super-admin only');
        }
        $testId = (int) ($data['test_id'] ?? 0);
        $ids = array_map('intval', (array) ($data['ids'] ?? []));
        $n = $this->staging->deployToTest($testId, $ids);
        echo json_encode(['ok' => true, 'deployed' => $n], JSON_UNESCAPED_UNICODE);
    }

    private function subCourses(): void
    {
        McqAuth::requireMcqApi();
        $rows = db()->query(
            'SELECT sc.id, sc.name, sc.name_te, c.name AS course_name
             FROM sub_courses sc INNER JOIN courses c ON c.id = sc.course_id
             ORDER BY c.name, sc.name'
        )->fetchAll() ?: [];
        echo json_encode(['ok' => true, 'items' => $rows], JSON_UNESCAPED_UNICODE);
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

    private function bad(string $msg): void
    {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    }
}
