-- ---------------------------------------------------------------------
-- Servizi, tabella di confronto: ritocchi alle voci (21/10/2026, Manuel).
-- Sempre in bozza, da confermare con Gaia (DA-CHIEDERE n. 14).
--
-- - «Selezione e gestione dei fornitori» diventa «Ricerca e selezione dei
--   fornitori» (planning) + nuova «Coordinamento dei fornitori scelti»
--   (coordinamento: il planning la eredita con la regola include);
-- - «Sopralluoghi con i fornitori»: anche design;
-- - nuova «Scelta di fioristi e noleggi per l'allestimento» (design: il
--   planning la eredita).
-- service_slugs indica solo i servizi che comprendono la voce
-- direttamente: gli inclusi li aggiunge il sito (include_slugs).
--
-- Idempotente (UPDATE per etichetta, INSERT solo se la voce manca).
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `service_compare`
SET `label_it` = 'Ricerca e selezione dei fornitori', `label_en` = 'Supplier research and selection'
WHERE `label_it` = 'Selezione e gestione dei fornitori';

UPDATE `service_compare` SET `service_slugs` = 'coordinamento,design'
WHERE `label_it` = 'Sopralluoghi con i fornitori';

INSERT INTO `service_compare` (`label_it`, `label_en`, `service_slugs`, `sort_order`)
SELECT 'Coordinamento dei fornitori scelti', 'Coordination of the chosen suppliers', 'coordinamento', 0
WHERE NOT EXISTS (SELECT 1 FROM `service_compare` WHERE `label_it` = 'Coordinamento dei fornitori scelti');

INSERT INTO `service_compare` (`label_it`, `label_en`, `service_slugs`, `sort_order`)
SELECT 'Scelta di fioristi e noleggi per l''allestimento', 'Choosing florists and rentals for the set-up', 'design', 0
WHERE NOT EXISTS (SELECT 1 FROM `service_compare` WHERE `label_it` = 'Scelta di fioristi e noleggi per l''allestimento');

-- Ordine: location, fornitori (ricerca, coordinamento), budget, inviti,
-- concept, allestimenti, fioristi e noleggi, sopralluoghi, timeline,
-- coordinamento del giorno, imprevisti.
UPDATE `service_compare` SET `sort_order` = CASE `label_it`
  WHEN 'Ricerca e scelta della location' THEN 1
  WHEN 'Ricerca e selezione dei fornitori' THEN 2
  WHEN 'Coordinamento dei fornitori scelti' THEN 3
  WHEN 'Gestione del budget e dei contratti' THEN 4
  WHEN 'Inviti e conferme degli ospiti' THEN 5
  WHEN 'Concept e design dell''evento' THEN 6
  WHEN 'Allestimenti e styling' THEN 7
  WHEN 'Scelta di fioristi e noleggi per l''allestimento' THEN 8
  WHEN 'Sopralluoghi con i fornitori' THEN 9
  WHEN 'Timeline dettagliata della giornata' THEN 10
  WHEN 'Coordinamento il giorno dell''evento' THEN 11
  WHEN 'Gestione degli imprevisti' THEN 12
  ELSE `sort_order` END;
