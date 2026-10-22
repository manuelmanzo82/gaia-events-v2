-- ---------------------------------------------------------------------
-- Cookie policy (28/10/2026): la prova del campo «Ultimo aggiornamento»
-- dal browser ha risalvato il corpo con gli a capo \r\n del form. Si
-- torna agli a capo semplici della migrazione privacy. Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `pages` SET
  `body_it` = REPLACE(`body_it`, CHAR(13), ''),
  `body_en` = REPLACE(`body_en`, CHAR(13), ''),
  `intro_it` = REPLACE(`intro_it`, CHAR(13), ''),
  `intro_en` = REPLACE(`intro_en`, CHAR(13), ''),
  `meta_description_it` = REPLACE(`meta_description_it`, CHAR(13), ''),
  `meta_description_en` = REPLACE(`meta_description_en`, CHAR(13), '')
WHERE `page_key` = 'cookie';
