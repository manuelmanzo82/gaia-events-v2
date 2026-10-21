-- ---------------------------------------------------------------------
-- Rinomine del giro 11: Blog → Magazine, Storie → Matrimoni.
--
-- Nessuna modifica di schema: cambia solo il titolo della pagina indice
-- del blog (pages.page_key = 'blog'), che il sito usa come H1 e in llms.txt.
-- Si corregge solo se è ancora quello vecchio. Le chiavi interne (blog,
-- stories) restano: gli URL nuovi vengono da routes.ts, i 301 dal
-- .htaccess del sito.
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `pages` SET `title_it` = 'Magazine' WHERE `page_key` = 'blog' AND `title_it` = 'Blog';
UPDATE `pages` SET `title_en` = 'Magazine' WHERE `page_key` = 'blog' AND `title_en` = 'Blog';
