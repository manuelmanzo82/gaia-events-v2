-- ---------------------------------------------------------------------
-- Destination wedding: fascia degli orologi in fondo alla foto d'apertura
-- (stile tabellone partenze). Città e ordine nelle impostazioni: una per
-- riga, «NOME|fuso IANA» (es. ROMA|Europe/Rome); la prima è quella di
-- Gaia (in oro). Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('destination.orologi.citta', 'destination', 'Orologi — città, una per riga: NOME|fuso (es. ROMA|Europe/Rome); la prima è in oro', 'textarea',
 'ROMA|Europe/Rome\nLONDON|Europe/London\nNEW YORK|America/New_York\nLOS ANGELES|America/Los_Angeles\nDUBAI|Asia/Dubai\nHONG KONG|Asia/Hong_Kong',
 'ROME|Europe/Rome\nLONDON|Europe/London\nNEW YORK|America/New_York\nLOS ANGELES|America/Los_Angeles\nDUBAI|Asia/Dubai\nHONG KONG|Asia/Hong_Kong', 20),
('destination.orologi.riga', 'destination', 'Orologi — riga sopra la fascia', 'text',
 'Ovunque siate, lavoro sul vostro fuso orario.', 'Wherever you are, I work on your time.', 21),
('destination.orologi.davoi', 'destination', 'Orologi — etichetta per l’ora del visitatore', 'text',
 'DA VOI', 'YOUR TIME', 22)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
