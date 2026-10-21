-- ---------------------------------------------------------------------
-- Chi sono, valori: «Passione» senza nomi leggibili (vedi la migrazione
-- 2026-10-21-chi-sono-valori-creativita.sql, già applicata sullo staging
-- prima che le si aggiungesse questa riga). Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id AND p.page_key = 'about'
SET ps.items_it = JSON_SET(ps.items_it,
  '$[1].media', (SELECT id FROM `media` WHERE path = 'foto/banchetto-edificio-storico.jpg'))
WHERE ps.kind = 'values' AND JSON_LENGTH(ps.items_it) >= 4;
