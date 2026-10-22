-- ---------------------------------------------------------------------
-- Pagina 404 (27/10/2026): titolo, riga e foto nel gruppo «errore».
-- La foto è la 5, tavoli in giardino con le lampade: nitida, non in home.
-- Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('errore.titolo', 'errore', 'Pagina non trovata (404) — titolo', 'text',
 'Questa pagina non era nel programma della giornata.',
 'This page wasn’t on the day’s schedule.', 1),
('errore.riga', 'errore', 'Pagina non trovata (404) — riga', 'textarea',
 'Capita anche ai programmi migliori: la pagina che cercate non c’è, o ha cambiato indirizzo.',
 'It happens to the best-laid plans: the page you’re looking for isn’t here, or it has moved.', 2)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'errore.photos.foto', 'errore', 'Pagina non trovata (404) — foto', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/tavoli-giardino-lampade.jpg'), NULL, 3
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
