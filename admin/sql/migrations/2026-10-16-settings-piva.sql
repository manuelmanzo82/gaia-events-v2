-- ---------------------------------------------------------------------
-- Partita IVA nelle impostazioni: obbligatoria per legge sui siti delle
-- attivita' italiane, compare nella riga legale del footer. Finche' e'
-- vuota il sito mostra il segnaposto «[P.IVA da Gaia]».
--
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT IGNORE INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('legal.piva', 'generale', 'Partita IVA (riga legale del footer)', 'text', NULL, NULL, 5);
