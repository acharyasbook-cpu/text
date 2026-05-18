<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/services/McqDifficultyProfiles.php';

final class McqGenerationJobRepository
{
    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        if (!SchemaHelper::hasTable('st_mcq_generation_jobs') || $id < 1) {
            return null;
        }
        $st = db()->prepare('SELECT * FROM st_mcq_generation_jobs WHERE id=? LIMIT 1');
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        db()->prepare(
            'INSERT INTO st_mcq_generation_jobs (segment_id, api_slot_id, subject_key, difficulty_scale,
             language_mode, status, current_page, total_pages, questions_per_page, excel_mapping, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            (int) $data['segment_id'],
            !empty($data['api_slot_id']) ? (int) $data['api_slot_id'] : null,
            (string) ($data['subject_key'] ?? 'General'),
            McqDifficultyProfiles::normalize((string) ($data['difficulty_scale'] ?? 'SGT')),
            (string) ($data['language_mode'] ?? 'bilingual_en_te'),
            'pending',
            0,
            (int) ($data['total_pages'] ?? 1),
            McqDifficultyProfiles::normalizeQuestionCount((int) ($data['questions_per_page'] ?? 3)),
            isset($data['excel_mapping']) ? json_encode($data['excel_mapping'], JSON_UNESCAPED_UNICODE) : null,
            $data['created_by'] ?? null,
        ]);

        return (int) db()->lastInsertId();
    }

    public function markProcessing(int $id): void
    {
        db()->prepare('UPDATE st_mcq_generation_jobs SET status="processing", updated_at=NOW() WHERE id=?')
            ->execute([$id]);
    }

    public function advancePage(int $id, int $page, ?string $error = null): void
    {
        if ($error !== null) {
            db()->prepare(
                'UPDATE st_mcq_generation_jobs SET current_page=?, last_error=?, status="failed", updated_at=NOW() WHERE id=?'
            )->execute([$page, $error, $id]);

            return;
        }
        $job = $this->find($id);
        $total = (int) ($job['total_pages'] ?? 0);
        $status = $page >= $total ? 'completed' : 'processing';
        db()->prepare(
            'UPDATE st_mcq_generation_jobs SET current_page=?, status=?, last_error=NULL, updated_at=NOW() WHERE id=?'
        )->execute([$page, $status, $id]);
    }
}
