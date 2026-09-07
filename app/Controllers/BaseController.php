<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Response;
use App\Core\View;

abstract class BaseController
{
    protected function render(string $viewPath, array $data = [], ?string $layout = 'layouts/header'): void
    {
        View::render($viewPath, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        Response::json($data, $status);
    }

    protected function requireAuth(): int
    {
        return Auth::id();
    }
}
