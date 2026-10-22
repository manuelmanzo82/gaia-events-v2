-- ---------------------------------------------------------------------
-- Pagina Contatti (/contatti/ ↔ /en/contact/), 24/10/2026. Testi in
-- BOZZA. La pagina è la CTA: niente mappa, niente CtaFinale.
--
-- 1. settings.field_type «video»: un'impostazione che sceglie un video
--    dalla libreria (qui: il video di saluto di Gaia, oggi vuoto).
-- 2. videos.sottotitoli_it/en: tracce VTT caricabili dalla libreria.
-- 3. Pagina «contact»: titolo «Scriviamoci» / «Get in touch», riga, meta.
-- 4. Impostazioni del gruppo «contatti»: video, ritratto (107), tessere,
--    messaggio precompilato di WhatsApp, form, social.
-- Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- 1 -----------------------------------------------------------------------
ALTER TABLE `settings`
  MODIFY `field_type` ENUM('text','textarea','html','media','number','boolean','video') NOT NULL DEFAULT 'text';

-- 2 -----------------------------------------------------------------------
ALTER TABLE `videos`
  ADD COLUMN IF NOT EXISTS `sottotitoli_it` VARCHAR(400) DEFAULT NULL AFTER `file_webm`,
  ADD COLUMN IF NOT EXISTS `sottotitoli_en` VARCHAR(400) DEFAULT NULL AFTER `sottotitoli_it`;

-- 3 -----------------------------------------------------------------------
UPDATE `pages` SET
  `title_it` = 'Scriviamoci',
  `title_en` = 'Get in touch',
  `intro_it` = 'Una domanda, un''idea, una data in mente: scrivetemi come preferite. Rispondo entro 48 ore nei giorni feriali.',
  `intro_en` = 'A question, an idea, a date in mind: reach me however you like. I reply within 48 hours on weekdays.',
  `meta_title_it` = 'Contatti: scriviamoci',
  `meta_title_en` = 'Contact: get in touch',
  `meta_description_it` = 'Scrivete a Gaia Crognale, wedding & event planner a Roma: WhatsApp, telefono, email o un messaggio dal sito. Risposta entro 48 ore nei giorni feriali.',
  `meta_description_en` = 'Get in touch with Gaia Crognale, wedding & event planner in Rome: WhatsApp, phone, email or a message from the site. A reply within 48 hours on weekdays.'
WHERE `page_key` = 'contact' AND (`title_it` = 'Iniziamo a conoscerci' OR `title_it` IS NULL OR `title_it` = '');

-- 4 -----------------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('contatti.video',                     'contatti', 'Video di saluto di Gaia (dalla libreria Video; vuoto = ritratto)', 'video', NULL, NULL, 1),
('contatti.firma',                     'contatti', 'Firma sotto il ritratto',            'text',     'Gaia', 'Gaia', 3),
('contatti.tessera.conosciamoci.titolo','contatti', 'Tessera Conosciamoci — titolo',     'text',     'Conosciamoci', 'Let’s talk', 10),
('contatti.tessera.conosciamoci.testo', 'contatti', 'Tessera Conosciamoci — riga',       'text',     '3 minuti, senza impegno', '3 minutes, no obligation', 11),
('contatti.tessera.whatsapp.titolo',    'contatti', 'Tessera WhatsApp — titolo',         'text',     'WhatsApp', 'WhatsApp', 12),
('contatti.tessera.whatsapp.testo',     'contatti', 'Tessera WhatsApp — riga',           'text',     'Il modo più veloce per scrivermi', 'The quickest way to reach me', 13),
('contatti.whatsapp.messaggio',         'contatti', 'WhatsApp — messaggio precompilato', 'text',     'Ciao Gaia, ti scrivo dal sito: vorrei parlarti del mio evento.', 'Hi Gaia, I’m writing from your website: I’d love to talk about my event.', 14),
('contatti.tessera.telefono.titolo',    'contatti', 'Tessera Telefono — titolo',         'text',     'Telefono', 'Phone', 15),
('contatti.tessera.telefono.testo',     'contatti', 'Tessera Telefono — riga',           'text',     'Chiamatemi nei giorni feriali', 'Call me on weekdays', 16),
('contatti.tessera.email.titolo',       'contatti', 'Tessera Email — titolo',            'text',     'Email', 'Email', 17),
('contatti.form.titolo',                'contatti', 'Form — titolo',                     'text',     'Oppure lasciatemi un messaggio', 'Or leave me a message', 20),
('contatti.form.grazie',                'contatti', 'Form — ringraziamento',             'textarea', 'Grazie, il vostro messaggio è arrivato. Vi rispondo entro 48 ore nei giorni feriali.', 'Thank you, your message has arrived. I’ll reply within 48 hours on weekdays.', 21),
('contatti.social.titolo',              'contatti', 'Social — titolo',                   'text',     'Seguitemi', 'Follow me', 30)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group_name` = VALUES(`group_name`), `field_type` = VALUES(`field_type`);

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'contatti.photos.ritratto', 'contatti', 'Ritratto di Gaia (se non c’è il video)', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/planner-serra-tavoli.png'), NULL, 2
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
