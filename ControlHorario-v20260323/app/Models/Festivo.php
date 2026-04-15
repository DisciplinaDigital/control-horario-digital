<?php

namespace App\Models;

use App\Core\Database;

class Festivo
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function create(array $data): int|false
    {
        // Upsert to avoid duplicate key issues
        $sql = "INSERT INTO festivos (fecha, descripcion, tipo, created_at)
                VALUES (:fecha, :descripcion, :tipo, NOW())
                ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion), tipo = VALUES(tipo)";

        $this->db->execute($sql, [
            ':fecha'       => $data['fecha'],
            ':descripcion' => $data['descripcion'],
            ':tipo'        => $data['tipo'] ?? 'nacional',
        ]);

        return (int)$this->db->lastInsertId() ?: false;
    }

    public function update(int $id, array $data): bool
    {
        $sets   = [];
        $params = [':id' => $id];

        if (isset($data['descripcion'])) {
            $sets[]                = "descripcion = :descripcion";
            $params[':descripcion'] = $data['descripcion'];
        }

        if (isset($data['tipo'])) {
            $sets[]      = "tipo = :tipo";
            $params[':tipo'] = $data['tipo'];
        }

        if (empty($sets)) {
            return false;
        }

        return $this->db->execute(
            "UPDATE festivos SET " . implode(', ', $sets) . " WHERE id = :id",
            $params
        ) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->db->execute("DELETE FROM festivos WHERE id = ?", [$id]) > 0;
    }

    public function findAll(?int $year = null): array
    {
        if ($year) {
            return $this->db->fetchAll(
                "SELECT * FROM festivos WHERE YEAR(fecha) = ? ORDER BY fecha ASC",
                [$year]
            );
        }

        return $this->db->fetchAll("SELECT * FROM festivos ORDER BY fecha ASC");
    }

    public function findByDate(string $date): ?array
    {
        return $this->db->fetchOne("SELECT * FROM festivos WHERE fecha = ?", [$date]);
    }

    public function isFestivo(string $date): bool
    {
        $result = $this->db->fetchOne(
            "SELECT COUNT(*) as total FROM festivos WHERE fecha = ?",
            [$date]
        );
        return ((int)($result['total'] ?? 0)) > 0;
    }

    public function importarNacionalesEspana(int $year): int
    {
        $festivos = $this->getFestivosNacionalesEspana($year);
        $imported = 0;

        foreach ($festivos as $festivo) {
            try {
                $this->create([
                    'fecha'       => $festivo['fecha'],
                    'descripcion' => $festivo['descripcion'],
                    'tipo'        => 'nacional',
                ]);
                $imported++;
            } catch (\Exception $e) {
                // Skip duplicates
            }
        }

        return $imported;
    }

    private function getFestivosNacionalesEspana(int $year): array
    {
        // Calcular Semana Santa
        $easter    = easter_date($year);
        $jueves    = date('Y-m-d', $easter - 3 * 86400);
        $viernes   = date('Y-m-d', $easter - 2 * 86400);
        $domingo   = date('Y-m-d', $easter);

        return [
            ['fecha' => "{$year}-01-01", 'descripcion' => 'Año Nuevo'],
            ['fecha' => "{$year}-01-06", 'descripcion' => 'Reyes Magos (Epifanía del Señor)'],
            ['fecha' => $jueves,          'descripcion' => 'Jueves Santo'],
            ['fecha' => $viernes,         'descripcion' => 'Viernes Santo'],
            ['fecha' => "{$year}-05-01", 'descripcion' => 'Día del Trabajador'],
            ['fecha' => "{$year}-08-15", 'descripcion' => 'Asunción de la Virgen'],
            ['fecha' => "{$year}-10-12", 'descripcion' => 'Día de la Hispanidad'],
            ['fecha' => "{$year}-11-01", 'descripcion' => 'Todos los Santos'],
            ['fecha' => "{$year}-12-06", 'descripcion' => 'Día de la Constitución'],
            ['fecha' => "{$year}-12-08", 'descripcion' => 'Inmaculada Concepción'],
            ['fecha' => "{$year}-12-25", 'descripcion' => 'Navidad'],
        ];
    }

    public function getFestivosForCalendar(int $year): array
    {
        return $this->findAll($year);
    }
}
