-- ---------------------------------------------------------------------
-- Chi sono, valori come schede impilate (21/10/2026): niente ospiti
-- riconoscibili né nomi leggibili. «Passione»: sul menù della mise en
-- place, a 1920, si leggono i nomi degli sposi → tavolo imperiale lungo
-- l'edificio di mattoni (2358 px, maxWidth 1179). «Creatività»: la
-- violinista aveva gli ospiti ai tavoli sullo sfondo → composizione di
-- fiori sullo sgabello con il drappo (2362 px, maxWidth 1181). Entrambe
-- reggono la foto da ~994 px della scheda a 1920.
--
-- Idempotente (JSON_SET sovrascrive).
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id AND p.page_key = 'about'
SET ps.items_it = JSON_SET(ps.items_it,
  '$[1].media', (SELECT id FROM `media` WHERE path = 'foto/banchetto-edificio-storico.jpg'),
  '$[3].media', (SELECT id FROM `media` WHERE path = 'foto/composizione-sgabello-drappo.jpg'))
WHERE ps.kind = 'values' AND JSON_LENGTH(ps.items_it) >= 4;
