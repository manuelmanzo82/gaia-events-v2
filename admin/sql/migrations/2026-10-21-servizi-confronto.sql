-- ---------------------------------------------------------------------
-- Servizi, tabella «Quale fa per voi?» (21/10/2026).
--
-- service_compare: le voci di confronto comuni a tutti i servizi, IT/EN.
-- service_slugs = i servizi che comprendono DIRETTAMENTE la voce (slug
-- separati da virgola). Le spunte sul sito seguono anche include_slugs
-- dei servizi, la stessa logica del preventivatore: il wedding planning
-- completo include coordinamento e design, quindi eredita le loro voci
-- senza doverle ripetere qui.
--
-- Le 10 voci sono una BOZZA scritta da noi, da confermare con Gaia
-- (DA-CHIEDERE-A-GAIA n. 14). Si modificano nel back office, sezione
-- «Confronto servizi».
--
-- Idempotente: CREATE TABLE IF NOT EXISTS, voci inserite solo se la
-- tabella è vuota.
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `service_compare` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `label_it`      VARCHAR(190) DEFAULT NULL,
  `label_en`      VARCHAR(190) DEFAULT NULL,
  `service_slugs` VARCHAR(255) DEFAULT NULL,
  `active`        TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`    SMALLINT NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_service_compare_active` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `service_compare` (`label_it`, `label_en`, `service_slugs`, `sort_order`)
SELECT v.it, v.en, v.s, v.o
FROM (
  SELECT 'Ricerca e scelta della location'        AS it, 'Venue search and selection'          AS en, 'planning'      AS s, 1  AS o UNION ALL
  SELECT 'Selezione e gestione dei fornitori',            'Supplier selection and management',          'planning',           2  UNION ALL
  SELECT 'Gestione del budget e dei contratti',           'Budget and contract management',             'planning',           3  UNION ALL
  SELECT 'Inviti e conferme degli ospiti',                'Invitations and RSVPs',                      'planning',           4  UNION ALL
  SELECT 'Concept e design dell''evento',                 'Concept and design',                         'design',             5  UNION ALL
  SELECT 'Allestimenti e styling',                        'Set design and styling',                     'design',             6  UNION ALL
  SELECT 'Sopralluoghi con i fornitori',                  'Site visits with suppliers',                 'coordinamento',      7  UNION ALL
  SELECT 'Timeline dettagliata della giornata',           'A detailed timeline for the day',            'coordinamento',      8  UNION ALL
  SELECT 'Coordinamento il giorno dell''evento',          'Coordination on the day',                    'coordinamento',      9  UNION ALL
  SELECT 'Gestione degli imprevisti',                     'Handling the unexpected',                    'coordinamento',      10
) AS v
WHERE NOT EXISTS (SELECT 1 FROM `service_compare`);

-- Titolo della sezione (gruppo impostazioni «servizi»).
INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('servizi.compare.title', 'servizi', 'Tabella di confronto — titolo', 'text', 'Quale fa per voi?', 'Which one is right for you?', 10)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group_name` = VALUES(`group_name`);
