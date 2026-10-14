-- ---------------------------------------------------------------------
-- Gaia Events v2 — schema del back office
--
-- MySQL 8 / MariaDB 10.4+, utf8mb4.
-- Ogni campo di testo esiste in due colonne: *_it e *_en. Se la colonna
-- _en è NULL o vuota, la pagina inglese non viene generata dal sito.
--
-- Caricamento:
--   mysql -u UTENTE -p NOME_DB < schema.sql
--
-- Nessun dato reale qui dentro: i contenuti di esempio stanno in
-- seed.example.sql.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;
SET time_zone = '+01:00';

-- ---------------------------------------------------------------------
-- Utenti e accessi
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`         VARCHAR(190) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `name`          VARCHAR(120) NOT NULL,
  -- admin: tutto, incluse impostazioni tecniche e pubblicazione.
  -- editor: solo contenuti.
  `role`          ENUM('admin','editor') NOT NULL DEFAULT 'editor',
  `active`        TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME DEFAULT NULL,
  `last_login_ip` VARCHAR(45) DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_admin_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tentativi falliti, per il throttling del login.
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email`        VARCHAR(190) NOT NULL DEFAULT '',
  `ip`           VARCHAR(45) NOT NULL DEFAULT '',
  `attempted_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_attempts_email` (`email`, `attempted_at`),
  KEY `idx_login_attempts_ip` (`ip`, `attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Immagini
--
-- `path` è relativo alla cartella uploads/ e viene servito dal sito come
-- /uploads/{path}. Le varianti stanno accanto all'originale con il nome
-- {base}-{larghezza}.{webp|avif}: has_webp e has_avif dicono quali
-- esistono davvero, perché l'AVIF dipende dal supporto del server.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `media` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `path`           VARCHAR(400) NOT NULL,
  `width`          INT UNSIGNED NOT NULL DEFAULT 0,
  `height`         INT UNSIGNED NOT NULL DEFAULT 0,
  `bytes`          INT UNSIGNED NOT NULL DEFAULT 0,
  `variant_widths` JSON DEFAULT NULL,
  `has_webp`       TINYINT(1) NOT NULL DEFAULT 0,
  `has_avif`       TINYINT(1) NOT NULL DEFAULT 0,
  -- Punto focale 0–1, per il crop via CSS quando il contenitore ha un
  -- rapporto diverso da quello della foto.
  `focal_x`        DECIMAL(4,3) NOT NULL DEFAULT 0.500,
  `focal_y`        DECIMAL(4,3) NOT NULL DEFAULT 0.500,
  `alt_it`         VARCHAR(255) DEFAULT NULL,
  `alt_en`         VARCHAR(255) DEFAULT NULL,
  `caption_it`     VARCHAR(500) DEFAULT NULL,
  `caption_en`     VARCHAR(500) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_media_path` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Impostazioni: chiave/valore bilingue
--
-- Sostituisce il singleton da ~400 righe di schema che c'era su Sanity.
-- `group_name` raggruppa i campi nelle schermate del back office,
-- `field_type` dice al form se mostrare input, textarea o editor HTML.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `settings` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(190) NOT NULL,
  `group_name` VARCHAR(80) NOT NULL DEFAULT 'generale',
  `label`      VARCHAR(190) NOT NULL DEFAULT '',
  `field_type` ENUM('text','textarea','html','media','number','boolean') NOT NULL DEFAULT 'text',
  `value_it`   TEXT DEFAULT NULL,
  `value_en`   TEXT DEFAULT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`group_name`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pagine statiche
--
-- `page_key` corrisponde a una chiave di ROUTES in site/src/i18n/routes.ts:
-- about, services, portfolio, reviews, blog, faq, quote, contact,
-- destinationWedding, privacy, cookie.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `pages` (
  `id`                   INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_key`             VARCHAR(60) NOT NULL,
  `title_it`             VARCHAR(255) DEFAULT NULL,
  `title_en`             VARCHAR(255) DEFAULT NULL,
  `intro_it`             TEXT DEFAULT NULL,
  `intro_en`             TEXT DEFAULT NULL,
  `body_it`              MEDIUMTEXT DEFAULT NULL,
  `body_en`              MEDIUMTEXT DEFAULT NULL,
  `meta_title_it`        VARCHAR(255) DEFAULT NULL,
  `meta_title_en`        VARCHAR(255) DEFAULT NULL,
  `meta_description_it`  VARCHAR(400) DEFAULT NULL,
  `meta_description_en`  VARCHAR(400) DEFAULT NULL,
  `hero_media_id`        INT UNSIGNED DEFAULT NULL,
  `og_media_id`          INT UNSIGNED DEFAULT NULL,
  `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pages_key` (`page_key`),
  KEY `fk_pages_hero` (`hero_media_id`),
  KEY `fk_pages_og` (`og_media_id`),
  CONSTRAINT `fk_pages_hero` FOREIGN KEY (`hero_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_pages_og` FOREIGN KEY (`og_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blocchi ripetibili di una pagina: servono alla destination wedding
-- (servizi, destinazioni, processo, FAQ, testimonial, CTA) e alla pagina
-- chi sono (timeline del percorso e valori).
CREATE TABLE IF NOT EXISTS `page_sections` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `page_id`    INT UNSIGNED NOT NULL,
  `kind`       ENUM('intro','services','destinations','process','faq','testimonial','cta','timeline','values') NOT NULL,
  `title_it`   VARCHAR(255) DEFAULT NULL,
  `title_en`   VARCHAR(255) DEFAULT NULL,
  `text_it`    TEXT DEFAULT NULL,
  `text_en`    TEXT DEFAULT NULL,
  -- Elenchi di voci {title, text}: una riga per lingua, validata dal back office.
  `items_it`   JSON DEFAULT NULL,
  `items_en`   JSON DEFAULT NULL,
  `media_id`   INT UNSIGNED DEFAULT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_page_sections_page` (`page_id`, `sort_order`),
  KEY `fk_page_sections_media` (`media_id`),
  CONSTRAINT `fk_page_sections_page` FOREIGN KEY (`page_id`) REFERENCES `pages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_page_sections_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Servizi
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `services` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`                VARCHAR(120) NOT NULL,
  `title_it`            VARCHAR(255) DEFAULT NULL,
  `title_en`            VARCHAR(255) DEFAULT NULL,
  `subtitle_it`         VARCHAR(255) DEFAULT NULL,
  `subtitle_en`         VARCHAR(255) DEFAULT NULL,
  `description_it`      TEXT DEFAULT NULL,
  `description_en`      TEXT DEFAULT NULL,
  -- Elenco di stringhe.
  `features_it`         JSON DEFAULT NULL,
  `features_en`         JSON DEFAULT NULL,
  `icon`                VARCHAR(60) DEFAULT NULL,
  `media_id`            INT UNSIGNED DEFAULT NULL,
  `meta_title_it`       VARCHAR(255) DEFAULT NULL,
  `meta_title_en`       VARCHAR(255) DEFAULT NULL,
  `meta_description_it` VARCHAR(400) DEFAULT NULL,
  `meta_description_en` VARCHAR(400) DEFAULT NULL,
  `active`              TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`          SMALLINT NOT NULL DEFAULT 0,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_services_slug` (`slug`),
  KEY `idx_services_active` (`active`, `sort_order`),
  KEY `fk_services_media` (`media_id`),
  CONSTRAINT `fk_services_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Eventi (portfolio)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `events` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`                VARCHAR(160) NOT NULL,
  `macro_category`      ENUM('wedding','corporate','celebrations') NOT NULL DEFAULT 'wedding',
  `title_it`            VARCHAR(255) DEFAULT NULL,
  `title_en`            VARCHAR(255) DEFAULT NULL,
  `location_it`         VARCHAR(255) DEFAULT NULL,
  `location_en`         VARCHAR(255) DEFAULT NULL,
  `event_date`          DATE DEFAULT NULL,
  `category_it`         VARCHAR(120) DEFAULT NULL,
  `category_en`         VARCHAR(120) DEFAULT NULL,
  `excerpt_it`          TEXT DEFAULT NULL,
  `excerpt_en`          TEXT DEFAULT NULL,
  `description_it`      MEDIUMTEXT DEFAULT NULL,
  `description_en`      MEDIUMTEXT DEFAULT NULL,
  `cover_media_id`      INT UNSIGNED DEFAULT NULL,
  `hero_media_id`       INT UNSIGNED DEFAULT NULL,
  -- URL YouTube o Vimeo; il CSP del sito consente solo questi due domini.
  `video_url`           VARCHAR(500) DEFAULT NULL,
  `meta_title_it`       VARCHAR(255) DEFAULT NULL,
  `meta_title_en`       VARCHAR(255) DEFAULT NULL,
  `meta_description_it` VARCHAR(400) DEFAULT NULL,
  `meta_description_en` VARCHAR(400) DEFAULT NULL,
  `featured`            TINYINT(1) NOT NULL DEFAULT 0,
  `active`              TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`          SMALLINT NOT NULL DEFAULT 0,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_events_slug` (`slug`),
  KEY `idx_events_active` (`active`, `sort_order`),
  KEY `idx_events_featured` (`featured`, `sort_order`),
  KEY `fk_events_cover` (`cover_media_id`),
  KEY `fk_events_hero` (`hero_media_id`),
  CONSTRAINT `fk_events_cover` FOREIGN KEY (`cover_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_events_hero` FOREIGN KEY (`hero_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Galleria di un evento: l'ordine è deciso da Gaia nel back office.
CREATE TABLE IF NOT EXISTS `event_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id`   INT UNSIGNED NOT NULL,
  `media_id`   INT UNSIGNED NOT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_images` (`event_id`, `media_id`),
  KEY `idx_event_images_order` (`event_id`, `sort_order`),
  KEY `fk_event_images_media` (`media_id`),
  CONSTRAINT `fk_event_images_event` FOREIGN KEY (`event_id`) REFERENCES `events` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_event_images_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Recensioni
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reviews` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `author`        VARCHAR(160) NOT NULL,
  `event_name_it` VARCHAR(255) DEFAULT NULL,
  `event_name_en` VARCHAR(255) DEFAULT NULL,
  `review_date`   DATE DEFAULT NULL,
  `text_it`       TEXT DEFAULT NULL,
  `text_en`       TEXT DEFAULT NULL,
  `rating`        TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `media_id`      INT UNSIGNED DEFAULT NULL,
  `featured`      TINYINT(1) NOT NULL DEFAULT 0,
  `active`        TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`    SMALLINT NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_active` (`active`, `sort_order`),
  KEY `fk_reviews_media` (`media_id`),
  CONSTRAINT `fk_reviews_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Blog
--
-- Lo slug è bilingue: /blog/titolo-it e /en/blog/title-en.
-- Fonte unica, a differenza della v1 dove indice e articoli leggevano
-- due sorgenti diverse e un articolo nuovo restava senza pagina.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `posts` (
  `id`                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug_it`             VARCHAR(190) DEFAULT NULL,
  `slug_en`             VARCHAR(190) DEFAULT NULL,
  `title_it`            VARCHAR(255) DEFAULT NULL,
  `title_en`            VARCHAR(255) DEFAULT NULL,
  `excerpt_it`          TEXT DEFAULT NULL,
  `excerpt_en`          TEXT DEFAULT NULL,
  `category_it`         VARCHAR(120) DEFAULT NULL,
  `category_en`         VARCHAR(120) DEFAULT NULL,
  `post_date`           DATE NOT NULL,
  `reading_time`        TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `cover_media_id`      INT UNSIGNED DEFAULT NULL,
  `body_it`             MEDIUMTEXT DEFAULT NULL,
  `body_en`             MEDIUMTEXT DEFAULT NULL,
  `meta_title_it`       VARCHAR(255) DEFAULT NULL,
  `meta_title_en`       VARCHAR(255) DEFAULT NULL,
  `meta_description_it` VARCHAR(400) DEFAULT NULL,
  `meta_description_en` VARCHAR(400) DEFAULT NULL,
  `active`              TINYINT(1) NOT NULL DEFAULT 1,
  `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug_it` (`slug_it`),
  UNIQUE KEY `uq_posts_slug_en` (`slug_en`),
  KEY `idx_posts_active` (`active`, `post_date`),
  KEY `fk_posts_cover` (`cover_media_id`),
  CONSTRAINT `fk_posts_cover` FOREIGN KEY (`cover_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- FAQ
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `faqs` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `question_it` VARCHAR(500) DEFAULT NULL,
  `question_en` VARCHAR(500) DEFAULT NULL,
  `answer_it`   TEXT DEFAULT NULL,
  `answer_en`   TEXT DEFAULT NULL,
  `group_name`  VARCHAR(80) NOT NULL DEFAULT 'generale',
  `active`      TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`  SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_faqs_active` (`active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Richieste dal sito: preventivi e contatti
--
-- Sostituisce Web3Forms. Contiene dati personali: non va mai in un dump
-- committato in repo.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `inquiries` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`       ENUM('quote','contact') NOT NULL DEFAULT 'quote',
  `lang`       ENUM('it','en') NOT NULL DEFAULT 'it',
  `name`       VARCHAR(160) NOT NULL,
  `email`      VARCHAR(190) NOT NULL,
  `phone`      VARCHAR(60) DEFAULT NULL,
  `event_date` DATE DEFAULT NULL,
  `event_type` VARCHAR(120) DEFAULT NULL,
  `guests`     SMALLINT UNSIGNED DEFAULT NULL,
  `message`    TEXT DEFAULT NULL,
  -- Risposte del preventivatore oltre ai campi fissi.
  `payload`    JSON DEFAULT NULL,
  `ip`         VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `status`     ENUM('new','read','answered','archived') NOT NULL DEFAULT 'new',
  `notes`      TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inquiries_status` (`status`, `created_at`),
  KEY `idx_inquiries_type` (`type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Instagram (feed automatico, più avanti)
--
-- Le immagini vengono scaricate in uploads/ e referenziate via media_id:
-- così il sito non dipende dalla CDN di Instagram e il CSP resta chiuso.
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `instagram_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `ig_id`      VARCHAR(80) NOT NULL,
  `media_type` ENUM('image','video') NOT NULL DEFAULT 'image',
  `media_id`   INT UNSIGNED DEFAULT NULL,
  `caption_it` TEXT DEFAULT NULL,
  `caption_en` TEXT DEFAULT NULL,
  `permalink`  VARCHAR(500) NOT NULL,
  `posted_at`  DATETIME DEFAULT NULL,
  `active`     TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  `synced_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_instagram_ig_id` (`ig_id`),
  KEY `fk_instagram_media` (`media_id`),
  CONSTRAINT `fk_instagram_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Registro delle pubblicazioni
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `publish_log` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED DEFAULT NULL,
  `status`      ENUM('requested','accepted','failed') NOT NULL DEFAULT 'requested',
  `http_status` SMALLINT DEFAULT NULL,
  `message`     VARCHAR(500) DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_publish_log_user` (`user_id`),
  CONSTRAINT `fk_publish_log_user` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
