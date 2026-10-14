-- ---------------------------------------------------------------------
-- Contenuti utili recuperati da Sanity prima di spegnerlo.
--
-- Fonte: progetto yfcp9735, dataset production. Backup completo in
-- E:\CLAUDE CODE\gaia-backup-sanity (fuori dalla repo, mai committato).
--
-- Entra solo il materiale reale:
--   4 servizi con le loro caratteristiche
--   indirizzo, pagina Facebook e meta description
--   biografia in tre paragrafi, citazione, timeline e valori
--   titoli e sottotitoli hero di chi sono, servizi, portfolio e contatti
--
-- Restano fuori per decisione: eventi, Instagram e le immagini Adobe
-- Stock. Il resto del dataset era materiale di prova.
--
-- Scartati anche due elementi di prova nascosti nei contenuti buoni:
-- la voce di timeline 2025 "Prova / Test funzionalita".
--
-- Solo italiano: le colonne _en restano vuote.
--
-- Caricamento, dopo schema.sql e seed.example.sql:
--   mysql -u UTENTE -p NOME_DB < seed-contenuti-sanity.sql
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- I blocchi di pagina ora servono anche a chi sono: timeline e valori.
-- Idempotente: su un database gia' creato aggiunge i due valori all'enum.
ALTER TABLE `page_sections`
  MODIFY COLUMN `kind` ENUM('intro','services','destinations','process',
    'faq','testimonial','cta','timeline','values') NOT NULL;

-- ---------------------------------------------------------------------
-- Servizi
--
-- Sostituiscono i tre segnaposto di seed.example.sql: questi sono i
-- quattro veri, gli stessi che Gaia cita nelle FAQ gia' importate.
-- ---------------------------------------------------------------------

DELETE FROM `services` WHERE `slug` IN ('full-planning', 'coordinamento', 'eventi-aziendali');

INSERT INTO `services` (`slug`, `title_it`, `subtitle_it`, `description_it`, `features_it`, `icon`, `active`, `sort_order`)
VALUES ('planning', 'Wedding Planning Completo', 'Full Service', 'Un servizio a 360 gradi che vi accompagna dalla prima idea fino all''ultimo ballo. Mi occupo di ogni aspetto dell''organizzazione, permettendovi di vivere il periodo del fidanzamento senza stress.', '["Consulenza iniziale e definizione del concept", "Ricerca e selezione delle location", "Gestione budget e contratti fornitori", "Coordinamento catering, fiori, musica", "Design personalizzato degli allestimenti", "Gestione inviti e RSVP", "Timeline dettagliata della giornata", "Coordinamento completo il giorno del matrimonio"]', 'calendar', 1, 1)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `subtitle_it` = VALUES(`subtitle_it`),
  `description_it` = VALUES(`description_it`), `features_it` = VALUES(`features_it`),
  `icon` = VALUES(`icon`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `services` (`slug`, `title_it`, `subtitle_it`, `description_it`, `features_it`, `icon`, `active`, `sort_order`)
VALUES ('coordinamento', 'Coordinamento del Giorno', 'Day-of', 'Avete già organizzato tutto ma desiderate un professionista che gestisca la giornata? Questo servizio è pensato per garantire che tutto proceda perfettamente mentre voi vi godete ogni istante.', '["Incontri pre-matrimonio per conoscere i dettagli", "Verifica e coordinamento con tutti i fornitori", "Gestione della timeline", "Supervisione allestimenti", "Punto di riferimento per ospiti e fornitori", "Gestione imprevisti", "Presenza dal setup alla chiusura"]', 'users', 1, 2)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `subtitle_it` = VALUES(`subtitle_it`),
  `description_it` = VALUES(`description_it`), `features_it` = VALUES(`features_it`),
  `icon` = VALUES(`icon`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `services` (`slug`, `title_it`, `subtitle_it`, `description_it`, `features_it`, `icon`, `active`, `sort_order`)
VALUES ('design', 'Design & Styling', 'Creative', 'La bellezza sta nei dettagli. Creo concept unici e allestimenti che trasformano qualsiasi location in uno spazio magico, riflettendo la vostra personalità e il vostro stile.', '["Creazione mood board e concept visivo", "Progettazione allestimenti personalizzati", "Selezione palette colori e materiali", "Coordinamento con fioristi e fornitori", "Styling tavoli e aree cerimonia", "Elementi decorativi personalizzati", "Supervisione montaggio e smontaggio"]', 'palette', 1, 3)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `subtitle_it` = VALUES(`subtitle_it`),
  `description_it` = VALUES(`description_it`), `features_it` = VALUES(`features_it`),
  `icon` = VALUES(`icon`), `sort_order` = VALUES(`sort_order`);

INSERT INTO `services` (`slug`, `title_it`, `subtitle_it`, `description_it`, `features_it`, `icon`, `active`, `sort_order`)
VALUES ('destination', 'Destination Wedding', 'Worldwide', 'Sognate un matrimonio in una location lontana da casa? Organizzo destination wedding in tutta Italia e all''estero, gestendo ogni aspetto logistico con la stessa cura di un evento locale.', '["Ricerca location esclusive", "Gestione logistica viaggi e alloggi", "Selezione e coordinamento fornitori locali", "Supporto burocratico e permessi", "Organizzazione attività per gli ospiti", "Welcome dinner e farewell brunch", "Presenza in loco per tutto il soggiorno"]', 'plane', 1, 4)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `subtitle_it` = VALUES(`subtitle_it`),
  `description_it` = VALUES(`description_it`), `features_it` = VALUES(`features_it`),
  `icon` = VALUES(`icon`), `sort_order` = VALUES(`sort_order`);

-- ---------------------------------------------------------------------
-- Impostazioni e testi
-- ---------------------------------------------------------------------

UPDATE `settings` SET `value_it` = 'Wedding Planner & Event Manager' WHERE `setting_key` = 'hero.eyebrow';
UPDATE `settings` SET `value_it` = 'Creiamo Insieme il Tuo Sogno' WHERE `setting_key` = 'hero.title';
UPDATE `settings` SET `value_it` = 'Esclusività e ricercatezza per il giorno più importante della tua vita' WHERE `setting_key` = 'hero.subtitle';
UPDATE `settings` SET `value_it` = 'Parliamone Insieme' WHERE `setting_key` = 'hero.ctaLabel';
UPDATE `settings` SET `value_it` = 'Ciao, sono Gaia' WHERE `setting_key` = 'sections.about.title';
UPDATE `settings` SET `value_it` = 'Da oltre 10 anni trasformo i sogni in eventi indimenticabili. Ho organizzato matrimoni in location esclusive in tutta Italia e all''estero, curando ogni dettaglio con passione. Ho avuto anche l''onore di collaborare con brand prestigiosi come Calzedonia, BNL e Zurich. Il mio obiettivo è rendere il vostro giorno speciale unico e senza stress, curando ogni dettaglio con passione e professionalità.' WHERE `setting_key` = 'sections.about.text';
UPDATE `settings` SET `value_it` = 'Come Posso Aiutarti' WHERE `setting_key` = 'sections.services.title';
UPDATE `settings` SET `value_it` = 'I Nostri Eventi' WHERE `setting_key` = 'sections.portfolio.title';
UPDATE `settings` SET `value_it` = 'Il Tuo Evento Perfetto Inizia da Qui' WHERE `setting_key` = 'sections.cta.title';
UPDATE `settings` SET `value_it` = 'Ogni grande evento inizia con una conversazione. Raccontami la tua visione e insieme la trasformeremo in realtà.' WHERE `setting_key` = 'sections.cta.text';
UPDATE `settings` SET `value_it` = 'Ciao, sono Gaia' WHERE `setting_key` = 'about.title';
UPDATE `settings` SET `value_it` = 'Da oltre dieci anni dedico la mia vita alla realizzazione di matrimoni da sogno. La mia storia con gli eventi è iniziata quasi per caso, organizzando il matrimonio di una cara amica, e da quel momento ho capito che questa era la mia vocazione.' WHERE `setting_key` = 'about.text';
UPDATE `settings` SET `value_it` = 'Viale Vaticano 53 - 00165 Roma' WHERE `setting_key` = 'contact.address';
UPDATE `settings` SET `value_it` = 'Wedding Planner & Event Manager a Roma. Gaia Crognale organizza matrimoni ed eventi esclusivi in Italia e all''estero. Destination wedding, cerimonie civili e religiose, eventi corporate. Trasformiamo i tuoi sogni in realtà.' WHERE `setting_key` = 'metaDescription';

-- ---------------------------------------------------------------------
-- Hero delle pagine
-- ---------------------------------------------------------------------

INSERT INTO `pages` (`page_key`, `title_it`, `intro_it`, `body_it`)
VALUES ('about', 'Chi Sono', 'La passione per i matrimoni perfetti', '<h2>Ciao, sono Gaia</h2><p>Da oltre dieci anni dedico la mia vita alla realizzazione di matrimoni da sogno. La mia storia con gli eventi è iniziata quasi per caso, organizzando il matrimonio di una cara amica, e da quel momento ho capito che questa era la mia vocazione.</p><p>Ogni coppia che incontro porta con sé una storia unica, sogni e desideri che meritano di essere ascoltati e realizzati. Il mio approccio è basato sull''ascolto attento e sulla comprensione profonda di ciò che rende speciale ogni storia d''amore.</p><p>Roma, con la sua bellezza senza tempo, è la cornice perfetta per i matrimoni che organizzo. Ma la mia esperienza si estende ben oltre i confini della città eterna, abbracciando location mozzafiato in tutta Italia e all''estero.</p><blockquote>Il mio obiettivo è trasformare la vostra visione in realtà, curando ogni dettaglio con passione e dedizione per creare un giorno che superi ogni aspettativa.</blockquote>')
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `intro_it` = VALUES(`intro_it`),
  `body_it` = COALESCE(VALUES(`body_it`), `body_it`);

INSERT INTO `pages` (`page_key`, `title_it`, `intro_it`, `body_it`)
VALUES ('services', 'I Miei Servizi', 'Soluzioni su misura per ogni esigenza', '<p>Scoprite i servizi pensati per accompagnarvi verso il giorno più bello della vostra vita. Che desideriate un supporto completo o solo una guida esperta per il grande giorno, troveremo insieme la soluzione perfetta per voi.</p>')
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `intro_it` = VALUES(`intro_it`),
  `body_it` = COALESCE(VALUES(`body_it`), `body_it`);

INSERT INTO `pages` (`page_key`, `title_it`, `intro_it`, `body_it`)
VALUES ('portfolio', 'Storie ed Emozioni', 'Ogni evento racconta una storia unica. Scoprite alcuni dei momenti magici creati per i miei clienti.', NULL)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `intro_it` = VALUES(`intro_it`),
  `body_it` = COALESCE(VALUES(`body_it`), `body_it`);

INSERT INTO `pages` (`page_key`, `title_it`, `intro_it`, `body_it`)
VALUES ('contact', 'Iniziamo a Conoscerci', 'Sono qui per ascoltare i vostri sogni e trasformarli in realtà', NULL)
ON DUPLICATE KEY UPDATE `title_it` = VALUES(`title_it`), `intro_it` = VALUES(`intro_it`),
  `body_it` = COALESCE(VALUES(`body_it`), `body_it`);

-- ---------------------------------------------------------------------
-- Timeline e valori della pagina chi sono
-- ---------------------------------------------------------------------

DELETE ps FROM `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id
WHERE p.page_key = 'about' AND ps.kind IN ('timeline', 'values');

INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `items_it`, `sort_order`)
SELECT p.id, 'timeline', 'Il mio percorso', '[{"label": "2014", "title": "L''Inizio del Viaggio", "text": "Il primo matrimonio organizzato per una cara amica. La scintilla che ha acceso una passione inarrestabile."}, {"label": "2016", "title": "Prima Collaborazione Corporate", "text": "Il primo grande evento aziendale. La collaborazione con brand prestigiosi apre nuovi orizzonti professionali."}, {"label": "2018", "title": "Destination Weddings", "text": "L''avventura si allarga oltre i confini italiani. Matrimoni da sogno in Toscana, Puglia, Costiera Amalfitana e oltre."}, {"label": "2022", "title": "150+ Eventi", "text": "Un traguardo importante: oltre 150 eventi realizzati con successo, ognuno unico e indimenticabile."}]', 1
FROM `pages` p WHERE p.page_key = 'about';

INSERT INTO `page_sections` (`page_id`, `kind`, `title_it`, `items_it`, `sort_order`)
SELECT p.id, 'values', 'I miei valori', '[{"title": "Eleganza", "text": "Ogni dettaglio curato con raffinatezza e gusto impeccabile."}, {"title": "Passione", "text": "Dedizione totale per rendere ogni evento un''esperienza unica."}, {"title": "Eccellenza", "text": "Standard elevati e fornitori selezionati per risultati impeccabili."}, {"title": "Creatività", "text": "Idee originali e concept unici che riflettono la vostra personalità."}]', 2
FROM `pages` p WHERE p.page_key = 'about';
