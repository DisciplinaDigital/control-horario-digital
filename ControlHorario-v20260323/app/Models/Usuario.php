<?php

namespace App\Models;

use App\Core\Database;

class Usuario
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM usuarios WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM usuarios WHERE email = ? AND deleted_at IS NULL",
            [strtolower(trim($email))]
        );
    }

    public function create(array $data): int|false
    {
        $sql = "INSERT INTO usuarios
                    (nombre, apellidos, email, password_hash, role, activo, telefono, departamento,
                     dias_vacaciones_anuales, terminos_aceptados, created_at, updated_at)
                VALUES
                    (:nombre, :apellidos, :email, :password_hash, :role, :activo, :telefono, :departamento,
                     :dias_vacaciones_anuales, :terminos_aceptados, NOW(), NOW())";

        $this->db->execute($sql, [
            ':nombre'                  => $data['nombre'],
            ':apellidos'               => $data['apellidos'],
            ':email'                   => strtolower(trim($data['email'])),
            ':password_hash'           => $this->hashPassword($data['password']),
            ':role'                    => $data['role'] ?? 'usuario',
            ':activo'                  => $data['activo'] ?? 1,
            ':telefono'                => $data['telefono'] ?? null,
            ':departamento'            => $data['departamento'] ?? null,
            ':dias_vacaciones_anuales' => $data['dias_vacaciones_anuales'] ?? 22,
            ':terminos_aceptados'      => $data['terminos_aceptados'] ?? 0,
        ]);

        $id = (int)$this->db->lastInsertId();

        // Initialize vacation days for current year
        if ($id) {
            $this->db->execute(
                "INSERT INTO dias_vacaciones (usuario_id, anio, dias_totales, dias_usados, dias_pendientes)
                 VALUES (?, YEAR(NOW()), ?, 0, ?)
                 ON DUPLICATE KEY UPDATE dias_totales = VALUES(dias_totales), dias_pendientes = VALUES(dias_pendientes)",
                [$id, $data['dias_vacaciones_anuales'] ?? 22, $data['dias_vacaciones_anuales'] ?? 22]
            );
        }

        return $id;
    }

    public function update(int $id, array $data): bool
    {
        $allowedFields = ['nombre', 'apellidos', 'email', 'role', 'activo', 'telefono',
                          'departamento', 'dias_vacaciones_anuales', 'terminos_aceptados',
                          'fecha_aceptacion_terminos', 'ultimo_acceso', 'max_distance_override',
                          'must_change_password', 'password_hash'];

        $sets   = [];
        $params = [];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $data)) {
                $sets[]          = "{$field} = :{$field}";
                $params[":{$field}"] = $data[$field];
            }
        }

        if (isset($data['password'])) {
            $sets[]            = "password_hash = :password_hash";
            $params[':password_hash'] = $this->hashPassword($data['password']);
        }

        if (empty($sets)) {
            return false;
        }

        $sets[]        = "updated_at = NOW()";
        $params[':id'] = $id;

        $sql = "UPDATE usuarios SET " . implode(', ', $sets) . " WHERE id = :id AND deleted_at IS NULL";
        return $this->db->execute($sql, $params) > 0;
    }

    public function softDelete(int $id): bool
    {
        // Solo desactiva — NO establece deleted_at.
        // deleted_at se reserva para el borrado legal tras 4 años de retención.
        // El usuario inactivo sigue visible en el panel para cumplir la Ley de Registro de Jornada.
        return $this->db->execute(
            "UPDATE usuarios SET activo = 0, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL",
            [$id]
        ) > 0;
    }

    public function reactivate(int $id): bool
    {
        return $this->db->execute(
            "UPDATE usuarios SET activo = 1, updated_at = NOW() WHERE id = ? AND deleted_at IS NULL",
            [$id]
        ) > 0;
    }

    public function all(array $filters = []): array
    {
        $where  = ["u.deleted_at IS NULL"];
        $params = [];   // solo posicionales para evitar mezcla con PDO

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[]  = "(u.nombre LIKE ? OR u.apellidos LIKE ? OR u.email LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if (!empty($filters['role'])) {
            $where[]  = "u.role = ?";
            $params[] = $filters['role'];
        }

        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $where[]  = "u.activo = ?";
            $params[] = (int)$filters['activo'];
        }

        if (!empty($filters['departamento'])) {
            $where[]  = "u.departamento = ?";
            $params[] = $filters['departamento'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $orderBy     = 'u.activo DESC, u.apellidos ASC, u.nombre ASC';
        $limit       = isset($filters['limit']) ? 'LIMIT ' . (int)$filters['limit'] : '';
        $offset      = isset($filters['offset']) ? 'OFFSET ' . (int)$filters['offset'] : '';

        return $this->db->fetchAll(
            "SELECT u.*,
                    COALESCE(dv.dias_totales, u.dias_vacaciones_anuales) as vacaciones_totales,
                    COALESCE(dv.dias_usados, 0) as vacaciones_usadas
             FROM usuarios u
             LEFT JOIN dias_vacaciones dv ON dv.usuario_id = u.id AND dv.anio = YEAR(NOW())
             {$whereClause}
             ORDER BY {$orderBy}
             {$limit} {$offset}",
            $params
        );
    }

    public function count(array $filters = []): int
    {
        $where  = ["deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $where[]  = "(nombre LIKE ? OR apellidos LIKE ? OR email LIKE ?)";
            $params[] = $s;
            $params[] = $s;
            $params[] = $s;
        }

        if (!empty($filters['role'])) {
            $where[]  = "role = ?";
            $params[] = $filters['role'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $result = $this->db->fetchOne("SELECT COUNT(*) as total FROM usuarios {$whereClause}", $params);
        return (int)($result['total'] ?? 0);
    }

    public function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public function hashPassword(string $plain): string
    {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function updateLastAccess(int $id): void
    {
        $this->db->execute(
            "UPDATE usuarios SET ultimo_acceso = NOW(), updated_at = NOW() WHERE id = ?",
            [$id]
        );
    }

    public function getActiveToday(): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(DISTINCT f.usuario_id) as total
             FROM fichajes f
             JOIN usuarios u ON u.id = f.usuario_id
             WHERE DATE(f.fecha_hora) = CURDATE()
               AND u.role = 'usuario'
               AND u.activo = 1
               AND u.deleted_at IS NULL",
            []
        );
        return (int)($result['total'] ?? 0);
    }

    public function getDepartamentos(): array
    {
        return $this->db->fetchAll(
            "SELECT DISTINCT departamento FROM usuarios
             WHERE departamento IS NOT NULL AND deleted_at IS NULL
             ORDER BY departamento"
        );
    }
}
