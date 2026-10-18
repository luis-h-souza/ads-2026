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

    public static function updateExam(int $id, ?string $date, ?string $time, bool $completed, ?float $grade): void
    {
        $db = self::db();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare('UPDATE disciplinas SET data_prova = ? WHERE id = ?');
            $stmt->execute([$date, $id]);

            $stmt = $db->prepare(
                'INSERT INTO provas (disciplina_id, horario, concluida, nota)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE horario = VALUES(horario), concluida = VALUES(concluida), nota = VALUES(nota)'
            );
            $stmt->execute([$id, $time, $completed ? 1 : 0, $grade]);

            $db->commit();
        } catch (\Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }

    public static function canSetResult(int $id, int $userId): bool
    {
        $stmt = self::db()->prepare(
            'SELECT COUNT(i.id) AS total_items,
                    SUM(CASE WHEN p.item_estudo_id IS NOT NULL THEN 1 ELSE 0 END) AS completed_items,
                    MAX(COALESCE(ex.concluida, 0)) AS exam_completed
             FROM disciplinas d
             LEFT JOIN unidades u ON u.disciplina_id = d.id
             LEFT JOIN itens_estudo i ON i.unidade_id = u.id
             LEFT JOIN progresso_itens p ON p.item_estudo_id = i.id AND p.usuario_id = ?
             LEFT JOIN provas ex ON ex.disciplina_id = d.id
             WHERE d.id = ?
             GROUP BY d.id'
        );
        $stmt->execute([$userId, $id]);
        $progress = $stmt->fetch();

        return $progress !== false
            && (int) $progress['total_items'] > 0
            && (int) $progress['total_items'] === (int) $progress['completed_items']
            && (int) $progress['exam_completed'] === 1;
    }

    public static function setResult(int $id, string $result): bool
    {
        $stmt = self::db()->prepare('UPDATE disciplinas SET resultado = ? WHERE id = ?');
        return $stmt->execute([$result, $id]);
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
