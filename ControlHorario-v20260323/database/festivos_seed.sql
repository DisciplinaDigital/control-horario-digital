-- =============================================
-- Festivos Nacionales de España 2024-2030
-- Control Horario Digital
-- =============================================

USE `control_horario`;

-- =============================================
-- 2024
-- =============================================
INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
('2024-01-01', 'Año Nuevo', 'nacional'),
('2024-01-06', 'Epifanía del Señor (Reyes Magos)', 'nacional'),
('2024-03-28', 'Jueves Santo', 'nacional'),
('2024-03-29', 'Viernes Santo', 'nacional'),
('2024-05-01', 'Día del Trabajador', 'nacional'),
('2024-08-15', 'Asunción de la Virgen', 'nacional'),
('2024-10-12', 'Día de la Hispanidad', 'nacional'),
('2024-11-01', 'Todos los Santos', 'nacional'),
('2024-12-06', 'Día de la Constitución Española', 'nacional'),
('2024-12-08', 'Inmaculada Concepción', 'nacional'),
('2024-12-25', 'Natividad del Señor (Navidad)', 'nacional')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- =============================================
-- 2025
-- =============================================
INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
('2025-01-01', 'Año Nuevo', 'nacional'),
('2025-01-06', 'Epifanía del Señor (Reyes Magos)', 'nacional'),
('2025-04-17', 'Jueves Santo', 'nacional'),
('2025-04-18', 'Viernes Santo', 'nacional'),
('2025-05-01', 'Día del Trabajador', 'nacional'),
('2025-08-15', 'Asunción de la Virgen', 'nacional'),
('2025-10-12', 'Día de la Hispanidad', 'nacional'),
('2025-11-01', 'Todos los Santos', 'nacional'),
('2025-12-06', 'Día de la Constitución Española', 'nacional'),
('2025-12-08', 'Inmaculada Concepción', 'nacional'),
('2025-12-25', 'Natividad del Señor (Navidad)', 'nacional')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- =============================================
-- 2026
-- =============================================
INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
('2026-01-01', 'Año Nuevo', 'nacional'),
('2026-01-06', 'Epifanía del Señor (Reyes Magos)', 'nacional'),
('2026-04-02', 'Jueves Santo', 'nacional'),
('2026-04-03', 'Viernes Santo', 'nacional'),
('2026-05-01', 'Día del Trabajador', 'nacional'),
('2026-08-15', 'Asunción de la Virgen', 'nacional'),
('2026-10-12', 'Día de la Hispanidad', 'nacional'),
('2026-11-01', 'Todos los Santos', 'nacional'),
('2026-12-06', 'Día de la Constitución Española', 'nacional'),
('2026-12-08', 'Inmaculada Concepción', 'nacional'),
('2026-12-25', 'Natividad del Señor (Navidad)', 'nacional')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- =============================================
-- 2027
-- =============================================
INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
('2027-01-01', 'Año Nuevo', 'nacional'),
('2027-01-06', 'Epifanía del Señor (Reyes Magos)', 'nacional'),
('2027-03-25', 'Jueves Santo', 'nacional'),
('2027-03-26', 'Viernes Santo', 'nacional'),
('2027-05-01', 'Día del Trabajador', 'nacional'),
('2027-08-15', 'Asunción de la Virgen', 'nacional'),
('2027-10-12', 'Día de la Hispanidad', 'nacional'),
('2027-11-01', 'Todos los Santos', 'nacional'),
('2027-12-06', 'Día de la Constitución Española', 'nacional'),
('2027-12-08', 'Inmaculada Concepción', 'nacional'),
('2027-12-25', 'Natividad del Señor (Navidad)', 'nacional')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- =============================================
-- 2028
-- =============================================
INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
('2028-01-01', 'Año Nuevo', 'nacional'),
('2028-01-06', 'Epifanía del Señor (Reyes Magos)', 'nacional'),
('2028-04-13', 'Jueves Santo', 'nacional'),
('2028-04-14', 'Viernes Santo', 'nacional'),
('2028-05-01', 'Día del Trabajador', 'nacional'),
('2028-08-15', 'Asunción de la Virgen', 'nacional'),
('2028-10-12', 'Día de la Hispanidad', 'nacional'),
('2028-11-01', 'Todos los Santos', 'nacional'),
('2028-12-06', 'Día de la Constitución Española', 'nacional'),
('2028-12-08', 'Inmaculada Concepción', 'nacional'),
('2028-12-25', 'Natividad del Señor (Navidad)', 'nacional')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- =============================================
-- 2029
-- =============================================
INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
('2029-01-01', 'Año Nuevo', 'nacional'),
('2029-01-06', 'Epifanía del Señor (Reyes Magos)', 'nacional'),
('2029-03-29', 'Jueves Santo', 'nacional'),
('2029-03-30', 'Viernes Santo', 'nacional'),
('2029-05-01', 'Día del Trabajador', 'nacional'),
('2029-08-15', 'Asunción de la Virgen', 'nacional'),
('2029-10-12', 'Día de la Hispanidad', 'nacional'),
('2029-11-01', 'Todos los Santos', 'nacional'),
('2029-12-06', 'Día de la Constitución Española', 'nacional'),
('2029-12-08', 'Inmaculada Concepción', 'nacional'),
('2029-12-25', 'Natividad del Señor (Navidad)', 'nacional')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

-- =============================================
-- 2030
-- =============================================
INSERT INTO `festivos` (`fecha`, `descripcion`, `tipo`) VALUES
('2030-01-01', 'Año Nuevo', 'nacional'),
('2030-01-06', 'Epifanía del Señor (Reyes Magos)', 'nacional'),
('2030-04-18', 'Jueves Santo', 'nacional'),
('2030-04-19', 'Viernes Santo', 'nacional'),
('2030-05-01', 'Día del Trabajador', 'nacional'),
('2030-08-15', 'Asunción de la Virgen', 'nacional'),
('2030-10-12', 'Día de la Hispanidad', 'nacional'),
('2030-11-01', 'Todos los Santos', 'nacional'),
('2030-12-06', 'Día de la Constitución Española', 'nacional'),
('2030-12-08', 'Inmaculada Concepción', 'nacional'),
('2030-12-25', 'Natividad del Señor (Navidad)', 'nacional')
ON DUPLICATE KEY UPDATE `descripcion` = VALUES(`descripcion`);

SELECT CONCAT('Total festivos importados: ', COUNT(*), ' (2024-2030)') AS resultado FROM `festivos`;
