<?php

declare(strict_types=1);

class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT id, name, email, phone, role, avatar_url, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function verifyLogin(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        unset($user['password_hash']);

        return $user;
    }

    public function registerStudent(string $name, string $email, string $phone, string $password): int
    {
        $name = trim($name);
        $email = trim(strtolower($email));
        $phone = trim($phone);
        if ($name === '' || $email === '' || strlen($password) < 6) {
            throw new InvalidArgumentException('Name, valid email, and password (min 6 chars) are required.');
        }
        if ($this->findByEmail($email)) {
            throw new InvalidArgumentException('An account with this email already exists.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $cols = 'name, email, phone, password_hash, role';
        $vals = '?,?,?,?,\'student\'';
        $params = [$name, $email, $phone !== '' ? $phone : null, $hash];
        if (SchemaHelper::columnExists('users', 'mobile_verified')) {
            $cols .= ', mobile_verified';
            $vals .= ',?';
            $params[] = $phone !== '' ? 1 : 0;
        }
        $st = db()->prepare("INSERT INTO users ({$cols}) VALUES ({$vals})");
        $st->execute($params);

        return (int) db()->lastInsertId();
    }

    public function touchLastLogin(int $userId): void
    {
        if ($userId < 1) {
            return;
        }
        if (SchemaHelper::columnExists('users', 'last_login_at')) {
            db()->prepare('UPDATE users SET last_login_at=NOW() WHERE id=?')->execute([$userId]);
        }
        if (SchemaHelper::hasTable('user_login_events')) {
            $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);
            $hash = $ua !== '' ? hash('sha256', $ua) : null;
            $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);
            db()->prepare(
                'INSERT INTO user_login_events (user_id, user_agent_hash, user_agent_snippet, ip_address) VALUES (?,?,?,?)'
            )->execute([$userId, $hash, $ua !== '' ? $ua : null, $ip !== '' ? $ip : null]);
        }
    }
}
