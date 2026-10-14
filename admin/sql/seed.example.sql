-- ---------------------------------------------------------------------
-- Dati iniziali di esempio.
--
-- Crea le chiavi delle impostazioni e le righe delle pagine previste dal
-- sito, con testi segnaposto. Nessun dato reale, nessun utente: l'utente
-- si crea con
--   php admin/bin/create-user.php --email=… --name="…" --role=admin
--
-- Caricamento, dopo schema.sql:
--   mysql -u UTENTE -p NOME_DB < seed.example.sql
--
-- Le chiavi di `settings` usano la notazione con punto e l'export le
-- ritrasforma nella struttura annidata di site/src/content/settings.json:
-- cambiare una chiave qui senza cambiare il tipo in site/src/lib/types.ts
-- significa un campo che il sito non legge.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Impostazioni
-- ---------------------------------------------------------------------

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('tagline',                  'generale', 'Sottotitolo del sito',      'text',     'Wedding & event planner a Roma', 'Wedding & event planner in Rome', 1),
('metaTitle',                'seo',      'Title della home',          'text',     'Gaia Events — Wedding & Event Planner a Roma', 'Gaia Events — Wedding & Event Planner in Rome', 1),
('metaDescription',          'seo',      'Meta description di default','textarea','Gaia Crognale progetta matrimoni ed eventi su misura a Roma e in tutta Italia.', 'Gaia Crognale designs bespoke weddings and events in Rome and across Italy.', 2),
('ogImage',                  'seo',      'Immagine di condivisione',  'media',    NULL, NULL, 3),

('hero.eyebrow',             'home',     'Hero — sovratitolo',        'text',     'Wedding & Event Planner', 'Wedding & Event Planner', 1),
('hero.title',               'home',     'Hero — titolo',             'text',     'Il vostro giorno, progettato nei dettagli', 'Your day, designed down to the detail', 2),
('hero.subtitle',            'home',     'Hero — sottotitolo',        'textarea', 'Matrimoni ed eventi su misura a Roma e in tutta Italia.', 'Bespoke weddings and events in Rome and across Italy.', 3),
('hero.ctaLabel',            'home',     'Hero — testo del pulsante', 'text',     'Raccontami il tuo evento', 'Tell me about your event', 4),
('hero.image',               'home',     'Hero — immagine',           'media',    NULL, NULL, 5),

('sections.about.title',     'home',     'Sezione chi sono — titolo', 'text',     'Chi sono', 'About me', 10),
('sections.about.text',      'home',     'Sezione chi sono — testo',  'textarea', 'Accompagno coppie e aziende nella progettazione di eventi che somigliano a chi li vive.', 'I guide couples and companies in designing events that look like the people living them.', 11),
('sections.services.title',  'home',     'Sezione servizi — titolo',  'text',     'Come lavoro', 'How I work', 20),
('sections.services.text',   'home',     'Sezione servizi — testo',   'textarea', 'Dalla consulenza iniziale al coordinamento del giorno.', 'From the first consultation to day-of coordination.', 21),
('sections.portfolio.title', 'home',     'Sezione portfolio — titolo','text',     'Eventi realizzati', 'Selected events', 30),
('sections.portfolio.text',  'home',     'Sezione portfolio — testo', 'textarea', 'Ogni evento è una storia diversa.', 'Every event is a different story.', 31),
('sections.reviews.title',   'home',     'Sezione recensioni — titolo','text',    'Dicono di me', 'Kind words', 40),
('sections.reviews.text',    'home',     'Sezione recensioni — testo','textarea', NULL, NULL, 41),
('sections.instagram.title', 'home',     'Sezione Instagram — titolo','text',     'Su Instagram', 'On Instagram', 50),
('sections.instagram.text',  'home',     'Sezione Instagram — testo', 'textarea', NULL, NULL, 51),
('sections.cta.title',       'home',     'Sezione finale — titolo',   'text',     'Parliamo del vostro evento', 'Let''s talk about your event', 60),
('sections.cta.text',        'home',     'Sezione finale — testo',    'textarea', 'Scrivimi: raccontami data, luogo e atmosfera che immaginate.', 'Write to me: tell me the date, the place and the mood you imagine.', 61),

('about.title',              'chi sono', 'Titolo',                    'text',     'Gaia Crognale', 'Gaia Crognale', 1),
('about.text',               'chi sono', 'Testo',                     'textarea', 'Testo segnaposto.', 'Placeholder text.', 2),
('about.image',              'chi sono', 'Ritratto',                  'media',    NULL, NULL, 3),

('contact.title',            'contatti', 'Titolo',                    'text',     'Contatti', 'Contact', 1),
('contact.text',             'contatti', 'Testo',                     'textarea', 'Rispondo di solito entro 24 ore, dal lunedì al venerdì.', 'I usually reply within 24 hours, Monday to Friday.', 2),
('contact.address',          'contatti', 'Dove',                      'text',     'Roma, Italia', 'Rome, Italy', 3)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`), `group_name` = VALUES(`group_name`);

-- ---------------------------------------------------------------------
-- Pagine
--
-- page_key deve coincidere con una chiave di ROUTES in
-- site/src/i18n/routes.ts.
-- ---------------------------------------------------------------------

INSERT INTO `pages` (`page_key`, `title_it`, `title_en`, `intro_it`, `intro_en`, `body_it`, `body_en`) VALUES
('about', 'Chi sono', 'About me',
 'Progetto eventi da anni e continuo a pensare che il lusso vero sia non dover pensare a niente.',
 'I have been designing events for years and I still believe real luxury is not having to think about anything.',
 '<p>Testo segnaposto.</p>', '<p>Placeholder text.</p>'),

('services', 'Servizi', 'Services',
 'Tre formule di lavoro, pensate per livelli diversi di coinvolgimento.',
 'Three ways of working, for different levels of involvement.',
 NULL, NULL),

('portfolio', 'Portfolio', 'Portfolio',
 'Ogni evento è una storia diversa.', 'Every event is a different story.', NULL, NULL),

('reviews', 'Dicono di me', 'Reviews',
 'Le parole delle coppie e delle aziende con cui ho lavorato.',
 'Words from the couples and companies I have worked with.', NULL, NULL),

('blog', 'Blog', 'Blog',
 NULL, NULL, NULL, NULL),

('faq', 'Domande frequenti', 'Frequently asked questions',
 NULL, NULL, NULL, NULL),

('quote', 'Richiedi un preventivo', 'Request a quote',
 'Poche domande per capire cosa avete in mente. Nessun prezzo automatico: la proposta la scrivo io.',
 'A few questions to understand what you have in mind. No automatic pricing: I write the proposal myself.',
 NULL, NULL),

('contact', 'Contatti', 'Contact',
 NULL, NULL, NULL, NULL),

('destinationWedding', 'Destination wedding in Italia', 'Destination wedding in Italy',
 'Sposarsi in Italia arrivando da lontano richiede un referente sul posto: quello sono io.',
 'Getting married in Italy from abroad calls for someone on the ground: that is me.',
 NULL, NULL),

('privacy', 'Privacy policy', 'Privacy policy',
 NULL, NULL,
 '<p>Testo segnaposto: l''informativa della v1 va importata qui, con l''email gaiacrognale@gmail.com.</p>',
 '<p>Placeholder text.</p>'),

('cookie', 'Cookie policy', 'Cookie policy',
 NULL, NULL,
 '<p>Testo segnaposto: la cookie policy della v1 va importata qui.</p>',
 '<p>Placeholder text.</p>')
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`);

-- ---------------------------------------------------------------------
-- Blocchi della destination wedding
--
-- I blocchi non hanno una chiave unica, quindi si ripulisce prima: così il
-- file si può ricaricare senza ritrovarsi gli stessi blocchi in doppio.
-- ---------------------------------------------------------------------

DELETE ps FROM `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id
WHERE p.page_key = 'destinationWedding';

INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `text_it`, `text_en`, `items_it`, `items_en`, `sort_order`)
SELECT p.id, 'services', 'Cosa comprende', 'What is included', NULL, NULL,
       '[{"title":"Documenti e burocrazia","text":"Vi guido fra i documenti necessari per sposarsi in Italia."},{"title":"Location scouting","text":"Visito le location per voi e vi mando materiale onesto, non da brochure."}]',
       '[{"title":"Paperwork and bureaucracy","text":"I guide you through the documents needed to marry in Italy."},{"title":"Venue scouting","text":"I visit venues for you and send honest material, not brochure shots."}]',
       1
FROM `pages` p WHERE p.page_key = 'destinationWedding';

INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `items_it`, `items_en`, `sort_order`)
SELECT p.id, 'destinations', 'Dove', 'Where',
       '[{"title":"Roma","text":""},{"title":"Costiera Amalfitana","text":""},{"title":"Lago di Como","text":""}]',
       '[{"title":"Rome","text":""},{"title":"Amalfi Coast","text":""},{"title":"Lake Como","text":""}]',
       2
FROM `pages` p WHERE p.page_key = 'destinationWedding';

INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `title_en`, `text_it`, `text_en`, `sort_order`)
SELECT p.id, 'cta', 'Scriviamoci', 'Let''s talk',
       'Fuso orario diverso? Fissiamo una videochiamata.',
       'Different time zone? Let''s set up a video call.',
       3
FROM `pages` p WHERE p.page_key = 'destinationWedding';

-- ---------------------------------------------------------------------
-- FAQ di esempio
--
-- Segnaposto: se carichi anche seed-contenuti-v1.sql, quello le cancella
-- e mette al loro posto le 12 FAQ vere della v1.
-- ---------------------------------------------------------------------

DELETE FROM `faqs` WHERE `question_it` IN (
  'Quanto tempo prima vi devo contattare?',
  'Lavorate solo a Roma?',
  'Posso affidarvi solo una parte dell''organizzazione?',
  'Come si costruisce il preventivo?'
);

INSERT INTO `faqs` (`question_it`, `question_en`, `answer_it`, `answer_en`, `group_name`, `active`, `sort_order`) VALUES
('Quanto tempo prima vi devo contattare?', 'How far in advance should I contact you?',
 'Per un matrimonio l''ideale è dodici mesi prima, ma ho organizzato eventi splendidi anche in tre mesi.',
 'Twelve months ahead is ideal for a wedding, but I have organised beautiful events in three months too.',
 'generale', 1, 1),
('Lavorate solo a Roma?', 'Do you work only in Rome?',
 'Roma è la mia base, ma seguo eventi in tutta Italia e destination wedding per coppie che arrivano dall''estero.',
 'Rome is my base, but I work across Italy and handle destination weddings for couples coming from abroad.',
 'generale', 1, 2),
('Posso affidarvi solo una parte dell''organizzazione?', 'Can I hand over only part of the planning?',
 'Sì: il coordinamento del giorno è pensato esattamente per chi ha già organizzato tutto.',
 'Yes: day-of coordination is designed exactly for those who have already planned everything.',
 'servizi', 1, 3),
('Come si costruisce il preventivo?', 'How is the quote built?',
 'Dopo una prima chiacchierata preparo una proposta su misura: non ci sono listini automatici.',
 'After a first conversation I prepare a bespoke proposal: there are no automatic price lists.',
 'servizi', 1, 4);

-- ---------------------------------------------------------------------
-- Servizi di esempio
-- ---------------------------------------------------------------------

INSERT INTO `services` (`slug`, `title_it`, `title_en`, `subtitle_it`, `subtitle_en`, `description_it`, `description_en`, `features_it`, `features_en`, `icon`, `active`, `sort_order`) VALUES
('full-planning', 'Full planning', 'Full planning',
 'Dalla prima idea all''ultimo brindisi', 'From the first idea to the last toast',
 'Vi accompagno in ogni fase: concept, budget, selezione dei fornitori, progettazione degli allestimenti e coordinamento del giorno.',
 'I guide you through every stage: concept, budget, supplier selection, design and day-of coordination.',
 '["Concept e mood board","Gestione del budget","Selezione fornitori","Coordinamento del giorno"]',
 '["Concept and mood board","Budget management","Supplier selection","Day-of coordination"]',
 'sparkles', 1, 1),
('coordinamento', 'Coordinamento del giorno', 'Day-of coordination',
 'Avete organizzato tutto: ci penso io a farlo funzionare', 'You planned it all: I make it run',
 'Entro in gioco nelle settimane finali, raccolgo i fili e gestisco la regia della giornata.',
 'I step in during the final weeks, pull the threads together and direct the day.',
 '["Timeline dettagliata","Briefing dei fornitori","Regia della giornata"]',
 '["Detailed timeline","Supplier briefing","On-site direction"]',
 'clock', 1, 2),
('eventi-aziendali', 'Eventi aziendali', 'Corporate events',
 'Quando l''immagine conta', 'When image matters',
 'Inaugurazioni, cene di gala e convention: eventi coerenti con l''identità dell''azienda.',
 'Openings, gala dinners and conventions: events consistent with your brand.',
 '["Location scouting","Allestimenti brandizzati","Gestione ospiti"]',
 NULL,
 'briefcase', 1, 3)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `description_it` = VALUES(`description_it`);
