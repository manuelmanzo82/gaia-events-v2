-- ---------------------------------------------------------------------
-- Dicono di me (giro 11): il titolo inglese della pagina segue la voce
-- di menu («Kind words»). Si corregge solo se è ancora «Reviews».
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `pages` SET `title_en` = 'Kind words' WHERE `page_key` = 'reviews' AND `title_en` = 'Reviews';
