-- ---------------------------------------------------------------------
-- Via la vecchia sezione «Eventi» (portfolio) — giro 11.
--
-- La pagina /portfolio/ non esiste più (301 su /eventi/, che legge la
-- tabella stories, «I miei eventi»). Le tabelle events ed event_images
-- erano rimaste congelate con soli dati di esempio; sullo staging sono
-- vuote (verificato il 21/10/2026). Le foto restano in media: il legame
-- era solo in event_images e in events.cover/hero_media_id, con FK
-- verso media, non il contrario.
--
-- Insieme va via instagram_items, tabella dello schema iniziale mai
-- usata (il feed vero sta in instagram_posts), e la riga «portfolio»
-- della tabella pages, che nessuna pagina legge più.
--
-- PRIMA: php8.3 -q admin/bin/dump-sicurezza.php (come da CLAUDE.md).
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `event_images`;
DROP TABLE IF EXISTS `events`;
DROP TABLE IF EXISTS `instagram_items`;

DELETE FROM `pages` WHERE `page_key` = 'portfolio';
