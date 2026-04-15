<?php
/**
 * =====================================================
 * Control Horario Digital - Asistente de Instalación
 * Conforme a la Ley de Registro de Jornada (RD-Ley 8/2019)
 * Copyright (C) 2026 Javier Ortiz - disciplinadigital.es
 * * Este programa es software libre: usted puede redistribuirlo y/o modificarlo 
 * bajo los términos de la Licencia Pública General GNU publicada por 
 * la Free Software Foundation, ya sea la versión 3 de la Licencia, o 
 * (a su elección) cualquier versión posterior.
 * =====================================================
 * Archivo autocontenido. No requiere Composer ni ninguna
 * dependencia externa. Solo PHP 8.1+ y MySQL/MariaDB.
 */

declare(strict_types=1);

header('Content-Type: text/html; charset=UTF-8');
@ini_set('default_charset', 'UTF-8');

// --- Mostrar errores para diagnóstico (se puede desactivar tras instalar) ---
@ini_set('display_errors', '1');
@ini_set('display_startup_errors', '1');
@error_reporting(E_ALL);

define('INSTALL_DIR',  __DIR__);
define('ROOT_DIR',     dirname(dirname(__DIR__)));
define('LOCK_FILE',    INSTALL_DIR . '/.lock');
define('ENV_FILE',     ROOT_DIR . '/.env');
define('UPLOADS_DIR',  ROOT_DIR . '/public/assets/uploads');
define('LOGO_SRC',     INSTALL_DIR . '/logo.png');
define('LOGO_DST',     ROOT_DIR . '/public/assets/logo.png');

session_start();

// --- Bloqueo si ya está instalado ---
if (file_exists(LOCK_FILE) && ($_GET['step'] ?? '') !== 'done') {
    $appUrl = file_get_contents(LOCK_FILE);
    header('Location: ' . trim($appUrl));
    exit;
}

// --- Inicializar datos de sesión ---
// Inicializar (o re-inicializar) si faltan claves esenciales
if (!isset($_SESSION['install']['data']) || !is_array($_SESSION['install']['data'])) {
    $_SESSION['install'] = [
        'step'   => 1,
        'data'   => [],
        'errors' => [],
    ];
}

$step   = (int)($_GET['step'] ?? $_SESSION['install']['step'] ?? 1);
$errors = [];
$info   = [];

// --- Requisitos del sistema ---
function checkRequirements(): array
{
    $checks = [];

    $checks[] = [
        'name'   => 'PHP ' . PHP_VERSION,
        'ok'     => version_compare(PHP_VERSION, '8.1.0', '>='),
        'detail' => 'Se requiere PHP 8.1 o superior',
    ];
    $checks[] = [
        'name'   => 'Extensión PDO',
        'ok'     => extension_loaded('pdo'),
        'detail' => 'Necesaria para la conexión a base de datos',
    ];
    $checks[] = [
        'name'   => 'Extensión PDO MySQL',
        'ok'     => extension_loaded('pdo_mysql'),
        'detail' => 'Necesaria para MySQL/MariaDB',
    ];
    $checks[] = [
        'name'   => 'Extensión OpenSSL',
        'ok'     => extension_loaded('openssl'),
        'detail' => 'Necesaria para firmas criptográficas',
    ];
    $checks[] = [
        'name'   => 'Extensión mbstring',
        'ok'     => extension_loaded('mbstring'),
        'detail' => 'Necesaria para manejo de texto UTF-8',
    ];
    $checks[] = [
        'name'   => 'Extensión JSON',
        'ok'     => extension_loaded('json'),
        'detail' => 'Necesaria para la API',
    ];
    $checks[] = [
        'name'   => 'Extensión GD o Imagick',
        'ok'     => extension_loaded('gd') || extension_loaded('imagick'),
        'detail' => 'Recomendada para procesamiento de imágenes',
        'warn'   => true,
    ];
    $checks[] = [
        'name'   => 'Escritura en ' . ROOT_DIR,
        'ok'     => is_writable(ROOT_DIR),
        'detail' => 'Necesaria para crear el archivo .env',
    ];
    $checks[] = [
        'name'   => 'Escritura en public/assets/uploads',
        'ok'     => is_writable(ROOT_DIR . '/public/assets') || is_dir(ROOT_DIR . '/public/assets/uploads'),
        'detail' => 'Necesaria para subir el logo',
        'warn'   => true,
    ];
    $checks[] = [
        'name'   => 'Mod_rewrite / URL Rewriting',
        'ok'     => (
            isset($_SERVER['HTTP_MOD_REWRITE']) ||
            (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())) ||
            isset($_SERVER['REDIRECT_URL'])
        ),
        'detail' => 'Necesario para URLs amigables (Apache)',
        'warn'   => true,
    ];

    return $checks;
}

function allRequirementsOk(array $checks): bool
{
    foreach ($checks as $c) {
        if (!$c['ok'] && empty($c['warn'])) {
            return false;
        }
    }
    return true;
}

// --- Prueba de conexión a base de datos ---
function testDbConnection(array $d): ?PDO
{
    try {
        $dsn = "mysql:host={$d['db_host']};port={$d['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $d['db_user'], $d['db_pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        ]);
        return $pdo;
    } catch (\PDOException $e) {
        return null;
    }
}

// --- Generar clave aleatoria ---
function generateKey(int $len = 32): string
{
    return bin2hex(random_bytes($len));
}

function normalizeInstallerCoordinate(string $value): string
{
    $value = trim(str_replace(',', '.', $value));
    if ($value === '') {
        return '';
    }

    if (!is_numeric($value)) {
        return $value;
    }

    return number_format((float)$value, 8, '.', '');
}

// --- Procesamiento de pasos POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $postedStep = (int)($_POST['step'] ?? 0);

    // Paso 1: Requisitos → avanzar a paso 2
    if ($postedStep === 1) {
        $_SESSION['install']['step'] = 2;
        header('Location: ?step=2');
        exit;
    }

    // Paso 2: BD
    if ($postedStep === 2) {
        $d = [
            'db_host' => trim($_POST['db_host'] ?? 'localhost'),
            'db_port' => trim($_POST['db_port'] ?? '3306'),
            'db_name' => trim($_POST['db_name'] ?? ''),
            'db_user' => trim($_POST['db_user'] ?? ''),
            'db_pass' => $_POST['db_pass'] ?? '',
        ];
        if (empty($d['db_name'])) $errors[] = 'El nombre de la base de datos es obligatorio.';
        if (empty($d['db_user'])) $errors[] = 'El usuario de base de datos es obligatorio.';

        if (empty($errors)) {
            $pdo = testDbConnection($d);
            if (!$pdo) {
                $errors[] = 'No se pudo conectar a MySQL con los datos proporcionados. Verifica host, usuario y contraseña.';
            } else {
                $_SESSION['install']['data'] = array_merge($_SESSION['install']['data'] ?? [], $d);
                $step = 3;
                $_SESSION['install']['step'] = 3;
            }
        }
    }

    // Paso 3: Aplicación
    elseif ($postedStep === 3) {
        $d = [
            'app_name'        => trim($_POST['app_name'] ?? 'Control Horario Digital'),
            'app_url'         => rtrim(trim($_POST['app_url'] ?? ''), '/'),
            'company_name'    => trim($_POST['company_name'] ?? ''),
            'company_address' => trim($_POST['company_address'] ?? ''),
            'company_cif'     => trim($_POST['company_cif'] ?? ''),
            'timezone'        => trim($_POST['timezone'] ?? 'Europe/Madrid'),
        ];
        if (empty($d['app_url'])) $errors[] = 'La URL de la aplicación es obligatoria.';
        if (empty($d['company_name'])) $errors[] = 'El nombre de la empresa es obligatorio.';

        if (empty($errors)) {
            $_SESSION['install']['data'] = array_merge($_SESSION['install']['data'] ?? [], $d);
            $step = 4;
            $_SESSION['install']['step'] = 4;
        }
    }

    // Paso 4: Admin
    elseif ($postedStep === 4) {
        $d = [
            'admin_nombre'    => trim($_POST['admin_nombre'] ?? ''),
            'admin_apellidos' => trim($_POST['admin_apellidos'] ?? ''),
            'admin_email'     => trim($_POST['admin_email'] ?? ''),
            'admin_password'  => $_POST['admin_password'] ?? '',
            'admin_password2' => $_POST['admin_password2'] ?? '',
        ];
        if (empty($d['admin_nombre']))    $errors[] = 'El nombre del administrador es obligatorio.';
        if (empty($d['admin_email']) || !filter_var($d['admin_email'], FILTER_VALIDATE_EMAIL))
            $errors[] = 'Email de administrador inválido.';
        if (strlen($d['admin_password']) < 8)
            $errors[] = 'La contraseña debe tener al menos 8 caracteres.';
        if ($d['admin_password'] !== $d['admin_password2'])
            $errors[] = 'Las contraseñas no coinciden.';

        if (empty($errors)) {
            $_SESSION['install']['data'] = array_merge($_SESSION['install']['data'] ?? [], $d);
            $step = 5;
            $_SESSION['install']['step'] = 5;
        }
    }

    // Paso 5: Email SMTP
    elseif ($postedStep === 5) {
        $d = [
            'smtp_host'      => trim($_POST['smtp_host'] ?? ''),
            'smtp_port'      => trim($_POST['smtp_port'] ?? '587'),
            'smtp_secure'    => trim($_POST['smtp_secure'] ?? 'tls'),
            'smtp_user'      => trim($_POST['smtp_user'] ?? ''),
            'smtp_pass'      => $_POST['smtp_pass'] ?? '',
            'smtp_from'      => trim($_POST['smtp_from'] ?? ''),
            'smtp_from_name' => trim($_POST['smtp_from_name'] ?? ''),
            'smtp_skip'      => isset($_POST['smtp_skip']),
        ];
        $_SESSION['install']['data'] = array_merge($_SESSION['install']['data'] ?? [], $d);
        $step = 6;
        $_SESSION['install']['step'] = 6;
    }

    // Paso 6: Geolocalización
    elseif ($postedStep === 6) {
        $d = [
            'default_lat'  => normalizeInstallerCoordinate($_POST['default_lat'] ?? ''),
            'default_lon'  => normalizeInstallerCoordinate($_POST['default_lon'] ?? ''),
            'max_distance' => trim($_POST['max_distance'] ?? '30'),
            'min_accuracy' => trim($_POST['min_accuracy'] ?? '10'),
            'work_start'   => trim($_POST['work_start'] ?? '08:00'),
            'work_end'     => trim($_POST['work_end'] ?? '18:00'),
            'vac_days'     => trim($_POST['vac_days'] ?? '22'),
        ];
        if ($d['default_lat'] !== '' && !is_numeric($d['default_lat'])) $errors[] = 'La latitud no es válida.';
        if ($d['default_lon'] !== '' && !is_numeric($d['default_lon'])) $errors[] = 'La longitud no es válida.';

        if (empty($errors)) {
            $_SESSION['install']['data'] = array_merge($_SESSION['install']['data'] ?? [], $d);
            $step = 7;
            $_SESSION['install']['step'] = 7;
        } else {
            $step = 6;
            $_SESSION['install']['step'] = 6;
        }
    }

    // Paso 7: INSTALAR
    elseif ($postedStep === 7) {
        $d = $_SESSION['install']['data'];

        // Aumentar límites para el proceso de instalación
        @set_time_limit(180);
        @ini_set('memory_limit', '256M');

        try {
            // Verificar que tenemos datos de sesión
            if (empty($d['db_host']) || empty($d['db_name']) || empty($d['db_user'])) {
                throw new \RuntimeException('Datos de instalación incompletos. Por favor, vuelve a empezar el asistente.');
            }
            // 1. Conectar sin base de datos
            $pdo = new PDO(
                "mysql:host={$d['db_host']};port={$d['db_port']};charset=utf8mb4",
                $d['db_user'],
                $d['db_pass'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]
            );

            // 2. Crear base de datos
            $dbName = preg_replace('/[^a-zA-Z0-9_]/', '', $d['db_name']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `{$dbName}`");
            $info[] = "✓ Base de datos '{$dbName}' creada / verificada.";

            // 3. Crear tablas
            foreach (getSchemaSql() as $sql) {
                $sql = trim($sql);
                if ($sql) $pdo->exec($sql);
            }
            $info[] = '✓ Tablas creadas correctamente.';

            // 3b. Triggers de inmutabilidad (fichajes y audit_log)
            foreach (getImmutabilityTriggersSql() as $sql) {
                $pdo->exec($sql);
            }
            $info[] = '✓ Triggers de inmutabilidad aplicados (fichajes y audit_log).';

            // 4. Usuario administrador
            $passwordHash = password_hash($d['admin_password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO `usuarios` (nombre, apellidos, email, password_hash, role, activo, dias_vacaciones_anuales, terminos_aceptados)
                VALUES (?, ?, ?, ?, 'admin', 1, 22, 1)
                ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), password_hash = VALUES(password_hash)
            ");
            $stmt->execute([$d['admin_nombre'], $d['admin_apellidos'], $d['admin_email'], $passwordHash]);
            $adminId = $pdo->lastInsertId() ?: 1;
            $info[] = "✓ Usuario administrador '{$d['admin_email']}' creado.";

            // 5. Días de vacaciones para admin
            $pdo->prepare("
                INSERT INTO dias_vacaciones (usuario_id, `anio`, dias_totales, dias_usados, dias_pendientes)
                VALUES (?, YEAR(NOW()), 22, 0, 22)
                ON DUPLICATE KEY UPDATE dias_totales = 22
            ")->execute([$adminId]);

            // 6. Festivos nacionales 2024-2030
            foreach (getFestivosSql() as $sql) {
                $sql = trim($sql);
                if ($sql) $pdo->exec($sql);
            }
            $info[] = '✓ Festivos nacionales 2024–2030 importados.';

            // 7. Configuración inicial
            $configValues = [
                ['company_name',          $d['company_name'],       'text'],
                ['company_address',       $d['company_address'],    'text'],
                ['company_cif',           $d['company_cif'],        'text'],
                ['logo',                  null,                     'file'],
                ['color_primary',         '#2563eb',                'color'],
                ['color_secondary',       '#64748b',                'color'],
                ['color_accent',          '#0ea5e9',                'color'],
                ['color_success',         '#16a34a',                'color'],
                ['color_warning',         '#d97706',                'color'],
                ['color_danger',          '#dc2626',                'color'],
                ['color_bg',              '#f8fafc',                'color'],
                ['color_text',            '#1e293b',                'color'],
                ['default_lat',           $d['default_lat'] ?: '',  'number'],
                ['default_lon',           $d['default_lon'] ?: '',  'number'],
                ['max_distance',          $d['max_distance'],       'number'],
                ['min_accuracy',          $d['min_accuracy'],       'number'],
                ['default_vacation_days', $d['vac_days'],           'number'],
                ['work_start',            $d['work_start'],         'text'],
                ['work_end',              $d['work_end'],           'text'],
            ];
            $stmtConf = $pdo->prepare("
                INSERT INTO configuracion (clave, valor, tipo) VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor)
            ");
            foreach ($configValues as $cv) {
                $stmtConf->execute($cv);
            }
            $info[] = '✓ Configuración del sistema guardada.';

            // 8. Copiar logo al directorio público
            if (!is_dir(UPLOADS_DIR)) {
                @mkdir(UPLOADS_DIR, 0755, true);
            }
            if (file_exists(LOGO_SRC) && !file_exists(LOGO_DST)) {
                @copy(LOGO_SRC, LOGO_DST);
            }

            // 9. Crear archivo .env
            $hmacSecret = generateKey(32);
            $appKey     = generateKey(32);
            $smtpHost   = $d['smtp_skip'] ? '' : ($d['smtp_host'] ?? '');
            $smtpPort   = $d['smtp_skip'] ? '587' : ($d['smtp_port'] ?? '587');
            $smtpSecure = $d['smtp_skip'] ? 'tls' : ($d['smtp_secure'] ?? 'tls');
            $smtpUser   = $d['smtp_skip'] ? '' : ($d['smtp_user'] ?? '');
            $smtpPass   = $d['smtp_skip'] ? '' : ($d['smtp_pass'] ?? '');
            $smtpFrom   = $d['smtp_skip'] ? '' : ($d['smtp_from'] ?? '');
            $smtpName   = $d['smtp_skip'] ? '' : ($d['smtp_from_name'] ?? $d['company_name']);

            $envContent = <<<ENV
APP_NAME="{$d['app_name']}"
APP_ENV=production
APP_DEBUG=false
APP_URL={$d['app_url']}
APP_KEY={$appKey}

DB_HOST={$d['db_host']}
DB_PORT={$d['db_port']}
DB_NAME={$d['db_name']}
DB_USER={$d['db_user']}
DB_PASS={$d['db_pass']}

HMAC_SECRET={$hmacSecret}

SMTP_HOST={$smtpHost}
SMTP_PORT={$smtpPort}
SMTP_SECURE={$smtpSecure}
SMTP_USER={$smtpUser}
SMTP_PASS={$smtpPass}
SMTP_FROM={$smtpFrom}
SMTP_FROM_NAME="{$smtpName}"

DEFAULT_LAT={$d['default_lat']}
DEFAULT_LON={$d['default_lon']}
MAX_DISTANCE={$d['max_distance']}
MIN_ACCURACY={$d['min_accuracy']}

TIMEZONE={$d['timezone']}
SESSION_LIFETIME=3600
ENV;

            // Crear directorio storage si no existe
            if (!is_dir(ROOT_DIR . '/storage/logs')) {
                @mkdir(ROOT_DIR . '/storage/logs', 0755, true);
            }

            $written = file_put_contents(ENV_FILE, $envContent);
            if ($written === false) {
                throw new \RuntimeException(
                    'No se pudo escribir el archivo .env en ' . ENV_FILE .
                    '. Comprueba que el directorio raíz tiene permisos de escritura (chmod 755 o 777).'
                );
            }
            $info[] = '✓ Archivo .env generado correctamente.';

            // 10. Crear lock file
            $lockWritten = file_put_contents(LOCK_FILE, $d['app_url']);
            if ($lockWritten === false) {
                // No es fatal, pero lo avisamos
                error_log('Control Horario installer: no se pudo escribir el lock file en ' . LOCK_FILE);
            }
            $info[] = '✓ Instalación completada.';

            // Guardar datos para mostrar en paso 8 ANTES de limpiar sesión
            $installedAppUrl    = $d['app_url'] ?? '';
            $installedAdminMail = $d['admin_email'] ?? '';

            // Limpiar sesión
            $_SESSION['install'] = [
                'done_app_url'    => $installedAppUrl,
                'done_admin_mail' => $installedAdminMail,
            ];

            $step = 8; // Paso de éxito

        } catch (\Throwable $e) {
            $errors[] = 'Error durante la instalación: ' . $e->getMessage();
            $step = 7;
        }
    }
}

// --- SQL DE ESQUEMA (sin CREATE DATABASE / USE / datos hardcoded) ---
function getSchemaSql(): array
{
    return [
        "CREATE TABLE IF NOT EXISTS `usuarios` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `nombre` VARCHAR(100) NOT NULL,
          `apellidos` VARCHAR(100) NOT NULL,
          `email` VARCHAR(150) NOT NULL UNIQUE,
          `password_hash` VARCHAR(255) NOT NULL,
          `role` ENUM('admin','usuario') DEFAULT 'usuario',
          `activo` TINYINT(1) DEFAULT 1,
          `must_change_password` TINYINT(1) NOT NULL DEFAULT 0,
          `telefono` VARCHAR(20),
          `departamento` VARCHAR(100),
          `dias_vacaciones_anuales` INT DEFAULT 22,
          `max_distance_override` INT NULL DEFAULT NULL COMMENT '0=sin límite, NULL=usar global, >0=metros',
          `terminos_aceptados` TINYINT(1) DEFAULT 0,
          `fecha_aceptacion_terminos` DATETIME NULL,
          `ultimo_acceso` DATETIME NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `deleted_at` DATETIME NULL,
          INDEX `idx_email` (`email`),
          INDEX `idx_role` (`role`),
          INDEX `idx_activo` (`activo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `fichajes` (
          `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `usuario_id` INT UNSIGNED NOT NULL,
          `tipo` ENUM('entrada','salida') NOT NULL,
          `fecha_hora` DATETIME NOT NULL,
          `latitud` DECIMAL(10,8) NULL,
          `longitud` DECIMAL(11,8) NULL,
          `precision_ubicacion` DECIMAL(8,2) NULL,
          `dispositivo` VARCHAR(255) NULL,
          `ip_address` VARCHAR(45) NULL,
          `metodo_registro` ENUM('web','movil','terminal','manual_admin') DEFAULT 'web',
          `hash_integridad` VARCHAR(64) NOT NULL,
          `hash_anterior` VARCHAR(64) NULL,
          `firmado_en` DATETIME NOT NULL,
          `es_correccion` TINYINT(1) DEFAULT 0,
          `correccion_justificacion` TEXT NULL,
          `incidencia_id` INT UNSIGNED NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_usuario_fecha` (`usuario_id`, `fecha_hora`),
          INDEX `idx_fecha` (`fecha_hora`),
          INDEX `idx_tipo` (`tipo`),
          CONSTRAINT `fk_fichajes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Registros inmutables. PROHIBIDO UPDATE/DELETE por Ley de Registro de Jornada'",

        "CREATE TABLE IF NOT EXISTS `incidencias` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `numero_incidencia` VARCHAR(20) NOT NULL UNIQUE,
          `usuario_id` INT UNSIGNED NOT NULL,
          `admin_id` INT UNSIGNED NULL,
          `tipo` ENUM('olvido_entrada','olvido_salida','error_ubicacion','otro') DEFAULT 'otro',
          `fecha_fichaje` DATE NOT NULL,
          `hora_solicitada` TIME NULL,
          `razon` TEXT NOT NULL,
          `estado` ENUM('pendiente','aceptada','rechazada') DEFAULT 'pendiente',
          `comentario_admin` TEXT NULL,
          `fecha_solicitud` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `fecha_resolucion` DATETIME NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_usuario` (`usuario_id`),
          INDEX `idx_estado` (`estado`),
          CONSTRAINT `fk_incidencias_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
          CONSTRAINT `fk_incidencias_admin` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `vacaciones` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `usuario_id` INT UNSIGNED NOT NULL,
          `admin_id` INT UNSIGNED NULL,
          `fecha_inicio` DATE NOT NULL,
          `fecha_fin` DATE NOT NULL,
          `tipo` ENUM('normal','trabajo','permiso','baja') DEFAULT 'normal',
          `estado` ENUM('pendiente','aprobada','rechazada','cancelada') DEFAULT 'pendiente',
          `origen` ENUM('solicitada','empresa') DEFAULT 'solicitada',
          `comentario_usuario` TEXT NULL,
          `comentario_admin` TEXT NULL,
          `fecha_solicitud` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `fecha_resolucion` DATETIME NULL,
          `fecha_cancelacion` DATETIME NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_usuario` (`usuario_id`),
          INDEX `idx_estado` (`estado`),
          INDEX `idx_fechas` (`fecha_inicio`, `fecha_fin`),
          CONSTRAINT `fk_vacaciones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
          CONSTRAINT `fk_vacaciones_admin` FOREIGN KEY (`admin_id`) REFERENCES `usuarios` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `dias_vacaciones` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `usuario_id` INT UNSIGNED NOT NULL,
          `anio` YEAR NOT NULL,
          `dias_totales` INT DEFAULT 22,
          `dias_usados` INT DEFAULT 0,
          `dias_pendientes` INT DEFAULT 0,
          UNIQUE KEY `unique_user_year` (`usuario_id`, `anio`),
          CONSTRAINT `fk_diasvac_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `festivos` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `fecha` DATE NOT NULL UNIQUE,
          `descripcion` VARCHAR(200) NOT NULL,
          `tipo` ENUM('nacional','autonomico','local','empresa') DEFAULT 'nacional',
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_fecha` (`fecha`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `notificaciones` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `usuario_id` INT UNSIGNED NOT NULL,
          `tipo` ENUM('incidencia','vacacion','sistema','fichaje') NOT NULL,
          `titulo` VARCHAR(200) NOT NULL,
          `mensaje` TEXT NOT NULL,
          `referencia_tipo` VARCHAR(50) NULL,
          `referencia_id` INT UNSIGNED NULL,
          `leida` TINYINT(1) DEFAULT 0,
          `fecha_lectura` DATETIME NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_usuario_leida` (`usuario_id`, `leida`),
          CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `audit_log` (
          `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `usuario_id` INT UNSIGNED NULL,
          `accion` VARCHAR(100) NOT NULL,
          `entidad` VARCHAR(50) NULL,
          `entidad_id` INT UNSIGNED NULL,
          `datos_anteriores` JSON NULL,
          `datos_nuevos` JSON NULL,
          `ip_address` VARCHAR(45) NULL,
          `user_agent` VARCHAR(500) NULL,
          `resultado` ENUM('exitoso','fallido') DEFAULT 'exitoso',
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_usuario` (`usuario_id`),
          INDEX `idx_accion` (`accion`),
          INDEX `idx_entidad` (`entidad`, `entidad_id`),
          INDEX `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Log de auditoría inmutable. PROHIBIDO UPDATE/DELETE por requisito legal'",

        "CREATE TABLE IF NOT EXISTS `configuracion` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `clave` VARCHAR(100) NOT NULL UNIQUE,
          `valor` TEXT NULL,
          `tipo` ENUM('text','color','file','boolean','number') DEFAULT 'text',
          `descripcion` VARCHAR(255) NULL,
          `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `sesiones` (
          `id` VARCHAR(128) PRIMARY KEY,
          `usuario_id` INT UNSIGNED NOT NULL,
          `ip_address` VARCHAR(45) NULL,
          `user_agent` VARCHAR(500) NULL,
          `iniciada_en` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `ultimo_acceso` DATETIME DEFAULT CURRENT_TIMESTAMP,
          `terminada_en` DATETIME NULL,
          `activa` TINYINT(1) DEFAULT 1,
          INDEX `idx_usuario` (`usuario_id`),
          INDEX `idx_activa` (`activa`),
          CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        // --- Seguridad: intentos de login (brute force) ---
        "CREATE TABLE IF NOT EXISTS `login_intentos` (
          `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `ip` VARCHAR(45) NOT NULL,
          `email` VARCHAR(150) NOT NULL,
          `exitoso` TINYINT(1) DEFAULT 0,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_ip_created` (`ip`, `created_at`),
          INDEX `idx_email_created` (`email`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Registro de intentos de login para protección anti fuerza bruta'",

        // --- Seguridad: tokens de recuperación de contraseña ---
        "CREATE TABLE IF NOT EXISTS `password_resets` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `email` VARCHAR(150) NOT NULL,
          `token_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 del token enviado al usuario',
          `expires_at` DATETIME NOT NULL,
          `usado` TINYINT(1) DEFAULT 0,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_token` (`token_hash`),
          INDEX `idx_email` (`email`),
          INDEX `idx_expires` (`expires_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        COMMENT='Tokens de un solo uso para recuperación de contraseña (expiración 1h)'",
    ];
}


function getImmutabilityTriggersSql(): array
{
    return [
        "DROP TRIGGER IF EXISTS trg_fichajes_no_update",
        "CREATE TRIGGER trg_fichajes_no_update BEFORE UPDATE ON fichajes FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los fichajes son inmutables y no pueden modificarse.'",
        "DROP TRIGGER IF EXISTS trg_fichajes_no_delete",
        "CREATE TRIGGER trg_fichajes_no_delete BEFORE DELETE ON fichajes FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los fichajes son inmutables y no pueden eliminarse.'",
        "DROP TRIGGER IF EXISTS trg_audit_log_no_update",
        "CREATE TRIGGER trg_audit_log_no_update BEFORE UPDATE ON audit_log FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El log de auditoría es inmutable y no puede modificarse.'",
        "DROP TRIGGER IF EXISTS trg_audit_log_no_delete",
        "CREATE TRIGGER trg_audit_log_no_delete BEFORE DELETE ON audit_log FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El log de auditoría es inmutable y no puede eliminarse.'",
    ];
}

// --- SQL DE FESTIVOS ---
function getFestivosSql(): array
{
    return [
        "INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
        ('2024-01-01','Año Nuevo','nacional'),('2024-01-06','Reyes Magos','nacional'),
        ('2024-03-28','Jueves Santo','nacional'),('2024-03-29','Viernes Santo','nacional'),
        ('2024-05-01','Día del Trabajador','nacional'),('2024-08-15','Asunción de la Virgen','nacional'),
        ('2024-10-12','Día de la Hispanidad','nacional'),('2024-11-01','Todos los Santos','nacional'),
        ('2024-12-06','Día de la Constitución','nacional'),('2024-12-08','Inmaculada Concepción','nacional'),
        ('2024-12-25','Navidad','nacional')
        ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)",

        "INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
        ('2025-01-01','Año Nuevo','nacional'),('2025-01-06','Reyes Magos','nacional'),
        ('2025-04-17','Jueves Santo','nacional'),('2025-04-18','Viernes Santo','nacional'),
        ('2025-05-01','Día del Trabajador','nacional'),('2025-08-15','Asunción de la Virgen','nacional'),
        ('2025-10-12','Día de la Hispanidad','nacional'),('2025-11-01','Todos los Santos','nacional'),
        ('2025-12-06','Día de la Constitución','nacional'),('2025-12-08','Inmaculada Concepción','nacional'),
        ('2025-12-25','Navidad','nacional')
        ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)",

        "INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
        ('2026-01-01','Año Nuevo','nacional'),('2026-01-06','Reyes Magos','nacional'),
        ('2026-04-02','Jueves Santo','nacional'),('2026-04-03','Viernes Santo','nacional'),
        ('2026-05-01','Día del Trabajador','nacional'),('2026-08-15','Asunción de la Virgen','nacional'),
        ('2026-10-12','Día de la Hispanidad','nacional'),('2026-11-01','Todos los Santos','nacional'),
        ('2026-12-06','Día de la Constitución','nacional'),('2026-12-08','Inmaculada Concepción','nacional'),
        ('2026-12-25','Navidad','nacional')
        ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)",

        "INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
        ('2027-01-01','Año Nuevo','nacional'),('2027-01-06','Reyes Magos','nacional'),
        ('2027-03-25','Jueves Santo','nacional'),('2027-03-26','Viernes Santo','nacional'),
        ('2027-05-01','Día del Trabajador','nacional'),('2027-08-15','Asunción de la Virgen','nacional'),
        ('2027-10-12','Día de la Hispanidad','nacional'),('2027-11-01','Todos los Santos','nacional'),
        ('2027-12-06','Día de la Constitución','nacional'),('2027-12-08','Inmaculada Concepción','nacional'),
        ('2027-12-25','Navidad','nacional')
        ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)",

        "INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
        ('2028-01-01','Año Nuevo','nacional'),('2028-01-06','Reyes Magos','nacional'),
        ('2028-04-13','Jueves Santo','nacional'),('2028-04-14','Viernes Santo','nacional'),
        ('2028-05-01','Día del Trabajador','nacional'),('2028-08-15','Asunción de la Virgen','nacional'),
        ('2028-10-12','Día de la Hispanidad','nacional'),('2028-11-01','Todos los Santos','nacional'),
        ('2028-12-06','Día de la Constitución','nacional'),('2028-12-08','Inmaculada Concepción','nacional'),
        ('2028-12-25','Navidad','nacional')
        ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)",

        "INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
        ('2029-01-01','Año Nuevo','nacional'),('2029-01-06','Reyes Magos','nacional'),
        ('2029-03-29','Jueves Santo','nacional'),('2029-03-30','Viernes Santo','nacional'),
        ('2029-05-01','Día del Trabajador','nacional'),('2029-08-15','Asunción de la Virgen','nacional'),
        ('2029-10-12','Día de la Hispanidad','nacional'),('2029-11-01','Todos los Santos','nacional'),
        ('2029-12-06','Día de la Constitución','nacional'),('2029-12-08','Inmaculada Concepción','nacional'),
        ('2029-12-25','Navidad','nacional')
        ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)",

        "INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
        ('2030-01-01','Año Nuevo','nacional'),('2030-01-06','Reyes Magos','nacional'),
        ('2030-04-18','Jueves Santo','nacional'),('2030-04-19','Viernes Santo','nacional'),
        ('2030-05-01','Día del Trabajador','nacional'),('2030-08-15','Asunción de la Virgen','nacional'),
        ('2030-10-12','Día de la Hispanidad','nacional'),('2030-11-01','Todos los Santos','nacional'),
        ('2030-12-06','Día de la Constitución','nacional'),('2030-12-08','Inmaculada Concepción','nacional'),
        ('2030-12-25','Navidad','nacional')
        ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)",
    ];
}

// --- Helpers de vista ---
function h(mixed $v): string { return htmlspecialchars((string)$v, ENT_QUOTES); }
function val(string $key, string $default = ''): string
{
    return h($_SESSION['install']['data'][$key] ?? $default);
}
function logoTag(): string
{
    if (file_exists(LOGO_SRC)) {
        $b64 = base64_encode(file_get_contents(LOGO_SRC));
        return '<img src="data:image/png;base64,' . $b64 . '" alt="Logo" class="installer-logo">';
    }
    return '<span class="installer-logo-text">Control Horario Digital</span>';
}

$totalSteps = 7;
$pct = $step <= $totalSteps ? round(($step - 1) / ($totalSteps - 1) * 100) : 100;

?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalación - Control Horario Digital</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  :root {
    --brand: #2563eb;
    --brand-dark: #1d4ed8;
    --brand-light: #eff6ff;
    --success: #16a34a;
    --warn: #d97706;
    --danger: #dc2626;
  }
  body {
    background: linear-gradient(135deg, #f0f4ff 0%, #e8f0fe 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', system-ui, sans-serif;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 2rem 1rem;
  }
  .installer-wrap {
    width: 100%;
    max-width: 720px;
  }
  .installer-header {
    text-align: center;
    margin-bottom: 2rem;
  }
  .installer-logo {
    max-height: 70px;
    max-width: 320px;
    object-fit: contain;
  }
  .installer-logo-text {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--brand);
    letter-spacing: -0.5px;
  }
  .installer-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 8px 40px rgba(37,99,235,.12);
    overflow: hidden;
  }
  .installer-progress-bar {
    height: 4px;
    background: linear-gradient(90deg, var(--brand) <?= $pct ?>%, #e2e8f0 <?= $pct ?>%);
    transition: all .4s ease;
  }
  .installer-steps {
    display: flex;
    gap: 0;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    overflow-x: auto;
    padding: 0 1.5rem;
  }
  .installer-step-item {
    padding: .75rem 1rem;
    font-size: .78rem;
    font-weight: 600;
    color: #94a3b8;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: .35rem;
    border-bottom: 3px solid transparent;
    transition: all .2s;
  }
  .installer-step-item.active {
    color: var(--brand);
    border-bottom-color: var(--brand);
  }
  .installer-step-item.done {
    color: var(--success);
  }
  .installer-step-item .step-num {
    width: 20px; height: 20px;
    border-radius: 50%;
    background: #e2e8f0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: .7rem;
    font-weight: 700;
    flex-shrink: 0;
  }
  .installer-step-item.active .step-num { background: var(--brand); color: #fff; }
  .installer-step-item.done .step-num   { background: var(--success); color: #fff; }
  .installer-body { padding: 2rem 2.5rem; }
  .step-title { font-size: 1.4rem; font-weight: 700; color: #1e293b; margin-bottom: .3rem; }
  .step-subtitle { color: #64748b; font-size: .9rem; margin-bottom: 1.5rem; }
  .form-label { font-weight: 600; font-size: .875rem; color: #374151; }
  .form-text  { font-size: .78rem; }
  .req-item { display: flex; align-items: center; gap: .6rem; padding: .4rem .5rem; border-radius: 6px; margin-bottom: .3rem; font-size: .875rem; }
  .req-ok   { background: #f0fdf4; color: #166534; }
  .req-warn { background: #fffbeb; color: #92400e; }
  .req-fail { background: #fef2f2; color: #991b1b; }
  .geo-map-hint { background: #eff6ff; border-radius: 8px; padding: .75rem 1rem; font-size: .82rem; color: #1e40af; border-left: 3px solid var(--brand); }
  .success-icon { font-size: 4rem; color: var(--success); }
  .success-card { text-align: center; padding: 1rem 0 2rem; }
  .btn-brand { background: var(--brand); color: #fff; border: none; border-radius: 8px; padding: .65rem 2rem; font-weight: 600; transition: background .2s; }
  .btn-brand:hover { background: var(--brand-dark); color: #fff; }
  .btn-outline-brand { border: 2px solid var(--brand); color: var(--brand); border-radius: 8px; padding: .6rem 2rem; font-weight: 600; background: transparent; transition: all .2s; }
  .btn-outline-brand:hover { background: var(--brand); color: #fff; }
  .footer-note { text-align: center; margin-top: 1.5rem; font-size: .78rem; color: #94a3b8; }
  .alert-list li { margin-bottom: .25rem; }
  @media(max-width:576px) { .installer-body { padding: 1.5rem 1rem; } }
</style>
</head>
<body>
<div class="installer-wrap">

  <div class="installer-header">
    <?= logoTag() ?>
    <p class="mt-2 mb-0 text-muted" style="font-size:.85rem">
      <i class="bi bi-shield-check me-1 text-primary"></i>
      Sistema de Control Horario - Conforme a la Ley de Registro de Jornada (RD-Ley 8/2019)
    </p>
  </div>

  <div class="installer-card">
    <div class="installer-progress-bar"></div>

    <?php if ($step < 8): ?>
    <div class="installer-steps">
      <?php
      $steps = [
        1 => ['Bienvenida', 'bi-house'],
        2 => ['Base de datos', 'bi-database'],
        3 => ['Aplicación', 'bi-gear'],
        4 => ['Administrador', 'bi-person-fill-gear'],
        5 => ['Email', 'bi-envelope'],
        6 => ['Geolocalización', 'bi-geo-alt'],
        7 => ['Instalar', 'bi-rocket'],
      ];
      foreach ($steps as $n => [$label, $icon]):
        $cls = $n < $step ? 'done' : ($n === $step ? 'active' : '');
      ?>
      <div class="installer-step-item <?= $cls ?>">
        <span class="step-num">
          <?= $n < $step ? '<i class="bi bi-check" style="font-size:.7rem"></i>' : $n ?>
        </span>
        <i class="bi <?= $icon ?>"></i><?= $label ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <div class="installer-body">

      <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <ul class="mb-0 alert-list ps-3">
          <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if (!empty($info) && $step === 8): ?>
      <div class="alert alert-success">
        <ul class="mb-0 alert-list ps-3">
          <?php foreach ($info as $i): ?><li><?= h($i) ?></li><?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- PASO 1: Bienvenida + Requisitos -->
      <?php if ($step === 1):
        $checks = checkRequirements();
        $allOk  = allRequirementsOk($checks);
      ?>
      <div class="step-title"><i class="bi bi-house-heart me-2 text-primary"></i>Bienvenido al Instalador</div>
      <p class="step-subtitle">Este asistente configurará el sistema en pocos minutos. Antes de comenzar, verificamos que tu servidor cumple los requisitos mínimos.</p>

      <h6 class="fw-bold mb-2">Verificación de requisitos del sistema</h6>
      <?php foreach ($checks as $c):
        if ($c['ok']) $cls = 'req-ok';
        elseif (!empty($c['warn'])) $cls = 'req-warn';
        else $cls = 'req-fail';
        $icon = $c['ok'] ? 'bi-check-circle-fill' : (!empty($c['warn']) ? 'bi-exclamation-triangle-fill' : 'bi-x-circle-fill');
      ?>
      <div class="req-item <?= $cls ?>">
        <i class="bi <?= $icon ?>"></i>
        <span><strong><?= h($c['name']) ?></strong> - <?= h($c['detail']) ?></span>
      </div>
      <?php endforeach; ?>

      <?php if (!$allOk): ?>
      <div class="alert alert-danger mt-3">
        <i class="bi bi-x-octagon me-2"></i>
        Algunos requisitos <strong>obligatorios</strong> no se cumplen. Corrígelos antes de continuar.
      </div>
      <?php endif; ?>

      <div class="d-flex justify-content-end mt-4">
        <form method="POST">
          <input type="hidden" name="step" value="1">
          <button type="submit" class="btn btn-brand" <?= !$allOk ? 'disabled' : '' ?>>
            Comenzar instalación <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </form>
      </div>

      <!-- PASO 2: Base de datos -->
      <?php elseif ($step === 2): ?>
      <div class="step-title"><i class="bi bi-database me-2 text-primary"></i>Configuración de Base de Datos</div>
      <p class="step-subtitle">Introduce los datos de conexión a MySQL/MariaDB. La base de datos se creará automáticamente si no existe.</p>

      <form method="POST" novalidate>
        <input type="hidden" name="step" value="2">
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label">Servidor MySQL <span class="text-danger">*</span></label>
            <input type="text" name="db_host" class="form-control" value="<?= val('db_host','localhost') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label">Puerto</label>
            <input type="number" name="db_port" class="form-control" value="<?= val('db_port','3306') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Nombre de la base de datos <span class="text-danger">*</span></label>
            <input type="text" name="db_name" class="form-control" value="<?= val('db_name','control_horario') ?>" required>
            <div class="form-text">Se creará si no existe. Solo letras, números y guiones bajos.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Usuario MySQL <span class="text-danger">*</span></label>
            <input type="text" name="db_user" class="form-control" value="<?= val('db_user','root') ?>" required autocomplete="username">
          </div>
          <div class="col-md-6">
            <label class="form-label">Contraseña MySQL</label>
            <div class="input-group">
              <input type="password" name="db_pass" class="form-control" id="dbPass" value="" autocomplete="current-password">
              <button type="button" class="btn btn-outline-secondary" onclick="togglePass('dbPass',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
          <a href="?step=1" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Atrás
          </a>
          <button type="submit" class="btn btn-brand">
            Probar conexión y continuar <i class="bi bi-arrow-right ms-1"></i>
          </button>
        </div>
      </form>

      <!-- PASO 3: Configuración de la aplicación -->
      <?php elseif ($step === 3): ?>
      <div class="step-title"><i class="bi bi-gear me-2 text-primary"></i>Configuración de la Aplicación</div>
      <p class="step-subtitle">Datos de tu empresa y URL donde estará disponible el sistema.</p>

      <form method="POST" novalidate>
        <input type="hidden" name="step" value="3">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label">Nombre de la aplicación</label>
            <input type="text" name="app_name" class="form-control" value="<?= val('app_name','Control Horario Digital') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">URL de la aplicación <span class="text-danger">*</span></label>
            <?php
            // Auto-detectar URL base:
            // - Si se accede desde la raíz (root .htaccess rewrite), SCRIPT_NAME=/install/index.php
            //   → dirname×2 = '' → URL = https://domain.com  (sin /public)
            // - Si se accede directamente desde /public/, SCRIPT_NAME=/public/install/index.php
            //   → dirname×2 = /public → URL = https://domain.com/public
            $_autoProto   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $_autoHost    = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $_autoAppPath = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'] ?? '/public/install/index.php')), '/');
            $_autoUrl     = "{$_autoProto}://{$_autoHost}{$_autoAppPath}";
            ?>
            <input type="url" name="app_url" class="form-control" value="<?= val('app_url', $_autoUrl) ?>" required>
            <div class="form-text">URL raíz de la aplicación seguido de /public, sin barra final. Ejemplo: <code>https://tudominio.com/public</code></div>
          </div>
          <div class="col-12">
            <label class="form-label">Nombre de la empresa <span class="text-danger">*</span></label>
            <input type="text" name="company_name" class="form-control" value="<?= val('company_name') ?>" required>
          </div>
          <div class="col-md-8">
            <label class="form-label">Dirección de la empresa</label>
            <input type="text" name="company_address" class="form-control" value="<?= val('company_address') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">CIF / NIF</label>
            <input type="text" name="company_cif" class="form-control" value="<?= val('company_cif') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Zona horaria</label>
            <select name="timezone" class="form-select">
              <?php foreach (['Europe/Madrid','Europe/Lisbon','Atlantic/Canary','UTC'] as $tz): ?>
              <option value="<?= $tz ?>" <?= val('timezone','Europe/Madrid') === $tz ? 'selected' : '' ?>><?= $tz ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
          <a href="?step=2" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Atrás</a>
          <button type="submit" class="btn btn-brand">Continuar <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
      </form>

      <!-- PASO 4: Cuenta de administrador -->
      <?php elseif ($step === 4): ?>
      <div class="step-title"><i class="bi bi-person-fill-gear me-2 text-primary"></i>Cuenta de Administrador</div>
      <p class="step-subtitle">Crea el usuario administrador principal. Podrás añadir más usuarios desde el panel.</p>

      <form method="POST" novalidate id="adminForm">
        <input type="hidden" name="step" value="4">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Nombre <span class="text-danger">*</span></label>
            <input type="text" name="admin_nombre" class="form-control" value="<?= val('admin_nombre') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Apellidos</label>
            <input type="text" name="admin_apellidos" class="form-control" value="<?= val('admin_apellidos') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Email de administrador <span class="text-danger">*</span></label>
            <input type="email" name="admin_email" class="form-control" value="<?= val('admin_email') ?>" required autocomplete="email">
          </div>
          <div class="col-md-6">
            <label class="form-label">Contraseña <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="admin_password" class="form-control" id="adminPass" required minlength="8" autocomplete="new-password">
              <button type="button" class="btn btn-outline-secondary" onclick="togglePass('adminPass',this)"><i class="bi bi-eye"></i></button>
            </div>
            <div class="form-text">Mínimo 8 caracteres.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Repetir contraseña <span class="text-danger">*</span></label>
            <div class="input-group">
              <input type="password" name="admin_password2" class="form-control" id="adminPass2" required autocomplete="new-password">
              <button type="button" class="btn btn-outline-secondary" onclick="togglePass('adminPass2',this)"><i class="bi bi-eye"></i></button>
            </div>
          </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
          <a href="?step=3" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Atrás</a>
          <button type="submit" class="btn btn-brand">Continuar <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
      </form>

      <!-- PASO 5: Configuración de email -->
      <?php elseif ($step === 5): ?>
      <div class="step-title"><i class="bi bi-envelope me-2 text-primary"></i>Configuración de Email (SMTP)</div>
      <p class="step-subtitle">Opcional. Permite enviar notificaciones y recuperación de contraseñas. Puedes configurarlo después desde el panel de administración.</p>

      <form method="POST">
        <input type="hidden" name="step" value="5">

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="smtp_skip" id="smtpSkip" onchange="toggleSmtp(this)" <?= val('smtp_skip') ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="smtpSkip">Omitir configuración de email por ahora</label>
          </div>
        </div>

        <div id="smtpFields" <?= val('smtp_skip') ? 'style="display:none"' : '' ?>>
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Servidor SMTP</label>
              <input type="text" name="smtp_host" class="form-control" value="<?= val('smtp_host') ?>" placeholder="smtp.tuempresa.com">
            </div>
            <div class="col-md-2">
              <label class="form-label">Puerto</label>
              <select name="smtp_port" class="form-select">
                <option value="587" <?= val('smtp_port','587')==='587'?'selected':'' ?>>587 (TLS)</option>
                <option value="465" <?= val('smtp_port','587')==='465'?'selected':'' ?>>465 (SSL)</option>
                <option value="25"  <?= val('smtp_port','587')==='25' ?'selected':'' ?>>25</option>
              </select>
            </div>
            <div class="col-md-2">
              <label class="form-label">Cifrado</label>
              <select name="smtp_secure" class="form-select">
                <option value="tls" <?= val('smtp_secure','tls')==='tls'?'selected':'' ?>>TLS</option>
                <option value="ssl" <?= val('smtp_secure','tls')==='ssl'?'selected':'' ?>>SSL</option>
                <option value="none" <?= val('smtp_secure','tls')==='none'?'selected':'' ?>>Ninguno</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Usuario SMTP</label>
              <input type="email" name="smtp_user" class="form-control" value="<?= val('smtp_user') ?>" autocomplete="email">
            </div>
            <div class="col-md-6">
              <label class="form-label">Contraseña SMTP</label>
              <div class="input-group">
                <input type="password" name="smtp_pass" class="form-control" id="smtpPass" autocomplete="current-password">
                <button type="button" class="btn btn-outline-secondary" onclick="togglePass('smtpPass',this)"><i class="bi bi-eye"></i></button>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email remitente</label>
              <input type="email" name="smtp_from" class="form-control" value="<?= val('smtp_from') ?>" placeholder="noreply@tuempresa.com">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nombre remitente</label>
              <input type="text" name="smtp_from_name" class="form-control" value="<?= val('smtp_from_name') ?>">
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
          <a href="?step=4" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Atrás</a>
          <button type="submit" class="btn btn-brand">Continuar <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
      </form>

      <!-- PASO 6: Geolocalización -->
      <?php elseif ($step === 6): ?>
      <div class="step-title"><i class="bi bi-geo-alt me-2 text-primary"></i>Ubicación de la Oficina</div>
      <p class="step-subtitle">Los fichajes se validarán por geolocalización GPS. Introduce las coordenadas de tu oficina y los parámetros de jornada.</p>

      <div class="geo-map-hint mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Para obtener coordenadas exactas: abre <strong>Google Maps</strong>, haz clic derecho en tu oficina y copia las coordenadas que aparecen. O pulsa el botón
        <strong>"Obtener mi ubicación actual"</strong>.
      </div>

      <form method="POST">
        <input type="hidden" name="step" value="6">
        <div class="row g-3">
          <div class="col-12">
            <button type="button" class="btn btn-outline-primary btn-sm" onclick="getMyLocation()">
              <i class="bi bi-crosshair me-1"></i>Obtener mi ubicación actual
            </button>
            <span id="geoStatus" class="ms-2 text-muted" style="font-size:.82rem"></span>
          </div>
          <div class="col-md-6">
            <label class="form-label">Latitud de la oficina</label>
            <input type="text" name="default_lat" id="inpLat" class="form-control" value="<?= val('default_lat') ?>" placeholder="ej: 40.416775">
          </div>
          <div class="col-md-6">
            <label class="form-label">Longitud de la oficina</label>
            <input type="text" name="default_lon" id="inpLon" class="form-control" value="<?= val('default_lon') ?>" placeholder="ej: -3.703790">
          </div>
          <div class="col-md-6">
            <label class="form-label">Radio máximo para fichar (metros)</label>
            <input type="number" name="max_distance" class="form-control" value="<?= val('max_distance','30') ?>" min="5" max="500">
            <div class="form-text">Los empleados deben estar a menos de esta distancia para poder fichar.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label">Precisión GPS mínima (metros)</label>
            <input type="number" name="min_accuracy" class="form-control" value="<?= val('min_accuracy','10') ?>" min="5" max="100">
          </div>
          <div class="col-md-4">
            <label class="form-label">Inicio de jornada</label>
            <input type="time" name="work_start" class="form-control" value="<?= val('work_start','08:00') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Fin de jornada</label>
            <input type="time" name="work_end" class="form-control" value="<?= val('work_end','18:00') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label">Días de vacaciones / año</label>
            <input type="number" name="vac_days" class="form-control" value="<?= val('vac_days','22') ?>" min="1" max="60">
          </div>
        </div>
        <div class="d-flex justify-content-between mt-4">
          <a href="?step=5" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Atrás</a>
          <button type="submit" class="btn btn-brand">Continuar <i class="bi bi-arrow-right ms-1"></i></button>
        </div>
      </form>

      <!-- PASO 7: Resumen + Instalar -->
      <?php elseif ($step === 7):
        $d = $_SESSION['install']['data'] ?? [];
      ?>
      <div class="step-title"><i class="bi bi-rocket me-2 text-primary"></i>Resumen y Confirmación</div>
      <p class="step-subtitle">Revisa los datos antes de iniciar la instalación. El proceso creará la base de datos, las tablas y el archivo de configuración.</p>

      <div class="row g-2 mb-4">
        <?php
        $summary = [
          ['bi-database',         'Base de datos',   ($d['db_host'] ?? '') . ' / ' . ($d['db_name'] ?? '')],
          ['bi-building',         'Empresa',         $d['company_name'] ?? ''],
          ['bi-link-45deg',       'URL App',         $d['app_url'] ?? ''],
          ['bi-person-fill-gear', 'Administrador',   ($d['admin_nombre'] ?? '') . ' <' . ($d['admin_email'] ?? '') . '>'],
          ['bi-envelope',         'Email',           ($d['smtp_skip'] ?? false) ? 'No configurado' : ($d['smtp_host'] ?? 'No configurado')],
          ['bi-geo-alt',          'Coordenadas',     ($d['default_lat'] ?? '-') . ', ' . ($d['default_lon'] ?? '-')],
        ];
        foreach ($summary as [$icon, $label, $val]):
        ?>
        <div class="col-md-6">
          <div class="d-flex align-items-start gap-2 p-2 bg-light rounded">
            <i class="bi <?= $icon ?> text-primary mt-1"></i>
            <div>
              <div style="font-size:.75rem;color:#64748b;font-weight:600"><?= h($label) ?></div>
              <div style="font-size:.875rem;word-break:break-all"><?= h($val) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="alert alert-info">
        <i class="bi bi-lock-fill me-2"></i>
        <strong>Inmutabilidad legal:</strong> Se generarán claves criptográficas únicas (HMAC-SHA256) para garantizar que los registros de fichajes no puedan ser alterados, conforme al RD-Ley 8/2019.
      </div>

      <form method="POST">
        <input type="hidden" name="step" value="7">
        <div class="d-flex justify-content-between mt-3">
          <a href="?step=6" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Atrás</a>
          <button type="submit" class="btn btn-brand btn-lg" id="btnInstall">
            <i class="bi bi-rocket me-2"></i>Instalar ahora
          </button>
        </div>
      </form>

      <!-- PASO 8: ¡Instalación completada! -->
      <?php elseif ($step === 8):
        $appUrl     = $_SESSION['install']['done_app_url'] ?? '';
        $adminEmail = $_SESSION['install']['done_admin_mail'] ?? '';
        if (empty($appUrl) && file_exists(LOCK_FILE)) $appUrl = trim(file_get_contents(LOCK_FILE));
        if (empty($appUrl)) $appUrl = '#';
      ?>
      <div class="success-card">
        <div class="success-icon"><i class="bi bi-check-circle-fill"></i></div>
        <h2 class="fw-bold mt-3" style="color:#166534">¡Instalación completada!</h2>
        <p class="text-muted mt-2 mb-1">El sistema de Control Horario Digital está listo para usarse.</p>
        <p class="text-muted mb-4" style="font-size:.875rem">
          Accede con: <strong><?= h($adminEmail) ?></strong>
        </p>

        <div class="d-grid gap-2 col-8 mx-auto mb-4">
          <a href="<?= h($appUrl) ?>" class="btn btn-brand btn-lg">
            <i class="bi bi-arrow-right-circle me-2"></i>Ir al sistema
          </a>
          <a href="<?= h($appUrl) ?>/admin" class="btn btn-outline-brand">
            <i class="bi bi-speedometer2 me-2"></i>Panel de administración
          </a>
        </div>

        <div class="alert alert-warning text-start mt-2" style="font-size:.82rem">
          <i class="bi bi-shield-exclamation me-2"></i>
          <strong>Importante por seguridad:</strong> Elimina o protege la carpeta <code>install/</code>
          en tu servidor una vez confirmado que todo funciona correctamente.
          El archivo <code>install/.lock</code> evita que se pueda reinstalar accidentalmente.
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /installer-body -->
  </div><!-- /installer-card -->

  <p class="footer-note">
    Control Horario Digital &copy; <?= date('Y') ?> &mdash;
    Conforme a la Ley de Registro de Jornada (RD-Ley 8/2019) &mdash;
    PHP <?= PHP_VERSION ?>
  </p>

</div><!-- /installer-wrap -->

<script>
function togglePass(id, btn) {
  const inp = document.getElementById(id);
  if (inp.type === 'password') {
    inp.type = 'text';
    btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
  } else {
    inp.type = 'password';
    btn.innerHTML = '<i class="bi bi-eye"></i>';
  }
}

function toggleSmtp(cb) {
  document.getElementById('smtpFields').style.display = cb.checked ? 'none' : '';
}

function getMyLocation() {
  const st = document.getElementById('geoStatus');
  if (!navigator.geolocation) { st.textContent = 'Geolocalización no soportada.'; return; }
  st.textContent = 'Obteniendo ubicación...';
  navigator.geolocation.getCurrentPosition(
    pos => {
      document.getElementById('inpLat').value = pos.coords.latitude.toFixed(8);
      document.getElementById('inpLon').value = pos.coords.longitude.toFixed(8);
      st.textContent = '✓ Ubicación obtenida (precisión: ' + Math.round(pos.coords.accuracy) + 'm)';
      st.style.color = '#16a34a';
    },
    err => { st.textContent = 'Error: ' + err.message; st.style.color = '#dc2626'; }
  );
}

// Prevent double-submit on install step
document.addEventListener('DOMContentLoaded', function() {
  const btn = document.getElementById('btnInstall');
  if (btn) {
    btn.closest('form').addEventListener('submit', function() {
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Instalando...';
    });
  }
});
</script>
</body>
</html>