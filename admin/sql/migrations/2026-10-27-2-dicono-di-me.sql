-- ---------------------------------------------------------------------
-- Dicono di me (27/10/2026):
--  - reviews.consenso: «Consenso scritto ricevuto», senza non si pubblica
--    (lo controlla il back office; il sito mostra solo quelle con consenso);
--  - reviews.story_id: l'evento collegato, facoltativo;
--  - press.tipo: articolo, servizio TV, podcast, altro;
--  - foto della chiusura: la 1, navata con la gipsofila.
-- Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE `reviews`
  ADD COLUMN IF NOT EXISTS `consenso` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`,
  ADD COLUMN IF NOT EXISTS `story_id` INT UNSIGNED DEFAULT NULL AFTER `media_id`;

ALTER TABLE `press`
  ADD COLUMN IF NOT EXISTS `tipo` ENUM('articolo','tv','podcast','altro') NOT NULL DEFAULT 'articolo' AFTER `outlet`;

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'dicono.photos.cta', 'dicono', 'Dicono di me — foto della chiusura', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/navata-chiesa-gipsofila.jpg'), NULL, 1
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
