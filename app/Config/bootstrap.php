<?php

declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relativeClass = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/' . str_replace('\\', '/', $relativeClass) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});

function loadEnv(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim(trim($value), "\"'");

        if ($key !== '') {
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Carrega o .env localizado na raiz do projeto
loadEnv(dirname(__DIR__, 2) . '/.env');

function env(string $key, ?string $default = null): ?string
{
    if (array_key_exists($key, $_ENV)) {
        return $_ENV[$key];
    }

    $systemValue = getenv($key);
    return $systemValue === false ? $default : $systemValue;
}

function base_url(string $path = ''): string
{
    $configuredUrl = env('APP_URL');
    $configuredPath = $configuredUrl !== null ? parse_url($configuredUrl, PHP_URL_PATH) : false;

    if (is_string($configuredPath)) {
        $base = rtrim(str_replace('\\', '/', $configuredPath), '/');
    } else {
        $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        $base = $scriptName === '.' ? '' : rtrim($scriptName, '/');

        if ($base === '/public') {
            $base = '';
        }
    }

    $path = '/' . ltrim($path, '/');
    return $base . $path;
}
