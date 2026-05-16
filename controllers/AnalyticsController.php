<?php

declare(strict_types=1);

final class AnalyticsController
{
    private AnalyticsRepository $repo;

    public function __construct()
    {
        $this->repo = new AnalyticsRepository();
    }

    public function handle(): void
    {
        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? 'overview');
        if ($action === 'export_csv') {
            AdminAuthController::requireAdminApi(false);
            $this->exportCsv();

            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        AdminAuthController::requireAdminApi(false);

        try {
            match ($action) {
                'overview' => $this->overview(),
                'students' => $this->students(),
                'student_profile' => $this->studentProfile(),
                default => $this->bad('Unknown action'),
            };
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    private function overview(): void
    {
        $from = $this->dateParam('from');
        $to = $this->dateParam('to');
        echo json_encode(['ok' => true, 'data' => $this->repo->platformOverview($from, $to)], JSON_UNESCAPED_UNICODE);
    }

    private function students(): void
    {
        $from = $this->dateParam('from');
        $to = $this->dateParam('to');
        $q = trim((string) ($_GET['q'] ?? ''));
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(10, min(100, (int) ($_GET['limit'] ?? 50)));
        $offset = ($page - 1) * $limit;

        $result = $this->repo->searchStudents($q !== '' ? $q : null, $from, $to, $limit, $offset);
        echo json_encode([
            'ok' => true,
            'data' => $result['items'],
            'meta' => ['total' => $result['total'], 'page' => $page, 'limit' => $limit],
        ], JSON_UNESCAPED_UNICODE);
    }

    private function studentProfile(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $profile = $this->repo->studentProfile($id);
        if (!$profile) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'error' => 'Student not found']);
            return;
        }
        echo json_encode(['ok' => true, 'data' => $profile], JSON_UNESCAPED_UNICODE);
    }

    private function exportCsv(): void
    {
        $from = $this->dateParam('from');
        $to = $this->dateParam('to');
        $q = trim((string) ($_GET['q'] ?? ''));
        $rows = $this->repo->exportStudentRows($q !== '' ? $q : null, $from, $to);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="acharya-analytics-students-' . date('Y-m-d') . '.csv"');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            throw new RuntimeException('Cannot open output stream');
        }
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Registered', 'Last Active', 'Paid', 'Exams', 'Avg Score %']);
        foreach ($rows as $r) {
            fputcsv($out, [
                $r['id'] ?? '',
                $r['name'] ?? '',
                $r['email'] ?? '',
                $r['phone'] ?? '',
                $r['created_at'] ?? '',
                $r['last_active_at'] ?? '',
                !empty($r['is_paid']) ? 'Yes' : 'No',
                $r['exams_taken'] ?? 0,
                $r['avg_score_pct'] ?? '',
            ]);
        }
        fclose($out);
        exit;
    }

    private function dateParam(string $key): ?string
    {
        $v = trim((string) ($_GET[$key] ?? ''));
        if ($v === '') {
            return null;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $v);

        return ($dt && $dt->format('Y-m-d') === $v) ? $v : null;
    }

    private function bad(string $msg): void
    {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $msg]);
    }
}
