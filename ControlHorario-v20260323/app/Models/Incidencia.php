<?php

namespace App\Models;

use App\Core\Database;

class Incidencia
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int|false
    {
        $numero = $this->generarNumero();

        $sql = "INSERT INTO incidencias
                    (numero_incidencia, usuario_id, tipo, fecha_fichaje, hora_solicitada,
                     razon, estado, fecha_solicitud, created_at)
                VALUES
                    (:numero, :usuario_id, :tipo, :fecha_fichaje, :hora_solicitada,
                     :razon, 'pendiente', NOW(), NOW())";

        $this->db->execute($sql, [
            ':numero'          => $numero,
            ':usuario_id'      => $data['usuario_id'],
            ':tipo'            => $data['tipo'] ?? 'otro',
            ':fecha_fichaje'   => $data['fecha_fichaje'],
            ':hora_solicitada' => $data['hora_solicitada'] ?? null,
            ':razon'           => $data['razon'],
        ]);

        return (int)$this->db->lastInsertId() ?: false;
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT i.*, u.nombre, u.apellidos, u.email,
                    a.nombre as admin_nombre, a.apellidos as admin_apellidos
             FROM incidencias i
             JOIN usuarios u ON u.id = i.usuario_id
             LEFT JOIN usuarios a ON a.id = i.admin_id
             WHERE i.id = ?",
            [$id]
        );
    }

    public function findByUsuario(int $userId, array $filters = []): array
    {
        $where  = ["i.usuario_id = :usuario_id"];
        $params = [':usuario_id' => $userId];

        if (!empty($filters['estado'])) {
            $where[]        = "i.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        return $this->db->fetchAll(
            "SELECT i.*, u.nombre, u.apellidos
             FROM incidencias i
             JOIN usuarios u ON u.id = i.usuario_id
             {$whereClause}
             ORDER BY i.fecha_solicitud DESC
             LIMIT 100",
            $params
        );
    }

    public function findAll(array $filters = []): array
    {
        $where  = ["1=1"];
        $params = [];

        if (!empty($filters['estado'])) {
            $where[]        = "i.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }

        if (!empty($filters['usuario_id'])) {
            $where[]              = "i.usuario_id = :usuario_id";
            $params[':usuario_id'] = $filters['usuario_id'];
        }

        if (!empty($filters['tipo'])) {
            $where[]       = "i.tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $limit       = 'LIMIT ' . (int)($filters['limit'] ?? 100);

        return $this->db->fetchAll(
            "SELECT i.*, u.nombre, u.apellidos, u.email,
                    a.nombre as admin_nombre, a.apellidos as admin_apellidos
             FROM incidencias i
             JOIN usuarios u ON u.id = i.usuario_id
             LEFT JOIN usuarios a ON a.id = i.admin_id
             {$whereClause}
             ORDER BY
                CASE i.estado WHEN 'pendiente' THEN 0 WHEN 'aceptada' THEN 1 ELSE 2 END,
                i.fecha_solicitud DESC
             {$limit}",
            $params
        );
    }

    public function updateEstado(int $id, string $estado, int $adminId, string $comentario = ''): bool
    {
        return $this->db->execute(
            "UPDATE incidencias
             SET estado = ?, admin_id = ?, comentario_admin = ?, fecha_resolucion = NOW()
             WHERE id = ? AND estado = 'pendiente'",
            [$estado, $adminId, $comentario, $id]
        ) > 0;
    }

    public function generarNumero(): string
    {
        $year = date('Y');
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM incidencias WHERE YEAR(created_at) = ?",
            [$year]
        );
        $seq = ((int)($result['total'] ?? 0)) + 1;
        return sprintf('INC-%s-%04d', $year, $seq);
    }

    public function countPendientes(): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM incidencias WHERE estado = 'pendiente'"
        );
        return (int)($result['total'] ?? 0);
    }

    public function cancelar(int $id, int $userId): bool
    {
        return $this->db->execute(
            "UPDATE incidencias
             SET estado = 'rechazada', comentario_admin = 'Cancelada por el usuario', fecha_resolucion = NOW()
             WHERE id = ? AND usuario_id = ? AND estado = 'pendiente'",
            [$id, $userId]
        ) > 0;
    }
}
