-- ---------------------------------------------------------------------
-- Contatti: la tessera «Conosciamoci» passa dalla 102 (fiori e serra,
-- troppo simile al ritratto) alla 38 scalinata-villa-tavoli-tramonto:
-- scena ampia al tramonto, 2362 px, persone minuscole non riconoscibili.
-- Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `settings` SET `value_it` = (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/scalinata-villa-tavoli-tramonto.jpg')
WHERE `setting_key` = 'contatti.photos.tessera';
