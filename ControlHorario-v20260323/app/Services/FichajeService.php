<?php

namespace App\Services;

use App\Models\Fichaje;
use App\Models\Configuracion;

class FichajeService
{
    private Fichaje $fichajeModel;
    private IntegrityService $integrityService;
    private AuditService $auditService;
    private Configuracion $config;

    public function __construct()
    {
        $this->fichajeModel     = new Fichaje();
        $this->integrityService = new IntegrityService();
        $this->auditService     = new AuditService();
        $this->config           = new Configuracion();
    }

    /**
     * Registers a new fichaje (entry or exit)
     * Returns [success, message, fichaje]
     */
    public function registrar(
        int $userId,
        string $tipo,
        ?float $lat = null,
        ?float $lon = null,
        ?float $precision = null,
        string $metodo = 'web',
        ?string $justificacion = null,
        ?int $userMaxDistanceOverride = null,
        ?string $fechaHoraOverride = null,
        ?int $incidenciaId = null
    ): array {
        // Validate tipo
        if (!in_array($tipo, ['entrada', 'salida'])) {
            return ['success' => false, 'message' => 'Tipo de fichaje inválido.', 'fichaje' => null];
        }

        // ── Validar horario laboral configurado ──────────────────────────
        // Los administradores (manual_admin) siempre pueden fichar a cualquier hora.
        if ($metodo !== 'manual_admin') {
            $horarioError = $this->checkHorarioLaboral();
            if ($horarioError !== null) {
                return ['success' => false, 'message' => $horarioError, 'fichaje' => null];
            }
        }

        // Check if can fichar
        $canCheck = $this->canFichar($userId);
        if (!$canCheck['can']) {
            return ['success' => false, 'message' => $canCheck['message'], 'fichaje' => null];
        }

        if ($canCheck['tipo'] !== $tipo && $metodo !== 'manual_admin') {
            return [
                'success'  => false,
                'message'  => "Debe fichar {$canCheck['tipo']} primero.",
                'fichaje'  => null,
            ];
        }

        // Validate geolocation if provided (for web/mobile)
        if ($lat !== null && $lon !== null && $metodo !== 'manual_admin') {
            $geoCheck = $this->validateGeolocation($lat, $lon, $precision, $userMaxDistanceOverride);
            if (!$geoCheck['valid']) {
                return ['success' => false, 'message' => $geoCheck['message'], 'fichaje' => null];
            }
        }

        $fechaHora = $fechaHoraOverride ?: date('Y-m-d H:i:s');

        // Get previous hash for chain
        $hashAnterior = $this->integrityService->getLastHash($userId);

        $fichajeData = [
            'usuario_id'         => $userId,
            'tipo'               => $tipo,
            'fecha_hora'         => $fechaHora,
            'latitud'            => $lat,
            'longitud'           => $lon,
            'precision_ubicacion' => $precision,
        ];

        // Generate integrity hash
        $hash = $this->integrityService->generateHash($fichajeData, $hashAnterior);

        $fichajeId = $this->fichajeModel->create([
            'usuario_id'               => $userId,
            'tipo'                     => $tipo,
            'fecha_hora'               => $fechaHora,
            'latitud'                  => $lat,
            'longitud'                 => $lon,
            'precision_ubicacion'      => $precision,
            'dispositivo'              => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            'ip_address'               => $this->getIp(),
            'metodo_registro'          => $metodo,
            'hash_integridad'          => $hash,
            'hash_anterior'            => $hashAnterior,
            'firmado_en'               => date('Y-m-d H:i:s'),
            'es_correccion'            => $metodo === 'manual_admin' ? 1 : 0,
            'correccion_justificacion' => $justificacion,
            'incidencia_id'            => $incidenciaId,
        ]);

        if (!$fichajeId) {
            return ['success' => false, 'message' => 'Error al registrar fichaje.', 'fichaje' => null];
        }

        // Audit log
        $this->auditService->logFichaje($fichajeId, $tipo, $userId);

        $fichaje = $this->fichajeModel->findById($fichajeId);

        $tipoLabel = $tipo === 'entrada' ? 'Entrada' : 'Salida';
        return [
            'success' => true,
            'message' => "{$tipoLabel} registrada correctamente a las " . date('H:i', strtotime($fechaHora)),
            'fichaje' => $fichaje,
        ];
    }

    /**
     * Checks if user can fichar and what type
     * Returns [can => bool, tipo => 'entrada'|'salida', message => string]
     */
    public function canFichar(int $userId): array
    {
        $estado = $this->fichajeModel->getEstadoActual($userId);

        if ($estado === 'puede_entrada') {
            return ['can' => true, 'tipo' => 'entrada', 'message' => 'Puede registrar entrada'];
        }

        return ['can' => true, 'tipo' => 'salida', 'message' => 'Puede registrar salida'];
    }

    /**
     * Validates geolocation against office location
     * Returns [valid => bool, distance => float, message => string]
     */
    public function validateGeolocation(?float $lat, ?float $lon, ?float $precision, ?int $userMaxDistanceOverride = null): array
    {
        if ($lat === null || $lon === null) {
            return ['valid' => false, 'distance' => -1, 'message' => 'Ubicación no disponible'];
        }

        $defaults    = $this->config->defaults();
        $officeLat   = (float)$this->config->get('default_lat', $defaults['default_lat']);
        $officeLon   = (float)$this->config->get('default_lon', $defaults['default_lon']);
        $minAccuracy = (float)$this->config->get('min_accuracy', $defaults['min_accuracy']);

        // Si el usuario tiene un override personal, se usa. 0 = sin límite.
        if ($userMaxDistanceOverride !== null) {
            $maxDistance = (float)$userMaxDistanceOverride;
        } else {
            $maxDistance = (float)$this->config->get('max_distance', $defaults['max_distance']);
        }

        // Sin límite de distancia (override = 0)
        if ($maxDistance === 0.0) {
            $distance = $this->haversineDistance($lat, $lon, $officeLat, $officeLon);
            return ['valid' => true, 'distance' => round($distance, 1), 'message' => 'Sin restricción de radio'];
        }

        // Check accuracy
        if ($precision !== null && $precision > ($minAccuracy * 10)) {
            return [
                'valid'    => false,
                'distance' => -1,
                'message'  => "Precisión GPS insuficiente ({$precision}m). Debe ser menor de " . ($minAccuracy * 10) . "m",
            ];
        }

        // Calculate distance using Haversine formula
        $distance = $this->haversineDistance($lat, $lon, $officeLat, $officeLon);

        if ($distance > $maxDistance) {
            return [
                'valid'    => false,
                'distance' => round($distance, 1),
                'message'  => "Fuera del radio permitido. Distancia: " . round($distance) . "m (máximo: {$maxDistance}m)",
            ];
        }

        return [
            'valid'    => true,
            'distance' => round($distance, 1),
            'message'  => "Ubicación válida (" . round($distance) . "m del centro)",
        ];
    }

    /**
     * Haversine formula to calculate distance between two points in meters
     */
    private function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get today's summary for a user
     */
    public function getResumenDia(int $userId, string $date = ''): array
    {
        if (!$date) {
            $date = date('Y-m-d');
        }

        $fichajes = $this->fichajeModel->getByDateRange($userId, $date, $date);

        $entradas = array_filter($fichajes, fn($f) => $f['tipo'] === 'entrada');
        $salidas  = array_filter($fichajes, fn($f) => $f['tipo'] === 'salida');

        $horasTrabajadas = 0;
        $entradas = array_values($entradas);
        $salidas  = array_values($salidas);
        $pairs    = min(count($entradas), count($salidas));

        for ($i = 0; $i < $pairs; $i++) {
            $e = strtotime($entradas[$i]['fecha_hora']);
            $s = strtotime($salidas[$i]['fecha_hora']);
            $horasTrabajadas += ($s - $e) / 3600;
        }

        // If currently checked in, add time until now
        if (count($entradas) > count($salidas) && !empty($entradas)) {
            $lastEntrada = strtotime(end($entradas)['fecha_hora']);
            $horasTrabajadas += (time() - $lastEntrada) / 3600;
        }

        return [
            'date'            => $date,
            'fichajes'        => $fichajes,
            'entradas'        => $entradas,
            'salidas'         => $salidas,
            'horas_trabajadas' => round($horasTrabajadas, 2),
            'primera_entrada' => $entradas[0]['fecha_hora'] ?? null,
            'ultima_salida'   => !empty($salidas) ? end($salidas)['fecha_hora'] : null,
        ];
    }

    /**
     * Get this week's summary
     */
    public function getResumenSemana(int $userId): array
    {
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime('sunday this week'));

        $fichajes = $this->fichajeModel->getByDateRange($userId, $monday, $sunday);

        $days    = [];
        $totalH  = 0;

        // Group by day
        foreach ($fichajes as $f) {
            $d = date('Y-m-d', strtotime($f['fecha_hora']));
            $days[$d][] = $f;
        }

        $result = [];
        for ($i = 0; $i < 7; $i++) {
            $date      = date('Y-m-d', strtotime($monday . " +{$i} days"));
            $dayFichajes = $days[$date] ?? [];

            $entradas = array_values(array_filter($dayFichajes, fn($f) => $f['tipo'] === 'entrada'));
            $salidas  = array_values(array_filter($dayFichajes, fn($f) => $f['tipo'] === 'salida'));

            $h     = 0;
            $pairs = min(count($entradas), count($salidas));
            for ($j = 0; $j < $pairs; $j++) {
                $h += (strtotime($salidas[$j]['fecha_hora']) - strtotime($entradas[$j]['fecha_hora'])) / 3600;
            }

            $totalH += $h;

            $result[] = [
                'date'    => $date,
                'horas'   => round($h, 2),
                'fichajes' => $dayFichajes,
            ];
        }

        return [
            'days'          => $result,
            'total_horas'   => round($totalH, 2),
            'week_start'    => $monday,
            'week_end'      => $sunday,
        ];
    }

    /**
     * Calculates hours worked between entry and exit
     */
    public function calcularHorasTrabajadas(string $entrada, string $salida): float
    {
        $e = strtotime($entrada);
        $s = strtotime($salida);
        if ($s <= $e) {
            return 0;
        }
        return round(($s - $e) / 3600, 2);
    }

    /**
     * Comprueba si la hora actual está dentro del horario laboral configurado.
     * Devuelve null si está permitido, o el mensaje de error si no lo está.
     */
    private function checkHorarioLaboral(): ?string
    {
        $workStart = trim($this->config->get('work_start', ''));
        $workEnd   = trim($this->config->get('work_end', ''));

        // Si no hay horario configurado, no restringir
        if ($workStart === '' && $workEnd === '') {
            return null;
        }

        $ahoraMinutos = (int)date('H') * 60 + (int)date('i');

        if ($workStart !== '') {
            [$h, $m]      = array_map('intval', explode(':', $workStart));
            $startMinutos = $h * 60 + $m;

            if ($ahoraMinutos < $startMinutos) {
                return "Fuera de horario laboral. El fichaje se habilita a las {$workStart}.";
            }
        }

        if ($workEnd !== '') {
            [$h, $m]    = array_map('intval', explode(':', $workEnd));
            $endMinutos = $h * 60 + $m;

            if ($ahoraMinutos > $endMinutos) {
                return "Fuera de horario laboral. El horario finalizó a las {$workEnd}.";
            }
        }

        return null;
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
