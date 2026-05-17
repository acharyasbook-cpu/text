<?php

declare(strict_types=1);

final class PricingAdminController
{
    private PricingAdminRepository $repo;

    public function __construct()
    {
        $this->repo = new PricingAdminRepository();
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        AdminAuthController::requireAdminApi(false);

        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'matrix');
        try {
            match ($action) {
                'matrix', 'subscription_matrix' => $this->matrix(),
                'save', 'save_subscriptions' => $this->save(),
                default => $this->bad('Unknown action'),
            };
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    private function matrix(): void
    {
        echo json_encode([
            'ok' => true,
            'data' => $this->repo->subscriptionPricingMatrix(),
            'plan_defaults' => PricingAdminRepository::PLAN_DEFAULTS,
            'tables_ready' => SchemaHelper::hasTable('sub_course_plans'),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function save(): void
    {
        $raw = file_get_contents('php://input');
        $body = json_decode($raw ?: '{}', true);
        if (!is_array($body)) {
            $this->bad('Invalid JSON');
            return;
        }
        AdminAuthController::verifyCsrf((string) ($body['_csrf'] ?? ''));
        $rows = $body['rows'] ?? [];
        if (!is_array($rows)) {
            $this->bad('rows required');
            return;
        }
        $this->repo->saveSubscriptionPlans($rows);
        echo json_encode(['ok' => true, 'message' => 'Subscription pricing saved'], JSON_UNESCAPED_UNICODE);
    }

    private function bad(string $msg): void
    {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $msg]);
    }
}
