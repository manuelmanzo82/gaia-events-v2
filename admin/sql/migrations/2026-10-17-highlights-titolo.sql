-- ---------------------------------------------------------------------
-- Titolo bilingue sugli highlights: la frase breve che compare SULLA
-- scheda impilata della home (Cormorant, avorio, centrata). Facoltativo:
-- senza titolo il sito usa l'alt accorciato. IF NOT EXISTS: rilanciare e'
-- sicuro.
--
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE `highlights`
  ADD COLUMN IF NOT EXISTS `title_it` VARCHAR(190) DEFAULT NULL AFTER `luogo_id`,
  ADD COLUMN IF NOT EXISTS `title_en` VARCHAR(190) DEFAULT NULL AFTER `title_it`;
