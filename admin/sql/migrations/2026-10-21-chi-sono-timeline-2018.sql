-- ---------------------------------------------------------------------
-- Chi sono, timeline: nuovo testo della tappa 2018 (21/10/2026, Manuel).
-- Solo italiano (items_it); l'inglese resta la bozza del 16/10.
--
-- Idempotente (JSON_SET sovrascrive).
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id AND p.page_key = 'about'
SET ps.items_it = JSON_SET(ps.items_it, '$[2].text',
  'Il raggio si allarga oltre Roma: matrimoni in Toscana, Puglia e Costiera Amalfitana, per coppie che arrivano da tutto il mondo.')
WHERE ps.kind = 'timeline' AND JSON_UNQUOTE(JSON_EXTRACT(ps.items_it, '$[2].label')) = '2018';
