<?php

declare(strict_types=1);

final class WhatsAppHubController
{
    public function __construct(
        private WhatsAppHubRepository $repo = new WhatsAppHubRepository(),
        private WhatsAppDispatchService $dispatch = new WhatsAppDispatchService(),
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

        if ($method === 'POST' && !empty($_FILES['dispatch_file'])) {
            AdminAuthController::requireAdminApi(true, $_POST);
            $this->handleUpload();

            return;
        }

        if ($method !== 'POST') {
            $this->jsonError(405, 'POST or GET required');

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
        if (!WhatsAppDispatchService::columnReady()) {
            $this->jsonOk([
                'migration_required' => true,
                'main_courses' => [],
                'sub_courses' => [],
            ]);

            return;
        }

        $action = $_GET['action'] ?? 'bootstrap';
        switch ($action) {
            case 'bootstrap':
                $courseId = (int) ($_GET['course_id'] ?? 0);
                $this->jsonOk([
                    'migration_required' => false,
                    'main_courses' => $this->repo->mainCoursesForPicker(),
                    'sub_courses' => $this->repo->subCoursesForHub($courseId > 0 ? $courseId : null),
                ]);
                break;
            case 'sub_courses':
                $courseId = (int) ($_GET['course_id'] ?? 0);
                $this->jsonOk(['items' => $this->repo->subCoursesForHub($courseId > 0 ? $courseId : null)]);
                break;
            case 'sub_course':
                $scid = (int) ($_GET['sub_course_id'] ?? 0);
                $row = $this->repo->subCourseRow($scid);
                if (!$row) {
                    $this->jsonError(404, 'Sub-course not found');
                    break;
                }
                $this->jsonOk(['item' => $row]);
                break;
            default:
                $this->jsonError(400, 'Unknown action');
        }
    }

    /** @param array<string,mixed> $data */
    private function handlePost(array $data): void
    {
        if (!WhatsAppDispatchService::columnReady()) {
            $this->jsonError(503, 'Run php database/migrate_whatsapp_sub_course_groups.php');

            return;
        }

        $action = (string) ($data['action'] ?? '');
        switch ($action) {
            case 'save_group_link':
                $scid = (int) ($data['sub_course_id'] ?? 0);
                $link = (string) ($data['whatsapp_group_link'] ?? '');
                $this->repo->saveGroupLink($scid, $link);
                $row = $this->repo->subCourseRow($scid);
                $this->jsonOk([
                    'item' => $row,
                    'invite_token' => $row ? WhatsAppDispatchService::extractInviteToken((string) ($row['whatsapp_group_link'] ?? '')) : null,
                ]);
                break;

            case 'prepare_dispatch':
                $scid = (int) ($data['sub_course_id'] ?? 0);
                $message = (string) ($data['message'] ?? '');
                $attachments = is_array($data['attachments'] ?? null) ? $data['attachments'] : [];
                $row = $this->repo->subCourseRow($scid);
                if (!$row) {
                    $this->jsonError(404, 'Sub-course not found');
                    break;
                }
                $plan = $this->dispatch->buildDispatchPlan($row, $message, $attachments);
                $this->jsonOk(['dispatch' => $plan]);
                break;

            default:
                $this->jsonError(400, 'Unknown action');
        }
    }

    private function handleUpload(): void
    {
        if (!WhatsAppDispatchService::columnReady()) {
            $this->jsonError(503, 'Migration required');

            return;
        }

        $file = $_FILES['dispatch_file'] ?? null;
        if (!is_array($file)) {
            $this->jsonError(400, 'dispatch_file required');

            return;
        }

        $meta = $this->dispatch->storeDispatchUpload($file);
        $this->jsonOk(['attachment' => $meta]);
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
