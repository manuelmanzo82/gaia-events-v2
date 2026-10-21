-- ---------------------------------------------------------------------
-- Chi sono, citazione: punto focale della foto del prato sul lago.
--
-- A 390 il taglio verticale centrato lasciava la sposa a metà fuori dal
-- bordo destro: il punto focale va a x 0,6 (la sposa), y 0,58 (il cielo
-- resta libero per il testo). Si corregge solo se è ancora 0,5 / 0,58.
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `media` SET `focal_x` = 0.6
WHERE path = 'foto/prato-lago-crepuscolo-luci.jpg' AND focal_x = 0.5 AND focal_y = 0.58;
