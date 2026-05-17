<?php

declare(strict_types=1);

final class ExamManagerController
{
    private ExamManagerRepository $repo;

    public function __construct()
    {
        $this->repo = new ExamManagerRepository();
    }

    public function handle(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        AdminAuthController::requireAdminApi(false);

        $data = $this->readBody();
        $action = (string) ($_GET['action'] ?? $_POST['action'] ?? ($data['action'] ?? ''));

        try {
            match ($action) {
                'list' => $this->list(),
                'pool_mcqs' => $this->poolMcqs($data),
                'save_exam' => $this->saveExam($data),
                'patch_type' => $this->patchType($data),
                'questions' => $this->questions($data),
                'update_question' => $this->updateQuestion($data),
                'delete_question' => $this->deleteQuestion($data),
                'delete_exam' => $this->deleteExam($data),
                default => $this->error('Unknown action', 400),
            };
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage(), 400);
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

    private function list(): void
    {
        echo json_encode([
            'ok' => true,
            'data' => $this->repo->listTestsGrid(),
            'exam_types' => ExamManagerRepository::EXAM_TYPES,
            'negative_rates' => ExamManagerRepository::NEGATIVE_RATES,
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function poolMcqs(array $data): void
    {
        $subjectId = (int) ($data['subject_id'] ?? $_GET['subject_id'] ?? 0);
        $topicId = (int) ($data['topic_id'] ?? $_GET['topic_id'] ?? 0);
        $search = trim((string) ($data['q'] ?? $_GET['q'] ?? ''));
        if ($subjectId < 1) {
            throw new InvalidArgumentException('subject_id required');
        }
        echo json_encode([
            'ok' => true,
            'items' => $this->repo->poolMcqs($subjectId, $topicId > 0 ? $topicId : null, $search),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function saveExam(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $id = !empty($data['id']) ? (int) $data['id'] : null;
        $testId = $this->repo->saveExam($data, $id);
        echo json_encode([
            'ok' => true,
            'test_id' => $testId,
            'message' => 'పరీక్ష సేవ్ అయ్యింది',
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function patchType(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $testId = (int) ($data['test_id'] ?? 0);
        $type = (string) ($data['test_type'] ?? '');
        if ($testId < 1 || $type === '') {
            throw new InvalidArgumentException('test_id and test_type required');
        }
        $this->repo->patchTestType($testId, $type);
        echo json_encode(['ok' => true, 'test_type' => ExamManagerRepository::normalizeTestType($type)], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function questions(array $data): void
    {
        $testId = (int) ($data['test_id'] ?? $_GET['test_id'] ?? 0);
        if ($testId < 1) {
            throw new InvalidArgumentException('test_id required');
        }
        $test = $this->repo->testRow($testId);
        echo json_encode([
            'ok' => true,
            'test' => $test,
            'questions' => $this->repo->questionsForTestAdmin($testId),
        ], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function updateQuestion(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $qid = (int) ($data['id'] ?? 0);
        if ($qid < 1) {
            throw new InvalidArgumentException('id required');
        }
        $this->repo->updateQuestion($qid, $data);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function deleteQuestion(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $qid = (int) ($data['id'] ?? 0);
        if ($qid < 1) {
            throw new InvalidArgumentException('id required');
        }
        $this->repo->deleteQuestion($qid);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    /** @param array<string,mixed> $data */
    private function deleteExam(array $data): void
    {
        AdminAuthController::verifyCsrf((string) ($data['_csrf'] ?? ''));
        $testId = (int) ($data['id'] ?? 0);
        if ($testId < 1) {
            throw new InvalidArgumentException('id required');
        }
        $this->repo->deleteTest($testId);
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    private function error(string $msg, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
    }
}
