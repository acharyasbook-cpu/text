<?php

declare(strict_types=1);

final class PricingAdminController
{
    private PricingAdminRepository $repo;

    /** @var array<string,mixed>|null */
    private ?array $jsonPostBody = null;

    public function __construct()
    {
        $this->repo = new PricingAdminRepository();
    }

    /** @return array<string,mixed> */
    private function jsonPostBody(): array
    {
        if ($this->jsonPostBody !== null) {
            return $this->jsonPostBody;
        }
        $this->jsonPostBody = [];
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            return $this->jsonPostBody;
        }
        $ct = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
        if (!str_contains($ct, 'application/json')) {
            return $this->jsonPostBody;
        }
        $raw = file_get_contents('php://input');
        $decoded = json_decode($raw ?: '{}', true);
        $this->jsonPostBody = is_array($decoded) ? $decoded : [];

        return $this->jsonPostBody;
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        AdminAuthController::requireAdminApi(false);

        $json = $this->jsonPostBody();
        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? ($json['action'] ?? 'matrix'));
        try {
            match ($action) {
                'matrix', 'subscription_matrix' => $this->matrix(),
                'save', 'save_subscriptions' => $this->save(),
                'coupons_list' => $this->couponsList(),
                'coupon_save' => $this->couponSave(),
                'coupon_delete' => $this->couponDelete(),
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
        $body = $this->jsonPostBody();
        if ($body === []) {
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

    private function couponsList(): void
    {
        $repo = new CouponRepository();
        echo json_encode([
            'ok' => true,
            'coupons' => $repo->listAll(),
            'sub_courses' => $repo->subCoursesForSelect(),
            'table_ready' => CouponRepository::tableReady(),
            'usage_logs_ready' => CouponRepository::usageLogsReady(),
        ], JSON_UNESCAPED_UNICODE);
    }

    private function couponSave(): void
    {
        $body = $this->jsonPostBody();
        if ($body === []) {
            $this->bad('Invalid JSON');
            return;
        }
        AdminAuthController::verifyCsrf((string) ($body['_csrf'] ?? ''));
        if (!CouponRepository::tableReady()) {
            $this->bad('Run: php database/migrate_st_coupons.php');
            return;
        }
        $c = $body['coupon'] ?? null;
        if (!is_array($c)) {
            $this->bad('coupon object required');
            return;
        }
        $id = isset($c['id']) && (int) $c['id'] > 0 ? (int) $c['id'] : null;
        try {
            (new CouponRepository())->save($c, $id);
        } catch (Throwable $e) {
            $this->bad($e->getMessage());
            return;
        }
        echo json_encode(['ok' => true, 'message' => 'Coupon saved'], JSON_UNESCAPED_UNICODE);
    }

    private function couponDelete(): void
    {
        $body = $this->jsonPostBody();
        if ($body === []) {
            $this->bad('Invalid JSON');
            return;
        }
        AdminAuthController::verifyCsrf((string) ($body['_csrf'] ?? ''));
        $id = (int) ($body['id'] ?? 0);
        if ($id < 1) {
            $this->bad('id required');
            return;
        }
        (new CouponRepository())->delete($id);
        echo json_encode(['ok' => true, 'message' => 'Coupon deleted'], JSON_UNESCAPED_UNICODE);
    }

    private function bad(string $msg): void
    {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $msg]);
    }
}
