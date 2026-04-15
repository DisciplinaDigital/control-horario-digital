<?php

namespace App\Models;

use App\Core\Database;

class Configuracion
{
    private Database $db;
    private static array $cache = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (isset(static::$cache[$key])) {
            return static::$cache[$key];
        }

        $row = $this->db->fetchOne(
            "SELECT valor FROM configuracion WHERE clave = ?",
            [$key]
        );

        $value = $row ? $row['valor'] : $default;
        static::$cache[$key] = $value;
        return $value;
    }

    public function set(string $key, mixed $value, string $tipo = 'text', string $descripcion = ''): void
    {
        $this->db->execute(
            "INSERT INTO configuracion (clave, valor, tipo, descripcion)
             VALUES (:clave, :valor, :tipo, :descripcion)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_at = NOW()",
            [
                ':clave'       => $key,
                ':valor'       => $value,
                ':tipo'        => $tipo,
                ':descripcion' => $descripcion,
            ]
        );

        // Invalidate cache
        static::$cache[$key] = $value;
    }

    public function getAll(): array
    {
        $rows = $this->db->fetchAll("SELECT * FROM configuracion ORDER BY clave");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['clave']] = $row;
        }
        return $result;
    }

    public function getTheme(): array
    {
        $defaults = $this->defaults();
        return [
            '--color-primary'   => $this->get('color_primary', $defaults['color_primary']),
            '--color-secondary' => $this->get('color_secondary', $defaults['color_secondary']),
            '--color-accent'    => $this->get('color_accent', $defaults['color_accent']),
            '--color-success'   => $this->get('color_success', $defaults['color_success']),
            '--color-warning'   => $this->get('color_warning', $defaults['color_warning']),
            '--color-danger'    => $this->get('color_danger', $defaults['color_danger']),
            '--color-bg'        => $this->get('color_bg', $defaults['color_bg']),
            '--color-text'      => $this->get('color_text', $defaults['color_text']),
        ];
    }

    public function defaults(): array
    {
        return [
            'logo'                 => null,
            'company_name'         => 'Control Horario Digital',
            'company_address'      => '',
            'company_cif'          => '',
            'color_primary'        => '#2563eb',
            'color_secondary'      => '#64748b',
            'color_accent'         => '#0ea5e9',
            'color_success'        => '#16a34a',
            'color_warning'        => '#d97706',
            'color_danger'         => '#dc2626',
            'color_bg'             => '#f8fafc',
            'color_text'           => '#1e293b',
            'default_lat'          => '36.62906',
            'default_lon'          => '-4.82644',
            'max_distance'         => '30',
            'min_accuracy'         => '10',
            'default_vacation_days' => '22',
            'work_start'           => '07:00',
            'work_end'             => '19:30',
        ];
    }

    public function initDefaults(): void
    {
        $existing = $this->getAll();
        foreach ($this->defaults() as $key => $value) {
            if (!isset($existing[$key])) {
                $tipo = 'text';
                if (str_starts_with($key, 'color_')) {
                    $tipo = 'color';
                } elseif ($key === 'logo') {
                    $tipo = 'file';
                } elseif (in_array($key, ['default_lat', 'default_lon', 'max_distance', 'min_accuracy', 'default_vacation_days'])) {
                    $tipo = 'number';
                }
                $this->set($key, $value, $tipo);
            }
        }
    }

    public function clearCache(): void
    {
        static::$cache = [];
    }

    public function setMultiple(array $data): void
    {
        foreach ($data as $key => $value) {
            $tipo = 'text';
            if (str_starts_with($key, 'color_')) {
                $tipo = 'color';
            } elseif (is_numeric($value) && in_array($key, ['max_distance', 'min_accuracy', 'default_vacation_days'])) {
                $tipo = 'number';
            }
            $this->set($key, $value, $tipo);
        }
        $this->clearCache();
    }
}
