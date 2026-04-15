<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Notificacion;

class NotificationService
{
    private Notificacion $notificacion;
    private Database $db;

    public function __construct()
    {
        $this->notificacion = new Notificacion();
        $this->db           = Database::getInstance();
    }

    public function notifyUser(int $userId, string $tipo, string $titulo, string $mensaje, string $refTipo = '', int $refId = 0): void
    {
        $this->notificacion->create([
            'usuario_id'      => $userId,
            'tipo'            => $tipo,
            'titulo'          => $titulo,
            'mensaje'         => $mensaje,
            'referencia_tipo' => $refTipo ?: null,
            'referencia_id'   => $refId ?: null,
        ]);
    }

    public function notifyAdmins(string $tipo, string $titulo, string $mensaje, string $refTipo = '', int $refId = 0): void
    {
        $admins = $this->db->fetchAll(
            "SELECT id FROM usuarios WHERE role = 'admin' AND activo = 1 AND deleted_at IS NULL"
        );

        foreach ($admins as $admin) {
            $this->notifyUser($admin['id'], $tipo, $titulo, $mensaje, $refTipo, $refId);
        }
    }

    public function onIncidenciaCreada(int $incidenciaId, int $userId, string $numero): void
    {
        // Notify admins
        $this->notifyAdmins(
            'incidencia',
            'Nueva incidencia ' . $numero,
            'Un empleado ha creado una nueva solicitud de incidencia.',
            'incidencias',
            $incidenciaId
        );
    }

    public function onIncidenciaResuelta(int $incidenciaId, int $userId, string $estado, string $numero): void
    {
        $estadoLabel = $estado === 'aceptada' ? 'aceptada' : 'rechazada';
        $this->notifyUser(
            $userId,
            'incidencia',
            "Incidencia {$numero} {$estadoLabel}",
            "Su solicitud de incidencia ha sido {$estadoLabel}.",
            'incidencias',
            $incidenciaId
        );
    }

    public function onVacacionSolicitada(int $vacacionId, int $userId, string $fechaInicio, string $fechaFin): void
    {
        $this->notifyAdmins(
            'vacacion',
            'Nueva solicitud de vacaciones',
            "Solicitud de vacaciones del {$fechaInicio} al {$fechaFin}.",
            'vacaciones',
            $vacacionId
        );
    }

    public function onVacacionResuelta(int $vacacionId, int $userId, string $estado): void
    {
        $estadoLabel = $estado === 'aprobada' ? 'aprobada' : 'rechazada';
        $this->notifyUser(
            $userId,
            'vacacion',
            "Solicitud de vacaciones {$estadoLabel}",
            "Su solicitud de vacaciones ha sido {$estadoLabel}.",
            'vacaciones',
            $vacacionId
        );
    }

    public function onUsuarioCreado(int $newUserId, string $nombre): void
    {
        $this->notifyUser(
            $newUserId,
            'sistema',
            'Bienvenido al sistema',
            "Hola {$nombre}, tu cuenta ha sido creada. Ya puedes empezar a registrar tus fichajes.",
            'usuarios',
            $newUserId
        );
    }

    public function onFichajeOlvidado(int $userId, string $fecha): void
    {
        $this->notifyUser(
            $userId,
            'fichaje',
            'Recordatorio de fichaje',
            "No has registrado salida el día {$fecha}. Si fue un olvido, crea una incidencia.",
            'fichajes',
            0
        );
    }
}
