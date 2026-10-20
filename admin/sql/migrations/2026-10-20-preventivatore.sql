-- ---------------------------------------------------------------------
-- Preventivatore (giro 8).
--
-- 1. inquiries: le risposte del nuovo modulo in colonne proprie (tipo di
--    evento, nomi, quando, dove, ospiti, servizi, budget, contatto
--    preferito, come mi avete conosciuta), la lettera così come è stata
--    inviata, il consenso privacy e l'esito della mail di notifica. Tutte
--    le risposte grezze restano anche in `payload`.
-- 2. stati: nuova · contattata · chiusa (new, contacted, closed). I
--    vecchi valori si convertono: read → new, answered → contacted,
--    archived → closed.
-- 3. impostazioni del preventivo (gruppo «preventivo»): testi della
--    pagina, tre rassicurazioni, tre domande frequenti, le ore di risposta,
--    la foto di ogni passaggio e la copia della lettera alla coppia
--    (spenta). Le foto si collegano per percorso del file, non per id,
--    così la migrazione vale su qualunque database.
--
-- Idempotente: ADD COLUMN IF NOT EXISTS, INSERT … ON DUPLICATE KEY.
-- Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

ALTER TABLE `inquiries`
  ADD COLUMN IF NOT EXISTS `event_kind` ENUM('matrimonio','unione_civile','festa','aziendale') DEFAULT NULL AFTER `lang`,
  ADD COLUMN IF NOT EXISTS `partner_name` VARCHAR(160) DEFAULT NULL AFTER `name`,
  ADD COLUMN IF NOT EXISTS `company` VARCHAR(160) DEFAULT NULL AFTER `partner_name`,
  ADD COLUMN IF NOT EXISTS `contact_pref` ENUM('email','whatsapp','telefono') DEFAULT NULL AFTER `phone`,
  ADD COLUMN IF NOT EXISTS `date_precision` ENUM('giorno','mese','da_definire') DEFAULT NULL AFTER `event_date`,
  ADD COLUMN IF NOT EXISTS `zone_mode` ENUM('zone','libero','consigliateci','location') DEFAULT NULL AFTER `event_type`,
  ADD COLUMN IF NOT EXISTS `zones` JSON DEFAULT NULL AFTER `zone_mode`,
  ADD COLUMN IF NOT EXISTS `venue_name` VARCHAR(190) DEFAULT NULL AFTER `zones`,
  ADD COLUMN IF NOT EXISTS `from_abroad` TINYINT(1) NOT NULL DEFAULT 0 AFTER `venue_name`,
  ADD COLUMN IF NOT EXISTS `guests_range` VARCHAR(20) DEFAULT NULL AFTER `guests`,
  ADD COLUMN IF NOT EXISTS `services` JSON DEFAULT NULL AFTER `guests_range`,
  ADD COLUMN IF NOT EXISTS `budget_range` VARCHAR(30) DEFAULT NULL AFTER `services`,
  ADD COLUMN IF NOT EXISTS `source` VARCHAR(60) DEFAULT NULL AFTER `budget_range`,
  ADD COLUMN IF NOT EXISTS `letter` TEXT DEFAULT NULL AFTER `message`,
  ADD COLUMN IF NOT EXISTS `privacy_at` DATETIME DEFAULT NULL AFTER `letter`,
  ADD COLUMN IF NOT EXISTS `mail_sent_at` DATETIME DEFAULT NULL AFTER `notes`,
  ADD COLUMN IF NOT EXISTS `mail_error` VARCHAR(255) DEFAULT NULL AFTER `mail_sent_at`;

ALTER TABLE `inquiries`
  MODIFY `status` ENUM('new','read','answered','archived','contacted','closed') NOT NULL DEFAULT 'new';

UPDATE `inquiries` SET `status` = 'new' WHERE `status` = 'read';
UPDATE `inquiries` SET `status` = 'contacted' WHERE `status` = 'answered';
UPDATE `inquiries` SET `status` = 'closed' WHERE `status` = 'archived';

ALTER TABLE `inquiries`
  MODIFY `status` ENUM('new','contacted','closed') NOT NULL DEFAULT 'new';

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('quote.eyebrow',            'preventivo', 'Preventivo — sovratitolo',              'text',     'Preventivo', 'Enquiry', 1),
('quote.title',              'preventivo', 'Preventivo — titolo',                   'text',     'Raccontatemi il vostro giorno', 'Tell me about your day', 2),
('quote.line',               'preventivo', 'Preventivo — la riga di Gaia',          'textarea', 'Ogni grande giorno inizia da una conversazione.', 'Every beautiful day begins with a conversation.', 3),
('quote.signature',          'preventivo', 'Preventivo — firma',                    'text',     'Gaia', 'Gaia', 4),
('quote.replyHours',         'preventivo', 'Ore entro cui Gaia risponde',           'number',   '48', '48', 5),
('quote.assurance1.title',   'preventivo', 'Rassicurazione 1 — titolo',             'text',     'Risposta entro 48 ore', 'A reply within 48 hours', 10),
('quote.assurance1.text',    'preventivo', 'Rassicurazione 1 — testo',              'textarea', 'Leggo ogni richiesta di persona e vi rispondo entro due giorni.', 'I read every enquiry myself and reply within two days.', 11),
('quote.assurance2.title',   'preventivo', 'Rassicurazione 2 — titolo',             'text',     'Primo colloquio gratuito', 'A free first meeting', 12),
('quote.assurance2.text',    'preventivo', 'Rassicurazione 2 — testo',              'textarea', 'Ci conosciamo a Roma o in videochiamata, senza costi.', 'We meet in Rome or on a video call, at no cost.', 13),
('quote.assurance3.title',   'preventivo', 'Rassicurazione 3 — titolo',             'text',     'Nessun impegno', 'No commitment', 14),
('quote.assurance3.text',    'preventivo', 'Rassicurazione 3 — testo',              'textarea', 'Inviare la richiesta non vi vincola a nulla.', 'Sending your enquiry does not commit you to anything.', 15),
('quote.faq1.question',      'preventivo', 'Domanda frequente 1',                   'text',     'Quanto costa il primo incontro?', 'How much does the first meeting cost?', 20),
('quote.faq1.answer',        'preventivo', 'Domanda frequente 1 — risposta',       'textarea', 'Niente: il primo colloquio, a Roma o in videochiamata, serve a conoscerci e a capire che cosa sognate. Il preventivo arriva dopo, su misura.', 'Nothing: the first meeting, in Rome or on a video call, is for getting to know each other and understanding what you dream of. The quote comes afterwards, tailored to you.', 21),
('quote.faq2.question',      'preventivo', 'Domanda frequente 2',                   'text',     'Lavori anche fuori Roma?', 'Do you work outside Rome?', 22),
('quote.faq2.answer',        'preventivo', 'Domanda frequente 2 — risposta',       'textarea', 'Sì: organizzo matrimoni ed eventi in tutta Italia e seguo le coppie che arrivano dall''estero, dalla scelta della location al giorno stesso.', 'Yes: I plan weddings and events all over Italy and look after couples coming from abroad, from choosing the venue to the day itself.', 23),
('quote.faq3.question',      'preventivo', 'Domanda frequente 3',                   'text',     'Con quanto anticipo conviene scriverti?', 'How far ahead should we get in touch?', 24),
('quote.faq3.answer',        'preventivo', 'Domanda frequente 3 — risposta',       'textarea', 'Per un matrimonio l''ideale è dodici-diciotto mesi prima, soprattutto in alta stagione. Ma non è mai troppo tardi per chiedere: scrivetemi e vediamo insieme.', 'For a wedding, twelve to eighteen months ahead is ideal, especially in high season. But it is never too late to ask: write to me and we will see together.', 25),
('quote.sendCopy',           'preventivo', 'Invia una copia della lettera alla coppia (1 = sì, 0 = no)', 'boolean', '0', '0', 40)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT v.k, 'preventivo', v.l, 'media', (SELECT CAST(m.id AS CHAR) FROM `media` m WHERE m.path = v.p), NULL, v.o
FROM (
  SELECT 'quote.photos.event'    AS k, 'Foto — tipo di evento'  AS l, 'foto/giardino-pini-tavoli-sera.jpg'       AS p, 30 AS o UNION ALL
  SELECT 'quote.photos.names',         'Foto — voi',                  'foto/brindisi-sposi-tramonto.jpg',            31 UNION ALL
  SELECT 'quote.photos.when',          'Foto — quando',               'foto/torta-albero-luci-sospese.jpg',     32 UNION ALL
  SELECT 'quote.photos.where',         'Foto — dove',                 'foto/tavolo-terrazza-tetti-tramonto.jpg',    33 UNION ALL
  SELECT 'quote.photos.guests',        'Foto — ospiti',               'foto/banchetto-edificio-storico.jpg',         34 UNION ALL
  SELECT 'quote.photos.help',          'Foto — aiuto',                'foto/planner-sistema-tavolo.jpg',             35 UNION ALL
  SELECT 'quote.photos.budget',        'Foto — budget',               'foto/sala-dipinti-tavoli-rotondi.jpg',              36 UNION ALL
  SELECT 'quote.photos.contact',       'Foto — contatti',             'foto/musicisti-violino-chitarra.jpg',            37 UNION ALL
  SELECT 'quote.photos.letter',        'Foto — lettera',              'foto/tableau-tessuto-ospiti.jpg',         38 UNION ALL
  SELECT 'quote.photos.thanks',        'Foto — ringraziamento',       'foto/tavolo-lungo-candele-calici.jpg',       39
) AS v
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
