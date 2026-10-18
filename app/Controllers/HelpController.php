<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;

final class HelpController extends BaseController
{
    public function index(): void
    {
        $returnToLogin = ($_GET['origem'] ?? null) === 'login';

        $this->render('help/index', [
            'pageTitle' => 'Ajuda · Painel de Estudos',
            'user' => Auth::user(),
            'backUrl' => base_url($returnToLogin ? '/login' : '/dashboard'),
        ]);
    }
}
