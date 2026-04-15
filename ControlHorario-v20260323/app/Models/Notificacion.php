<?php

namespace App\Models;

use App\Core\Database;

class Notificacion
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO notificaciones
                    (usuario_id, tipo, titulo, mensaje, referencia_tipo, referencia_id, leida, created_at)
                VALUES
                    (:usuario_id, :tipo, :titulo, :mensaje, :referencia_tipo, :referencia_id, 0, NOW())";

        $this->db->execute($sql, [
            ':usuario_id'     => $data['usuario_id'],
            ':tipo'           => $data['tipo'],
            ':titulo'         => $data['titulo'],
            ':mensaje'        => $data['mensaje'],
            ':referencia_tipo' => $data['referencia_tipo'] ?? null,
            ':referencia_id'  => $data['referencia_id'] ?? null,
        ]);

        return (int)$this->db->lastInsertId() ?: false;
    }

    public function findByUsuario(int $userId, bool $onlyUnread = false): array
    {
        $unreadCondition = $onlyUnread ? "AND leida = 0" : "";

        return $this->db->fetchAll(
            "SELECT * FROM notificaciones
             WHERE usuario_id = ? {$unreadCondition}
             ORDER BY created_at DESC
             LIMIT 50",
            [$userId]
        );
    }

    public function markRead(int $id): bool
    {
        return $this->db->execute(
            "UPDATE notificaciones SET leida = 1, fecha_lectura = NOW() WHERE id = ?",
            [$id]
        ) > 0;
    }

    public function markAllRead(int $userId): bool
    {
        return $this->db->execute(
            "UPDATE notificaciones SET leida = 1, fecha_lectura = NOW()
             WHERE usuario_id = ? AND leida = 0",
            [$userId]
        ) > 0;
    }

    public function countUnread(int $userId): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM notificaciones WHERE usuario_id = ? AND leida = 0",
            [$userId]
        );
        return (int)($result['total'] ?? 0);
    }

    public function getRecent(int $userId, int $limit = 5): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY created_at DESC LIMIT ?",
            [$userId, $limit]
        );
    }
}
