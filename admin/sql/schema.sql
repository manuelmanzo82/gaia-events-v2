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
  -- Larghezza massima in pixel CSS a cui l'immagine resta nitida: e' la
  -- larghezza del file diviso 2, perche' su uno schermo retina servono due
  -- pixel di file per ogni pixel CSS. Calcolata all'upload da ImageService.
  `max_css_width`  INT UNSIGNED NOT NULL DEFAULT 0,
  -- Uso consentito, ricavato da max_css_width. Il sito non deve mai mostrare
  -- un'immagine piu' grande di cosi', lightbox compresa.
  `usage_tier`     ENUM('hero','gallery_large','card','thumbnail') NOT NULL DEFAULT 'thumbnail',
  `alt_it`         VARCHAR(255) DEFAULT NULL,
  `alt_en`         VARCHAR(255) DEFAULT NULL,
  `caption_it`     VARCHAR(500) DEFAULT NULL,
  `caption_en`     VARCHAR(500) DEFAULT NULL,
  -- Nota di servizio per il back office (es. "da sostituire con il file
  -- senza watermark prima del lancio"). Non viene mai esportata al sito.
  `note_interne`   VARCHAR(500) DEFAULT NULL,
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
  -- Slug degli altri servizi compresi in questo (migrazione 2026-10-20-servizi-include.sql).
  `include_slugs`       VARCHAR(255) DEFAULT NULL,
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

-- Colonne del preventivatore dalla migrazione 2026-10-20-preventivatore.sql.
CREATE TABLE IF NOT EXISTS `inquiries` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type`           ENUM('quote','contact') NOT NULL DEFAULT 'quote',
  `lang`           ENUM('it','en') NOT NULL DEFAULT 'it',
  `event_kind`     ENUM('matrimonio','unione_civile','festa','aziendale') DEFAULT NULL,
  `name`           VARCHAR(160) NOT NULL,
  `partner_name`   VARCHAR(160) DEFAULT NULL,
  `company`        VARCHAR(160) DEFAULT NULL,
  `email`          VARCHAR(190) NOT NULL,
  `phone`          VARCHAR(60) DEFAULT NULL,
  `contact_pref`   ENUM('email','whatsapp','telefono') DEFAULT NULL,
  `event_date`     DATE DEFAULT NULL,
  `date_precision` ENUM('giorno','mese','da_definire') DEFAULT NULL,
  `event_type`     VARCHAR(120) DEFAULT NULL,
  `zone_mode`      ENUM('zone','libero','consigliateci','location') DEFAULT NULL,
  `zones`          JSON DEFAULT NULL,
  `venue_name`     VARCHAR(190) DEFAULT NULL,
  `from_abroad`    TINYINT(1) NOT NULL DEFAULT 0,
  `guests`         SMALLINT UNSIGNED DEFAULT NULL,
  `guests_range`   VARCHAR(20) DEFAULT NULL,
  `services`       JSON DEFAULT NULL,
  `budget_range`   VARCHAR(30) DEFAULT NULL,
  `source`         VARCHAR(60) DEFAULT NULL,
  `message`        TEXT DEFAULT NULL,
  -- La lettera del preventivatore così come è stata inviata.
  `letter`         TEXT DEFAULT NULL,
  `privacy_at`     DATETIME DEFAULT NULL,
  -- Tutte le risposte grezze del preventivatore, con le etichette mostrate.
  `payload`        JSON DEFAULT NULL,
  `ip`             VARCHAR(45) DEFAULT NULL,
  `user_agent`     VARCHAR(255) DEFAULT NULL,
  -- nuova · contattata · chiusa
  `status`         ENUM('new','contacted','closed') NOT NULL DEFAULT 'new',
  `notes`          TEXT DEFAULT NULL,
  -- Esito della mail di notifica: la richiesta resta salvata anche se fallisce.
  `mail_sent_at`   DATETIME DEFAULT NULL,
  `mail_error`     VARCHAR(255) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
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

-- ---------------------------------------------------------------------
-- FASE 2 (16/10/2026): luoghi, location, highlights, partner, storie,
-- hero per pagina. Stesse definizioni della migrazione
-- migrations/2026-10-16-fase2-modello-dati.sql.
-- ---------------------------------------------------------------------

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
  -- Frase breve SULLA scheda impilata della home; senza, il sito usa l'alt.
  `title_it`   VARCHAR(190) DEFAULT NULL,
  `title_en`   VARCHAR(190) DEFAULT NULL,
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
  `section`      ENUM('home','chi-sono','servizi','destination','luogo','cta') NOT NULL,
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
  `event_type`              ENUM('matrimonio','unione_civile','festa','aziendale') NOT NULL DEFAULT 'matrimonio',
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

-- ---------------------------------------------------------------------
-- Instagram (19/10/2026): post caricati a mano, pronti per la
-- sincronizzazione via API. Stessa definizione della migrazione
-- migrations/2026-10-19-instagram-posts.sql (che aggiunge anche le
-- impostazioni instagram.handle e instagram.numero_post).
-- ---------------------------------------------------------------------

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

-- ---------------------------------------------------------------------
-- Dicono di me (giro 11): stampa e aziende clienti
-- (migrazione 2026-10-21-stampa-clienti.sql)
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `press` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `outlet`        VARCHAR(190) NOT NULL,
  `title`         VARCHAR(255) NOT NULL,
  `published_on`  DATE DEFAULT NULL,
  `url`           VARCHAR(500) DEFAULT NULL,
  `logo_media_id` INT UNSIGNED DEFAULT NULL,
  `excerpt_it`    TEXT DEFAULT NULL,
  `excerpt_en`    TEXT DEFAULT NULL,
  `active`        TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`    SMALLINT NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_press_active` (`active`, `sort_order`),
  KEY `fk_press_logo` (`logo_media_id`),
  CONSTRAINT `fk_press_logo` FOREIGN KEY (`logo_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `clients` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(190) NOT NULL,
  `logo_media_id` INT UNSIGNED DEFAULT NULL,
  `sector`        VARCHAR(120) DEFAULT NULL,
  `website`       VARCHAR(300) DEFAULT NULL,
  `active`        TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`    SMALLINT NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_clients_active` (`active`, `sort_order`),
  KEY `fk_clients_logo` (`logo_media_id`),
  CONSTRAINT `fk_clients_logo` FOREIGN KEY (`logo_media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
