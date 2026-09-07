<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $pattern, callable|array|string $handler): void
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => rtrim($pattern, '/') ?: '/',
            'handler' => $handler,
        ];
    }

    public function get(string $pattern, callable|array|string $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable|array|string $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function patch(string $pattern, callable|array|string $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    public function delete(string $pattern, callable|array|string $handler): void
    {
        $this->add('DELETE', $pattern, $handler);
    }

    public function dispatch(string $method, string $path): void
    {
        $method = strtoupper($method);
        $path = rtrim($path, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Converte parâmetros da rota como {id} para regex numérico ou {slug} para caracteres
            $pattern = preg_replace('#\{[a-zA-Z_][a-zA-Z0-9_]*\}#', '([^/]+)', $route['pattern']);
            if (!preg_match('#^' . $pattern . '$#', $path, $matches)) {
                continue;
            }

            array_shift($matches);
            // Converte argumentos numéricos se aplicável
            $params = array_map(fn($v) => is_numeric($v) ? (int) $v : $v, $matches);

            $this->executeHandler($route['handler'], $params);
            return;
        }

        // Se for requisição de API, responde JSON 404
        if (str_starts_with($path, '/api') || (!empty($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))) {
            Response::json(['erro' => 'Endpoint de API não encontrado.'], 404);
        }

        http_response_code(404);
        echo '<h1>404 - Página Não Encontrada</h1><p><a href="' . base_url('/') . '">Voltar para o início</a></p>';
    }

    private function executeHandler(callable|array|string $handler, array $params): void
    {
        if ($handler instanceof Closure) {
            $handler(...$params);
            return;
        }

        if (is_array($handler) && count($handler) === 2) {
            [$class, $action] = $handler;
            $instance = is_object($class) ? $class : new $class();
            $instance->$action(...$params);
            return;
        }

        if (is_string($handler) && str_contains($handler, '@')) {
            [$class, $action] = explode('@', $handler, 2);
            $instance = new $class();
            $instance->$action(...$params);
            return;
        }

        if (is_callable($handler)) {
            $handler(...$params);
        }
    }
}
