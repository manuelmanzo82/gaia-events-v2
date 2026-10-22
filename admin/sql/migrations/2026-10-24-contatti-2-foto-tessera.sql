-- ---------------------------------------------------------------------
-- Contatti: foto della tessera «Conosciamoci» (card fotografica).
-- 102 tavolo-fiori-campo-candele: nitida (1112 px CSS), senza persone,
-- non usata altrove nella pagina Contatti. Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'contatti.photos.tessera', 'contatti', 'Foto — tessera Conosciamoci', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/tavolo-fiori-campo-candele.png'), NULL, 4
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
