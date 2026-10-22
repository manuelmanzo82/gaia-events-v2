-- ---------------------------------------------------------------------
-- Magazine (/magazine/ ↔ /en/magazine/), 23/10/2026: schema.
--
-- 1. post_categories: categorie con nome e slug bilingui (filtro
--    ?categoria=); posts.category_id. Le 5 categorie della v1 migrano.
-- 2. posts: featured (in evidenza), modified_date («Aggiornato il …»,
--    dateModified), sommario_it/en («In breve», un punto per riga),
--    blocchi (JSON dell'editor a blocchi), cover_video_id.
-- 3. videos: libreria video (clip caricata o YouTube/Vimeo), poster
--    obbligatorio dalla libreria media.
-- 4. Impostazioni del gruppo «magazine» e intro della pagina.
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- 1 -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `post_categories` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome_it`    VARCHAR(120) DEFAULT NULL,
  `nome_en`    VARCHAR(120) DEFAULT NULL,
  `slug_it`    VARCHAR(120) DEFAULT NULL,
  `slug_en`    VARCHAR(120) DEFAULT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_post_categories_slug_it` (`slug_it`),
  UNIQUE KEY `uq_post_categories_slug_en` (`slug_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `post_categories` (`nome_it`, `nome_en`, `slug_it`, `slug_en`, `sort_order`) VALUES
('Location', 'Venues', 'location', 'venues', 1),
('Ispirazioni', 'Inspiration', 'ispirazioni', 'inspiration', 2),
('Destination', 'Destination weddings', 'destination', 'destination-weddings', 3),
('Organizzazione', 'Planning', 'organizzazione', 'planning', 4),
('Tendenze', 'Trends', 'tendenze', 'trends', 5);

-- 3 (prima del 2: posts.cover_video_id punta qui) -----------------------
CREATE TABLE IF NOT EXISTS `videos` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tipo`            ENUM('clip','youtube','vimeo') NOT NULL DEFAULT 'clip',
  `titolo_it`       VARCHAR(255) DEFAULT NULL,
  `titolo_en`       VARCHAR(255) DEFAULT NULL,
  `descrizione_it`  VARCHAR(500) DEFAULT NULL,
  `descrizione_en`  VARCHAR(500) DEFAULT NULL,
  -- Clip: file in uploads/video/ (mp4 H.264 obbligatorio, webm facoltativo).
  `file_mp4`        VARCHAR(400) DEFAULT NULL,
  `file_webm`       VARCHAR(400) DEFAULT NULL,
  -- YouTube/Vimeo: link incollato e id estratto dal back office.
  `url`             VARCHAR(500) DEFAULT NULL,
  `provider_id`     VARCHAR(64) DEFAULT NULL,
  -- Poster dalla libreria media: obbligatorio (niente miniature esterne).
  -- Larghezza e altezza del video sono quelle del poster.
  `poster_media_id` INT UNSIGNED DEFAULT NULL,
  `durata_sec`      INT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_videos_poster` (`poster_media_id`),
  CONSTRAINT `fk_videos_poster` FOREIGN KEY (`poster_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2 -----------------------------------------------------------------------
ALTER TABLE `posts`
  ADD COLUMN IF NOT EXISTS `category_id` INT UNSIGNED DEFAULT NULL AFTER `category_en`,
  ADD COLUMN IF NOT EXISTS `featured` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`,
  ADD COLUMN IF NOT EXISTS `modified_date` DATE DEFAULT NULL AFTER `post_date`,
  ADD COLUMN IF NOT EXISTS `sommario_it` TEXT DEFAULT NULL AFTER `excerpt_en`,
  ADD COLUMN IF NOT EXISTS `sommario_en` TEXT DEFAULT NULL AFTER `sommario_it`,
  ADD COLUMN IF NOT EXISTS `blocchi` MEDIUMTEXT DEFAULT NULL AFTER `body_en`,
  ADD COLUMN IF NOT EXISTS `cover_video_id` INT UNSIGNED DEFAULT NULL AFTER `cover_media_id`;

UPDATE `posts` p JOIN `post_categories` c ON c.nome_it = p.category_it
SET p.category_id = c.id
WHERE p.category_id IS NULL;

-- 4 -----------------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('magazine.autrice.bio', 'magazine', 'Box autrice — due righe', 'text',
 'Sono Gaia, wedding planner a Roma. Qui racconto quello che imparo organizzando matrimoni ed eventi.',
 'I’m Gaia, a wedding planner based in Rome. Here I share what I learn planning weddings and events.', 10)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group_name` = VALUES(`group_name`);

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'magazine.photos.cta', 'magazine', 'Foto — CTA finale del Magazine', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/banchetto-edificio-storico.jpg'), NULL, 30
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'magazine.photos.autrice', 'magazine', 'Foto — ritratto dell’autrice', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/planner-serra-tavoli.png'), NULL, 31
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

UPDATE `pages` SET
  `intro_it` = 'Idee, consigli e storie dal mondo dei matrimoni, scritti da Roma.',
  `intro_en` = 'Ideas, advice and stories from the world of weddings, written from Rome.'
WHERE `page_key` = 'blog' AND (`intro_it` IS NULL OR `intro_it` = '');
UPDATE `pages` SET `title_en` = 'Magazine' WHERE `page_key` = 'blog' AND (`title_en` IS NULL OR `title_en` = '');
