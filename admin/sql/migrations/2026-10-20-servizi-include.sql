-- ---------------------------------------------------------------------
-- Servizi: che cosa include ciascuno (giro 10).
--
-- include_slugs: gli slug degli altri servizi compresi in questo,
-- separati da virgola. Nel preventivatore, scegliendo un servizio, quelli
-- inclusi si segnano da soli come «Incluso». Valore iniziale: il wedding
-- planning completo include coordinamento del giorno e design & styling
-- (da confermare con Gaia, DA-CHIEDERE-A-GAIA n. 10).
--
-- Sottotitoli italiani: nel database c'erano quelli inglesi anche in
-- subtitle_it. Si correggono solo se sono ancora quelli.
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE `services`
  ADD COLUMN IF NOT EXISTS `include_slugs` VARCHAR(255) DEFAULT NULL AFTER `features_en`;

UPDATE `services` SET `include_slugs` = 'coordinamento,design' WHERE `slug` = 'planning' AND `include_slugs` IS NULL;

UPDATE `services` SET `subtitle_it` = 'Servizio completo' WHERE `slug` = 'planning' AND `subtitle_it` = 'Full service';
UPDATE `services` SET `subtitle_it` = 'Il giorno dell''evento' WHERE `slug` = 'coordinamento' AND `subtitle_it` = 'Day-of';
UPDATE `services` SET `subtitle_it` = 'Creatività' WHERE `slug` = 'design' AND `subtitle_it` = 'Creative';
UPDATE `services` SET `subtitle_it` = 'In Italia e nel mondo' WHERE `slug` = 'destination' AND `subtitle_it` = 'Worldwide';
