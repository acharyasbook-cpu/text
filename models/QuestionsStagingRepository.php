<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/ExamManagerRepository.php';

final class QuestionsStagingRepository
{
    public const STATUS_RAW = 'raw_ai';
    public const STATUS_EXAMINER = 'examiner_approved';
    public const STATUS_LIVE = 'live_approved';

    public static function ready(): bool
    {
        return SchemaHelper::hasTable('st_questions_staging');
    }

    /** @return list<array<string,mixed>> */
    public function listByStatus(string $status, ?string $subjectKey = null, int $limit = 200): array
    {
        if (!self::ready()) {
            return [];
        }
        $limit = max(1, min(500, $limit));
        $where = ['q.status = ?'];
        $params = [$status];
        if ($subjectKey !== null && $subjectKey !== '') {
            $where[] = 'q.subject_key = ?';
            $params[] = $subjectKey;
        }
        $sql = "SELECT q.*, s.topic_name, s.pdf_name
                FROM st_questions_staging q
                LEFT JOIN st_pdf_segments s ON s.id = q.segment_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY q.updated_at DESC, q.id DESC
                LIMIT {$limit}";
        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll() ?: [];
    }

    /** @param array<string,mixed> $row */
    public function insertRaw(array $row): int
    {
        if (!self::ready()) {
            throw new RuntimeException('Run migrate_mcq_ai_engine.php');
        }
        db()->prepare(
            'INSERT INTO st_questions_staging (
                segment_id, job_id, page_number, subject_key, difficulty_scale,
                question_text, question_text_te, option_a, option_b, option_c, option_d,
                option_a_te, option_b_te, option_c_te, option_d_te,
                correct_option, bilingual_layout, status, metadata
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $row['segment_id'] ?? null,
            $row['job_id'] ?? null,
            $row['page_number'] ?? null,
            $row['subject_key'] ?? 'General',
            $row['difficulty_scale'] ?? 'SGT',
            $row['question_text'] ?? '',
            $row['question_text_te'] ?? null,
            $row['option_a'] ?? '',
            $row['option_b'] ?? '',
            $row['option_c'] ?? '',
            $row['option_d'] ?? '',
            $row['option_a_te'] ?? null,
            $row['option_b_te'] ?? null,
            $row['option_c_te'] ?? null,
            $row['option_d_te'] ?? null,
            strtoupper((string) ($row['correct_option'] ?? 'A')),
            !empty($row['bilingual_layout']) ? 1 : 0,
            self::STATUS_RAW,
            isset($row['metadata']) ? json_encode($row['metadata'], JSON_UNESCAPED_UNICODE) : null,
        ]);

        return (int) db()->lastInsertId();
    }

    /** @param array<string,mixed> $patch */
    public function update(int $id, array $patch): void
    {
        $allowed = [
            'question_text', 'question_text_te', 'option_a', 'option_b', 'option_c', 'option_d',
            'option_a_te', 'option_b_te', 'option_c_te', 'option_d_te', 'correct_option', 'status',
        ];
        $sets = [];
        $params = [];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $patch)) {
                $sets[] = "{$col}=?";
                $params[] = $patch[$col];
            }
        }
        if ($sets === []) {
            return;
        }
        $params[] = $id;
        db()->prepare('UPDATE st_questions_staging SET ' . implode(',', $sets) . ', updated_at=NOW() WHERE id=?')
            ->execute($params);
    }

    /** @param list<int> $ids */
    public function examinerApprove(array $ids, int $examinerId): int
    {
        if ($ids === []) {
            return 0;
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $params = array_merge([self::STATUS_EXAMINER, $examinerId], $ids);
        $st = db()->prepare(
            "UPDATE st_questions_staging SET status=?, examiner_id=?, approved_examiner_at=NOW()
             WHERE id IN ({$ph}) AND status=?"
        );
        $params[] = self::STATUS_RAW;
        $st->execute($params);

        return $st->rowCount();
    }

    /** @param list<int> $ids */
    public function deployToTest(int $testId, array $ids): int
    {
        if ($ids === [] || $testId < 1) {
            return 0;
        }
        $deployed = 0;
        $order = (int) db()->query('SELECT COALESCE(MAX(question_order),0) FROM test_questions WHERE test_id=' . (int) $testId)
            ->fetchColumn();
        foreach ($ids as $qid) {
            $st = db()->prepare('SELECT * FROM st_questions_staging WHERE id=? AND status=? LIMIT 1');
            $st->execute([(int) $qid, self::STATUS_EXAMINER]);
            $q = $st->fetch();
            if (!$q) {
                continue;
            }
            $order++;
            $text = (string) $q['question_text'];
            if (!empty($q['question_text_te'])) {
                $text .= "\n\n" . (string) $q['question_text_te'];
            }
            db()->prepare(
                'INSERT INTO test_questions (test_id, question_order, question_text, option_a, option_b, option_c, option_d, correct_option, marks)
                 VALUES (?,?,?,?,?,?,?,?,1)'
            )->execute([
                $testId, $order, $text,
                $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'],
                $q['correct_option'],
            ]);
            $newQid = (int) db()->lastInsertId();
            db()->prepare(
                'UPDATE st_questions_staging SET status=?, approved_admin_at=NOW(), deployed_test_id=?, deployed_question_id=?
                 WHERE id=?'
            )->execute([self::STATUS_LIVE, $testId, $newQid, (int) $q['id']]);
            $deployed++;
        }
        if ($deployed > 0) {
            (new ExamManagerRepository())->syncTestTotals($testId);
        }

        return $deployed;
    }

    public function delete(int $id): void
    {
        db()->prepare('DELETE FROM st_questions_staging WHERE id=?')->execute([$id]);
    }
}
