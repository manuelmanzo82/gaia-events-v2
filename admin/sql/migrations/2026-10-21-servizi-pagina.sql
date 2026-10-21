-- ---------------------------------------------------------------------
-- Pagina Servizi (/servizi/ ↔ /en/services/), 21/10/2026.
--
-- 1. services.page_media_id / page_media2_id: foto grande e piccola del
--    capitolo nella pagina (la foto della card in home resta media_id).
--    Per la destination la grande è la fascia a tutta larghezza.
-- 2. page_hero «services»: titolo, frase e foto dell'apertura (57: il
--    consenso degli sposi è bloccante per il lancio, DA-CHIEDERE n. 14).
-- 3. page_sections della pagina: «intro» (filosofia), «process» (i 5
--    passi del metodo, con la foto per voce nella chiave media), «cta»
--    (non solo matrimoni, foto in media_id). Testi in BOZZA, da Gaia.
-- 4. Impostazioni del gruppo «servizi»: titoli di sezione e foto della
--    CTA finale.
-- 5. partners.consenso_citazione: la sezione Partner mostra solo chi ha
--    dato il consenso.
-- 6. FAQ della pagina: gruppo «servizi» per le sei scelte.
-- 7. pages.services: meta title e description in bozza, se vuoti.
-- Foto collegate per percorso del file.
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- 1 -----------------------------------------------------------------------
ALTER TABLE `services`
  ADD COLUMN IF NOT EXISTS `page_media_id` INT UNSIGNED DEFAULT NULL AFTER `media_id`,
  ADD COLUMN IF NOT EXISTS `page_media2_id` INT UNSIGNED DEFAULT NULL AFTER `page_media_id`;

UPDATE `services` s SET
  s.page_media_id  = (SELECT id FROM `media` WHERE path = CASE s.slug
      WHEN 'planning' THEN 'foto/tavolo-viale-alberato-luci.jpg'
      WHEN 'coordinamento' THEN 'foto/altare-cerimonia-candele.jpg'
      WHEN 'design' THEN 'foto/tavolo-lungo-gipsofila-candele.jpg'
      WHEN 'destination' THEN 'foto/cerimonia-prato-vista-mare.jpg' END),
  s.page_media2_id = (SELECT id FROM `media` WHERE path = CASE s.slug
      WHEN 'planning' THEN 'foto/bouquet-poltrona-velluto.jpg'
      WHEN 'coordinamento' THEN 'foto/portale-chiesa-fiori.jpg'
      WHEN 'design' THEN 'foto/tavolo-fiori-viola-luci.jpg' END)
WHERE s.slug IN ('planning', 'coordinamento', 'design', 'destination') AND s.page_media_id IS NULL;

-- 2 -----------------------------------------------------------------------
INSERT INTO `page_hero` (`page_key`, `title_it`, `title_en`, `subtitle_it`, `subtitle_en`)
VALUES ('services', 'Come posso aiutarvi', 'How I can help',
        'Dalla prima idea all''ultimo ballo, oppure solo per il giorno più importante.',
        'From the first idea to the last dance, or just for the day that matters most.')
ON DUPLICATE KEY UPDATE `page_key` = `page_key`;

INSERT IGNORE INTO `page_hero_images` (`hero_id`, `media_id`, `sort_order`)
SELECT h.id, m.id, 1 FROM `page_hero` h JOIN `media` m ON m.path = 'foto/sposi-fronte-luci-notte.jpg'
WHERE h.page_key = 'services';

-- 3 -----------------------------------------------------------------------
INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `text_it`, `text_en`, `sort_order`)
SELECT p.id, 'intro', 'Quanto affidarmi lo decidete voi.', 'How much you hand over is up to you.',
  'Tutto, dalla prima idea all''ultimo ballo. Una parte, quella che vi pesa di più. Oppure solo il giorno, perché ve lo godiate senza pensieri.',
  'All of it, from the first idea to the last dance. A part, the one that weighs on you most. Or just the day itself, so you can enjoy it without a care.',
  1
FROM `pages` p WHERE p.page_key = 'services'
  AND NOT EXISTS (SELECT 1 FROM `page_sections` x WHERE x.page_id = p.id AND x.kind = 'intro');

INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `items_it`, `items_en`, `sort_order`)
SELECT p.id, 'process', 'Come lavoro', 'How I work',
  JSON_ARRAY(
    JSON_OBJECT('title', 'La consulenza', 'text', 'Ci conosciamo, a Roma o in videochiamata: mi raccontate come immaginate il vostro giorno e capiamo insieme di cosa avete bisogno.', 'media', (SELECT id FROM `media` WHERE path = 'foto/arco-edera-cortile-tavoli.jpg')),
    JSON_OBJECT('title', 'La proposta', 'text', 'Vi mando una proposta su misura: il servizio, i tempi, le prime idee e un preventivo chiaro, senza sorprese.', 'media', (SELECT id FROM `media` WHERE path = 'foto/scalinata-villa-tavoli-tramonto.jpg')),
    JSON_OBJECT('title', 'La progettazione', 'text', 'Location, fornitori, allestimenti, timeline: costruiamo tutto una scelta alla volta, e vi tengo aggiornati a ogni passo.', 'media', (SELECT id FROM `media` WHERE path = 'foto/navata-chiesa-gipsofila.jpg')),
    JSON_OBJECT('title', 'Il giorno', 'text', 'Sono lì dal montaggio all''ultimo ospite: coordino fornitori e tempi e risolvo gli imprevisti prima che ve ne accorgiate.', 'media', (SELECT id FROM `media` WHERE path = 'foto/uscita-chiesa-arco-fiori.jpg')),
    JSON_OBJECT('title', 'Dopo il giorno', 'text', 'Chiudo i conti con i fornitori, faccio riportare a casa quello che è vostro e vi lascio solo i ricordi.', 'media', (SELECT id FROM `media` WHERE path = 'foto/brindisi-sposi-tramonto.jpg'))
  ),
  JSON_ARRAY(
    JSON_OBJECT('title', 'The consultation', 'text', 'We meet, in Rome or on a video call: you tell me how you picture your day and together we work out what you need.'),
    JSON_OBJECT('title', 'The proposal', 'text', 'I send you a tailored proposal: the service, the timings, the first ideas and a clear quote, with no surprises.'),
    JSON_OBJECT('title', 'The planning', 'text', 'Venue, suppliers, styling, timeline: we build it one choice at a time, and I keep you updated at every step.'),
    JSON_OBJECT('title', 'The day', 'text', 'I am there from set-up to the last guest: I coordinate suppliers and timings and solve the unexpected before you notice.'),
    JSON_OBJECT('title', 'After the day', 'text', 'I settle the accounts with the suppliers, make sure what is yours goes home, and leave you only the memories.')
  ),
  2
FROM `pages` p WHERE p.page_key = 'services'
  AND NOT EXISTS (SELECT 1 FROM `page_sections` x WHERE x.page_id = p.id AND x.kind = 'process');

INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `text_it`, `text_en`, `media_id`, `sort_order`)
SELECT p.id, 'cta', 'Non solo matrimoni', 'Not only weddings',
  'Feste private, anniversari ed eventi aziendali: stessa cura, stesso metodo.',
  'Private parties, anniversaries and corporate events: the same care, the same method.',
  (SELECT id FROM `media` WHERE path = 'foto/cantante-scalinata-villa.jpg'),
  3
FROM `pages` p WHERE p.page_key = 'services'
  AND NOT EXISTS (SELECT 1 FROM `page_sections` x WHERE x.page_id = p.id AND x.kind = 'cta');

-- 4 -----------------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('servizi.partners.title', 'servizi', 'Partner — titolo',             'text', 'I partner con cui lavoro', 'The partners I work with', 20),
('servizi.faq.title',      'servizi', 'Domande frequenti — titolo',   'text', 'Domande frequenti', 'Frequently asked questions', 21)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group_name` = VALUES(`group_name`);

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'servizi.photos.cta', 'servizi', 'Foto — CTA finale di Servizi', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/sposa-bouquet-sguardo.jpg'), NULL, 30
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

-- 5 -----------------------------------------------------------------------
ALTER TABLE `partners`
  ADD COLUMN IF NOT EXISTS `consenso_citazione` TINYINT(1) NOT NULL DEFAULT 0 AFTER `active`;

-- 6 -----------------------------------------------------------------------
UPDATE `faqs` SET `group_name` = 'servizi'
WHERE `question_it` IN (
  'Come funziona la consulenza iniziale?',
  'Posso scegliere solo alcuni servizi?',
  'Ti occupi anche della scelta dei fornitori?',
  'Quanto costa un wedding planner?',
  'Come funziona il pagamento?',
  'Organizzi matrimoni all''estero?'
);

-- 7 -----------------------------------------------------------------------
UPDATE `pages` SET `meta_title_it` = 'Servizi: wedding planning, coordinamento e design a Roma'
WHERE `page_key` = 'services' AND (`meta_title_it` IS NULL OR `meta_title_it` = '');
UPDATE `pages` SET `meta_title_en` = 'Services: wedding planning, coordination and design in Rome'
WHERE `page_key` = 'services' AND (`meta_title_en` IS NULL OR `meta_title_en` = '');
UPDATE `pages` SET `meta_description_it` = 'Wedding planning completo, coordinamento del giorno, design e destination wedding: scegliete quanto affidarmi, dalla prima idea all''ultimo ballo. Roma, tutta Italia e all''estero.'
WHERE `page_key` = 'services' AND (`meta_description_it` IS NULL OR `meta_description_it` = '');
UPDATE `pages` SET `meta_description_en` = 'Full wedding planning, day-of coordination, design and destination weddings: choose how much to hand over, from the first idea to the last dance. Rome, across Italy and abroad.'
WHERE `page_key` = 'services' AND (`meta_description_en` IS NULL OR `meta_description_en` = '');
