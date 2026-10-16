-- ---------------------------------------------------------------------
-- Manifesto della home: una frase grande + firma, sotto l'hero.
--
-- Due chiavi nella tabella k/v `settings`, gruppo "home", che il back
-- office mostra come gli altri testi (le chiavi nascono qui, il form le
-- modifica soltanto). L'export le porta in settings.json sotto `home`:
-- home.manifesto e home.manifestoFirma (camelCase come hero.ctaLabel).
--
-- Il testo italiano e' la citazione della biografia importata da Sanity;
-- la versione inglese arriva a parte (bozza da far rivedere a Gaia, vedi
-- docs/DA-CHIEDERE-A-GAIA.md), non da questo file.
--
-- INSERT IGNORE: la chiave e' UNIQUE, rilanciare e' sicuro.
--
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT IGNORE INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('home.manifesto',      'home', 'Manifesto — frase grande sotto l''hero', 'textarea', 'Il mio obiettivo è trasformare la vostra visione in realtà, curando ogni dettaglio con passione e dedizione per creare un giorno che superi ogni aspettativa.', NULL, 6),
('home.manifestoFirma', 'home', 'Manifesto — firma',                     'text',     'Gaia Crognale', 'Gaia Crognale', 7);
