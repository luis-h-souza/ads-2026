<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Models\Semester;
use DateTimeImmutable;

final class SemesterController extends BaseController
{
    public function index(): void
    {
        $userId = $this->requireAuth();
        $semesters = Semester::listByUser($userId);
        $this->json(['semestres' => $semesters]);
    }

    public function store(): void
    {
        $userId = $this->requireAuth();
        $data = Request::json();
        Request::requireFields($data, ['nome', 'ano']);

        $this->validateOptionalDates($data);

        if (($data['data_inicio'] ?? null) !== null && ($data['data_fim'] ?? null) !== null && $data['data_fim'] < $data['data_inicio']) {
            $this->json(['erro' => 'A data de fim não pode ser anterior à data de início.'], 422);
        }

        $id = Semester::create(
            $userId,
            (string) $data['nome'],
            (int) $data['ano'],
            $data['data_inicio'] ?? null,
            $data['data_fim'] ?? null
        );

        $this->json(['id' => $id], 201);
    }

    public function show(int $id): void
    {
        $userId = $this->requireAuth();
        $tree = Semester::getTree($id, $userId);

        if (!$tree) {
            $this->json(['erro' => 'Semestre não encontrado.'], 404);
        }

        $this->json($tree);
    }

    public function update(int $id): void
    {
        $userId = $this->requireAuth();
        $current = Semester::findOwned($id, $userId);
        if (!$current) {
            $this->json(['erro' => 'Semestre não encontrado.'], 404);
        }

        $data = Request::json();
        $this->validateOptionalDates($data);

        $merged = array_merge($current, $data);
        if (($merged['data_inicio'] ?? null) !== null && ($merged['data_fim'] ?? null) !== null && $merged['data_fim'] < $merged['data_inicio']) {
            $this->json(['erro' => 'A data de fim não pode ser anterior à data de início.'], 422);
        }

        if (isset($data['ano']) && ((int) $data['ano'] < 1 || !filter_var($data['ano'], FILTER_VALIDATE_INT))) {
            $this->json(['erro' => 'O campo ano deve ser um número inteiro positivo.'], 422);
        }

        $success = Semester::update($id, $userId, $data);
        if (!$success) {
            $this->json(['erro' => 'Nenhum campo válido para atualizar.'], 422);
        }

        $this->json(['id' => $id, 'atualizado' => true]);
    }

    public function delete(int $id): void
    {
        $userId = $this->requireAuth();
        $current = Semester::findOwned($id, $userId);
        if (!$current) {
            $this->json(['erro' => 'Semestre não encontrado.'], 404);
        }

        Semester::delete($id, $userId);
        $this->json(['id' => $id, 'removido' => true]);
    }

    private function validateOptionalDates(array $data): void
    {
        foreach (['data_inicio', 'data_fim'] as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null) {
                continue;
            }
            $date = DateTimeImmutable::createFromFormat('Y-m-d', (string) $data[$field]);
            if (!is_string($data[$field]) || $date === false || $date->format('Y-m-d') !== $data[$field]) {
                $this->json(['erro' => "O campo {$field} deve estar em formato YYYY-MM-DD."], 422);
            }
        }
    }
}
