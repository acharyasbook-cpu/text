<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/models/StSubjectRepository.php';

final class ExaminerRepository
{
    public static function ready(): bool
    {
        return SchemaHelper::hasTable('st_examiners');
    }

    /** @return list<array<string,mixed>> */
    public function listAll(): array
    {
        if (!self::ready()) {
            return [];
        }

        return db()->query(
            'SELECT id, email, assigned_subject, status, created_at FROM st_examiners ORDER BY assigned_subject, email'
        )->fetchAll() ?: [];
    }

    /** @return array<string,mixed>|null */
    public function findByEmail(string $email): ?array
    {
        if (!self::ready()) {
            return null;
        }
        $st = db()->prepare('SELECT * FROM st_examiners WHERE email=? AND status="active" LIMIT 1');
        $st->execute([strtolower(trim($email))]);

        return $st->fetch() ?: null;
    }

    /** @return array<string,mixed>|null */
    public function verifyLogin(string $email, string $password): ?array
    {
        $row = $this->findByEmail($email);
        if (!$row || !password_verify($password, (string) $row['password_hash'])) {
            return null;
        }
        unset($row['password_hash']);

        return $row;
    }

    /** @param array<string,mixed> $data */
    public function save(array $data, ?int $id = null): int
    {
        if (!self::ready()) {
            throw new RuntimeException('Run migrate_mcq_ai_engine.php');
        }
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $subject = trim((string) ($data['assigned_subject'] ?? ''));
        $status = ($data['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active';
        if ($email === '' || $subject === '') {
            throw new InvalidArgumentException('Email and assigned subject are required');
        }
        if (StSubjectRepository::ready()) {
            $cat = new StSubjectRepository();
            if (!$cat->findByName($subject)) {
                throw new InvalidArgumentException('Unknown subject — add it in Subject Manager first');
            }
        }

        if ($id !== null && $id > 0) {
            $params = [$email, $subject, $status, $id];
            $sql = 'UPDATE st_examiners SET email=?, assigned_subject=?, status=?';
            if (!empty($data['password'])) {
                $sql .= ', password_hash=?';
                $params = [$email, $subject, $status, password_hash((string) $data['password'], PASSWORD_DEFAULT), $id];
            }
            $sql .= ' WHERE id=?';
            db()->prepare($sql)->execute($params);

            return $id;
        }

        $pass = (string) ($data['password'] ?? '');
        if ($pass === '') {
            throw new InvalidArgumentException('Password required for new examiner');
        }
        db()->prepare(
            'INSERT INTO st_examiners (email, password_hash, assigned_subject, status) VALUES (?,?,?,?)'
        )->execute([$email, password_hash($pass, PASSWORD_DEFAULT), $subject, $status]);

        return (int) db()->lastInsertId();
    }

    public function delete(int $id): void
    {
        db()->prepare('DELETE FROM st_examiners WHERE id=?')->execute([$id]);
    }
}
