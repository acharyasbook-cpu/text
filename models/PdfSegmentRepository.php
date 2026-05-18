<?php

declare(strict_types=1);

final class PdfSegmentRepository
{
    public static function ready(): bool
    {
        return SchemaHelper::hasTable('st_pdf_segments');
    }

    /** @return list<array<string,mixed>> */
    public function listForSubject(?string $assignedSubject, ?int $subCourseId = null): array
    {
        if (!self::ready()) {
            return [];
        }
        $where = ['1=1'];
        $params = [];
        if ($assignedSubject !== null && $assignedSubject !== '') {
            $where[] = 's.assigned_subject = ?';
            $params[] = $assignedSubject;
        }
        if ($subCourseId !== null && $subCourseId > 0) {
            $where[] = 's.sub_course_id = ?';
            $params[] = $subCourseId;
        }
        $sql = 'SELECT s.*, sc.name AS sub_course_name, sc.slug AS sub_course_slug
                FROM st_pdf_segments s
                LEFT JOIN sub_courses sc ON sc.id = s.sub_course_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY s.pdf_name, s.start_page, s.id';
        $st = db()->prepare($sql);
        $st->execute($params);

        return $st->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        if (!self::ready() || $id < 1) {
            return null;
        }
        $st = db()->prepare('SELECT * FROM st_pdf_segments WHERE id=? LIMIT 1');
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    /** @param array<string,mixed> $data */
    public function save(array $data, ?int $id = null): int
    {
        if (!self::ready()) {
            throw new RuntimeException('Run migrate_mcq_ai_engine.php');
        }
        $fields = [
            'pdf_name' => (string) ($data['pdf_name'] ?? ''),
            'topic_name' => (string) ($data['topic_name'] ?? ''),
            'start_page' => max(1, (int) ($data['start_page'] ?? 1)),
            'end_page' => max(1, (int) ($data['end_page'] ?? 1)),
            'sub_course_id' => !empty($data['sub_course_id']) ? (int) $data['sub_course_id'] : null,
            'assigned_subject' => (string) ($data['assigned_subject'] ?? 'General'),
            'parent_segment_id' => !empty($data['parent_segment_id']) ? (int) $data['parent_segment_id'] : null,
            'storage_path' => !empty($data['storage_path']) ? (string) $data['storage_path'] : null,
            'page_count' => !empty($data['page_count']) ? (int) $data['page_count'] : null,
        ];
        if ($fields['end_page'] < $fields['start_page']) {
            $fields['end_page'] = $fields['start_page'];
        }

        if ($id !== null && $id > 0) {
            db()->prepare(
                'UPDATE st_pdf_segments SET pdf_name=?, topic_name=?, start_page=?, end_page=?,
                 sub_course_id=?, assigned_subject=?, parent_segment_id=?, storage_path=?, page_count=?
                 WHERE id=?'
            )->execute([
                $fields['pdf_name'], $fields['topic_name'], $fields['start_page'], $fields['end_page'],
                $fields['sub_course_id'], $fields['assigned_subject'], $fields['parent_segment_id'],
                $fields['storage_path'], $fields['page_count'], $id,
            ]);

            return $id;
        }

        db()->prepare(
            'INSERT INTO st_pdf_segments (pdf_name, topic_name, start_page, end_page, sub_course_id,
             assigned_subject, parent_segment_id, storage_path, page_count)
             VALUES (?,?,?,?,?,?,?,?,?)'
        )->execute([
            $fields['pdf_name'], $fields['topic_name'], $fields['start_page'], $fields['end_page'],
            $fields['sub_course_id'], $fields['assigned_subject'], $fields['parent_segment_id'],
            $fields['storage_path'], $fields['page_count'],
        ]);

        return (int) db()->lastInsertId();
    }

    public function delete(int $id): void
    {
        if ($id < 1) {
            return;
        }
        db()->prepare('DELETE FROM st_pdf_segments WHERE id=?')->execute([$id]);
    }
}
