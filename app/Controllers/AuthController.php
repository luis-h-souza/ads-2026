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

        $name = is_string($data['nome']) ? trim($data['nome']) : '';
        $email = is_string($data['email']) ? strtolower(trim($data['email'])) : '';
        $password = is_string($data['senha']) ? $data['senha'] : '';

        if ($name === '') {
            $this->json(['erro' => 'Informe seu nome.'], 422);
        }

        if (strlen($name) > 120) {
            $this->json(['erro' => 'O nome deve ter no máximo 120 caracteres.'], 422);
        }

        if (strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['erro' => 'Informe um e-mail válido.'], 422);
        }

        if (strlen($password) < 8) {
            $this->json(['erro' => 'A senha deve ter ao menos 8 caracteres.'], 422);
        }

        $existing = User::findByEmail($email);
        if ($existing) {
            $this->json(['erro' => 'Este e-mail já está cadastrado.'], 409);
        }

        $id = User::create($name, $email, $password);
        Auth::login($id, $name, $email);

        $this->json([
            'id' => $id,
            'nome' => $name,
            'email' => $email,
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
