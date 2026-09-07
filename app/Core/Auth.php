<?php

declare(strict_types=1);

namespace App\Core;

final class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['usuario_id']);
    }

    public static function id(): int
    {
        if (!self::check()) {
            Response::json(['erro' => 'Autenticação necessária.'], 401);
        }
        return (int) $_SESSION['usuario_id'];
    }

    public static function user(): ?array
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => (int) $_SESSION['usuario_id'],
            'nome' => $_SESSION['usuario_nome'] ?? '',
            'email' => $_SESSION['usuario_email'] ?? '',
        ];
    }

    public static function login(int $userId, string $name, string $email): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = $userId;
        $_SESSION['usuario_nome'] = $name;
        $_SESSION['usuario_email'] = $email;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
        session_destroy();
    }
}
