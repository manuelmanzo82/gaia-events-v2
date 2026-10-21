-- ---------------------------------------------------------------------
-- Pagina Chi sono (fase 4.2): impostazioni del gruppo «chi sono».
--
-- 1. about.since — anno di inizio, per la riga «Wedding & event planner
--    a Roma, dal …»: vuoto = la riga resta senza «dal».
-- 2. about.stats.1-4 — i numeri (valore + etichetta IT/EN: anni, eventi,
--    location, paesi). Vuoti = la sezione non esiste. Solo numeri veri,
--    da Gaia (DA-CHIEDERE-A-GAIA n. 13, priorità alta).
-- 3. about.photos.* — foto della citazione a tutto schermo e dei due link
--    finali (I miei eventi, Servizi), collegate per percorso del file.
-- 4. pages.about — meta title e meta description IT/EN composti da noi
--    con la tagline e la prima frase del testo (bozza, DA-CHIEDERE n. 13);
--    si scrivono solo se ancora vuoti.
--
-- Idempotente: INSERT … ON DUPLICATE KEY, UPDATE condizionati.
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('about.since',         'chi sono', 'Anno di inizio (riga «dal …» sotto il nome; vuoto = senza)', 'text', '', '', 1),
('about.stats.1.value', 'chi sono', 'Numero 1 — valore (es. 12)',        'text', '', '', 10),
('about.stats.1.label', 'chi sono', 'Numero 1 — etichetta (es. anni)',   'text', '', '', 11),
('about.stats.2.value', 'chi sono', 'Numero 2 — valore',                 'text', '', '', 12),
('about.stats.2.label', 'chi sono', 'Numero 2 — etichetta (es. eventi)', 'text', '', '', 13),
('about.stats.3.value', 'chi sono', 'Numero 3 — valore',                 'text', '', '', 14),
('about.stats.3.label', 'chi sono', 'Numero 3 — etichetta (es. location)', 'text', '', '', 15),
('about.stats.4.value', 'chi sono', 'Numero 4 — valore',                 'text', '', '', 16),
('about.stats.4.label', 'chi sono', 'Numero 4 — etichetta (es. paesi)',  'text', '', '', 17)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group_name` = VALUES(`group_name`);

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT v.k, 'chi sono', v.l, 'media', (SELECT CAST(m.id AS CHAR) FROM `media` m WHERE m.path = v.p), NULL, v.o
FROM (
  SELECT 'about.photos.quote'    AS k, 'Foto — citazione a tutto schermo' AS l, 'foto/torta-albero-luci-sospese.jpg' AS p, 20 AS o UNION ALL
  SELECT 'about.photos.events',        'Foto — link «I miei eventi»',          'foto/castello-costa-crepuscolo.png',      21 UNION ALL
  SELECT 'about.photos.services',      'Foto — link «Servizi»',                'foto/tavolo-terrazza-colline.jpg',        22
) AS v
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group_name` = VALUES(`group_name`);

UPDATE `pages` SET `meta_title_it` = 'Gaia Crognale, wedding & event planner a Roma'
WHERE `page_key` = 'about' AND (`meta_title_it` IS NULL OR `meta_title_it` = '');
UPDATE `pages` SET `meta_title_en` = 'Gaia Crognale, wedding & event planner in Rome'
WHERE `page_key` = 'about' AND (`meta_title_en` IS NULL OR `meta_title_en` = '');
UPDATE `pages` SET `meta_description_it` = 'Chi sono: Gaia Crognale, wedding & event planner a Roma. Da oltre dieci anni dedico la mia vita alla realizzazione di matrimoni da sogno, a Roma, in tutta Italia e all''estero.'
WHERE `page_key` = 'about' AND (`meta_description_it` IS NULL OR `meta_description_it` = '');
UPDATE `pages` SET `meta_description_en` = 'About Gaia Crognale, wedding & event planner in Rome. For more than ten years I have devoted my life to creating dream weddings, in Rome, across Italy and abroad.'
WHERE `page_key` = 'about' AND (`meta_description_en` IS NULL OR `meta_description_en` = '');
