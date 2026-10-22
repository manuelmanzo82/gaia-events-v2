-- ---------------------------------------------------------------------
-- Pagina Destination wedding (/destination-wedding/ ↔ /en/destination-wedding/),
-- 22/10/2026. Testi in BOZZA, da confermare con Gaia (DA-CHIEDERE n. 15).
--
-- 1. Nuovi tipi di blocco «riti» e «ospiti»; sezione highlight «ospiti»
--    (la striscia di foto della pagina).
-- 2. stories.destination: l'evento è un destination wedding (sezione
--    «Matrimoni arrivati da lontano»).
-- 3. destination_regioni: le regioni della sezione «Dove» (mappa + elenco
--    testuale); luogo_id collega la pagina della regione in Luoghi.
-- 4. page_hero «destinationWedding» (foto 60), intro EN della fascia in
--    home, meta title/description in bozza.
-- 5. Blocchi della pagina: via i tre segnaposto (Cosa comprende, Dove con
--    Roma/Costiera/Como, Scriviamoci), dentro Perché con me (values), Dove
--    (destinations), Come funziona da lontano (timeline), Il rito (riti),
--    I vostri ospiti (ospiti).
-- 6. Striscia degli ospiti: highlights con la sezione «ospiti».
-- 7. FAQ del gruppo «destination» (6, IT + EN).
-- 8. Impostazioni del gruppo «destination».
-- Foto collegate per percorso del file.
--
-- Idempotente. Si applica con: php8.3 -q admin/bin/applica-migrazione.php
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- 1 -----------------------------------------------------------------------
ALTER TABLE `page_sections` MODIFY `kind` ENUM('intro','services','destinations','process','faq','testimonial','cta','timeline','values','riti','ospiti') NOT NULL;
ALTER TABLE `highlight_sections` MODIFY `section` ENUM('home','chi-sono','servizi','destination','luogo','cta','ospiti') NOT NULL;

-- 2 -----------------------------------------------------------------------
ALTER TABLE `stories` ADD COLUMN IF NOT EXISTS `destination` TINYINT(1) NOT NULL DEFAULT 0 AFTER `event_type`;

-- 3 -----------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `destination_regioni` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nome_it`       VARCHAR(190) DEFAULT NULL,
  `nome_en`       VARCHAR(190) DEFAULT NULL,
  `testo_it`      VARCHAR(500) DEFAULT NULL,
  `testo_en`      VARCHAR(500) DEFAULT NULL,
  `media_id`      INT UNSIGNED DEFAULT NULL,
  -- Regione da accendere sulla mappa (codice minuscolo, es. «lazio»).
  `regione_mappa` VARCHAR(40) NOT NULL,
  -- Punto facoltativo per una zona dentro la regione (es. la Costiera).
  `lat`           DECIMAL(8,5) DEFAULT NULL,
  `lng`           DECIMAL(8,5) DEFAULT NULL,
  -- Pagina della regione in Luoghi: il link compare solo se esiste.
  `luogo_id`      INT UNSIGNED DEFAULT NULL,
  `active`        TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order`    SMALLINT NOT NULL DEFAULT 0,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_destination_regioni` (`active`, `sort_order`),
  KEY `fk_destination_regioni_media` (`media_id`),
  KEY `fk_destination_regioni_luogo` (`luogo_id`),
  CONSTRAINT `fk_destination_regioni_media` FOREIGN KEY (`media_id`) REFERENCES `media` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_destination_regioni_luogo` FOREIGN KEY (`luogo_id`) REFERENCES `luoghi` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `destination_regioni` (`nome_it`, `nome_en`, `testo_it`, `testo_en`, `media_id`, `regione_mappa`, `lat`, `lng`, `active`, `sort_order`)
SELECT 'Roma e Lazio', 'Rome & Lazio', 'Ville storiche, chiese barocche e terrazze sui tetti: la città eterna e i suoi dintorni, a due passi dall''aeroporto.', 'Historic villas, baroque churches and rooftop terraces: the Eternal City and the countryside around it, close to the airport.', (SELECT id FROM `media` WHERE path = 'foto/tavolo-terrazza-tetti-tramonto.jpg'), 'lazio', 41.9028, 12.4964, 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `destination_regioni` WHERE `nome_it` = 'Roma e Lazio');
INSERT INTO `destination_regioni` (`nome_it`, `nome_en`, `testo_it`, `testo_en`, `media_id`, `regione_mappa`, `lat`, `lng`, `active`, `sort_order`)
SELECT 'Toscana', 'Tuscany', 'Colline, cipressi e tenute tra i vigneti, per un matrimonio che dura tutto il weekend.', 'Rolling hills, cypress lines and wine estates, made for a wedding that lasts the whole weekend.', (SELECT id FROM `media` WHERE path = 'foto/tavolo-terrazza-colline.jpg'), 'toscana', NULL, NULL, 1, 2
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `destination_regioni` WHERE `nome_it` = 'Toscana');
INSERT INTO `destination_regioni` (`nome_it`, `nome_en`, `testo_it`, `testo_en`, `media_id`, `regione_mappa`, `lat`, `lng`, `active`, `sort_order`)
SELECT 'Costiera Amalfitana', 'Amalfi Coast', 'Terrazze sul mare e paesi a picco sulla costa, dove ogni dettaglio va prenotato con largo anticipo.', 'Terraces over the sea and villages clinging to the cliffs, where every detail needs booking well ahead.', NULL, 'campania', 40.6340, 14.6027, 1, 3
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `destination_regioni` WHERE `nome_it` = 'Costiera Amalfitana');
INSERT INTO `destination_regioni` (`nome_it`, `nome_en`, `testo_it`, `testo_en`, `media_id`, `regione_mappa`, `lat`, `lng`, `active`, `sort_order`)
SELECT 'Puglia', 'Puglia', 'Masserie bianche, ulivi secolari e luce del Sud, per una festa all''aperto fino a tardi.', 'Whitewashed masserie, ancient olive groves and southern light, for an open-air party that runs late.', NULL, 'puglia', NULL, NULL, 1, 4
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `destination_regioni` WHERE `nome_it` = 'Puglia');
INSERT INTO `destination_regioni` (`nome_it`, `nome_en`, `testo_it`, `testo_en`, `media_id`, `regione_mappa`, `lat`, `lng`, `active`, `sort_order`)
SELECT 'Lago di Como', 'Lake Como', 'Ville affacciate sull''acqua, giardini all''italiana e montagne sullo sfondo.', 'Villas on the water’s edge, formal Italian gardens and mountains behind.', NULL, 'lombardia', 45.9870, 9.2610, 0, 5
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `destination_regioni` WHERE `nome_it` = 'Lago di Como');

-- 4 -----------------------------------------------------------------------
INSERT INTO `page_hero` (`page_key`, `title_it`, `title_en`, `subtitle_it`, `subtitle_en`)
VALUES ('destinationWedding', 'Destination wedding in Italia', 'Your wedding in Italy', 'Sposarsi in Italia arrivando da lontano richiede un referente sul posto: quello sono io.', 'Marrying in Italy from abroad takes someone on the ground. That someone is me.')
ON DUPLICATE KEY UPDATE `page_key` = `page_key`;
INSERT IGNORE INTO `page_hero_images` (`hero_id`, `media_id`, `sort_order`)
SELECT h.id, m.id, 1 FROM `page_hero` h JOIN `media` m ON m.path = 'foto/cerimonia-prato-vista-mare.jpg'
WHERE h.page_key = 'destinationWedding';
UPDATE `pages` SET `intro_en` = 'Marrying in Italy from abroad takes someone on the ground. That someone is me.' WHERE `page_key` = 'destinationWedding';
UPDATE `pages` SET `meta_title_it` = 'Destination wedding in Italia per coppie dall''estero'
WHERE `page_key` = 'destinationWedding' AND (`meta_title_it` IS NULL OR `meta_title_it` = '');
UPDATE `pages` SET `meta_title_en` = 'Destination Wedding Planner in Italy & Rome'
WHERE `page_key` = 'destinationWedding' AND (`meta_title_en` IS NULL OR `meta_title_en` = '');
UPDATE `pages` SET `meta_description_it` = 'Wedding planner a Roma per coppie dall''estero: location, rito, ospiti e fornitori seguiti sul posto, da Roma alla Toscana, dalla Costiera alla Puglia.'
WHERE `page_key` = 'destinationWedding' AND (`meta_description_it` IS NULL OR `meta_description_it` = '');
UPDATE `pages` SET `meta_description_en` = 'Planning a wedding in Italy from abroad? I’m Gaia, a Rome-based destination wedding planner: venues, ceremony, guests and suppliers, handled on the ground.'
WHERE `page_key` = 'destinationWedding' AND (`meta_description_en` IS NULL OR `meta_description_en` = '');

-- 5 -----------------------------------------------------------------------
-- I segnaposto si tolgono una volta sola: se c'è già il blocco «riti» la
-- pagina è stata migrata e non si tocca più nulla.
DELETE ps FROM `page_sections` ps JOIN `pages` p ON p.id = ps.page_id
WHERE p.page_key = 'destinationWedding' AND ps.kind IN ('services', 'destinations', 'cta')
  AND NOT EXISTS (SELECT 1 FROM (SELECT x.page_id FROM `page_sections` x WHERE x.kind = 'riti') y WHERE y.page_id = p.id);
INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `text_it`, `text_en`, `items_it`, `items_en`, `sort_order`)
SELECT p.id, 'values', 'Perché con me', 'Why work with me', NULL, NULL, '[{"title": "Sul posto", "icon": "posto", "text": "Visito le location e incontro i fornitori al posto vostro. Vi mostro tutto con foto, video e videochiamate, senza filtri da brochure."}, {"title": "La vostra lingua", "icon": "lingua", "text": "Organizziamo tutto in italiano e in inglese. Con i fornitori parlo io: menu, orari, dettagli, niente si perde nella traduzione."}, {"title": "Una rete di fiducia", "icon": "rete", "text": "Lavoro con fornitori locali che conosco di persona: ogni scelta è già verificata, anche se voi siete dall''altra parte del mondo."}]', '[{"title": "On the ground", "text": "I visit the venues and meet the suppliers on your behalf, then show you everything through photos, videos and video calls. No brochure gloss."}, {"title": "Your language", "text": "We plan everything in English and Italian. I do the talking with suppliers, so menus, timings and details never get lost in translation."}, {"title": "A trusted network", "text": "I work with local suppliers I know personally, so every choice has already been tested, even if you’re on the other side of the world."}]', 1
FROM `pages` p WHERE p.page_key = 'destinationWedding'
  AND NOT EXISTS (SELECT 1 FROM `page_sections` x WHERE x.page_id = p.id AND x.kind = 'values');
INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `text_it`, `text_en`, `items_it`, `items_en`, `sort_order`)
SELECT p.id, 'destinations', 'Dove', 'Where in Italy', 'Roma è la mia base. Da qui vi seguo nelle regioni che conosco meglio.', 'Rome is my home base. From here I plan weddings across the regions I know best.', NULL, NULL, 2
FROM `pages` p WHERE p.page_key = 'destinationWedding'
  AND NOT EXISTS (SELECT 1 FROM `page_sections` x WHERE x.page_id = p.id AND x.kind = 'destinations');
INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `text_it`, `text_en`, `items_it`, `items_en`, `sort_order`)
SELECT p.id, 'timeline', 'Come funziona da lontano', 'How it works from abroad', NULL, NULL, '[{"label": "12", "unit": "mesi prima", "title": "La prima videochiamata", "text": "Ci conosciamo in videochiamata e parliamo di date, regione e numero di ospiti. Poi vi mando una prima selezione di location."}, {"label": "6", "unit": "mesi prima", "title": "I sopralluoghi", "text": "Visito le location per voi e ve le mostro dal vivo in videochiamata, stanza per stanza. Se potete venire in Italia per un viaggio di ispezione, lo organizzo io."}, {"label": "3", "unit": "mesi prima", "title": "I dettagli", "text": "Fornitori confermati, menu, allestimenti, il programma di ogni giorno. Vi aggiorno a ogni scelta, nel vostro fuso orario."}, {"label": "7", "unit": "giorni prima", "title": "Arrivano gli ospiti", "text": "Accoglienza, transfer, hotel e cena di benvenuto: sono sul posto e tengo i fili di tutto."}, {"label": "0", "unit": "il giorno", "title": "Il giorno", "text": "Voi pensate solo a godervelo. Al resto penso io, dal mattino all''ultimo ballo."}]', '[{"unit": "months to go", "title": "The first video call", "text": "We meet on a video call to talk dates, region and guest count. Then I send you a first shortlist of venues."}, {"unit": "months to go", "title": "The site visits", "text": "I walk the venues for you and show you around live on video, room by room. If you can fly over for an inspection trip, I’ll plan it for you."}, {"unit": "months to go", "title": "The details", "text": "Suppliers confirmed, menu, styling, the schedule for every day. I keep you posted on each decision, in your time zone."}, {"unit": "days to go", "title": "Your guests arrive", "text": "Welcome, transfers, hotels and the welcome dinner: I’m on the ground holding every thread."}, {"unit": "the day", "title": "The day", "text": "All you have to do is enjoy it. I take care of the rest, from the morning to the last dance."}]', 3
FROM `pages` p WHERE p.page_key = 'destinationWedding'
  AND NOT EXISTS (SELECT 1 FROM `page_sections` x WHERE x.page_id = p.id AND x.kind = 'timeline');
INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `text_it`, `text_en`, `items_it`, `items_en`, `sort_order`)
SELECT p.id, 'riti', 'Il rito', 'Your ceremony', 'Ogni rito ha le sue regole: vi aiuto a capire quale fa per voi e a prepararlo per tempo.', 'Each kind of ceremony has its own rules. I help you work out which one suits you and get it ready in good time.', '[{"title": "Civile", "text": "Ha valore legale. Servono documenti del vostro paese, spesso tradotti e legalizzati: l''elenco esatto e i tempi dipendono dalla nazionalità e dal comune, e vanno verificati per tempo con il vostro consolato e con l''ufficio di stato civile.", "cura": "Vi accompagno nei passaggi e coordino la cerimonia in comune o in una location autorizzata."}, {"title": "Simbolico", "text": "Non ha valore legale e non richiede documenti: vi sposate legalmente nel vostro paese e qui celebrate il rito che volete, dove volete, con le vostre parole.", "cura": "Trovo il celebrante, costruiamo insieme il rito e lo allestisco."}, {"title": "Religioso", "text": "Il matrimonio in chiesa richiede un percorso con la vostra parrocchia e con la diocesi italiana. Se e come abbia anche valore civile dipende dalla vostra situazione e va verificato. Per altre confessioni, vediamo insieme cosa è possibile.", "cura": "Cerco la chiesa, tengo i contatti con la parrocchia e coordino musica, fiori e tempi."}]', '[{"title": "Civil", "text": "It is legally binding. You’ll need documents from your home country, often translated and legalised. The exact list and timings depend on your nationality and the town hall, so check them early with your consulate and the local registry office.", "cura": "I guide you through each step and coordinate the ceremony at the town hall or an authorised venue."}, {"title": "Symbolic", "text": "It has no legal standing and needs no paperwork. You marry legally back home, then celebrate here the ceremony you want, wherever you want, in your own words.", "cura": "I find the celebrant, we shape the ceremony together and I set it all up."}, {"title": "Religious", "text": "A church wedding means working with your home parish and the Italian diocese. Whether it is also legally recognised depends on your situation and needs checking. For other faiths, we look into what’s possible together.", "cura": "I find the church, liaise with the parish and coordinate music, flowers and timings."}]', 4
FROM `pages` p WHERE p.page_key = 'destinationWedding'
  AND NOT EXISTS (SELECT 1 FROM `page_sections` x WHERE x.page_id = p.id AND x.kind = 'riti');
INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `text_it`, `text_en`, `items_it`, `items_en`, `sort_order`)
SELECT p.id, 'ospiti', 'I vostri ospiti', 'Your guests', 'Chi viaggia per voi merita un''accoglienza all''altezza. Penso io anche ai giorni intorno al matrimonio.', 'The people who travel for you deserve a proper welcome. I take care of the days around the wedding too.', '[{"title": "La cena di benvenuto", "text": "La sera prima, per rompere il ghiaccio tra famiglie e amici arrivati da lontano."}, {"title": "Transfer", "text": "Dall''aeroporto all''hotel, dall''hotel alla location: nessuno resta a piedi."}, {"title": "Dove dormire", "text": "Camere bloccate vicino alla location, per tutte le tasche."}, {"title": "Le esperienze", "text": "Degustazioni, visite, una giornata al mare: qualcosa da fare tra un brindisi e l''altro."}]', '[{"title": "Welcome dinner", "text": "The night before, to break the ice between families and friends who’ve travelled far."}, {"title": "Transfers", "text": "Airport to hotel, hotel to venue: nobody gets left behind."}, {"title": "Where to stay", "text": "Room blocks near the venue, for every budget."}, {"title": "Experiences", "text": "Wine tastings, guided tours, a day by the sea: something to enjoy between celebrations."}]', 5
FROM `pages` p WHERE p.page_key = 'destinationWedding'
  AND NOT EXISTS (SELECT 1 FROM `page_sections` x WHERE x.page_id = p.id AND x.kind = 'ospiti');

-- 6 -----------------------------------------------------------------------
INSERT IGNORE INTO `highlights` (`media_id`, `active`) SELECT id, 1 FROM `media` WHERE path = 'foto/tavolo-cestini-aperitivo.png';
INSERT IGNORE INTO `highlight_sections` (`highlight_id`, `section`, `sort_order`)
SELECT h.id, 'ospiti', 1 FROM `highlights` h JOIN `media` m ON m.id = h.media_id WHERE m.path = 'foto/tavolo-cestini-aperitivo.png';
INSERT IGNORE INTO `highlights` (`media_id`, `active`) SELECT id, 1 FROM `media` WHERE path = 'foto/villa-cortile-dall-alto.jpg';
INSERT IGNORE INTO `highlight_sections` (`highlight_id`, `section`, `sort_order`)
SELECT h.id, 'ospiti', 2 FROM `highlights` h JOIN `media` m ON m.id = h.media_id WHERE m.path = 'foto/villa-cortile-dall-alto.jpg';
INSERT IGNORE INTO `highlights` (`media_id`, `active`) SELECT id, 1 FROM `media` WHERE path = 'foto/giardino-pini-tavoli-sera.jpg';
INSERT IGNORE INTO `highlight_sections` (`highlight_id`, `section`, `sort_order`)
SELECT h.id, 'ospiti', 3 FROM `highlights` h JOIN `media` m ON m.id = h.media_id WHERE m.path = 'foto/giardino-pini-tavoli-sera.jpg';
INSERT IGNORE INTO `highlights` (`media_id`, `active`) SELECT id, 1 FROM `media` WHERE path = 'foto/facciata-villa-tavoli-sera.jpg';
INSERT IGNORE INTO `highlight_sections` (`highlight_id`, `section`, `sort_order`)
SELECT h.id, 'ospiti', 4 FROM `highlights` h JOIN `media` m ON m.id = h.media_id WHERE m.path = 'foto/facciata-villa-tavoli-sera.jpg';
INSERT IGNORE INTO `highlights` (`media_id`, `active`) SELECT id, 1 FROM `media` WHERE path = 'foto/tavolo-verde-fiori-candele.jpg';
INSERT IGNORE INTO `highlight_sections` (`highlight_id`, `section`, `sort_order`)
SELECT h.id, 'ospiti', 5 FROM `highlights` h JOIN `media` m ON m.id = h.media_id WHERE m.path = 'foto/tavolo-verde-fiori-candele.jpg';
INSERT IGNORE INTO `highlights` (`media_id`, `active`) SELECT id, 1 FROM `media` WHERE path = 'foto/serra-illuminata-dall-alto.png';
INSERT IGNORE INTO `highlight_sections` (`highlight_id`, `section`, `sort_order`)
SELECT h.id, 'ospiti', 6 FROM `highlights` h JOIN `media` m ON m.id = h.media_id WHERE m.path = 'foto/serra-illuminata-dall-alto.png';
INSERT IGNORE INTO `highlights` (`media_id`, `active`) SELECT id, 1 FROM `media` WHERE path = 'foto/vassoio-dolci-cameriere.png';
INSERT IGNORE INTO `highlight_sections` (`highlight_id`, `section`, `sort_order`)
SELECT h.id, 'ospiti', 7 FROM `highlights` h JOIN `media` m ON m.id = h.media_id WHERE m.path = 'foto/vassoio-dolci-cameriere.png';

-- 7 -----------------------------------------------------------------------
INSERT INTO `faqs` (`question_it`, `question_en`, `answer_it`, `answer_en`, `group_name`, `active`, `sort_order`)
SELECT 'Con quanto anticipo dobbiamo iniziare?', 'How far ahead should we start planning?', 'Consiglio di iniziare almeno un anno prima, soprattutto per i mesi più richiesti. Con meno tempo si può fare, ma la scelta di location e fornitori si restringe.', 'I recommend starting at least a year ahead, especially for peak months. It can be done in less time, but the choice of venues and suppliers narrows.', 'destination', 1, 21
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question_it` = 'Con quanto anticipo dobbiamo iniziare?' AND `group_name` = 'destination');
INSERT INTO `faqs` (`question_it`, `question_en`, `answer_it`, `answer_en`, `group_name`, `active`, `sort_order`)
SELECT 'Possiamo sposarci legalmente in Italia?', 'Can we get legally married in Italy?', 'Sì, con rito civile o religioso, presentando i documenti richiesti. Requisiti e tempi cambiano con la nazionalità e vanno verificati con il vostro consolato. In alternativa vi sposate legalmente a casa e qui celebrate un rito simbolico, che non richiede documenti.', 'Yes, with a civil or religious ceremony, once you provide the required documents. Requirements and timings vary with nationality, so check them with your consulate. Alternatively, marry legally at home and hold a symbolic ceremony here, which needs no paperwork.', 'destination', 1, 22
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question_it` = 'Possiamo sposarci legalmente in Italia?' AND `group_name` = 'destination');
INSERT INTO `faqs` (`question_it`, `question_en`, `answer_it`, `answer_en`, `group_name`, `active`, `sort_order`)
SELECT 'In che lingua organizziamo tutto?', 'What language will we plan in?', 'In italiano e in inglese. Con i fornitori parlo io; per la cerimonia possiamo prevedere un celebrante o un interprete nella vostra lingua.', 'In English and Italian. I handle every conversation with suppliers, and for the ceremony we can arrange a celebrant or interpreter in your language.', 'destination', 1, 23
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question_it` = 'In che lingua organizziamo tutto?' AND `group_name` = 'destination');
INSERT INTO `faqs` (`question_it`, `question_en`, `answer_it`, `answer_en`, `group_name`, `active`, `sort_order`)
SELECT 'Vi occupate del viaggio e dell''alloggio degli ospiti?', 'Can you help with our guests’ travel and accommodation?', 'Vi aiuto a trovare hotel e dimore vicino alla location, a bloccare le camere e a organizzare i transfer. I voli li prenotano gli ospiti, con indicazioni chiare per arrivare.', 'I help you find hotels and villas near the venue, hold room blocks and arrange transfers. Guests book their own flights, with clear directions for getting here.', 'destination', 1, 24
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question_it` = 'Vi occupate del viaggio e dell''alloggio degli ospiti?' AND `group_name` = 'destination');
INSERT INTO `faqs` (`question_it`, `question_en`, `answer_it`, `answer_en`, `group_name`, `active`, `sort_order`)
SELECT 'Possiamo scegliere la location senza venire in Italia?', 'Can we choose a venue without coming to Italy?', 'Sì. Visito le location per voi e ve le mostro in videochiamata, con foto e video onesti. Se volete vederle di persona, organizzo un viaggio di ispezione in pochi giorni.', 'Yes. I visit venues for you and show them on video calls, with honest photos and footage. If you’d like to see them in person, I’ll plan a short inspection trip.', 'destination', 1, 25
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question_it` = 'Possiamo scegliere la location senza venire in Italia?' AND `group_name` = 'destination');
INSERT INTO `faqs` (`question_it`, `question_en`, `answer_it`, `answer_en`, `group_name`, `active`, `sort_order`)
SELECT 'Come funziona il preventivo?', 'How does the quote work?', 'Mi raccontate data indicativa, regione, numero di ospiti e cosa vi serve, dal modulo o in videochiamata. Vi mando una proposta su misura, chiara e senza sorprese.', 'Tell me your rough date, region, guest count and what you need, through the form or on a video call. I’ll send you a tailored proposal, clear and with no surprises.', 'destination', 1, 26
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `faqs` WHERE `question_it` = 'Come funziona il preventivo?' AND `group_name` = 'destination');

-- 8 -----------------------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('destination.faq.title',    'destination', 'Domande frequenti — titolo',        'text', 'Domande frequenti', 'Questions couples ask', 10),
('destination.storie.title', 'destination', 'Matrimoni realizzati — titolo',     'text', 'Matrimoni arrivati da lontano', 'Couples who came from afar', 11),
('destination.cta.video',    'destination', 'CTA finale — riga videochiamata',   'text', 'Preferite vedervi? Organizziamo una videochiamata, nel vostro fuso orario.', 'Rather talk face to face? Let’s set up a video call in your time zone.', 12)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group_name` = VALUES(`group_name`);
INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'destination.photos.cta', 'destination', 'Foto — CTA finale di Destination', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/tavolo-rotondo-serra.png'), NULL, 30
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
