<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/SchemaHelper.php';

class TestRepository
{
    public function forCourse(int $courseId, ?string $type = null): array
    {
        $statusCond = SchemaHelper::testsHasStatus() ? 't.status = 1 AND ' : '';
        $sql = 'SELECT t.*, s.name AS subject_name, s.slug AS subject_slug
                FROM tests t LEFT JOIN subjects s ON s.id = t.subject_id
                WHERE t.course_id = ? AND ' . $statusCond . 't.is_active = 1';
        $params = [$courseId];
        if ($type) {
            $sql .= ' AND t.test_type = ?';
            $params[] = $type;
        }
        $sql .= ' ORDER BY FIELD(t.test_type, "topic","division","revision","grand","model"), t.title';
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /** Visible tests for a subject (student hub), all five categories */
    public function forSubject(int $subjectId): array
    {
        $statusCond = SchemaHelper::testsHasStatus() ? 't.status = 1 AND ' : '';
        $stmt = db()->prepare(
            'SELECT t.* FROM tests t
            WHERE t.subject_id = ? AND ' . $statusCond . ' t.is_active = 1
            ORDER BY FIELD(t.test_type, "topic","division","revision","grand","model"), t.title'
        );
        $stmt->execute([$subjectId]);

        return $stmt->fetchAll();
    }

    public function findBySlug(string $courseSlug, string $testSlug): ?array
    {
        $cLive = SchemaHelper::coursesHasStatus() ? 'c.status = 1' : 'c.is_active = 1';
        $statusCond = SchemaHelper::testsHasStatus() ? 't.status = 1 AND ' : '';
        $stmt = db()->prepare("SELECT t.*, c.slug AS course_slug, c.name AS course_name
            FROM tests t JOIN courses c ON c.id = t.course_id
            WHERE c.slug = ? AND t.slug = ? AND {$cLive} AND {$statusCond}t.is_active = 1");
        $stmt->execute([$courseSlug, $testSlug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function questionsForTest(int $testId): array
    {
        $stmt = db()->prepare('SELECT id, question_order, question_text, question_text_te,
            option_a, option_b, option_c, option_d, marks, topic_tag
            FROM test_questions WHERE test_id = ? ORDER BY question_order');
        $stmt->execute([$testId]);
        return $stmt->fetchAll();
    }

    public function recentAttempts(int $userId, int $limit = 5): array
    {
        $stmt = db()->prepare('SELECT ta.*, t.title, t.title_te, t.test_type, t.slug AS test_slug,
            c.slug AS course_slug, c.name AS course_name
            FROM test_attempts ta
            JOIN tests t ON t.id = ta.test_id
            JOIN courses c ON c.id = t.course_id
            WHERE ta.user_id = ? AND ta.status = "submitted"
            ORDER BY ta.submitted_at DESC LIMIT ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function performanceSummary(int $userId): array
    {
        $stmt = db()->prepare('SELECT
            COUNT(*) AS total_attempts,
            ROUND(AVG(score / NULLIF(max_score,0) * 100), 1) AS avg_percent,
            MAX(score) AS best_score,
            SUM(CASE WHEN t.test_type = "topic" THEN 1 ELSE 0 END) AS topic_tests,
            SUM(CASE WHEN t.test_type = "division" THEN 1 ELSE 0 END) AS division_tests,
            SUM(CASE WHEN t.test_type = "grand" THEN 1 ELSE 0 END) AS grand_tests
            FROM test_attempts ta JOIN tests t ON t.id = ta.test_id
            WHERE ta.user_id = ? AND ta.status = "submitted"');
        $stmt->execute([$userId]);
        return $stmt->fetch() ?: [
            'total_attempts' => 0, 'avg_percent' => 0, 'best_score' => 0,
            'topic_tests' => 0, 'division_tests' => 0, 'grand_tests' => 0,
        ];
    }

    public function subjectBreakdown(int $userId): array
    {
        $stmt = db()->prepare('SELECT COALESCE(s.name, "General") AS subject_name,
            ROUND(AVG(ta.score / NULLIF(ta.max_score,0) * 100), 1) AS avg_percent,
            COUNT(*) AS attempts
            FROM test_attempts ta
            JOIN tests t ON t.id = ta.test_id
            LEFT JOIN subjects s ON s.id = t.subject_id
            WHERE ta.user_id = ? AND ta.status = "submitted"
            GROUP BY s.id, s.name ORDER BY avg_percent DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
