-- ---------------------------------------------------------------------
-- Fase 2 — modello dati per home e nuove pagine (docs/DECISIONI-DESIGN).
--
-- Solo CREATE TABLE IF NOT EXISTS: niente ALTER, niente DROP, nessuna
-- operazione sui dati esistenti. La tabella `events` resta congelata:
-- il suo DROP arriverà in fase 3, in un commit dedicato, quando /storie
-- avrà preso il posto di /portfolio.
--
-- Nota: `media` ha già alt_it e alt_en dallo schema iniziale, quindi gli
-- highlights non duplicano l'alt (fonte unica sulla foto).
--
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Luoghi: gerarchia a due livelli, Italia → regione → città/zona.
-- parent_id NULL = regione. Slug unici globalmente (non per ramo):
-- semplificano rotte, sitemap e selettore di lingua.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `luoghi` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id`           INT UNSIGNED DEFAULT NULL,
  `tipo`                ENUM('regione','zona') NOT NULL DEFAULT 'zona',
  `nome_it`             VARCHAR(190) DEFAULT NULL,
  `nome_en`             VARCHAR(190) DEFAULT NULL,
  `slug_it`             VARCHAR(190) DEFAULT NULL,
  `slug_en`             VARCHAR(190) DEFAULT NULL,
  `intro_it`            TEXT DEFAULT NULL,
  `intro_en`            TEXT DEFAULT NULL,
  `testo_it`            MEDIUMTEXT DEFAULT NULL,
  `testo_en`            MEDIUMTEXT DEFAULT NULL,
  `cover_media_id`      INT UNSIGNED DEFAULT NULL,
  `meta_title_it`       VARCHAR(255) DEFAULT NULL,
  `meta_title_en`       VARCHAR(255) DEFAULT NULL,
  `meta_description_it` VARCHAR(400) DEFAULT NULL,
  `meta_description_en` VARCHAR(400) DEFAULT NULL,
  `active`              TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`          SMALLINT NOT NULL DEFAULT 0,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_luoghi_slug_it` (`slug_it`),
  UNIQUE KEY `uq_luoghi_slug_en` (`slug_en`),
  KEY `idx_luoghi_parent` (`parent_id`, `sort_order`),
  KEY `idx_luoghi_active` (`active`, `sort_order`),
  KEY `fk_luoghi_cover` (`cover_media_id`),
  -- RESTRICT: una regione con zone dentro non si cancella per sbaglio.
  CONSTRAINT `fk_luoghi_parent` FOREIGN KEY (`parent_id`) REFERENCES `luoghi` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_luoghi_cover` FOREIGN KEY (`cover_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foto generiche della zona: poche, le più belle.
CREATE TABLE IF NOT EXISTS `luogo_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `luogo_id`   INT UNSIGNED NOT NULL,
  `media_id`   INT UNSIGNED NOT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_luogo_images` (`luogo_id`, `media_id`),
  KEY `idx_luogo_images_order` (`luogo_id`, `sort_order`),
  KEY `fk_luogo_images_media` (`media_id`),
  CONSTRAINT `fk_luogo_images_luogo` FOREIGN KEY (`luogo_id`) REFERENCES `luoghi` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_luogo_images_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Location: elenco testuale per luogo, leggibile da Google e dalle AI.
-- RESTRICT sul luogo: cancellare una zona non fa sparire in silenzio le
-- sue location; il back office chiede di spostarle o eliminarle prima.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `locations` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome`           VARCHAR(190) NOT NULL,
  `tipo`           ENUM('villa','castello','chiesa','terrazza','tenuta','hotel','ristorante','altro') NOT NULL DEFAULT 'altro',
  `luogo_id`       INT UNSIGNED NOT NULL,
  `descrizione_it` TEXT DEFAULT NULL,
  `descrizione_en` TEXT DEFAULT NULL,
  `website`        VARCHAR(300) DEFAULT NULL,
  -- Per il futuro: una location molto cercata potrà avere una pagina sua.
  -- Oggi il flag non genera nulla.
  `own_page`       TINYINT(1) NOT NULL DEFAULT 0,
  `active`         TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`     SMALLINT NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_locations_luogo` (`luogo_id`, `active`, `sort_order`),
  CONSTRAINT `fk_locations_luogo` FOREIGN KEY (`luogo_id`) REFERENCES `luoghi` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Highlights: singole foto scelte da Gaia, per sezione. L'alt vive su
-- media (alt_it/alt_en), nessuna didascalia con credito: i crediti dei
-- fotografi stanno nella pagina Partner.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `highlights` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `media_id`   INT UNSIGNED NOT NULL,
  `luogo_id`   INT UNSIGNED DEFAULT NULL,
  `active`     TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_highlights_media` (`media_id`),
  KEY `fk_highlights_luogo` (`luogo_id`),
  CONSTRAINT `fk_highlights_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_highlights_luogo` FOREIGN KEY (`luogo_id`) REFERENCES `luoghi` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sezioni in cui la foto compare, con ordine PER sezione.
CREATE TABLE IF NOT EXISTS `highlight_sections` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `highlight_id` INT UNSIGNED NOT NULL,
  `section`      ENUM('home','chi-sono','servizi','destination','luogo') NOT NULL,
  `sort_order`   SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_highlight_sections` (`highlight_id`, `section`),
  KEY `idx_highlight_sections` (`section`, `sort_order`),
  CONSTRAINT `fk_highlight_sections_highlight` FOREIGN KEY (`highlight_id`) REFERENCES `highlights` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Partner: fotografi (crediti) e fornitori di fiducia.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `partners` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`           VARCHAR(190) NOT NULL,
  `category`       ENUM('fotografo','fiori','catering','musica','location','altro') NOT NULL DEFAULT 'altro',
  `logo_media_id`  INT UNSIGNED DEFAULT NULL,
  `description_it` TEXT DEFAULT NULL,
  `description_en` TEXT DEFAULT NULL,
  `website`        VARCHAR(300) DEFAULT NULL,
  `active`         TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`     SMALLINT NOT NULL DEFAULT 0,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_partners_active` (`active`, `sort_order`),
  KEY `fk_partners_logo` (`logo_media_id`),
  CONSTRAINT `fk_partners_logo` FOREIGN KEY (`logo_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Storie: 2-3 racconti completi, al posto degli eventi. Slug bilingue
-- come i post del blog. Il fotografo è un riferimento a partners.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `stories` (
  `id`                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug_it`                 VARCHAR(190) DEFAULT NULL,
  `slug_en`                 VARCHAR(190) DEFAULT NULL,
  `title_it`                VARCHAR(255) DEFAULT NULL,
  `title_en`                VARCHAR(255) DEFAULT NULL,
  `couple`                  VARCHAR(190) DEFAULT NULL,
  `location_name`           VARCHAR(255) DEFAULT NULL,
  `luogo_id`                INT UNSIGNED DEFAULT NULL,
  `story_date`              DATE DEFAULT NULL,
  `cover_media_id`          INT UNSIGNED DEFAULT NULL,
  `body_it`                 MEDIUMTEXT DEFAULT NULL,
  `body_en`                 MEDIUMTEXT DEFAULT NULL,
  `photographer_partner_id` INT UNSIGNED DEFAULT NULL,
  `meta_title_it`           VARCHAR(255) DEFAULT NULL,
  `meta_title_en`           VARCHAR(255) DEFAULT NULL,
  `meta_description_it`     VARCHAR(400) DEFAULT NULL,
  `meta_description_en`     VARCHAR(400) DEFAULT NULL,
  `active`                  TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`              SMALLINT NOT NULL DEFAULT 0,
  `created_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_stories_slug_it` (`slug_it`),
  UNIQUE KEY `uq_stories_slug_en` (`slug_en`),
  KEY `idx_stories_active` (`active`, `sort_order`),
  KEY `fk_stories_luogo` (`luogo_id`),
  KEY `fk_stories_cover` (`cover_media_id`),
  KEY `fk_stories_photographer` (`photographer_partner_id`),
  CONSTRAINT `fk_stories_luogo` FOREIGN KEY (`luogo_id`) REFERENCES `luoghi` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stories_cover` FOREIGN KEY (`cover_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_stories_photographer` FOREIGN KEY (`photographer_partner_id`) REFERENCES `partners` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `story_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `story_id`   INT UNSIGNED NOT NULL,
  `media_id`   INT UNSIGNED NOT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_story_images` (`story_id`, `media_id`),
  KEY `idx_story_images_order` (`story_id`, `sort_order`),
  KEY `fk_story_images_media` (`media_id`),
  CONSTRAINT `fk_story_images_story` FOREIGN KEY (`story_id`) REFERENCES `stories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_story_images_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Hero per pagina. Non nel k/v settings: le foto multiple ordinate ci
-- starebbero strette. I video sono file in uploads/video/ caricati dal
-- back office (su IONOS non c'è ffmpeg: MP4, WebM e poster arrivano già
-- pronti, max 8 MB l'uno per i video). Il poster passa da ImageService
-- come ogni altra immagine ed è l'elemento LCP: il video non entra mai
-- nel caricamento iniziale. video_url (esterno) per un futuro Vimeo.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `page_hero` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_key`        VARCHAR(60) NOT NULL,
  `title_it`        VARCHAR(255) DEFAULT NULL,
  `title_en`        VARCHAR(255) DEFAULT NULL,
  `subtitle_it`     VARCHAR(255) DEFAULT NULL,
  `subtitle_en`     VARCHAR(255) DEFAULT NULL,
  `cta_label_it`    VARCHAR(190) DEFAULT NULL,
  `cta_label_en`    VARCHAR(190) DEFAULT NULL,
  `cta_route`       VARCHAR(60) DEFAULT NULL,
  `video_path`      VARCHAR(400) DEFAULT NULL,
  `video_webm_path` VARCHAR(400) DEFAULT NULL,
  `poster_media_id` INT UNSIGNED DEFAULT NULL,
  `video_url`       VARCHAR(500) DEFAULT NULL,
  `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_page_hero_key` (`page_key`),
  KEY `fk_page_hero_poster` (`poster_media_id`),
  CONSTRAINT `fk_page_hero_poster` FOREIGN KEY (`poster_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foto hero: su mobile ruotano in dissolvenza, su desktop sono il
-- fallback quando il video manca o la connessione è lenta.
CREATE TABLE IF NOT EXISTS `page_hero_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `hero_id`    INT UNSIGNED NOT NULL,
  `media_id`   INT UNSIGNED NOT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_page_hero_images` (`hero_id`, `media_id`),
  KEY `idx_page_hero_images_order` (`hero_id`, `sort_order`),
  KEY `fk_page_hero_images_media` (`media_id`),
  CONSTRAINT `fk_page_hero_images_hero` FOREIGN KEY (`hero_id`) REFERENCES `page_hero` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_page_hero_images_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
