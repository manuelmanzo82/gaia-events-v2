-- ---------------------------------------------------------------------
-- Chi sono, timeline: foto che reggono (giro correttivo 21/10/2026).
--
-- A 1920 la cornice della tappa è larga 670 px e la foto, ingrandita per
-- la parallasse, è resa a ~817 px: serve un file vero di almeno ~1640 px.
-- 2016: serra-fontana-sera.png è uno screenshot ingrandito (~1000 px
-- veri) e si vedeva sgranato → tavoli-giardino-lampade.jpg (2048 px).
-- 2018: castello-costa-crepuscolo.png è largo 986 px → cerimonia-prato-
-- vista-mare.jpg (4160 px). Entrambe originali della fotocamera, 3:2.
-- 2014 e 2022 reggono (2048 e 2362 px).
--
-- Idempotente (JSON_SET sovrascrive).
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id AND p.page_key = 'about'
SET ps.items_it = JSON_SET(ps.items_it,
  '$[1].media', (SELECT id FROM `media` WHERE path = 'foto/tavoli-giardino-lampade.jpg'),
  '$[2].media', (SELECT id FROM `media` WHERE path = 'foto/cerimonia-prato-vista-mare.jpg'))
WHERE ps.kind = 'timeline' AND JSON_LENGTH(ps.items_it) >= 4;
