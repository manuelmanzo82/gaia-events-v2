-- ---------------------------------------------------------------------
-- Highlights: nuova sezione «cta» (la foto accanto alla CTA finale della
-- home). ENUM esteso in coda, nessun dato toccato. Rilanciare e' sicuro.
--
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE `highlight_sections`
  MODIFY `section` ENUM('home','chi-sono','servizi','destination','luogo','cta') NOT NULL;
