<?php

declare(strict_types=1);

namespace App\Models;

final class Semester extends BaseModel
{
    public static function listByUser(int $userId): array
    {
        $stmt = self::db()->prepare(
            'SELECT id, nome, ano, data_inicio, data_fim 
             FROM semestres 
             WHERE usuario_id = ? 
             ORDER BY ano DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public static function findOwned(int $id, int $userId): ?array
    {
        $stmt = self::db()->prepare(
            'SELECT id, nome, ano, data_inicio, data_fim 
             FROM semestres 
             WHERE id = ? AND usuario_id = ? 
             LIMIT 1'
        );
        $stmt->execute([$id, $userId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public static function create(int $userId, string $name, int $year, ?string $start = null, ?string $end = null): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO semestres (usuario_id, nome, ano, data_inicio, data_fim) 
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$userId, trim($name), $year, $start, $end]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $allowed = ['nome', 'ano', 'data_inicio', 'data_fim'];
        $updates = [];
        $params = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $updates[] = "{$field} = ?";
            $params[] = $data[$field];
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $params[] = $userId;

        $sql = 'UPDATE semestres SET ' . implode(', ', $updates) . ' WHERE id = ? AND usuario_id = ?';
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id, int $userId): bool
    {
        $stmt = self::db()->prepare('DELETE FROM semestres WHERE id = ? AND usuario_id = ?');
        return $stmt->execute([$id, $userId]);
    }

    public static function getTree(int $id, int $userId): ?array
    {
        $semester = self::findOwned($id, $userId);
        if (!$semester) {
            return null;
        }

        $sql = 'SELECT d.id AS disciplina_id, d.nome AS disciplina_nome, d.descricao, d.data_inicio, d.data_fim, d.data_prova, d.ordem AS disciplina_ordem, 
                       u.id AS unidade_id, u.titulo AS unidade_titulo, u.ordem AS unidade_ordem, 
                       i.id AS item_id, i.titulo AS item_titulo, i.tipo, i.ordem AS item_ordem, 
                       p.concluido_em 
                FROM disciplinas d 
                LEFT JOIN unidades u ON u.disciplina_id = d.id 
                LEFT JOIN itens_estudo i ON i.unidade_id = u.id 
                LEFT JOIN progresso_itens p ON p.item_estudo_id = i.id AND p.usuario_id = ? 
                WHERE d.semestre_id = ? 
                ORDER BY d.ordem, d.id, u.ordem, u.id, i.ordem, i.id';

        $stmt = self::db()->prepare($sql);
        $stmt->execute([$userId, $id]);

        $disciplines = [];
        foreach ($stmt as $row) {
            $dId = (int) $row['disciplina_id'];
            $disciplines[$dId] ??= [
                'id' => $dId,
                'nome' => $row['disciplina_nome'],
                'descricao' => $row['descricao'],
                'data_inicio' => $row['data_inicio'],
                'data_fim' => $row['data_fim'],
                'data_prova' => $row['data_prova'],
                'ordem' => (int) $row['disciplina_ordem'],
                'unidades' => [],
            ];

            if ($row['unidade_id'] === null) {
                continue;
            }

            $uId = (int) $row['unidade_id'];
            $disciplines[$dId]['unidades'][$uId] ??= [
                'id' => $uId,
                'titulo' => $row['unidade_titulo'],
                'ordem' => (int) $row['unidade_ordem'],
                'itens' => [],
            ];

            if ($row['item_id'] !== null) {
                $disciplines[$dId]['unidades'][$uId]['itens'][] = [
                    'id' => (int) $row['item_id'],
                    'titulo' => $row['item_titulo'],
                    'tipo' => $row['tipo'],
                    'ordem' => (int) $row['item_ordem'],
                    'concluido' => $row['concluido_em'] !== null,
                    'concluido_em' => $row['concluido_em'],
                ];
            }
        }

        foreach ($disciplines as &$d) {
            $d['unidades'] = array_values($d['unidades']);
        }
        unset($d);

        $semester['disciplinas'] = array_values($disciplines);
        return $semester;
    }
}
