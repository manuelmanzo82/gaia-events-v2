-- ---------------------------------------------------------------------
-- Pagina «Dicono di me» (giro 11): due entità nuove.
--
-- press    — Stampa: testata, titolo, data, link, logo opzionale,
--            estratto IT/EN. Un articolo o un'intervista che parla di Gaia.
-- clients  — Hanno scelto Gaia: aziende clienti (nome, logo, settore,
--            ordine, pubblicato) per la fila di loghi. Solo la struttura:
--            i loghi (Calzedonia, BNL, Zurich citati nella bio) si caricano
--            quando Gaia li manda con il permesso di usarli
--            (DA-CHIEDERE-A-GAIA n. 11).
--
-- La pagina e la voce di menu esistono solo se almeno uno dei tre blocchi
-- (recensioni, stampa, clienti) ha contenuti pubblicati.
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

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
