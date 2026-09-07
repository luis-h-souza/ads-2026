<?php

declare(strict_types=1);

namespace App\Core;

use PDOException;
use Throwable;

final class App
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function run(): void
    {
        $this->handleCors();
        $this->startSession();

        try {
            $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
            $basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');

            if ($basePath !== '' && $basePath !== '/' && str_starts_with($path, $basePath)) {
                $path = substr($path, strlen($basePath));
            }

            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            $this->router->dispatch($method, '/' . trim($path, '/'));
        } catch (PDOException $error) {
            error_log('PDO Error: ' . $error->getMessage());
            if ((int) $error->getCode() === 23000) {
                Response::json(['erro' => 'Já existe um registro com estes dados.'], 409);
            }
            Response::json(['erro' => 'Não foi possível processar a operação no banco de dados.'], 500);
        } catch (Throwable $error) {
            error_log('App Error: ' . $error->getMessage());
            Response::json([
                'erro' => env('APP_ENV') === 'production' ? 'Erro interno no servidor.' : $error->getMessage(),
                'detalhes' => env('APP_ENV') === 'production' ? null : [
                    'arquivo' => $error->getFile(),
                    'linha' => $error->getLine(),
                ],
            ], 500);
        }
    }

    private function handleCors(): void
    {
        $frontendOrigin = env('FRONTEND_URL');
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? null;

        if ($frontendOrigin && $requestOrigin === $frontendOrigin) {
            header('Access-Control-Allow-Origin: ' . $frontendOrigin);
            header('Access-Control-Allow-Credentials: true');
            header('Access-Control-Allow-Headers: Content-Type, X-Requested-With, Authorization');
            header('Access-Control-Allow-Methods: GET, POST, PATCH, DELETE, OPTIONS');
            header('Vary: Origin');
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
            if ($frontendOrigin && $requestOrigin !== $frontendOrigin) {
                http_response_code(403);
                exit;
            }
            http_response_code(204);
            exit;
        }
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('painel_estudos');
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => env('APP_ENV', 'development') === 'production' ? 'None' : 'Lax',
                'cookie_secure' => env('APP_ENV', 'development') === 'production',
            ]);
        }
    }
}
