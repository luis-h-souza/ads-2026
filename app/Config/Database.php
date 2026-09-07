<?php

declare(strict_types=1);

namespace App\Config;

use PDO;
use RuntimeException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $host = env('DB_HOST', '127.0.0.1');
        $name = env('DB_NAME', 'ads-aulas');
        $user = env('DB_USER', 'root');
        $password = env('DB_PASSWORD', '');
        $port = env('DB_PORT', '3306');

        if (!$host || !$name || $user === null) {
            throw new RuntimeException('As configurações de conexão com o banco de dados estão incompletas.');
        }

        self::$connection = new PDO(
            "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$connection;
    }
}
