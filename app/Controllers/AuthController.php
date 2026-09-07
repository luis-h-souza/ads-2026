<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\User;

final class AuthController extends BaseController
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Response::redirect(base_url('/dashboard'));
        }

        $this->render('auth/login', [
            'pageTitle' => 'Login · Painel de Estudos',
        ], null); // layout null pois login usa template próprio ou layout dedicado
    }

    public function login(): void
    {
        $data = Request::json();
        if (empty($data)) {
            $data = Request::all();
        }

        Request::requireFields($data, ['email', 'senha']);

        $email = strtolower(trim((string) $data['email']));
        $user = User::findByEmail($email);

        if (!$user || !password_verify((string) $data['senha'], $user['senha_hash'])) {
            $this->json(['erro' => 'E-mail ou senha inválidos.'], 401);
        }

        Auth::login((int) $user['id'], (string) $user['nome'], (string) $user['email']);

        $this->json([
            'id' => (int) $user['id'],
            'nome' => $user['nome'],
            'email' => $user['email'],
        ]);
    }

    public function register(): void
    {
        $data = Request::json();
        if (empty($data)) {
            $data = Request::all();
        }

        Request::requireFields($data, ['nome', 'email', 'senha']);

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen((string) $data['senha']) < 8) {
            $this->json(['erro' => 'Informe um e-mail válido e uma senha com ao menos 8 caracteres.'], 422);
        }

        $existing = User::findByEmail((string) $data['email']);
        if ($existing) {
            $this->json(['erro' => 'Este e-mail já está cadastrado.'], 409);
        }

        $id = User::create((string) $data['nome'], (string) $data['email'], (string) $data['senha']);
        Auth::login($id, trim((string) $data['nome']), strtolower(trim((string) $data['email'])));

        $this->json([
            'id' => $id,
            'nome' => trim((string) $data['nome']),
            'email' => strtolower(trim((string) $data['email'])),
        ], 201);
    }

    public function logout(): void
    {
        Auth::logout();

        if (Request::method() === 'GET') {
            Response::redirect(base_url('/login'));
        }

        $this->json(['ok' => true]);
    }

    public function me(): void
    {
        if (!Auth::check()) {
            $this->json(['autenticado' => false, 'usuario' => null]);
        }

        $this->json([
            'autenticado' => true,
            'usuario' => Auth::user(),
        ]);
    }
}
