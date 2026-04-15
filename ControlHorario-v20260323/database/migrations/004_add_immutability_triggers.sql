DROP TRIGGER IF EXISTS `trg_fichajes_no_update`;
DROP TRIGGER IF EXISTS `trg_fichajes_no_delete`;
DROP TRIGGER IF EXISTS `trg_audit_log_no_update`;
DROP TRIGGER IF EXISTS `trg_audit_log_no_delete`;

DELIMITER $$
CREATE TRIGGER `trg_fichajes_no_update`
BEFORE UPDATE ON `fichajes`
FOR EACH ROW
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los fichajes son inmutables y no pueden modificarse.'
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_fichajes_no_delete`
BEFORE DELETE ON `fichajes`
FOR EACH ROW
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'Los fichajes son inmutables y no pueden eliminarse.'
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_audit_log_no_update`
BEFORE UPDATE ON `audit_log`
FOR EACH ROW
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El log de auditoría es inmutable y no puede modificarse.'
$$
DELIMITER ;

DELIMITER $$
CREATE TRIGGER `trg_audit_log_no_delete`
BEFORE DELETE ON `audit_log`
FOR EACH ROW
SIGNAL SQLSTATE '45000'
SET MESSAGE_TEXT = 'El log de auditoría es inmutable y no puede eliminarse.'
$$
DELIMITER ;
