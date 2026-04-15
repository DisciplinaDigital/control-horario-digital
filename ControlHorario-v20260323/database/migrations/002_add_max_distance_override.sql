-- ================================================================
-- Migración 002: Radio GPS personalizado por usuario
-- Fecha: 2026-03-22
-- Ejecutar en MySQL/MariaDB una sola vez
-- ================================================================

ALTER TABLE usuarios
    ADD COLUMN max_distance_override INT NULL DEFAULT NULL
        COMMENT '0=sin límite, NULL=usar global, >0=metros específicos'
    AFTER dias_vacaciones_anuales;

-- Verificar que se ha añadido correctamente:
-- SHOW COLUMNS FROM usuarios LIKE 'max_distance_override';
