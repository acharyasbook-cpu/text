<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/ExaminerRepository.php';

/**
 * Global exam-subject catalog (st_subjects) with sub-course junction (st_sub_course_subjects).
 */
final class StSubjectRepository
{
    public static function ready(): bool
    {
        return SchemaHelper::hasTable('st_subjects') && SchemaHelper::hasTable('st_sub_course_subjects');
    }

    /** @return list<array<string,mixed>> */
    public function listAllWithMappings(): array
    {
        if (!self::ready()) {
            return [];
        }
        $rows = db()->query(
            'SELECT s.id, s.subject_name, s.created_at FROM st_subjects s ORDER BY s.subject_name ASC'
        )->fetchAll() ?: [];

        if (!SchemaHelper::hasTable('sub_courses')) {
            foreach ($rows as &$row) {
                $row['sub_course_ids'] = [];
                $row['sub_courses'] = [];
                $row['sub_course_labels'] = [];
            }
            unset($row);

            return $rows;
        }
        $mapSt = db()->prepare(
            'SELECT scs.subject_id, scs.sub_course_id, sc.name AS sub_course_name, sc.name_te AS sub_course_name_te,
                    c.name AS course_name
             FROM st_sub_course_subjects scs
             INNER JOIN sub_courses sc ON sc.id = scs.sub_course_id
             INNER JOIN courses c ON c.id = sc.course_id
             WHERE scs.subject_id = ?
             ORDER BY c.name, sc.name'
        );

        foreach ($rows as &$row) {
            $mapSt->execute([(int) $row['id']]);
            $maps = $mapSt->fetchAll() ?: [];
            $row['sub_course_ids'] = array_map(static fn (array $m): int => (int) $m['sub_course_id'], $maps);
            $row['sub_courses'] = $maps;
            $row['sub_course_labels'] = array_map(static function (array $m): string {
                $main = (string) ($m['course_name'] ?? '');
                $sub = (string) ($m['sub_course_name_te'] ?? $m['sub_course_name'] ?? '');

                return $main !== '' ? $main . ' — ' . $sub : $sub;
            }, $maps);
        }
        unset($row);

        return $rows;
    }

    /** @return list<string> */
    public function subjectNames(): array
    {
        if (!self::ready()) {
            return [];
        }

        return db()->query('SELECT subject_name FROM st_subjects ORDER BY subject_name')->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function findByName(string $name): ?array
    {
        if (!self::ready()) {
            return null;
        }
        $st = db()->prepare('SELECT * FROM st_subjects WHERE subject_name = ? LIMIT 1');
        $st->execute([$this->normalizeName($name)]);

        return $st->fetch() ?: null;
    }

    public function find(int $id): ?array
    {
        if ($id < 1 || !self::ready()) {
            return null;
        }
        $st = db()->prepare('SELECT * FROM st_subjects WHERE id = ? LIMIT 1');
        $st->execute([$id]);

        return $st->fetch() ?: null;
    }

    /**
     * @param list<int> $subCourseIds
     */
    public function create(string $name, array $subCourseIds = []): int
    {
        if (!self::ready()) {
            throw new RuntimeException('Run: php database/migrate_st_subjects.php');
        }
        $name = $this->normalizeName($name);
        if ($name === '') {
            throw new InvalidArgumentException('Subject name is required');
        }
        $existing = $this->findByName($name);
        if ($existing) {
            throw new InvalidArgumentException('Subject already exists: ' . $name);
        }
        db()->prepare('INSERT INTO st_subjects (subject_name) VALUES (?)')->execute([$name]);
        $id = (int) db()->lastInsertId();
        $this->syncSubCourses($id, $subCourseIds);

        return $id;
    }

    /**
     * @param list<int> $subCourseIds
     */
    public function updateMappings(int $subjectId, array $subCourseIds): void
    {
        if ($subjectId < 1) {
            return;
        }
        $this->syncSubCourses($subjectId, $subCourseIds);
    }

    public function delete(int $id): void
    {
        if ($id < 1 || !self::ready()) {
            return;
        }
        $row = $this->find($id);
        if (!$row) {
            return;
        }
        $name = (string) $row['subject_name'];
        if (ExaminerRepository::ready()) {
            $st = db()->prepare('SELECT COUNT(*) FROM st_examiners WHERE assigned_subject = ?');
            $st->execute([$name]);
            if ((int) $st->fetchColumn() > 0) {
                throw new RuntimeException(
                    'Cannot delete: examiners are assigned to this subject. Reassign or remove them first.'
                );
            }
        }
        db()->prepare('DELETE FROM st_sub_course_subjects WHERE subject_id = ?')->execute([$id]);
        db()->prepare('DELETE FROM st_subjects WHERE id = ?')->execute([$id]);
    }

    public function normalizeName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');

        return $name;
    }

    /** @param list<int> $subCourseIds */
    private function syncSubCourses(int $subjectId, array $subCourseIds): void
    {
        db()->prepare('DELETE FROM st_sub_course_subjects WHERE subject_id = ?')->execute([$subjectId]);
        $ins = db()->prepare(
            'INSERT IGNORE INTO st_sub_course_subjects (sub_course_id, subject_id) VALUES (?, ?)'
        );
        foreach (array_unique(array_filter(array_map('intval', $subCourseIds))) as $scId) {
            if ($scId > 0) {
                $ins->execute([$scId, $subjectId]);
            }
        }
    }
}
