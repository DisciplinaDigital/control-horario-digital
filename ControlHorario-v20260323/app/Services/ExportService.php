<?php

namespace App\Services;

use App\Models\Configuracion;
use App\Models\Fichaje;
use App\Models\Usuario;

class ExportService
{
    private Fichaje $fichajeModel;
    private Usuario $usuarioModel;
    private Configuracion $config;
    private IntegrityService $integrityService;

    public function __construct()
    {
        $this->fichajeModel = new Fichaje();
        $this->usuarioModel = new Usuario();
        $this->config = new Configuracion();
        $this->integrityService = new IntegrityService();
    }

    public function exportFichajesCSV(int $userId, string $from, string $to): string
    {
        $usuario = $this->usuarioModel->findById($userId);
        $fichajes = $this->fichajeModel->getByDateRange($userId, $from, $to);

        $rows = [];
        $rows[] = $this->csvRow([
            'Empleado',
            'Email',
            'Fecha',
            'Hora',
            'Tipo',
            'Método',
            'Latitud',
            'Longitud',
            'IP',
            'Hash Integridad',
            'Corrección',
        ]);

        foreach ($fichajes as $f) {
            $rows[] = $this->csvRow([
                ($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''),
                $usuario['email'] ?? '',
                date('d/m/Y', strtotime($f['fecha_hora'])),
                date('H:i:s', strtotime($f['fecha_hora'])),
                $f['tipo'] === 'entrada' ? 'Entrada' : 'Salida',
                $f['metodo_registro'],
                $f['latitud'] ?? '',
                $f['longitud'] ?? '',
                $f['ip_address'] ?? '',
                $f['hash_integridad'],
                $f['es_correccion'] ? 'Sí - ' . ($f['correccion_justificacion'] ?? '') : 'No',
            ]);
        }

        return implode("\n", $rows);
    }

    public function exportFichajesPDF(int $userId, string $from, string $to): void
    {
        $usuario = $this->usuarioModel->findById($userId);
        $fichajes = $this->fichajeModel->getByDateRange($userId, $from, $to);
        $company = $this->config->get('company_name', 'Control Horario Digital');
        $companyAddress = $this->config->get('company_address', '');
        $companyCif = $this->config->get('company_cif', '');

        $days = [];
        foreach ($fichajes as $f) {
            $date = date('Y-m-d', strtotime($f['fecha_hora']));
            $days[$date][$f['tipo']][] = $f;
        }

        $nombre = ($usuario['nombre'] ?? '') . ' ' . ($usuario['apellidos'] ?? '');

        header('Content-Type: text/html; charset=utf-8');

        echo "<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<title>Registro de Jornada - {$this->esc($nombre)}</title>
<style>
body{font-family:Arial,sans-serif;font-size:12px;color:#333}
h1{color:#2563eb}
table{width:100%;border-collapse:collapse;margin-top:20px}
th{background:#2563eb;color:#fff;padding:8px;text-align:left}
td{border:1px solid #ddd;padding:6px 8px}
tr:nth-child(even) td{background:#f8fafc}
.header{display:flex;justify-content:space-between;margin-bottom:20px}
.legal{margin-top:30px;font-size:10px;color:#666;border-top:1px solid #ddd;padding-top:10px}
@media print {.no-print{display:none}}
</style>
</head>
<body>
<div class='no-print' style='margin-bottom:20px;'>
  <button onclick='window.print()' style='padding:10px 20px;background:#2563eb;color:#fff;border:none;border-radius:4px;cursor:pointer;'>Imprimir / Guardar PDF</button>
</div>

<div class='header'>
  <div>
    <strong>{$this->esc($company)}</strong><br>
    {$this->esc($companyAddress)}<br>
    CIF: {$this->esc($companyCif)}
  </div>
  <div>
    <h2>REGISTRO DE JORNADA LABORAL</h2>
    <small>Conforme a la Ley de Registro de Jornada</small>
  </div>
</div>

<p><strong>Empleado:</strong> {$this->esc($nombre)}</p>
<p><strong>Email:</strong> {$this->esc($usuario['email'] ?? '')}</p>
<p><strong>Período:</strong> " . date('d/m/Y', strtotime($from)) . " - " . date('d/m/Y', strtotime($to)) . "</p>
<p><strong>Generado:</strong> " . date('d/m/Y H:i:s') . "</p>

<table>
  <thead>
    <tr>
      <th>Fecha</th>
      <th>Entrada</th>
      <th>Salida</th>
      <th>Horas trabajadas</th>
      <th>Método</th>
      <th>Integridad</th>
    </tr>
  </thead>
  <tbody>";

        $totalHoras = 0;
        foreach ($days as $date => $dayData) {
            $entradas = $dayData['entrada'] ?? [];
            $salidas = $dayData['salida'] ?? [];
            $pairs = max(count($entradas), count($salidas));

            for ($i = 0; $i < $pairs; $i++) {
                $entrada = $entradas[$i] ?? null;
                $salida = $salidas[$i] ?? null;
                $horas = '';

                if ($entrada && $salida) {
                    $h = (strtotime($salida['fecha_hora']) - strtotime($entrada['fecha_hora'])) / 3600;
                    $horas = number_format($h, 2) . 'h';
                    $totalHoras += $h;
                }

                $hashOk = $entrada ? ($this->integrityService->verifyFichaje($entrada) ? '✓' : '✗') : '';

                echo "<tr>
  <td>" . date('d/m/Y', strtotime($date)) . "</td>
  <td>" . ($entrada ? date('H:i:s', strtotime($entrada['fecha_hora'])) : '-') . "</td>
  <td>" . ($salida ? date('H:i:s', strtotime($salida['fecha_hora'])) : '-') . "</td>
  <td>{$horas}</td>
  <td>" . ($entrada ? $this->esc($entrada['metodo_registro']) : '') . "</td>
  <td style='color:" . ($hashOk === '✓' ? 'green' : 'red') . ";'>{$hashOk}</td>
</tr>";
            }
        }

        echo "</tbody>
  <tfoot>
    <tr>
      <td colspan='3'><strong>TOTAL</strong></td>
      <td><strong>" . number_format($totalHoras, 2) . "h</strong></td>
      <td colspan='2'></td>
    </tr>
  </tfoot>
</table>

<div class='legal'>
  <p>Este documento ha sido generado automáticamente por el sistema de control horario.</p>
  <p>Los registros están protegidos mediante firma HMAC-SHA256.</p>
  <p>La empresa debe conservar estos registros durante un mínimo de 4 años.</p>
</div>
</body>
</html>";
    }

    public function exportInspeccionCSV(array $filters = []): string
    {
        $rowsData = $this->buildInspectionSummaryRows($filters);
        $company = $this->config->get('company_name', 'Control Horario Digital');
        $companyAddress = $this->config->get('company_address', '');
        $companyCif = $this->config->get('company_cif', '');
        $from = $filters['fecha_desde'] ?? date('Y-m-01');
        $to = $filters['fecha_hasta'] ?? date('Y-m-d');

        $rows = [];
        $rows[] = $this->csvRow(['Informe', 'Registro diario de jornada para inspección']);
        $rows[] = $this->csvRow(['Empresa', $company]);
        $rows[] = $this->csvRow(['Dirección', $companyAddress]);
        $rows[] = $this->csvRow(['CIF', $companyCif]);
        $rows[] = $this->csvRow(['Período', date('d/m/Y', strtotime($from)) . ' - ' . date('d/m/Y', strtotime($to))]);
        $rows[] = $this->csvRow(['Generado', date('d/m/Y H:i:s')]);
        $rows[] = '';
        $rows[] = $this->csvRow([
            'Empleado',
            'Email',
            'Departamento',
            'Fecha',
            'Inicio',
            'Fin',
            'Horas',
            'Fichajes del día',
            'Correcciones',
            'Integridad',
            'Observaciones',
        ]);

        foreach ($rowsData as $row) {
            $rows[] = $this->csvRow([
                $row['empleado'],
                $row['email'],
                $row['departamento'],
                date('d/m/Y', strtotime($row['fecha'])),
                $row['inicio'] ? date('H:i:s', strtotime($row['inicio'])) : '',
                $row['fin'] ? date('H:i:s', strtotime($row['fin'])) : '',
                number_format($row['horas_trabajadas'], 2, '.', ''),
                $row['total_fichajes'],
                $row['correcciones'],
                $row['integridad_ok'] ? 'OK' : 'REVISAR',
                $row['observaciones'],
            ]);
        }

        return implode("\r\n", $rows);
    }

    public function exportInspeccionPDF(array $filters = []): void
    {
        $rows = $this->buildInspectionSummaryRows($filters);
        $company = $this->config->get('company_name', 'Control Horario Digital');
        $companyAddress = $this->config->get('company_address', '');
        $companyCif = $this->config->get('company_cif', '');
        $from = $filters['fecha_desde'] ?? date('Y-m-01');
        $to = $filters['fecha_hasta'] ?? date('Y-m-d');

        header('Content-Type: text/html; charset=utf-8');

        echo "<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<title>Informe de inspección</title>
<style>
body{font-family:Arial,sans-serif;font-size:12px;color:#1f2937;margin:24px}
h1,h2{margin:0 0 8px}
.topbar{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;margin-bottom:20px}
.meta p,.legal p{margin:4px 0}
.actions{margin-bottom:16px}
.actions button{padding:10px 18px;background:#2563eb;color:#fff;border:0;border-radius:6px;cursor:pointer}
table{width:100%;border-collapse:collapse;margin-top:16px}
th{background:#1d4ed8;color:#fff;padding:8px;text-align:left}
td{border:1px solid #d1d5db;padding:7px;vertical-align:top}
tr:nth-child(even) td{background:#f8fafc}
.muted{color:#6b7280}
.ok{color:#166534;font-weight:700}
.warn{color:#b45309;font-weight:700}
.legal{margin-top:20px;border-top:1px solid #d1d5db;padding-top:12px;font-size:11px}
@media print {.actions{display:none} body{margin:12px}}
</style>
</head>
<body>
<div class='actions'><button onclick='window.print()'>Imprimir / Guardar PDF</button></div>
<div class='topbar'>
  <div class='meta'>
    <h2>{$this->esc($company)}</h2>
    <p>{$this->esc($companyAddress)}</p>
    <p><strong>CIF:</strong> {$this->esc($companyCif)}</p>
  </div>
  <div class='meta'>
    <h1>Registro diario de jornada</h1>
    <p><strong>Período:</strong> " . date('d/m/Y', strtotime($from)) . " - " . date('d/m/Y', strtotime($to)) . "</p>
    <p><strong>Generado:</strong> " . date('d/m/Y H:i:s') . "</p>
    <p class='muted'>Informe para inspección de trabajo.</p>
  </div>
</div>
<table>
  <thead>
    <tr>
      <th>Empleado</th>
      <th>Departamento</th>
      <th>Fecha</th>
      <th>Inicio</th>
      <th>Fin</th>
      <th>Horas</th>
      <th>Fichajes</th>
      <th>Correc.</th>
      <th>Integridad</th>
      <th>Observaciones</th>
    </tr>
  </thead>
  <tbody>";

        foreach ($rows as $row) {
            echo "<tr>
  <td>{$this->esc($row['empleado'])}<br><span class='muted'>{$this->esc($row['email'])}</span></td>
  <td>{$this->esc($row['departamento'])}</td>
  <td>" . date('d/m/Y', strtotime($row['fecha'])) . "</td>
  <td>" . ($row['inicio'] ? date('H:i:s', strtotime($row['inicio'])) : '-') . "</td>
  <td>" . ($row['fin'] ? date('H:i:s', strtotime($row['fin'])) : '-') . "</td>
  <td>" . number_format($row['horas_trabajadas'], 2, ',', '.') . "</td>
  <td>" . (int)$row['total_fichajes'] . "</td>
  <td>" . (int)$row['correcciones'] . "</td>
  <td class='" . ($row['integridad_ok'] ? 'ok' : 'warn') . "'>" . ($row['integridad_ok'] ? 'OK' : 'REVISAR') . "</td>
  <td>{$this->esc($row['observaciones'])}</td>
</tr>";
        }

        echo "</tbody>
</table>
<div class='legal'>
  <p>Este informe resume el horario concreto de inicio y finalización de la jornada de cada persona trabajadora, conforme al artículo 34.9 del Estatuto de los Trabajadores.</p>
  <p>Los registros de jornada deben conservarse durante 4 años y permanecer a disposición de las personas trabajadoras, de sus representantes legales y de la Inspección de Trabajo y Seguridad Social.</p>
  <p>Los datos personales incluidos se tratan por obligación legal vinculada al registro de jornada y se limitan a los necesarios para control horario e inspección.</p>
</div>
</body>
</html>";
    }

    public function exportIntegridadPDF(array $report): void
    {
        $company = $this->config->get('company_name', 'Control Horario Digital');
        $companyAddress = $this->config->get('company_address', '');
        $companyCif = $this->config->get('company_cif', '');

        header('Content-Type: text/html; charset=utf-8');

        echo "<!DOCTYPE html>
<html lang='es'>
<head>
<meta charset='UTF-8'>
<title>Informe de integridad</title>
<style>
body{font-family:Arial,sans-serif;font-size:12px;color:#1f2937;margin:24px}
h1,h2{margin:0 0 8px}
.topbar{display:flex;justify-content:space-between;gap:24px;align-items:flex-start;margin-bottom:20px}
.actions{margin-bottom:16px}
.actions button{padding:10px 18px;background:#2563eb;color:#fff;border:0;border-radius:6px;cursor:pointer}
.summary{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin:16px 0}
.box{border:1px solid #d1d5db;border-radius:6px;padding:12px;text-align:center}
.box strong{display:block;font-size:22px}
table{width:100%;border-collapse:collapse;margin-top:16px}
th{background:#1d4ed8;color:#fff;padding:8px;text-align:left}
td{border:1px solid #d1d5db;padding:7px;vertical-align:top}
tr:nth-child(even) td{background:#f8fafc}
.ok{color:#166534;font-weight:700}
.bad{color:#b91c1c;font-weight:700}
.legal{margin-top:20px;border-top:1px solid #d1d5db;padding-top:12px;font-size:11px}
@media print {.actions{display:none} body{margin:12px}}
</style>
</head>
<body>
<div class='actions'><button onclick='window.print()'>Imprimir / Guardar PDF</button></div>
<div class='topbar'>
  <div>
    <h2>{$this->esc($company)}</h2>
    <div>{$this->esc($companyAddress)}</div>
    <div><strong>CIF:</strong> {$this->esc($companyCif)}</div>
  </div>
  <div>
    <h1>Informe de integridad de fichajes</h1>
    <div><strong>Generado:</strong> {$this->esc($report['generated_at'])}</div>
  </div>
</div>
<div class='summary'>
  <div class='box'><strong>" . (int)$report['total_users'] . "</strong>Total empleados</div>
  <div class='box'><strong>" . (int)$report['valid_users'] . "</strong>Cadenas válidas</div>
  <div class='box'><strong>" . (int)$report['invalid_users'] . "</strong>Cadenas con errores</div>
  <div class='box'><strong>" . (int)$report['total_records'] . "</strong>Total registros</div>
</div>
<table>
  <thead>
    <tr>
      <th>Empleado</th>
      <th>Email</th>
      <th>Total</th>
      <th>Verificados</th>
      <th>Estado</th>
      <th>Errores</th>
    </tr>
  </thead>
  <tbody>";

        foreach ($report['chains'] as $chain) {
            $errors = [];
            foreach ($chain['errors'] as $error) {
                $errors[] = $this->esc($error['fecha'] . ' - ' . $error['tipo'] . ' - ' . $error['error']);
            }

            echo "<tr>
  <td>{$this->esc(trim(($chain['nombre'] ?? '') . ' ' . ($chain['apellidos'] ?? '')))}</td>
  <td>{$this->esc($chain['email'] ?? '')}</td>
  <td>" . (int)$chain['total'] . "</td>
  <td>" . (int)$chain['verified'] . "</td>
  <td class='" . ($chain['valid'] ? 'ok' : 'bad') . "'>" . ($chain['valid'] ? 'VÁLIDA' : 'COMPROMETIDA') . "</td>
  <td>" . ($errors ? implode('<br>', $errors) : 'Sin errores') . "</td>
</tr>";
        }

        echo "</tbody>
</table>
<div class='legal'>
  <p>Este informe verifica la cadena de integridad HMAC-SHA256 de los registros de fichaje y su encadenado por orden de inserción.</p>
  <p>Los datos personales incluidos se tratan por obligación legal vinculada al registro de jornada. El acceso a este informe debe limitarse a personal autorizado.</p>
  <p>Conserve este informe junto con la documentación de control horario durante el plazo legal aplicable.</p>
</div>
</body>
</html>";
    }

    private function csvRow(array $fields): string
    {
        $escaped = array_map(function ($field) {
            $field = str_replace('"', '""', $field ?? '');
            return '"' . $field . '"';
        }, $fields);

        return implode(';', $escaped);
    }

    private function buildInspectionSummaryRows(array $filters = []): array
    {
        $fichajes = $this->fichajeModel->getAllWithFilters(array_merge($filters, ['limit' => 10000]));

        usort($fichajes, function (array $a, array $b) {
            return [$a['usuario_id'], $a['fecha_hora'], $a['id']] <=> [$b['usuario_id'], $b['fecha_hora'], $b['id']];
        });

        $grouped = [];

        foreach ($fichajes as $f) {
            $date = date('Y-m-d', strtotime($f['fecha_hora']));
            $key = $f['usuario_id'] . '|' . $date;

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'empleado' => trim(($f['nombre'] ?? '') . ' ' . ($f['apellidos'] ?? '')),
                    'email' => $f['email'] ?? '',
                    'departamento' => $f['departamento'] ?? '',
                    'fecha' => $date,
                    'inicio' => null,
                    'fin' => null,
                    'total_fichajes' => 0,
                    'correcciones' => 0,
                    'integridad_ok' => true,
                    'registros' => [],
                ];
            }

            $grouped[$key]['total_fichajes']++;
            $grouped[$key]['registros'][] = $f;

            if (!empty($f['es_correccion'])) {
                $grouped[$key]['correcciones']++;
            }

            if ($f['tipo'] === 'entrada' && ($grouped[$key]['inicio'] === null || $f['fecha_hora'] < $grouped[$key]['inicio'])) {
                $grouped[$key]['inicio'] = $f['fecha_hora'];
            }

            if ($f['tipo'] === 'salida' && ($grouped[$key]['fin'] === null || $f['fecha_hora'] > $grouped[$key]['fin'])) {
                $grouped[$key]['fin'] = $f['fecha_hora'];
            }

            if (!$this->integrityService->verifyFichaje($f)) {
                $grouped[$key]['integridad_ok'] = false;
            }
        }

        $rows = [];

        foreach ($grouped as $row) {
            $observaciones = [];

            if ($row['inicio'] === null || $row['fin'] === null) {
                $observaciones[] = 'Jornada incompleta';
            }

            if ($row['correcciones'] > 0) {
                $observaciones[] = $row['correcciones'] === 1
                    ? 'Incluye 1 corrección administrativa'
                    : 'Incluye ' . $row['correcciones'] . ' correcciones administrativas';
            }

            if (!$row['integridad_ok']) {
                $observaciones[] = 'Revisar integridad';
            }

            $row['horas_trabajadas'] = $this->calculateWorkedHours($row['registros']);
            $row['observaciones'] = implode('. ', $observaciones);
            unset($row['registros']);
            $rows[] = $row;
        }

        usort($rows, function (array $a, array $b) {
            return [$a['empleado'], $a['fecha']] <=> [$b['empleado'], $b['fecha']];
        });

        return $rows;
    }

    private function calculateWorkedHours(array $registros): float
    {
        usort($registros, function (array $a, array $b) {
            return [$a['fecha_hora'], $a['id']] <=> [$b['fecha_hora'], $b['id']];
        });

        $seconds = 0;
        $lastEntrada = null;

        foreach ($registros as $registro) {
            $timestamp = strtotime($registro['fecha_hora']);

            if ($registro['tipo'] === 'entrada') {
                $lastEntrada = $timestamp;
                continue;
            }

            if ($registro['tipo'] === 'salida' && $lastEntrada !== null && $timestamp >= $lastEntrada) {
                $seconds += $timestamp - $lastEntrada;
                $lastEntrada = null;
            }
        }

        return $seconds / 3600;
    }

    private function esc(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
