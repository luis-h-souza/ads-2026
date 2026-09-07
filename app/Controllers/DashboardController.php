<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Semester;

final class DashboardController extends BaseController
{
    public function index(): void
    {
        $user = Auth::user();
        $semesters = [];

        if (Auth::check()) {
            $semesters = Semester::listByUser(Auth::id());
        }

        $this->render('dashboard/index', [
            'pageTitle' => 'Painel de Estudos · Gestão de Aulas e Atividades',
            'user' => $user,
            'semesters' => $semesters,
            'isLoggedIn' => Auth::check(),
        ], null);
    }
}
