<?php

namespace App\Services;

use App\Core\Database;
use App\Core\Session;

class AuditService
{
    private Database $db;
    private Session $session;

    public function __construct()
    {
        $this->db      = Database::getInstance();
        $this->session = Session::getInstance();
    }

    /**
     * Immutable audit log - INSERT ONLY, never UPDATE/DELETE (legal requirement)
     */
    public function log(
        string $action,
        string $entity = '',
        ?int $entityId = null,
        mixed $oldData = null,
        mixed $newData = null,
        string $result = 'exitoso',
        ?int $userId = null
    ): void {
        try {
            $userId   = $userId ?? $this->session->get('user_id');
            $ip       = $this->getIp();
            $ua       = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);

            $this->db->execute(
                "INSERT INTO audit_log
                     (usuario_id, accion, entidad, entidad_id, datos_anteriores, datos_nuevos,
                      ip_address, user_agent, resultado, created_at)
                 VALUES
                     (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $userId,
                    $action,
                    $entity ?: null,
                    $entityId,
                    $oldData !== null ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null,
                    $newData !== null ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null,
                    $ip,
                    $ua,
                    $result,
                ]
            );
        } catch (\Exception $e) {
            // Log to file if DB fails - never throw from audit
            error_log('AuditService error: ' . $e->getMessage());
        }
    }

    public function logLogin(int $userId, string $email, bool $success): void
    {
        $this->log(
            action:   $success ? 'login.success' : 'login.failed',
            entity:   'usuarios',
            entityId: $userId,
            newData:  ['email' => $email],
            result:   $success ? 'exitoso' : 'fallido',
            userId:   $userId,
        );
    }

    public function logLogout(int $userId): void
    {
        $this->log(
            action:   'logout',
            entity:   'usuarios',
            entityId: $userId,
            userId:   $userId,
        );
    }

    public function logFichaje(int $fichajeId, string $tipo, int $userId): void
    {
        $this->log(
            action:   "fichaje.{$tipo}",
            entity:   'fichajes',
            entityId: $fichajeId,
            newData:  ['tipo' => $tipo, 'timestamp' => date('Y-m-d H:i:s')],
            userId:   $userId,
        );
    }

    public function logUsuarioCreado(int $newUserId, array $data): void
    {
        // Remove sensitive data before logging
        unset($data['password'], $data['password_hash']);
        $this->log(
            action:   'usuario.creado',
            entity:   'usuarios',
            entityId: $newUserId,
            newData:  $data,
        );
    }

    public function logUsuarioModificado(int $userId, array $oldData, array $newData): void
    {
        unset($oldData['password_hash'], $newData['password_hash'], $newData['password']);
        $this->log(
            action:   'usuario.modificado',
            entity:   'usuarios',
            entityId: $userId,
            oldData:  $oldData,
            newData:  $newData,
        );
    }

    public function logIncidencia(string $action, int $incidenciaId, array $data = []): void
    {
        $this->log(
            action:   "incidencia.{$action}",
            entity:   'incidencias',
            entityId: $incidenciaId,
            newData:  $data,
        );
    }

    public function logVacacion(string $action, int $vacacionId, array $data = []): void
    {
        $this->log(
            action:   "vacacion.{$action}",
            entity:   'vacaciones',
            entityId: $vacacionId,
            newData:  $data,
        );
    }

    public function logConfiguracion(string $key, mixed $oldValue, mixed $newValue): void
    {
        $this->log(
            action:   'configuracion.modificada',
            entity:   'configuracion',
            entityId: null,
            oldData:  [$key => $oldValue],
            newData:  [$key => $newValue],
        );
    }

    public function getRecent(int $limit = 100, array $filters = []): array
    {
        $where  = ["1=1"];
        $params = [];

        if (!empty($filters['usuario_id'])) {
            $where[]              = "al.usuario_id = :usuario_id";
            $params[':usuario_id'] = $filters['usuario_id'];
        }

        if (!empty($filters['accion'])) {
            $where[]       = "al.accion LIKE :accion";
            $params[':accion'] = '%' . $filters['accion'] . '%';
        }

        if (!empty($filters['fecha_desde'])) {
            $where[]              = "DATE(al.created_at) >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        return $this->db->fetchAll(
            "SELECT al.*, u.nombre, u.apellidos
             FROM audit_log al
             LEFT JOIN usuarios u ON u.id = al.usuario_id
             {$whereClause}
             ORDER BY al.created_at DESC
             LIMIT " . (int)$limit,
            $params
        );
    }

    private function getIp(): string
    {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
