-- ================================================================
-- Migración 003: Cambio de contraseña obligatorio tras reset admin
-- Fecha: 2026-03-22
-- Ejecutar en MySQL/MariaDB una sola vez
-- ================================================================

ALTER TABLE usuarios
    ADD COLUMN must_change_password TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = el admin ha reseteado la contraseña, debe cambiarla en el próximo login'
    AFTER activo;

-- Verificar:
-- SHOW COLUMNS FROM usuarios LIKE 'must_change_password';
