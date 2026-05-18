<?php

declare(strict_types=1);

final class ExamManagerRepository
{
    /** @var array<string,array{te:string,en:string,bundle:bool}> */
    public const EXAM_TYPES = [
        'topic' => ['te' => 'టాపిక్ టెస్ట్', 'en' => 'Topic Test', 'bundle' => false],
        'revision' => ['te' => 'రివిజన్ టెస్ట్', 'en' => 'Revision Test', 'bundle' => true],
        'sub_grand' => ['te' => 'సబ్ గ్రాండ్ టెస్ట్', 'en' => 'Sub Grand Test', 'bundle' => true],
        'grand' => ['te' => 'గ్రాండ్ టెస్ట్', 'en' => 'Grand Test', 'bundle' => true],
    ];

    /** @var list<float> */
    public const NEGATIVE_RATES = [0.0, 0.25, 0.33, 0.5];

    public static function normalizeTestType(string $type): string
    {
        $type = strtolower(trim($type));
        if ($type === 'division') {
            return 'sub_grand';
        }

        return array_key_exists($type, self::EXAM_TYPES) ? $type : 'topic';
    }

    public static function typeLabelTe(string $type): string
    {
        $type = self::normalizeTestType($type);

        return self::EXAM_TYPES[$type]['te'] ?? $type;
    }

    /** @return list<array<string,mixed>> */
    public function listTestsGrid(): array
    {
        $rows = (new AdminRepository())->allTests();
        foreach ($rows as &$row) {
            $row['test_type'] = self::normalizeTestType((string) ($row['test_type'] ?? 'topic'));
            $row['duration_mins'] = (int) ($row['duration_mins'] ?? 0);
            $row['is_unlimited_time'] = $row['duration_mins'] <= 0 ? 1 : 0;
            $row['negative_marking'] = (float) ($row['negative_marking'] ?? 0);
            $row['negative_enabled'] = $row['negative_marking'] > 0 ? 1 : 0;
        }
        unset($row);

        return $rows;
    }

    /**
     * MCQ pool for subject/topic (questions already in bank under same scope).
     *
     * @return list<array<string,mixed>>
     */
    public function poolMcqs(?int $subjectId, ?int $topicId, string $search = '', int $limit = 200): array
    {
        if ($subjectId === null || $subjectId < 1) {
            return [];
        }

        $tpTbl = SchemaHelper::topicsTable();
        $where = ['t.subject_id = ?'];
        $params = [$subjectId];

        if ($topicId !== null && $topicId > 0) {
            $where[] = '(t.topic_id = ? OR tp.id = ?)';
            $params[] = $topicId;
            $params[] = $topicId;
        }

        $q = trim($search);
        if ($q !== '') {
            $where[] = '(tq.question_text LIKE ? OR tq.question_text_te LIKE ? OR tq.topic_tag LIKE ?)';
            $like = '%' . $q . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $topicJoin = SchemaHelper::columnExists('tests', 'topic_id')
            ? "LEFT JOIN `{$tpTbl}` tp ON tp.id = t.topic_id" : 'LEFT JOIN `' . $tpTbl . '` tp ON 1=0';

        $sql = "SELECT tq.id, tq.question_order, tq.question_text, tq.question_text_te,
                       tq.correct_option, tq.marks, tq.topic_tag,
                       t.id AS source_test_id, t.title AS source_test_title, t.test_type AS source_test_type,
                       tp.title AS topic_title
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

    /** @param array<string,mixed> $data */
    public function saveExam(array $data, ?int $id = null): int
    {
        $unlimited = !empty($data['unlimited_time']);
        $duration = $unlimited ? 0 : max(1, (int) ($data['duration_mins'] ?? 60));
        $negEnabled = !empty($data['negative_enabled']);
        $negRate = $negEnabled ? (float) ($data['negative_marking'] ?? 0.25) : 0.0;
        if ($negEnabled) {
            $allowed = false;
            foreach (self::NEGATIVE_RATES as $r) {
                if ($r > 0 && abs($r - $negRate) < 0.001) {
                    $allowed = true;
                    break;
                }
            }
            if (!$allowed) {
                $negRate = 0.25;
            }
        }

        $testType = self::normalizeTestType((string) ($data['test_type'] ?? 'topic'));
        $payload = [
            'course_id' => (int) ($data['course_id'] ?? 0),
            'subject_id' => !empty($data['subject_id']) ? (int) $data['subject_id'] : null,
            'topic_id' => !empty($data['topic_id']) ? (int) $data['topic_id'] : null,
            'slug' => (string) ($data['slug'] ?? ''),
            'title' => trim((string) ($data['title'] ?? '')),
            'title_te' => trim((string) ($data['title_te'] ?? '')),
            'test_type' => $testType,
            'division_label' => trim((string) ($data['division_label'] ?? '')) ?: null,
            'duration_mins' => $duration,
            'total_questions' => (int) ($data['total_questions'] ?? 50),
            'total_marks' => (int) ($data['total_marks'] ?? 50),
            'passing_marks' => (int) ($data['passing_marks'] ?? 25),
            'negative_marking' => $negRate,
            'package_id' => null,
            'is_active' => !empty($data['is_active']) ? 1 : 0,
            'component_test_ids' => $data['component_test_ids'] ?? [],
        ];

        if ($payload['slug'] === '') {
            $payload['slug'] = slugify($payload['title']);
        }

        $repo = new AdminRepository();
        if ($testType === 'topic' && !empty($data['topic_id'])) {
            $payload['topic_id'] = (int) $data['topic_id'];
        }

        $testId = $repo->saveTest($payload, $id);

        $poolIds = $data['pool_question_ids'] ?? [];
        if (is_array($poolIds) && $poolIds !== []) {
            $this->cloneQuestionsToTest($testId, array_map('intval', $poolIds));
        }

        $external = trim((string) ($data['external_mcq_text'] ?? ''));
        if ($external !== '') {
            $this->importExternalMcqText($testId, $external);
        }

        $this->syncTestTotals($testId);

        return $testId;
    }

    /** @param list<int> $sourceQuestionIds */
    public function cloneQuestionsToTest(int $testId, array $sourceQuestionIds): int
    {
        if ($testId < 1 || $sourceQuestionIds === []) {
            return 0;
        }

        $repo = new AdminRepository();
        $order = (int) db()->query('SELECT COALESCE(MAX(question_order),0) FROM test_questions WHERE test_id=' . (int) $testId)->fetchColumn();
        $imported = 0;

        foreach (array_unique($sourceQuestionIds) as $qid) {
            $qid = (int) $qid;
            if ($qid < 1) {
                continue;
            }
            $st = db()->prepare('SELECT * FROM test_questions WHERE id=? LIMIT 1');
            $st->execute([$qid]);
            $row = $st->fetch();
            if (!$row) {
                continue;
            }
            ++$order;
            $repo->saveQuestion([
                'test_id' => $testId,
                'question_order' => $order,
                'question_text' => $row['question_text'],
                'question_text_te' => $row['question_text_te'],
                'option_a' => $row['option_a'],
                'option_b' => $row['option_b'],
                'option_c' => $row['option_c'],
                'option_d' => $row['option_d'],
                'correct_option' => $row['correct_option'],
                'explanation' => $row['explanation'],
                'marks' => (int) ($row['marks'] ?? 1),
                'topic_tag' => $row['topic_tag'],
            ]);
            ++$imported;
        }

        return $imported;
    }

    public function importExternalMcqText(int $testId, string $text): int
    {
        require_once dirname(__DIR__) . '/services/McqParserService.php';
        $parser = new McqParserService();
        $parsed = $parser->parseFromText($text);
        if ($parsed === []) {
            return 0;
        }

        $repo = new AdminRepository();
        $order = (int) db()->query('SELECT COALESCE(MAX(question_order),0) FROM test_questions WHERE test_id=' . (int) $testId)->fetchColumn();
        $n = 0;
        foreach ($parsed as $q) {
            $letter = strtoupper(substr((string) ($q['correct_option'] ?? 'A'), 0, 1));
            if (!in_array($letter, ['A', 'B', 'C', 'D'], true)) {
                continue;
            }
            ++$order;
            $repo->saveQuestion([
                'test_id' => $testId,
                'question_order' => $order,
                'question_text' => (string) ($q['question_text'] ?? ''),
                'question_text_te' => null,
                'option_a' => (string) ($q['option_a'] ?? ''),
                'option_b' => (string) ($q['option_b'] ?? ''),
                'option_c' => (string) ($q['option_c'] ?? ''),
                'option_d' => (string) ($q['option_d'] ?? ''),
                'correct_option' => $letter,
                'explanation' => null,
                'marks' => 1,
                'topic_tag' => null,
            ]);
            ++$n;
        }

        return $n;
    }

    public function patchTestType(int $testId, string $testType): void
    {
        if ($testId < 1) {
            return;
        }
        $testType = self::normalizeTestType($testType);
        db()->prepare('UPDATE tests SET test_type=? WHERE id=?')->execute([$testType, $testId]);
        if ($testType !== 'topic' && SchemaHelper::columnExists('tests', 'topic_id')) {
            db()->prepare('UPDATE tests SET topic_id=NULL WHERE id=?')->execute([$testId]);
        }
    }

    /** @return list<array<string,mixed>> */
    public function questionsForTestAdmin(int $testId): array
    {
        return (new AdminRepository())->questionsForTest($testId);
    }

    /** @param array<string,mixed> $data */
    public function updateQuestion(int $questionId, array $data): void
    {
        (new AdminRepository())->saveQuestion([
            'test_id' => (int) ($data['test_id'] ?? 0),
            'question_order' => (int) ($data['question_order'] ?? 1),
            'question_text' => trim((string) ($data['question_text'] ?? '')),
            'question_text_te' => trim((string) ($data['question_text_te'] ?? '')),
            'option_a' => trim((string) ($data['option_a'] ?? '')),
            'option_b' => trim((string) ($data['option_b'] ?? '')),
            'option_c' => trim((string) ($data['option_c'] ?? '')),
            'option_d' => trim((string) ($data['option_d'] ?? '')),
            'correct_option' => strtoupper((string) ($data['correct_option'] ?? 'A')),
            'explanation' => trim((string) ($data['explanation'] ?? '')),
            'marks' => (int) ($data['marks'] ?? 1),
            'topic_tag' => trim((string) ($data['topic_tag'] ?? '')),
        ], $questionId);
    }

    public function deleteQuestion(int $questionId): void
    {
        $st = db()->prepare('SELECT test_id FROM test_questions WHERE id=?');
        $st->execute([$questionId]);
        $testId = (int) $st->fetchColumn();
        (new AdminRepository())->deleteQuestion($questionId);
        if ($testId > 0) {
            $this->syncTestTotals($testId);
        }
    }

    public function deleteTest(int $testId): void
    {
        (new AdminRepository())->deleteTest($testId);
    }

    public function syncTestTotals(int $testId): void
    {
        $count = (int) db()->query('SELECT COUNT(*) FROM test_questions WHERE test_id=' . (int) $testId)->fetchColumn();
        $marks = (int) db()->query('SELECT COALESCE(SUM(marks),0) FROM test_questions WHERE test_id=' . (int) $testId)->fetchColumn();
        if ($count < 1) {
            return;
        }
        db()->prepare('UPDATE tests SET total_questions=?, total_marks=? WHERE id=?')->execute([
            $count,
            $marks > 0 ? $marks : $count,
            $testId,
        ]);
    }

    /** @return array<string,mixed>|null */
    public function testRow(int $testId): ?array
    {
        $st = db()->prepare(
            'SELECT t.*, c.name AS course_name, s.name AS subject_name
             FROM tests t
             JOIN courses c ON c.id = t.course_id
             LEFT JOIN subjects s ON s.id = t.subject_id
             WHERE t.id=? LIMIT 1'
        );
        $st->execute([$testId]);
        $row = $st->fetch();

        return $row ?: null;
    }

    /**
     * Best score % per student for a single test (for optional admin poster).
     *
     * @return list<array{user_id:int,name:string,best_pct:float,best_score:int,best_max:int}>
     */
    public function topScorersForTest(int $testId, int $limit = 3): array
    {
        if ($testId < 1) {
            return [];
        }
        $limit = max(1, min(10, $limit));
        $st = db()->prepare(
            "SELECT u.id AS user_id, u.name,
                    MAX(ta.score / NULLIF(ta.max_score, 0) * 100) AS best_pct,
                    MAX(ta.score) AS best_score,
                    MAX(ta.max_score) AS best_max
             FROM test_attempts ta
             INNER JOIN users u ON u.id = ta.user_id AND u.role = 'student'
             WHERE ta.test_id = ? AND ta.status = 'submitted' AND COALESCE(ta.max_score, 0) > 0
             GROUP BY u.id, u.name
             ORDER BY best_pct DESC, best_score DESC
             LIMIT {$limit}"
        );
        $st->execute([$testId]);

        return array_map(static function (array $r): array {
            return [
                'user_id' => (int) $r['user_id'],
                'name' => (string) $r['name'],
                'best_pct' => round((float) $r['best_pct'], 1),
                'best_score' => (int) $r['best_score'],
                'best_max' => (int) $r['best_max'],
            ];
        }, $st->fetchAll() ?: []);
    }
}
