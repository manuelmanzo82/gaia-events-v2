-- ---------------------------------------------------------------------
-- Chi sono, giro di revisione (21/10/2026): le foto delle sezioni.
--
-- 1. Voci dei blocchi «timeline» e «values» della pagina Chi sono: una
--    foto per voce, come chiave `media` (id in media) dentro items_it.
--    L'export la rende come `image` della voce. Collegamento per percorso
--    del file, così la migrazione vale su qualunque database.
-- 2. Impostazioni «chi sono»: foto dei link (eventi → castello al
--    crepuscolo, abbastanza largo per la card), della citazione (prato
--    sul lago, con il cielo calmo per il testo) e nuova foto della CTA
--    finale di questa pagina (about.photos.cta).
-- 3. Punto focale della foto della citazione.
--
-- Idempotente (JSON_SET sovrascrive, UPDATE condizionati).
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id AND p.page_key = 'about'
SET ps.items_it = JSON_SET(ps.items_it,
  '$[0].media', (SELECT id FROM `media` WHERE path = 'foto/navata-chiesa-gipsofila.jpg'),
  '$[1].media', (SELECT id FROM `media` WHERE path = 'foto/serra-fontana-sera.png'),
  '$[2].media', (SELECT id FROM `media` WHERE path = 'foto/castello-costa-crepuscolo.png'),
  '$[3].media', (SELECT id FROM `media` WHERE path = 'foto/scalinata-villa-tavoli-tramonto.jpg'))
WHERE ps.kind = 'timeline' AND JSON_LENGTH(ps.items_it) >= 4;

UPDATE `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id AND p.page_key = 'about'
SET ps.items_it = JSON_SET(ps.items_it,
  '$[0].media', (SELECT id FROM `media` WHERE path = 'foto/sala-dipinti-tavoli-rotondi.jpg'),
  '$[1].media', (SELECT id FROM `media` WHERE path = 'foto/planner-sistema-tavolo.jpg'),
  '$[2].media', (SELECT id FROM `media` WHERE path = 'foto/tavolo-lungo-candele-calici.jpg'),
  '$[3].media', (SELECT id FROM `media` WHERE path = 'foto/musicisti-violino-chitarra.jpg'))
WHERE ps.kind = 'values' AND JSON_LENGTH(ps.items_it) >= 4;

UPDATE `settings` SET `value_it` = (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/castello-mare-crepuscolo.png')
WHERE `setting_key` = 'about.photos.events';
UPDATE `settings` SET `value_it` = (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/prato-lago-crepuscolo-luci.jpg')
WHERE `setting_key` = 'about.photos.quote';

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'about.photos.cta', 'chi sono', 'Foto — CTA finale di Chi sono', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/tavolo-candele-sera.jpg'), NULL, 23
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

UPDATE `media` SET `focal_x` = 0.5, `focal_y` = 0.58
WHERE path = 'foto/prato-lago-crepuscolo-luci.jpg' AND focal_x = 0.5 AND focal_y = 0.5;
