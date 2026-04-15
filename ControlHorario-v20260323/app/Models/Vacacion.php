<?php

namespace App\Models;

use App\Core\Database;

class Vacacion
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO vacaciones
                    (usuario_id, fecha_inicio, fecha_fin, tipo, estado, origen,
                     comentario_usuario, fecha_solicitud, created_at)
                VALUES
                    (:usuario_id, :fecha_inicio, :fecha_fin, :tipo, 'pendiente', :origen,
                     :comentario_usuario, NOW(), NOW())";

        $this->db->execute($sql, [
            ':usuario_id'        => $data['usuario_id'],
            ':fecha_inicio'      => $data['fecha_inicio'],
            ':fecha_fin'         => $data['fecha_fin'],
            ':tipo'              => $data['tipo'] ?? 'normal',
            ':origen'            => $data['origen'] ?? 'solicitada',
            ':comentario_usuario' => $data['comentario_usuario'] ?? null,
        ]);

        return (int)$this->db->lastInsertId() ?: false;
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT v.*, u.nombre, u.apellidos, u.email,
                    a.nombre as admin_nombre, a.apellidos as admin_apellidos
             FROM vacaciones v
             JOIN usuarios u ON u.id = v.usuario_id
             LEFT JOIN usuarios a ON a.id = v.admin_id
             WHERE v.id = ?",
            [$id]
        );
    }

    public function findByUsuario(int $userId, ?int $year = null): array
    {
        $params = [$userId];
        $yearCondition = '';

        if ($year) {
            $yearCondition = "AND (YEAR(v.fecha_inicio) = ? OR YEAR(v.fecha_fin) = ?)";
            $params[] = $year;
            $params[] = $year;
        }

        return $this->db->fetchAll(
            "SELECT v.*, u.nombre, u.apellidos
             FROM vacaciones v
             JOIN usuarios u ON u.id = v.usuario_id
             WHERE v.usuario_id = ? {$yearCondition}
             ORDER BY v.fecha_inicio DESC",
            $params
        );
    }

    public function findAll(array $filters = []): array
    {
        $where  = ["1=1"];
        $params = [];

        if (!empty($filters['estado'])) {
            $where[]        = "v.estado = :estado";
            $params[':estado'] = $filters['estado'];
        }

        if (!empty($filters['usuario_id'])) {
            $where[]              = "v.usuario_id = :usuario_id";
            $params[':usuario_id'] = $filters['usuario_id'];
        }

        if (!empty($filters['year'])) {
            $where[]        = "(YEAR(v.fecha_inicio) = :year_ini OR YEAR(v.fecha_fin) = :year_fin)";
            $params[':year_ini'] = $filters['year'];
            $params[':year_fin'] = $filters['year'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        return $this->db->fetchAll(
            "SELECT v.*, u.nombre, u.apellidos, u.email, u.departamento,
                    a.nombre as admin_nombre, a.apellidos as admin_apellidos,
                    DATEDIFF(v.fecha_fin, v.fecha_inicio) + 1 as dias_totales
             FROM vacaciones v
             JOIN usuarios u ON u.id = v.usuario_id
             LEFT JOIN usuarios a ON a.id = v.admin_id
             {$whereClause}
             ORDER BY
                CASE v.estado WHEN 'pendiente' THEN 0 WHEN 'aprobada' THEN 1 ELSE 2 END,
                v.fecha_inicio DESC
             LIMIT 500",
            $params
        );
    }

    public function updateEstado(int $id, string $estado, int $adminId, string $comentario = ''): bool
    {
        $sql = "UPDATE vacaciones
                SET estado = ?, admin_id = ?, comentario_admin = ?, fecha_resolucion = NOW()
                WHERE id = ? AND estado = 'pendiente'";

        $updated = $this->db->execute($sql, [$estado, $adminId, $comentario, $id]) > 0;

        if ($updated && $estado === 'aprobada') {
            // Update vacation days tracking
            $vacacion = $this->findById($id);
            if ($vacacion) {
                $days = $this->calcularDiasLaborables($vacacion['fecha_inicio'], $vacacion['fecha_fin']);
                $year = date('Y', strtotime($vacacion['fecha_inicio']));
                $this->db->execute(
                    "INSERT INTO dias_vacaciones (usuario_id, anio, dias_totales, dias_usados, dias_pendientes)
                     VALUES (?, ?, 22, ?, 0)
                     ON DUPLICATE KEY UPDATE
                         dias_usados = dias_usados + ?,
                         dias_pendientes = GREATEST(0, dias_totales - dias_usados - ?)",
                    [$vacacion['usuario_id'], $year, $days, $days, $days]
                );
            }
        }

        return $updated;
    }

    public function cancelar(int $id, int $userId): bool
    {
        $vacacion = $this->findById($id);
        if (!$vacacion || $vacacion['usuario_id'] !== $userId) {
            return false;
        }

        $updated = $this->db->execute(
            "UPDATE vacaciones SET estado = 'cancelada', fecha_cancelacion = NOW()
             WHERE id = ? AND usuario_id = ? AND estado IN ('pendiente', 'aprobada')",
            [$id, $userId]
        ) > 0;

        // If was approved, restore vacation days
        if ($updated && $vacacion['estado'] === 'aprobada') {
            $days = $this->calcularDiasLaborables($vacacion['fecha_inicio'], $vacacion['fecha_fin']);
            $year = date('Y', strtotime($vacacion['fecha_inicio']));
            $this->db->execute(
                "UPDATE dias_vacaciones
                 SET dias_usados = GREATEST(0, dias_usados - ?),
                     dias_pendientes = LEAST(dias_totales, dias_pendientes + ?)
                 WHERE usuario_id = ? AND anio = ?",
                [$days, $days, $userId, $year]
            );
        }

        return $updated;
    }

    public function getDiasUsados(int $userId, int $year): int
    {
        $result = $this->db->fetchOne(
            "SELECT dias_usados FROM dias_vacaciones WHERE usuario_id = ? AND anio = ?",
            [$userId, $year]
        );
        return (int)($result['dias_usados'] ?? 0);
    }

    public function getDiasInfo(int $userId, int $year): array
    {
        $result = $this->db->fetchOne(
            "SELECT * FROM dias_vacaciones WHERE usuario_id = ? AND anio = ?",
            [$userId, $year]
        );

        if (!$result) {
            // Get default from user
            $user = $this->db->fetchOne("SELECT dias_vacaciones_anuales FROM usuarios WHERE id = ?", [$userId]);
            $total = (int)($user['dias_vacaciones_anuales'] ?? 22);
            return ['dias_totales' => $total, 'dias_usados' => 0, 'dias_pendientes' => $total];
        }

        return $result;
    }

    public function isDateAvailable(int $userId, string $date): bool
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM vacaciones
             WHERE usuario_id = ?
               AND estado IN ('pendiente', 'aprobada')
               AND ? BETWEEN fecha_inicio AND fecha_fin",
            [$userId, $date]
        );
        return ((int)($result['total'] ?? 0)) === 0;
    }

    public function getNoDisponibles(int $userId, int $year): array
    {
        $rows = $this->db->fetchAll(
            "SELECT fecha_inicio, fecha_fin, estado FROM vacaciones
             WHERE usuario_id = ? AND estado IN ('pendiente', 'aprobada')
               AND (YEAR(fecha_inicio) = ? OR YEAR(fecha_fin) = ?)",
            [$userId, $year, $year]
        );

        $dates = [];
        foreach ($rows as $row) {
            $start = new \DateTime($row['fecha_inicio']);
            $end   = new \DateTime($row['fecha_fin']);
            $end->modify('+1 day');

            $interval = new \DateInterval('P1D');
            $period   = new \DatePeriod($start, $interval, $end);

            foreach ($period as $dt) {
                $dates[] = [
                    'date'   => $dt->format('Y-m-d'),
                    'estado' => $row['estado'],
                ];
            }
        }

        return $dates;
    }

    public function countPendientes(): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM vacaciones WHERE estado = 'pendiente'"
        );
        return (int)($result['total'] ?? 0);
    }

    private function calcularDiasLaborables(string $from, string $to): int
    {
        $start = new \DateTime($from);
        $end   = new \DateTime($to);
        $end->modify('+1 day');

        $interval = new \DateInterval('P1D');
        $period   = new \DatePeriod($start, $interval, $end);

        $days = 0;
        foreach ($period as $dt) {
            $dow = (int)$dt->format('N');
            if ($dow < 6) { // Mon-Fri
                $days++;
            }
        }

        return $days;
    }

    public function asignar(array $data): int|false
    {
        $sql = "INSERT INTO vacaciones
                    (usuario_id, admin_id, fecha_inicio, fecha_fin, tipo, estado, origen,
                     comentario_admin, fecha_solicitud, fecha_resolucion, created_at)
                VALUES
                    (:usuario_id, :admin_id, :fecha_inicio, :fecha_fin, :tipo, 'aprobada', 'empresa',
                     :comentario_admin, NOW(), NOW(), NOW())";

        $this->db->execute($sql, [
            ':usuario_id'     => $data['usuario_id'],
            ':admin_id'       => $data['admin_id'],
            ':fecha_inicio'   => $data['fecha_inicio'],
            ':fecha_fin'      => $data['fecha_fin'],
            ':tipo'           => $data['tipo'] ?? 'normal',
            ':comentario_admin' => $data['comentario_admin'] ?? 'Asignado por administración',
        ]);

        return (int)$this->db->lastInsertId() ?: false;
    }
}
