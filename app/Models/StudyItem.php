<?php

declare(strict_types=1);

namespace App\Models;

final class StudyItem extends BaseModel
{
    public static function create(int $unitId, string $title, string $type = 'aula', int $order = 0): int
    {
        $stmt = self::db()->prepare(
            'INSERT INTO itens_estudo (unidade_id, titulo, tipo, ordem) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$unitId, trim($title), $type, $order]);
        return (int) self::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $allowed = ['titulo', 'tipo', 'ordem'];
        $updates = [];
        $params = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $updates[] = "{$field} = ?";
            if ($field === 'ordem') {
                $params[] = (int) $data[$field];
            } elseif ($field === 'titulo') {
                $params[] = trim((string) $data[$field]);
            } else {
                $params[] = (string) $data[$field];
            }
        }

        if (empty($updates)) {
            return false;
        }

        $params[] = $id;
        $sql = 'UPDATE itens_estudo SET ' . implode(', ', $updates) . ' WHERE id = ?';
        $stmt = self::db()->prepare($sql);
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $stmt = self::db()->prepare('DELETE FROM itens_estudo WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public static function setProgress(int $userId, int $itemId, bool $completed): bool
    {
        if ($completed) {
            $stmt = self::db()->prepare(
                'INSERT INTO progresso_itens (usuario_id, item_estudo_id, concluido_em) 
                 VALUES (?, ?, NOW()) 
                 ON DUPLICATE KEY UPDATE concluido_em = NOW()'
            );
            return $stmt->execute([$userId, $itemId]);
        }

        $stmt = self::db()->prepare(
            'DELETE FROM progresso_itens WHERE usuario_id = ? AND item_estudo_id = ?'
        );
        return $stmt->execute([$userId, $itemId]);
    }

    public static function getOwnerId(int $id): ?int
    {
        $stmt = self::db()->prepare(
            'SELECT s.usuario_id 
             FROM itens_estudo i 
             JOIN unidades u ON u.id = i.unidade_id 
             JOIN disciplinas d ON d.id = u.disciplina_id 
             JOIN semestres s ON s.id = d.semestre_id 
             WHERE i.id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (int) $val : null;
    }
}
