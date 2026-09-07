<?php

declare(strict_types=1);

namespace App\Models;

final class User extends BaseModel
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = self::db()->prepare('SELECT id, nome, email, senha_hash, created_at FROM usuarios WHERE email = ? LIMIT 1');
        $stmt->execute([strtolower(trim($email))]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = self::db()->prepare('SELECT id, nome, email, created_at FROM usuarios WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(string $name, string $email, string $password): int
    {
        $stmt = self::db()->prepare('INSERT INTO usuarios (nome, email, senha_hash) VALUES (?, ?, ?)');
        $stmt->execute([
            trim($name),
            strtolower(trim($email)),
            password_hash($password, PASSWORD_DEFAULT),
        ]);
        return (int) self::db()->lastInsertId();
    }
}
