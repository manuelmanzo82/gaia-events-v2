-- ---------------------------------------------------------------------
-- Chi sono, valori: foto nuove (giro correttivo 21/10/2026).
--
-- Niente ritratti tagliati né ospiti riconoscibili: scene e dettagli
-- orizzontali o ritagliabili in 3:2 — sala con i dipinti, mise en place
-- con il menù, tavolo lungo a lume di candela, violinista al tramonto
-- (figura lontana). Chiave `media` delle voci del blocco «values».
--
-- Idempotente (JSON_SET sovrascrive).
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id AND p.page_key = 'about'
SET ps.items_it = JSON_SET(ps.items_it,
  '$[0].media', (SELECT id FROM `media` WHERE path = 'foto/sala-dipinti-tavoli-rotondi.jpg'),
  '$[1].media', (SELECT id FROM `media` WHERE path = 'foto/mise-en-place-menu-pane.jpg'),
  '$[2].media', (SELECT id FROM `media` WHERE path = 'foto/tavolo-lungo-candele-calici.jpg'),
  '$[3].media', (SELECT id FROM `media` WHERE path = 'foto/violinista-cena-giardino.jpg'))
WHERE ps.kind = 'values' AND JSON_LENGTH(ps.items_it) >= 4;
