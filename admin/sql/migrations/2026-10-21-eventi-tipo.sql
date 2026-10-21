-- ---------------------------------------------------------------------
-- I miei eventi (giro 11): tipo di evento.
--
-- stories.event_type: matrimonio · unione_civile · festa · aziendale.
-- I record esistenti diventano «matrimonio» (default della colonna).
-- Nella pagina /eventi/ i matrimoni (e le unioni civili) vengono prima,
-- poi gli altri tipi; i filtri compaiono solo con almeno due gruppi.
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE `stories`
  ADD COLUMN IF NOT EXISTS `event_type` ENUM('matrimonio','unione_civile','festa','aziendale') NOT NULL DEFAULT 'matrimonio' AFTER `couple`;
