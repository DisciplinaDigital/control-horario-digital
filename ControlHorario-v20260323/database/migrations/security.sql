-- =============================================
-- Migración de Seguridad
-- Ejecutar UNA SOLA VEZ en el servidor
-- =============================================

-- ── 1. Intentos de login (brute force protection) ────────────
CREATE TABLE IF NOT EXISTS `login_intentos` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `ip` VARCHAR(45) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `exitoso` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_ip_created` (`ip`, `created_at`),
  INDEX `idx_email_created` (`email`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Registro de intentos de login para protección anti fuerza bruta';

-- ── 2. Tokens de recuperación de contraseña ─────────────────
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
COMMENT='Tokens de un solo uso para recuperación de contraseña (expiración 1h)';
