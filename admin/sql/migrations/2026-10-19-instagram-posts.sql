-- ---------------------------------------------------------------------
-- Instagram: post caricati a mano oggi, sincronizzati via API domani.
--
-- Ogni post è una foto della libreria (media: passa da ImageService come
-- ogni altra immagine, alt IT/EN su media). `source` distingue i post
-- inseriti da Gaia da quelli che arriveranno dalla sincronizzazione
-- (Instagram API with Instagram Login, vedi CLAUDE.md «Stato e prossimi
-- passi»); `ig_media_id` è l'id del post su Instagram, UNIQUE per non
-- importarlo due volte. `note_interne` resta nel back office e non viene
-- mai esportata.
--
-- Sostituisce `instagram_items` dello schema iniziale, mai usata: quella
-- resta dov'è (niente DROP qui) e va tolta insieme a `events` in fase 3.
--
-- Impostazioni: handle del profilo e quanti post mostrare in home.
-- IF NOT EXISTS / INSERT IGNORE: rilanciare è sicuro.
--
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `instagram_posts` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `media_id`     INT UNSIGNED NOT NULL,
  `permalink`    VARCHAR(500) DEFAULT NULL,
  `taken_at`     DATETIME DEFAULT NULL,
  `tipo`         ENUM('foto','reel','carosello') NOT NULL DEFAULT 'foto',
  `source`       ENUM('manuale','api') NOT NULL DEFAULT 'manuale',
  `ig_media_id`  VARCHAR(64) DEFAULT NULL,
  `nascosto`     TINYINT(1) NOT NULL DEFAULT 0,
  `note_interne` VARCHAR(500) DEFAULT NULL,
  `sort_order`   SMALLINT NOT NULL DEFAULT 0,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_instagram_posts_ig_media_id` (`ig_media_id`),
  KEY `idx_instagram_posts_visibili` (`nascosto`, `taken_at`, `sort_order`),
  KEY `fk_instagram_posts_media` (`media_id`),
  -- Se la foto viene eliminata dalla libreria, il post sparisce con lei.
  CONSTRAINT `fk_instagram_posts_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('instagram.handle',      'instagram', 'Instagram — nome del profilo (con la @)', 'text',   '@gaiacrognale_events', '@gaiacrognale_events', 1),
('instagram.numero_post', 'instagram', 'Instagram — quanti post in home',         'number', '8', '8', 2);
