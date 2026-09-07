<?php

declare(strict_types=1);

namespace App\Models;

final class Discipline extends BaseModel
{
    public static function create(
        int $semesterId,
        string $name,
        ?string $description = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?string $examDate = null,
        int $order = 0
    ): int {
        $stmt = self::db()->prepare(
            'INSERT INTO disciplinas (semestre_id, nome, descricao, data_inicio, data_fim, data_prova, ordem) 
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$semesterId, trim($name), $description, $startDate, $endDate, $examDate, $order]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $allowed = ['nome', 'descricao', 'data_inicio', 'data_fim', 'data_prova', 'ordem'];
        $updates = [];
        $params = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $updates[] = "{$field} = ?";
            $params[] = $field === 'ordem' ? (int) $data[$field] : ($field === 'nome' ? trim((string) $data[$field]) : $data[$field]);
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE disciplinas SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM disciplinas WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function getOwnerId(int $id): ?int
    {
        $stmt = self::db()->prepare(
            'SELECT s.usuario_id 
             FROM disciplinas d 
             JOIN semestres s ON s.id = d.semestre_id 
             WHERE d.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }
}
