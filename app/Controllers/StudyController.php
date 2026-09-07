<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Models\Discipline;
use App\Models\Semester;
use App\Models\StudyItem;
use App\Models\Unit;
use DateTimeImmutable;

final class StudyController extends BaseController
{
    public function storeDiscipline(): void
    {
        $userId = $this->requireAuth();
        $data = Request::json();
        Request::requireFields($data, ['semestre_id', 'nome']);

        $this->validateOptionalDates($data, ['data_inicio', 'data_fim', 'data_prova']);
        if (($data['data_inicio'] ?? null) !== null && ($data['data_fim'] ?? null) !== null && $data['data_fim'] < $data['data_inicio']) {
            $this->json(['erro' => 'A data de fim não pode ser anterior à data de início.'], 422);
        }

        $semester = Semester::findOwned((int) $data['semestre_id'], $userId);
        if (!$semester) {
            $this->json(['erro' => 'Semestre não encontrado.'], 404);
        }

        $id = Discipline::create(
            (int) $data['semestre_id'],
            (string) $data['nome'],
            $data['descricao'] ?? null,
            $data['data_inicio'] ?? null,
            $data['data_fim'] ?? null,
            $data['data_prova'] ?? null,
            (int) ($data['ordem'] ?? 0)
        );

        $this->json(['id' => $id], 201);
    }

    public function updateDiscipline(int $id): void
    {
        $userId = $this->requireAuth();
        $ownerId = Discipline::getOwnerId($id);
        if ($ownerId !== $userId) {
            $this->json(['erro' => 'Disciplina não encontrada.'], 404);
        }

        $data = Request::json();
        $this->validateOptionalDates($data, ['data_inicio', 'data_fim', 'data_prova']);
        if (isset($data['data_inicio'], $data['data_fim']) && $data['data_inicio'] !== null && $data['data_fim'] !== null && $data['data_fim'] < $data['data_inicio']) {
            $this->json(['erro' => 'A data de fim não pode ser anterior à data de início.'], 422);
        }

        $success = Discipline::update($id, $data);
        if (!$success) {
            $this->json(['erro' => 'Nenhum campo válido para atualizar.'], 422);
        }

        $this->json(['id' => $id, 'atualizado' => true]);
    }

    public function deleteDiscipline(int $id): void
    {
        $userId = $this->requireAuth();
        $ownerId = Discipline::getOwnerId($id);
        if ($ownerId !== $userId) {
            $this->json(['erro' => 'Disciplina não encontrada.'], 404);
        }

        Discipline::delete($id);
        $this->json(['id' => $id, 'removido' => true]);
    }

    public function storeUnit(): void
    {
        $userId = $this->requireAuth();
        $data = Request::json();
        Request::requireFields($data, ['disciplina_id', 'titulo']);

        $ownerId = Discipline::getOwnerId((int) $data['disciplina_id']);
        if ($ownerId !== $userId) {
            $this->json(['erro' => 'Disciplina não encontrada.'], 404);
        }

        $id = Unit::create((int) $data['disciplina_id'], (string) $data['titulo'], (int) ($data['ordem'] ?? 0));
        $this->json(['id' => $id], 201);
    }

    public function updateUnit(int $id): void
    {
        $userId = $this->requireAuth();
        $ownerId = Unit::getOwnerId($id);
        if ($ownerId !== $userId) {
            $this->json(['erro' => 'Unidade não encontrada.'], 404);
        }

        $data = Request::json();
        $success = Unit::update($id, $data);
        if (!$success) {
            $this->json(['erro' => 'Nenhum campo válido para atualizar.'], 422);
        }

        $this->json(['id' => $id, 'atualizado' => true]);
    }

    public function deleteUnit(int $id): void
    {
        $userId = $this->requireAuth();
        $ownerId = Unit::getOwnerId($id);
        if ($ownerId !== $userId) {
            $this->json(['erro' => 'Unidade não encontrada.'], 404);
        }

        Unit::delete($id);
        $this->json(['id' => $id, 'removido' => true]);
    }

    public function storeItem(): void
    {
        $userId = $this->requireAuth();
        $data = Request::json();
        Request::requireFields($data, ['unidade_id', 'titulo']);

        $ownerId = Unit::getOwnerId((int) $data['unidade_id']);
        if ($ownerId !== $userId) {
            $this->json(['erro' => 'Unidade não encontrada.'], 404);
        }

        $type = $data['tipo'] ?? 'aula';
        if (!in_array($type, ['aula', 'exercicio', 'tarefa'], true)) {
            $this->json(['erro' => 'Tipo de item inválido.'], 422);
        }

        $id = StudyItem::create((int) $data['unidade_id'], (string) $data['titulo'], $type, (int) ($data['ordem'] ?? 0));
        $this->json(['id' => $id], 201);
    }

    public function updateItem(int $id): void
    {
        $userId = $this->requireAuth();
        $ownerId = StudyItem::getOwnerId($id);
        if ($ownerId !== $userId) {
            $this->json(['erro' => 'Item não encontrado.'], 404);
        }

        $data = Request::json();
        if (isset($data['tipo']) && !in_array($data['tipo'], ['aula', 'exercicio', 'tarefa'], true)) {
            $this->json(['erro' => 'Tipo de item inválido.'], 422);
        }

        $success = StudyItem::update($id, $data);
        if (!$success) {
            $this->json(['erro' => 'Nenhum campo válido para atualizar.'], 422);
        }

        $this->json(['id' => $id, 'atualizado' => true]);
    }

    public function deleteItem(int $id): void
    {
        $userId = $this->requireAuth();
        $ownerId = StudyItem::getOwnerId($id);
        if ($ownerId !== $userId) {
            $this->json(['erro' => 'Item não encontrado.'], 404);
        }

        StudyItem::delete($id);
        $this->json(['id' => $id, 'removido' => true]);
    }

    public function updateProgress(int $id): void
    {
        $userId = $this->requireAuth();
        $ownerId = StudyItem::getOwnerId($id);
        if ($ownerId !== $userId) {
            $this->json(['erro' => 'Item não encontrado.'], 404);
        }

        $data = Request::json();
        if (!array_key_exists('concluido', $data) || !is_bool($data['concluido'])) {
            $this->json(['erro' => 'O campo concluido deve ser booleano.'], 422);
        }

        StudyItem::setProgress($userId, $id, $data['concluido']);
        $this->json(['item_id' => $id, 'concluido' => $data['concluido']]);
    }

    private function validateOptionalDates(array $data, array $fields): void
    {
        foreach ($fields as $field) {
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
