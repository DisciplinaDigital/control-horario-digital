-- =============================================
-- Control Horario Digital - Database Schema
-- Conforme a la Ley de Registro de Jornada (RD-Ley 8/2019)
-- =============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+01:00";
SET NAMES utf8mb4;

-- Create database if not exists
CREATE DATABASE IF NOT EXISTS `control_horario`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `control_horario`;

-- =============================================
-- USUARIOS
-- =============================================
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
  `role` ENUM('admin','usuario') DEFAULT 'usuario',
  `activo` TINYINT(1) DEFAULT 1,
  `must_change_password` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = debe cambiar contraseÃ±a en prÃ³ximo login',
  `telefono` VARCHAR(20),
  `departamento` VARCHAR(100),
  `dias_vacaciones_anuales` INT DEFAULT 22,
  `max_distance_override` INT NULL DEFAULT NULL COMMENT '0=sin lÃ­mite, NULL=usar global, >0=metros especÃ­ficos',
  `terminos_aceptados` TINYINT(1) DEFAULT 0,
  `fecha_aceptacion_terminos` DATETIME NULL,
  `ultimo_acceso` DATETIME NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME NULL COMMENT 'Soft delete - datos conservados 4 aÃ±os por requisito legal',
  INDEX `idx_email` (`email`),
  INDEX `idx_role` (`role`),
  INDEX `idx_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- FICHAJES (IMMUTABLE - no UPDATE/DELETE)
-- Requisito legal: registros inmutables con firma criptogrÃ¡fica
-- =============================================
CREATE TABLE IF NOT EXISTS `fichajes` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `tipo` ENUM('entrada','salida') NOT NULL,
  `fecha_hora` DATETIME NOT NULL,
  `latitud` DECIMAL(10,8) NULL,
  `longitud` DECIMAL(11,8) NULL,
  `precision_ubicacion` DECIMAL(8,2) NULL COMMENT 'PrecisiÃ³n GPS en metros',
  `dispositivo` VARCHAR(255) NULL COMMENT 'User agent del dispositivo',
  `ip_address` VARCHAR(45) NULL,
  `metodo_registro` ENUM('web','movil','terminal','manual_admin') DEFAULT 'web',
  -- Integridad criptogrÃ¡fica (Blockchain-like hash chain)
  `hash_integridad` VARCHAR(64) NOT NULL COMMENT 'HMAC-SHA256 del registro',
  `hash_anterior` VARCHAR(64) NULL COMMENT 'Hash del registro anterior (cadena)',
  `firmado_en` DATETIME NOT NULL COMMENT 'Momento de creaciÃ³n de la firma',
  -- Control de correcciones (admin)
  `es_correccion` TINYINT(1) DEFAULT 0 COMMENT '1 si fue aÃ±adido manualmente por admin',
  `correccion_justificacion` TEXT NULL COMMENT 'JustificaciÃ³n legal de la correcciÃ³n',
  `incidencia_id` INT UNSIGNED NULL COMMENT 'Incidencia que motivÃ³ la correcciÃ³n',
  -- Metadata
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_usuario_fecha` (`usuario_id`, `fecha_hora`),
  INDEX `idx_fecha` (`fecha_hora`),
  INDEX `idx_tipo` (`tipo`),
  CONSTRAINT `fk_fichajes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registros inmutables. PROHIBIDO UPDATE/DELETE por Ley de Registro de Jornada';

-- =============================================
-- INCIDENCIAS
-- =============================================
CREATE TABLE IF NOT EXISTS `incidencias` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `numero_incidencia` VARCHAR(20) NOT NULL UNIQUE COMMENT 'Formato: INC-YYYY-NNNN',
  `usuario_id` INT UNSIGNED NOT NULL,
  `admin_id` INT UNSIGNED NULL,
  `tipo` ENUM('olvido_entrada','olvido_salida','error_ubicacion','otro') DEFAULT 'otro',
  `fecha_fichaje` DATE NOT NULL COMMENT 'Fecha del fichaje que se quiere corregir',
  `hora_solicitada` TIME NULL COMMENT 'Hora que deberÃ­a haberse fichado',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- VACACIONES
-- =============================================
CREATE TABLE IF NOT EXISTS `vacaciones` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- DIAS VACACIONES (tracking por aÃ±o)
-- =============================================
CREATE TABLE IF NOT EXISTS `dias_vacaciones` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NOT NULL,
  `anio` YEAR NOT NULL,
  `dias_totales` INT DEFAULT 22,
  `dias_usados` INT DEFAULT 0,
  `dias_pendientes` INT DEFAULT 0,
  UNIQUE KEY `unique_user_year` (`usuario_id`, `anio`),
  CONSTRAINT `fk_diasvac_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- FESTIVOS
-- =============================================
CREATE TABLE IF NOT EXISTS `festivos` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `fecha` DATE NOT NULL UNIQUE,
  `descripcion` VARCHAR(200) NOT NULL,
  `tipo` ENUM('nacional','autonomico','local','empresa') DEFAULT 'nacional',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- NOTIFICACIONES
-- =============================================
CREATE TABLE IF NOT EXISTS `notificaciones` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- AUDIT LOG (IMMUTABLE - INSERT ONLY)
-- Requisito legal: registro de auditorÃ­a inmutable
-- =============================================
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT UNSIGNED NULL COMMENT 'NULL para acciones del sistema',
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
COMMENT='Log de auditorÃ­a inmutable. PROHIBIDO UPDATE/DELETE por requisito legal';


DELIMITER $$
CREATE TRIGGER `trg_fichajes_no_delete` BEFORE DELETE ON `fichajes` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los fichajes son inmutables y no pueden eliminarse.'
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_fichajes_no_update` BEFORE UPDATE ON `fichajes` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Los fichajes son inmutables y no pueden modificarse.'
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_log_no_delete` BEFORE DELETE ON `audit_log` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El log de auditoría es inmutable y no puede eliminarse.'
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_audit_log_no_update` BEFORE UPDATE ON `audit_log` FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'El log de auditoría es inmutable y no puede modificarse.'
$$
DELIMITER ;
-- =============================================
-- CONFIGURACION
-- =============================================
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `clave` VARCHAR(100) NOT NULL UNIQUE,
  `valor` TEXT NULL,
  `tipo` ENUM('text','color','file','boolean','number') DEFAULT 'text',
  `descripcion` VARCHAR(255) NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- SESIONES
-- =============================================
CREATE TABLE IF NOT EXISTS `sesiones` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- LOGIN INTENTOS (brute force protection)
-- =============================================
CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `exitoso` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ip_created` (`ip`, `created_at`),
  INDEX `idx_email_created` (`email`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de intentos de login para protecciÃ³n anti fuerza bruta';

-- =============================================
-- PASSWORD RESETS (recuperaciÃ³n de contraseÃ±a)
-- =============================================
CREATE TABLE IF NOT EXISTS `password_resets` (
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
COMMENT='Tokens de un solo uso para recuperaciÃ³n de contraseÃ±a (expiraciÃ³n 1h)';

-- =============================================
-- DATOS INICIALES
-- =============================================

-- Admin user (password: password)
-- ContraseÃ±a hash generada con password_hash('password', PASSWORD_BCRYPT)
INSERT INTO `usuarios` (`nombre`, `apellidos`, `email`, `password_hash`, `role`, `activo`, `dias_vacaciones_anuales`)
VALUES ('Admin', 'Sistema', 'admin@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 1, 22)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Sample employee
INSERT INTO `usuarios` (`nombre`, `apellidos`, `email`, `password_hash`, `role`, `activo`, `departamento`, `dias_vacaciones_anuales`)
VALUES ('Juan', 'GarcÃ­a PÃ©rez', 'empleado@empresa.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'usuario', 1, 'Operaciones', 22)
ON DUPLICATE KEY UPDATE `nombre` = VALUES(`nombre`);

-- Initialize vacation days for current year
INSERT INTO `dias_vacaciones` (`usuario_id`, `anio`, `dias_totales`, `dias_usados`, `dias_pendientes`)
SELECT id, YEAR(NOW()), `dias_vacaciones_anuales`, 0, `dias_vacaciones_anuales`
FROM `usuarios`
WHERE `deleted_at` IS NULL
ON DUPLICATE KEY UPDATE `dias_totales` = VALUES(`dias_totales`);

-- Default configuration
INSERT INTO `configuracion` (`clave`, `valor`, `tipo`, `descripcion`) VALUES
('company_name',         'Control Horario Digital', 'text',   'Nombre de la empresa'),
('company_address',      '',                         'text',   'DirecciÃ³n de la empresa'),
('company_cif',          '',                         'text',   'CIF/NIF de la empresa'),
('logo',                 NULL,                       'file',   'Nombre del archivo de logo'),
('color_primary',        '#2563eb',                  'color',  'Color principal'),
('color_secondary',      '#64748b',                  'color',  'Color secundario'),
('color_accent',         '#0ea5e9',                  'color',  'Color de acento'),
('color_success',        '#16a34a',                  'color',  'Color de Ã©xito'),
('color_warning',        '#d97706',                  'color',  'Color de advertencia'),
('color_danger',         '#dc2626',                  'color',  'Color de peligro'),
('color_bg',             '#f8fafc',                  'color',  'Color de fondo'),
('color_text',           '#1e293b',                  'color',  'Color de texto'),
('default_lat',          '36.62906',                 'number', 'Latitud de la oficina'),
('default_lon',          '-4.82644',                 'number', 'Longitud de la oficina'),
('max_distance',         '30',                       'number', 'Radio mÃ¡ximo para fichar (metros)'),
('min_accuracy',         '10',                       'number', 'PrecisiÃ³n GPS mÃ­nima requerida (metros)'),
('default_vacation_days','22',                       'number', 'DÃ­as de vacaciones por defecto'),
('work_start',           '07:00',                    'text',   'Hora de inicio de jornada'),
('work_end',             '19:30',                    'text',   'Hora de fin de jornada')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);
