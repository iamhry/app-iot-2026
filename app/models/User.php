<?php

declare(strict_types=1);

final class User
{
    public static function findById(int $id): ?array
    {
        $stmt = db()->prepare('SELECT id, name, username, password, created_at, updated_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function findByUsername(string $username): ?array
    {
        $stmt = db()->prepare('SELECT id, name, username, password, created_at, updated_at FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public static function create(string $name, string $username, string $password): int
    {
        $now = now_utc();
        $stmt = db()->prepare(
            'INSERT INTO users (name, username, password, created_at, updated_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $name,
            $username,
            password_hash($password, PASSWORD_DEFAULT),
            $now,
            $now,
        ]);
        return (int) db()->lastInsertId();
    }

    public static function updatePasswordHash(int $id, string $password): void
    {
        $stmt = db()->prepare('UPDATE users SET password = ?, updated_at = ? WHERE id = ?');
        $stmt->execute([password_hash($password, PASSWORD_DEFAULT), now_utc(), $id]);
    }
}
