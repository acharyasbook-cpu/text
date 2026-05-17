<?php

declare(strict_types=1);

final class WhatsAppMobileGatewayController
{
    private WhatsAppMobileGatewayService $gateway;

    private WhatsAppHubRepository $hub;

    public function __construct()
    {
        $this->gateway = new WhatsAppMobileGatewayService();
        $this->hub = new WhatsAppHubRepository();
    }

    public function dispatch(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $data = $this->readBody();
        $action = (string) ($_GET['action'] ?? $data['action'] ?? '');

        try {
            if ($method === 'GET' && ($action === '' || $action === 'config')) {
                AdminAuthController::requireAdminApi(false);
                $this->config();

                return;
            }

            if ($method === 'GET' && $action === 'build_url') {
                AdminAuthController::requireAdminApi(false);
                $this->buildUrl($data);

                return;
            }

            if ($method === 'POST') {
                AdminAuthController::requireAdminApi(true, $data);
                match ($action) {
                    'trigger', 'fire' => $this->trigger($data),
                    'trigger_sub_course' => $this->triggerSubCourse($data),
                    default => $this->error('Unknown action', 400),
                };

                return;
            }

            $this->error('GET (config|build_url) or POST (trigger) required', 405);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), 400);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
    }

    private function config(): void
    {
        echo json_encode([
            'ok' => true,
            'webhook_base' => WhatsAppMobileGatewayService::WEBHOOK_BASE,
            'query_format' => '?group={group_name}&message={message_body}',
            'example' => WhatsAppMobileGatewayService::WEBHOOK_BASE
                . '?group=' . rawurlencode('AP TET Telugu')
                . '&message=' . rawurlencode('ఆచార్య బుక్స్ — నేటి షెడ్యూల్'),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function buildUrl(array $data): void
    {
        [$group, $message] = $this->resolveGroupAndMessage($data);
        echo json_encode([
            'ok' => true,
            'trigger_url' => $this->gateway->buildTriggerUrl($group, $message),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function trigger(array $data): void
    {
        [$group, $message] = $this->resolveGroupAndMessage($data);
        $result = $this->gateway->fireTrigger($group, $message);
        $ok = !empty($result['success']);
        http_response_code($ok ? 200 : 502);
        echo json_encode([
            'ok' => $ok,
            'trigger_url' => $result['trigger_url'],
            'http_code' => $result['http_code'],
            'response_body' => $result['response_body'] ?? '',
            'error' => $result['error'] ?? null,
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function triggerSubCourse(array $data): void
    {
        if (!WhatsAppDispatchService::columnReady()) {
            throw new RuntimeException('Run php database/migrate_whatsapp_sub_course_groups.php');
        }
        $scid = (int) ($data['sub_course_id'] ?? 0);
        if ($scid < 1) {
            throw new InvalidArgumentException('sub_course_id required');
        }
        $row = $this->hub->subCourseRow($scid);
        if (!$row) {
            throw new InvalidArgumentException('Sub-course not found');
        }

        $group = trim((string) ($data['group_name'] ?? ''));
        if ($group === '') {
            $group = $this->gateway->groupNameFromSubCourse($row);
        }

        $message = trim((string) ($data['message'] ?? ''));
        if ($message === '' && !empty($data['compose_from_dispatch'])) {
            $dispatch = new WhatsAppDispatchService();
            $attachments = is_array($data['attachments'] ?? null) ? $data['attachments'] : [];
            $plan = $dispatch->buildDispatchPlan($row, '', $attachments);
            $message = (string) ($plan['message'] ?? '');
        }
        if ($message === '') {
            throw new InvalidArgumentException('message_body is required');
        }

        $result = $this->gateway->fireTrigger($group, $message);
        $ok = !empty($result['success']);
        http_response_code($ok ? 200 : 502);
        echo json_encode([
            'ok' => $ok,
            'group_name' => $group,
            'trigger_url' => $result['trigger_url'],
            'http_code' => $result['http_code'],
            'response_body' => $result['response_body'] ?? '',
            'error' => $result['error'] ?? null,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param array<string,mixed> $data
     * @return array{0:string,1:string}
     */
    private function resolveGroupAndMessage(array $data): array
    {
        $group = trim((string) ($data['group_name'] ?? $data['group'] ?? ''));
        $message = trim((string) ($data['message_body'] ?? $data['message'] ?? ''));
        if ($group === '') {
            throw new InvalidArgumentException('group_name (or group) is required');
        }
        if ($message === '') {
            throw new InvalidArgumentException('message_body (or message) is required');
        }

        return [$group, $message];
    }

    /** @return array<string,mixed> */
    private function readBody(): array
    {
        if (!empty($_POST['action'])) {
            return $_POST;
        }
        $raw = file_get_contents('php://input') ?: '';
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function error(string $msg, int $code): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    }
}
