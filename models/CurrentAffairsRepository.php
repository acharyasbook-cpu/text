<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/SchemaHelper.php';

final class CurrentAffairsRepository
{
    public const EXAM_QUESTION_COUNT = 25;

    public const POOL_LOTTERY_MIN = 50;

    public static function ready(): bool
    {
        return SchemaHelper::hasTable('st_current_affairs_pool')
            && SchemaHelper::hasTable('st_current_affairs_attempts');
    }

    public function todayDate(): string
    {
        return date('Y-m-d');
    }

    public function retentionCutoff(): string
    {
        return date('Y-m-d', strtotime('-1 year'));
    }

    /** @return list<array<string,mixed>> */
    public function poolForDate(string $examDate): array
    {
        $st = db()->prepare(
            'SELECT * FROM st_current_affairs_pool WHERE exam_date = ? ORDER BY id ASC'
        );
        $st->execute([$examDate]);

        return $st->fetchAll() ?: [];
    }

    public function poolCountForDate(string $examDate): int
    {
        $st = db()->prepare('SELECT COUNT(*) FROM st_current_affairs_pool WHERE exam_date = ?');
        $st->execute([$examDate]);

        return (int) $st->fetchColumn();
    }

    public function dateHasExam(string $examDate): bool
    {
        return $this->poolCountForDate($examDate) >= self::EXAM_QUESTION_COUNT;
    }

    /**
     * Pick 25 questions; ORDER BY RAND() when pool has more than 25.
     *
     * @return list<array<string,mixed>>
     */
    public function pickExamQuestions(string $examDate): array
    {
        $count = $this->poolCountForDate($examDate);
        $need = self::EXAM_QUESTION_COUNT;
        if ($count < $need) {
            return [];
        }
        if ($count === $need) {
            return $this->poolForDate($examDate);
        }
        $limit = (int) $need;
        $st = db()->prepare(
            "SELECT * FROM st_current_affairs_pool WHERE exam_date = ? ORDER BY RAND() LIMIT {$limit}"
        );
        $st->execute([$examDate]);

        return $st->fetchAll() ?: [];
    }

    /** @return array{attempt_count:int,last_attempt_at:?string}|null */
    public function attemptRow(int $userId, string $examDate): ?array
    {
        $st = db()->prepare(
            'SELECT attempt_count, last_attempt_at FROM st_current_affairs_attempts
             WHERE user_id = ? AND exam_date = ? LIMIT 1'
        );
        $st->execute([$userId, $examDate]);
        $row = $st->fetch();

        return $row ?: null;
    }

    public function recordAttempt(int $userId, string $examDate): void
    {
        $st = db()->prepare(
            'INSERT INTO st_current_affairs_attempts (user_id, exam_date, attempt_count, last_attempt_at)
             VALUES (?, ?, 1, NOW())
             ON DUPLICATE KEY UPDATE attempt_count = attempt_count + 1, last_attempt_at = NOW()'
        );
        $st->execute([$userId, $examDate]);
    }

    /**
     * Premium: months with exams within 1-year window, newest first.
     *
     * @return list<array{ym:string,label:string,count:int}>
     */
    public function monthsWithExams(string $sinceDate, ?string $untilDate = null): array
    {
        $until = $untilDate ?? $this->todayDate();
        $need = self::EXAM_QUESTION_COUNT;
        $sql = "SELECT DATE_FORMAT(d.exam_date, '%Y-%m') AS ym,
                       COUNT(*) AS day_count
                FROM (
                    SELECT exam_date FROM st_current_affairs_pool
                    WHERE exam_date >= ? AND exam_date <= ?
                    GROUP BY exam_date
                    HAVING COUNT(*) >= {$need}
                ) d
                GROUP BY ym
                ORDER BY ym DESC";
        $st = db()->prepare($sql);
        $st->execute([$sinceDate, $until]);
        $rows = $st->fetchAll() ?: [];
        $out = [];
        foreach ($rows as $r) {
            $ym = (string) ($r['ym'] ?? '');
            if ($ym === '') {
                continue;
            }
            [$y, $m] = array_map('intval', explode('-', $ym));
            $label = date('F Y', mktime(0, 0, 0, $m, 1, $y)) . ' Current Affairs';
            $out[] = [
                'ym' => $ym,
                'label' => $label,
                'count' => (int) ($r['day_count'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Distinct exam dates in a month (premium archive), newest first.
     *
     * @return list<string>
     */
    public function datesInMonth(string $ym, string $sinceDate, ?string $untilDate = null): array
    {
        $until = $untilDate ?? $this->todayDate();
        $st = db()->prepare(
            'SELECT exam_date FROM st_current_affairs_pool
             WHERE exam_date >= ? AND exam_date <= ?
               AND DATE_FORMAT(exam_date, \'%Y-%m\') = ?
             GROUP BY exam_date
             HAVING COUNT(*) >= ?
             ORDER BY exam_date DESC'
        );
        $st->execute([$sinceDate, $until, $ym, self::EXAM_QUESTION_COUNT]);
        $dates = [];
        foreach ($st->fetchAll() as $row) {
            $dates[] = (string) $row['exam_date'];
        }

        return $dates;
    }

    /**
     * Months strictly older than 1-year retention (for admin purge UI).
     *
     * @return list<array{ym:string,label:string,question_count:int}>
     */
    public function monthsEligibleForPurge(): array
    {
        $cutoff = $this->retentionCutoff();
        $st = db()->prepare(
            'SELECT DATE_FORMAT(exam_date, \'%Y-%m\') AS ym,
                    COUNT(*) AS question_count,
                    MIN(exam_date) AS min_date,
                    MAX(exam_date) AS max_date
             FROM st_current_affairs_pool
             WHERE exam_date < ?
             GROUP BY ym
             ORDER BY ym ASC'
        );
        $st->execute([$cutoff]);
        $out = [];
        foreach ($st->fetchAll() as $r) {
            $ym = (string) ($r['ym'] ?? '');
            if ($ym === '') {
                continue;
            }
            [$y, $m] = array_map('intval', explode('-', $ym));
            $out[] = [
                'ym' => $ym,
                'label' => date('F Y', mktime(0, 0, 0, $m, 1, $y)),
                'question_count' => (int) ($r['question_count'] ?? 0),
                'min_date' => (string) ($r['min_date'] ?? ''),
                'max_date' => (string) ($r['max_date'] ?? ''),
            ];
        }

        return $out;
    }

    public function purgeMonth(string $ym): int
    {
        if (!preg_match('/^\d{4}-\d{2}$/', $ym)) {
            throw new InvalidArgumentException('Invalid month key.');
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $delPool = $pdo->prepare(
                'DELETE FROM st_current_affairs_pool WHERE DATE_FORMAT(exam_date, \'%Y-%m\') = ?'
            );
            $delPool->execute([$ym]);
            $poolDeleted = $delPool->rowCount();

            $delAttempts = $pdo->prepare(
                'DELETE FROM st_current_affairs_attempts WHERE DATE_FORMAT(exam_date, \'%Y-%m\') = ?'
            );
            $delAttempts->execute([$ym]);

            $pdo->commit();

            return $poolDeleted;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * @param list<array{question_text:string,option_a:string,option_b:string,option_c:string,option_d:string,correct_option:string}> $rows
     */
    public function insertPoolBatch(string $examDate, array $rows, string $mode = 'manual'): int
    {
        $mode = $mode === 'ai' ? 'ai' : 'manual';
        $st = db()->prepare(
            'INSERT INTO st_current_affairs_pool
             (exam_date, question_text, option_a, option_b, option_c, option_d, correct_option, mode)
             VALUES (?,?,?,?,?,?,?,?)'
        );
        $n = 0;
        foreach ($rows as $row) {
            $opt = strtoupper((string) ($row['correct_option'] ?? 'A'));
            if (!in_array($opt, ['A', 'B', 'C', 'D'], true)) {
                $opt = 'A';
            }
            $st->execute([
                $examDate,
                trim((string) ($row['question_text'] ?? '')),
                trim((string) ($row['option_a'] ?? '')),
                trim((string) ($row['option_b'] ?? '')),
                trim((string) ($row['option_c'] ?? '')),
                trim((string) ($row['option_d'] ?? '')),
                $opt,
                $mode,
            ]);
            $n++;
        }

        return $n;
    }

    public function clearPoolForDate(string $examDate): void
    {
        $st = db()->prepare('DELETE FROM st_current_affairs_pool WHERE exam_date = ?');
        $st->execute([$examDate]);
    }

    /** @return list<array<string,mixed>> */
    public function adminStatsByDate(int $limit = 30): array
    {
        $st = db()->query(
            'SELECT exam_date,
                    COUNT(*) AS total_questions,
                    SUM(mode = \'manual\') AS manual_count,
                    SUM(mode = \'ai\') AS ai_count
             FROM st_current_affairs_pool
             GROUP BY exam_date
             ORDER BY exam_date DESC
             LIMIT ' . (int) $limit
        );

        return $st->fetchAll() ?: [];
    }
}
