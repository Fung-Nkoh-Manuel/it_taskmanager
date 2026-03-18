<?php

class UserModel extends BaseModel
{
    protected string $table = 'users';

    public function findByLogin(string $login): ?array
    {
        return $this->queryOne(
            'SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1',
            [$login, $login]
        );
    }

    public function findByEmail(string $email, ?int $excludeId = null): ?array
    {
        if ($excludeId) {
            return $this->queryOne('SELECT * FROM users WHERE email = ? AND id != ?', [$email, $excludeId]);
        }
        return $this->queryOne('SELECT * FROM users WHERE email = ?', [$email]);
    }

    public function findByUsername(string $username, ?int $excludeId = null): ?array
    {
        if ($excludeId) {
            return $this->queryOne('SELECT * FROM users WHERE username = ? AND id != ?', [$username, $excludeId]);
        }
        return $this->queryOne('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public function paginated(int $page): array
    {
        return $this->paginate(
            'SELECT * FROM users ORDER BY created_at DESC',
            [],
            $page
        );
    }

    public function allForSelect(): array
    {
        return $this->query(
            "SELECT id, full_name, role FROM users WHERE is_active = 1 ORDER BY full_name"
        );
    }

    public function create(array $data): int
    {
        return $this->insert(
            'INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, ?)',
            [
                $data['username'],
                $data['email'],
                password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                $data['full_name'],
                $data['role'] ?? 'utilisateur',
            ]
        );
    }

    public function update(int $id, array $data): void
    {
        $fields = [];
        $params = [];

        foreach (['full_name', 'email', 'role'] as $f) {
            if (isset($data[$f])) {
                $fields[] = "$f = ?";
                $params[] = $data[$f];
            }
        }

        if (!empty($data['password'])) {
            $fields[] = 'password = ?';
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $params[] = (int) $data['is_active'];
        }

        if (isset($data['avatar'])) {
            $fields[] = 'avatar = ?';
            $params[] = $data['avatar'];
        }

        if (empty($fields)) return;

        $params[] = $id;
        $this->execute('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?', $params);
    }

    public function updateLastLogin(int $id): void
    {
        $this->execute('UPDATE users SET last_login = NOW() WHERE id = ?', [$id]);
    }

    public function toggle(int $id): void
    {
        $this->execute('UPDATE users SET is_active = NOT is_active WHERE id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM users WHERE id = ?', [$id]);
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }
}
