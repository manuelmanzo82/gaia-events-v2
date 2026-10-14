-- ---------------------------------------------------------------------
-- Aggiornamenti dello schema per database già creati.
--
-- schema.sql resta la fonte di verità: su un database nuovo basta quello.
-- Qui stanno le ALTER da eseguire su un database che esiste già, in ordine
-- di data. Sono scritte per poter essere rieseguite senza danni.
--
--   mysql -u UTENTE -p NOME_DB < aggiornamenti.sql
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- 14/10/2026 — blocchi anche sulle pagine diverse da destination wedding
-- (servono alla pagina «chi sono»: percorso e valori)
-- ---------------------------------------------------------------------

ALTER TABLE `page_sections`
  MODIFY COLUMN `kind` ENUM('intro','services','destinations','process',
    'faq','testimonial','cta','timeline','values') NOT NULL;

-- ---------------------------------------------------------------------
-- 14/10/2026 — limite di visualizzazione delle immagini
--
-- Ogni immagine si porta dietro la larghezza massima a cui resta nitida
-- (larghezza del file diviso 2, per il retina) e l'uso che ne consegue.
-- Serve a usare anche le foto piccole, ciascuna al suo posto, invece di
-- scartarle: il sito non le ingrandirà mai oltre quel limite.
-- ---------------------------------------------------------------------

ALTER TABLE `media`
  ADD COLUMN IF NOT EXISTS `max_css_width` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `focal_y`,
  ADD COLUMN IF NOT EXISTS `usage_tier` ENUM('hero','gallery_large','card','thumbnail')
    NOT NULL DEFAULT 'thumbnail' AFTER `max_css_width`;

-- Riempie i valori per le immagini già caricate, con le stesse soglie di
-- ImageService: 1440 px CSS per l'hero, 1000 per la gallery grande, 480
-- per le card.
UPDATE `media` SET
  `max_css_width` = FLOOR(`width` / 2),
  `usage_tier` = CASE
    WHEN FLOOR(`width` / 2) >= 1440 THEN 'hero'
    WHEN FLOOR(`width` / 2) >= 1000 THEN 'gallery_large'
    WHEN FLOOR(`width` / 2) >= 480 THEN 'card'
    ELSE 'thumbnail'
  END
WHERE `width` > 0;
