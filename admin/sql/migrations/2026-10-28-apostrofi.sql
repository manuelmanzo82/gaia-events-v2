-- ---------------------------------------------------------------------
-- Apostrofi tipografici (28/10/2026): ' → ’ nei testi dei contenuti, IT
-- ed EN. Solo l'apostrofo tra due lettere o cifre (dall'8, baby's): gli
-- apici degli attributi HTML e le virgolette restano come sono. Nessuna
-- virgoletta dritta trovata nel testo visibile. Colonne trovate con una
-- scansione del database dello staging (114 apostrofi). Il pattern non usa
-- backslash: lookbehind e lookahead. Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

SET @ap = '(?<=[[:alnum:]À-ÿ])''(?=[[:alnum:]À-ÿ])';
-- Corpo HTML generato degli articoli: lì l'apostrofo è &#039;.
SET @ent = '(?<=[[:alnum:]À-ÿ])&#0?39;(?=[[:alnum:]À-ÿ])';

UPDATE `media` SET `alt_it` = REGEXP_REPLACE(`alt_it`, @ap, '’') WHERE `alt_it` LIKE '%''%';
UPDATE `media` SET `alt_en` = REGEXP_REPLACE(`alt_en`, @ap, '’') WHERE `alt_en` LIKE '%''%';

UPDATE `settings` SET `value_it` = REGEXP_REPLACE(`value_it`, @ap, '’') WHERE `value_it` LIKE '%''%';
UPDATE `settings` SET `value_en` = REGEXP_REPLACE(`value_en`, @ap, '’') WHERE `value_en` LIKE '%''%';

UPDATE `pages` SET
  `title_it` = REGEXP_REPLACE(`title_it`, @ap, '’'),
  `title_en` = REGEXP_REPLACE(`title_en`, @ap, '’'),
  `intro_it` = REGEXP_REPLACE(`intro_it`, @ap, '’'),
  `intro_en` = REGEXP_REPLACE(`intro_en`, @ap, '’'),
  `body_it` = REGEXP_REPLACE(`body_it`, @ap, '’'),
  `body_en` = REGEXP_REPLACE(`body_en`, @ap, '’'),
  `meta_title_it` = REGEXP_REPLACE(`meta_title_it`, @ap, '’'),
  `meta_title_en` = REGEXP_REPLACE(`meta_title_en`, @ap, '’'),
  `meta_description_it` = REGEXP_REPLACE(`meta_description_it`, @ap, '’'),
  `meta_description_en` = REGEXP_REPLACE(`meta_description_en`, @ap, '’');

UPDATE `page_sections` SET
  `title_it` = REGEXP_REPLACE(`title_it`, @ap, '’'),
  `title_en` = REGEXP_REPLACE(`title_en`, @ap, '’'),
  `text_it` = REGEXP_REPLACE(`text_it`, @ap, '’'),
  `text_en` = REGEXP_REPLACE(`text_en`, @ap, '’'),
  `items_it` = REGEXP_REPLACE(`items_it`, @ap, '’'),
  `items_en` = REGEXP_REPLACE(`items_en`, @ap, '’');

UPDATE `services` SET
  `subtitle_it` = REGEXP_REPLACE(`subtitle_it`, @ap, '’'),
  `subtitle_en` = REGEXP_REPLACE(`subtitle_en`, @ap, '’'),
  `description_it` = REGEXP_REPLACE(`description_it`, @ap, '’'),
  `description_en` = REGEXP_REPLACE(`description_en`, @ap, '’');

UPDATE `posts` SET
  `title_it` = REGEXP_REPLACE(`title_it`, @ap, '’'),
  `title_en` = REGEXP_REPLACE(`title_en`, @ap, '’'),
  `excerpt_it` = REGEXP_REPLACE(`excerpt_it`, @ap, '’'),
  `excerpt_en` = REGEXP_REPLACE(`excerpt_en`, @ap, '’'),
  `sommario_it` = REGEXP_REPLACE(`sommario_it`, @ap, '’'),
  `sommario_en` = REGEXP_REPLACE(`sommario_en`, @ap, '’'),
  `blocchi` = REGEXP_REPLACE(`blocchi`, @ap, '’'),
  `body_it` = REGEXP_REPLACE(REGEXP_REPLACE(`body_it`, @ap, '’'), @ent, '’'),
  `body_en` = REGEXP_REPLACE(REGEXP_REPLACE(`body_en`, @ap, '’'), @ent, '’'),
  `meta_description_it` = REGEXP_REPLACE(`meta_description_it`, @ap, '’'),
  `meta_description_en` = REGEXP_REPLACE(`meta_description_en`, @ap, '’');

UPDATE `faqs` SET
  `question_it` = REGEXP_REPLACE(`question_it`, @ap, '’'),
  `question_en` = REGEXP_REPLACE(`question_en`, @ap, '’'),
  `answer_it` = REGEXP_REPLACE(`answer_it`, @ap, '’'),
  `answer_en` = REGEXP_REPLACE(`answer_en`, @ap, '’');

UPDATE `highlights` SET
  `title_it` = REGEXP_REPLACE(`title_it`, @ap, '’'),
  `title_en` = REGEXP_REPLACE(`title_en`, @ap, '’');

UPDATE `page_hero` SET
  `subtitle_it` = REGEXP_REPLACE(`subtitle_it`, @ap, '’'),
  `subtitle_en` = REGEXP_REPLACE(`subtitle_en`, @ap, '’');

UPDATE `service_compare` SET
  `label_it` = REGEXP_REPLACE(`label_it`, @ap, '’'),
  `label_en` = REGEXP_REPLACE(`label_en`, @ap, '’');

UPDATE `destination_regioni` SET
  `testo_it` = REGEXP_REPLACE(`testo_it`, @ap, '’'),
  `testo_en` = REGEXP_REPLACE(`testo_en`, @ap, '’');
