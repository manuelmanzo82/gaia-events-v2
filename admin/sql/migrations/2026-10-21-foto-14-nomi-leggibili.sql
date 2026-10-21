-- ---------------------------------------------------------------------
-- Foto 14 (ventaglio-libretto-sedia): sul libretto si leggono i nomi degli
-- sposi, la data e la location. Via dalla galleria dell'evento «Un
-- matrimonio tra edera e affreschi» e nota interna «mai pubblicare».
-- La foto resta in libreria.
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

DELETE si FROM `story_images` si
JOIN `media` m ON m.id = si.media_id
WHERE m.path = 'foto/ventaglio-libretto-sedia.jpg';

UPDATE `media`
SET `note_interne` = 'MAI PUBBLICARE: nomi leggibili (sul libretto i nomi degli sposi, la data e la location).'
WHERE path = 'foto/ventaglio-libretto-sedia.jpg';
