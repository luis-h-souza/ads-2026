<?php

declare(strict_types=1);

namespace App\Models;

final class Unit extends BaseModel
{
    public static function create(int $disciplineId, string $title, int $order = 0): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO unidades (disciplina_id, titulo, ordem) VALUES (?, ?, ?)'
        );
        $stmt->execute([$disciplineId, trim($title), $order]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $allowed = ['titulo', 'ordem'];
        $updates = [];
        $params = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $updates[] = "{$field} = ?";
            $params[] = $field === 'ordem' ? (int) $data[$field] : trim((string) $data[$field]);
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE unidades SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM unidades WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function getOwnerId(int $id): ?int
    {
        $stmt = self::db()->prepare(
            'SELECT s.usuario_id 
             FROM unidades u 
             JOIN disciplinas d ON d.id = u.disciplina_id 
             JOIN semestres s ON s.id = d.semestre_id 
             WHERE u.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }
}
