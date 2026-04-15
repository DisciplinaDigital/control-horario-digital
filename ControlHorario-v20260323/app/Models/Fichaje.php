<?php

namespace App\Models;

use App\Core\Database;
use App\Services\IntegrityService;

class Fichaje
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Creates a new fichaje - IMMUTABLE after creation
     */
    public function create(array $data): int|false
    {
        $sql = "INSERT INTO fichajes
                    (usuario_id, tipo, fecha_hora, latitud, longitud, precision_ubicacion,
                     dispositivo, ip_address, metodo_registro, hash_integridad, hash_anterior,
                     firmado_en, es_correccion, correccion_justificacion, incidencia_id, created_at)
                VALUES
                    (:usuario_id, :tipo, :fecha_hora, :latitud, :longitud, :precision_ubicacion,
                     :dispositivo, :ip_address, :metodo_registro, :hash_integridad, :hash_anterior,
                     :firmado_en, :es_correccion, :correccion_justificacion, :incidencia_id, NOW())";

        $this->db->execute($sql, [
            ':usuario_id'              => $data['usuario_id'],
            ':tipo'                    => $data['tipo'],
            ':fecha_hora'              => $data['fecha_hora'],
            ':latitud'                 => $data['latitud'] ?? null,
            ':longitud'                => $data['longitud'] ?? null,
            ':precision_ubicacion'     => $data['precision_ubicacion'] ?? null,
            ':dispositivo'             => $data['dispositivo'] ?? null,
            ':ip_address'              => $data['ip_address'] ?? null,
            ':metodo_registro'         => $data['metodo_registro'] ?? 'web',
            ':hash_integridad'         => $data['hash_integridad'],
            ':hash_anterior'           => $data['hash_anterior'] ?? null,
            ':firmado_en'              => $data['firmado_en'] ?? date('Y-m-d H:i:s'),
            ':es_correccion'           => $data['es_correccion'] ?? 0,
            ':correccion_justificacion' => $data['correccion_justificacion'] ?? null,
            ':incidencia_id'           => $data['incidencia_id'] ?? null,
        ]);

        return (int)$this->db->lastInsertId() ?: false;
    }

    public function findById(int $id): ?array
    {
        return $this->db->fetchOne(
            "SELECT f.*, u.nombre, u.apellidos, u.email
             FROM fichajes f
             JOIN usuarios u ON u.id = f.usuario_id
             WHERE f.id = ?",
            [$id]
        );
    }

    public function findByUsuario(int $userId, array $filters = []): array
    {
        $where  = ["f.usuario_id = :usuario_id"];
        $params = [':usuario_id' => $userId];

        if (!empty($filters['fecha_desde'])) {
            $where[]              = "DATE(f.fecha_hora) >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $where[]              = "DATE(f.fecha_hora) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        if (!empty($filters['tipo'])) {
            $where[]       = "f.tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $limit       = isset($filters['limit']) ? 'LIMIT ' . (int)$filters['limit'] : 'LIMIT 100';
        $offset      = isset($filters['offset']) ? 'OFFSET ' . (int)$filters['offset'] : '';
        $order       = $filters['order'] ?? 'DESC';
        $order       = in_array(strtoupper($order), ['ASC', 'DESC']) ? $order : 'DESC';

        return $this->db->fetchAll(
            "SELECT f.*, u.nombre, u.apellidos
             FROM fichajes f
             JOIN usuarios u ON u.id = f.usuario_id
             {$whereClause}
             ORDER BY f.fecha_hora {$order}
             {$limit} {$offset}",
            $params
        );
    }

    public function getLastByUsuario(int $userId): ?array
    {
        return $this->db->fetchOne(
            "SELECT * FROM fichajes WHERE usuario_id = ? ORDER BY fecha_hora DESC LIMIT 1",
            [$userId]
        );
    }

    public function getEstadoActual(int $userId): string
    {
        $last = $this->getLastByUsuario($userId);

        if (!$last) {
            return 'puede_entrada';
        }

        // Check if today
        $lastDate = date('Y-m-d', strtotime($last['fecha_hora']));
        $today    = date('Y-m-d');

        if ($lastDate !== $today) {
            return 'puede_entrada';
        }

        return $last['tipo'] === 'entrada' ? 'puede_salida' : 'puede_entrada';
    }

    public function verifyIntegrity(int $id): bool
    {
        $fichaje = $this->db->fetchOne("SELECT * FROM fichajes WHERE id = ?", [$id]);
        if (!$fichaje) {
            return false;
        }

        return (new IntegrityService())->verifyFichaje($fichaje);
    }

    public function getByDateRange(int $userId, string $from, string $to): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM fichajes
             WHERE usuario_id = ?
               AND DATE(fecha_hora) >= ?
               AND DATE(fecha_hora) <= ?
             ORDER BY fecha_hora ASC",
            [$userId, $from, $to]
        );
    }

    public function getResumenMensual(int $userId, int $year, int $month): array
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to   = date('Y-m-t', strtotime($from));

        $fichajes = $this->getByDateRange($userId, $from, $to);

        $days    = [];
        $totals  = ['dias_trabajados' => 0, 'horas_totales' => 0, 'fichajes' => count($fichajes)];

        foreach ($fichajes as $f) {
            $date = date('Y-m-d', strtotime($f['fecha_hora']));
            if (!isset($days[$date])) {
                $days[$date] = ['entradas' => [], 'salidas' => []];
            }
            $days[$date][$f['tipo'] . 's'][] = $f;
        }

        foreach ($days as $date => $day) {
            $entradas = $day['entradas'];
            $salidas  = $day['salidas'];

            if (!empty($entradas)) {
                $totals['dias_trabajados']++;
                // Calculate hours for each pair
                $minEntradas = count($entradas);
                $minSalidas  = count($salidas);
                $pairs       = min($minEntradas, $minSalidas);

                for ($i = 0; $i < $pairs; $i++) {
                    $entrada = strtotime($entradas[$i]['fecha_hora']);
                    $salida  = strtotime($salidas[$i]['fecha_hora']);
                    $totals['horas_totales'] += ($salida - $entrada) / 3600;
                }
            }
        }

        return [
            'dias'           => $days,
            'totales'        => $totals,
            'year'           => $year,
            'month'          => $month,
        ];
    }

    public function getAllWithFilters(array $filters = []): array
    {
        $where  = ["1=1"];
        $params = [];

        if (!empty($filters['usuario_id'])) {
            $where[]              = "f.usuario_id = :usuario_id";
            $params[':usuario_id'] = $filters['usuario_id'];
        }

        if (!empty($filters['fecha_desde'])) {
            $where[]              = "DATE(f.fecha_hora) >= :fecha_desde";
            $params[':fecha_desde'] = $filters['fecha_desde'];
        }

        if (!empty($filters['fecha_hasta'])) {
            $where[]              = "DATE(f.fecha_hora) <= :fecha_hasta";
            $params[':fecha_hasta'] = $filters['fecha_hasta'];
        }

        if (!empty($filters['tipo'])) {
            $where[]       = "f.tipo = :tipo";
            $params[':tipo'] = $filters['tipo'];
        }

        if (!empty($filters['metodo_registro'])) {
            $where[]                    = "f.metodo_registro = :metodo_registro";
            $params[':metodo_registro'] = $filters['metodo_registro'];
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);
        $limit       = 'LIMIT ' . (int)($filters['limit'] ?? 500);
        $offset      = isset($filters['offset']) ? 'OFFSET ' . (int)$filters['offset'] : '';

        return $this->db->fetchAll(
            "SELECT f.*, u.nombre, u.apellidos, u.email, u.departamento
             FROM fichajes f
             JOIN usuarios u ON u.id = f.usuario_id
             {$whereClause}
             ORDER BY f.fecha_hora DESC
             {$limit} {$offset}",
            $params
        );
    }

    public function countToday(): int
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM fichajes WHERE DATE(fecha_hora) = CURDATE()"
        );
        return (int)($result['total'] ?? 0);
    }

    public function getTodayByUsuario(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM fichajes
             WHERE usuario_id = ? AND DATE(fecha_hora) = CURDATE()
             ORDER BY fecha_hora ASC",
            [$userId]
        );
    }

    public function getLastNByUsuario(int $userId, int $n = 10): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM fichajes WHERE usuario_id = ? ORDER BY fecha_hora DESC LIMIT ?",
            [$userId, $n]
        );
    }
}
