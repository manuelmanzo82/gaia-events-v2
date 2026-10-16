-- ---------------------------------------------------------------------
-- Note interne sulle immagini.
--
-- Un campo di servizio per il back office, mai esportato verso il sito:
-- "da sostituire con il file senza watermark prima del lancio", "nomi di
-- invitati leggibili, da verificare", "tenere per Chi sono". Nasce con
-- l'import delle foto vere (bin/importa-foto.php), che lo compila per le
-- foto che hanno qualcosa da sistemare.
--
-- IF NOT EXISTS: MariaDB lo accetta sull'ADD COLUMN, cosi' rilanciare la
-- migrazione resta sicuro.
--
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE `media`
  ADD COLUMN IF NOT EXISTS `note_interne` VARCHAR(500) DEFAULT NULL AFTER `caption_en`;
