-- ---------------------------------------------------------------------
-- FAQ (25/10/2026): gruppi nel database, ancore stabili per i link
-- condivisibili, testi riformulati in modo prudente, traduzioni EN in
-- bozza. Non pubblicate: 8 (doppione della pagina Servizi), 12 (unita
-- alla 5), 15 (matrimoni all'estero: da confermare).
-- Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `faq_groups` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  -- Chiave stabile usata dal sito (Servizi e Destination filtrano così).
  `chiave`     VARCHAR(40) NOT NULL,
  `nome_it`    VARCHAR(120) DEFAULT NULL,
  `nome_en`    VARCHAR(120) DEFAULT NULL,
  `slug_it`    VARCHAR(120) DEFAULT NULL,
  `slug_en`    VARCHAR(120) DEFAULT NULL,
  `sort_order` SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_faq_groups_chiave` (`chiave`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `faqs`
  ADD COLUMN IF NOT EXISTS `group_id` INT UNSIGNED DEFAULT NULL AFTER `group_name`,
  ADD COLUMN IF NOT EXISTS `slug_it` VARCHAR(120) DEFAULT NULL AFTER `answer_en`,
  ADD COLUMN IF NOT EXISTS `slug_en` VARCHAR(120) DEFAULT NULL AFTER `slug_it`;

INSERT IGNORE INTO `faq_groups` (`chiave`, `nome_it`, `nome_en`, `slug_it`, `slug_en`, `sort_order`) VALUES ('generali', 'Generali', 'General', 'generali', 'general', 1);
INSERT IGNORE INTO `faq_groups` (`chiave`, `nome_it`, `nome_en`, `slug_it`, `slug_en`, `sort_order`) VALUES ('servizi', 'Servizi', 'Services', 'servizi', 'services', 2);
INSERT IGNORE INTO `faq_groups` (`chiave`, `nome_it`, `nome_en`, `slug_it`, `slug_en`, `sort_order`) VALUES ('costi', 'Costi e pagamenti', 'Costs and payments', 'costi-e-pagamenti', 'costs-and-payments', 3);
INSERT IGNORE INTO `faq_groups` (`chiave`, `nome_it`, `nome_en`, `slug_it`, `slug_en`, `sort_order`) VALUES ('destination', 'Destination wedding', 'Destination weddings', 'destination-wedding', 'destination-weddings', 4);

UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'generali'), `group_name` = 'generali', `sort_order` = 1, `active` = 1, `question_it` = 'Come funziona il primo incontro?', `answer_it` = 'Ci conosciamo di persona a Roma o in videochiamata: mi raccontate come immaginate il vostro giorno e capiamo insieme di cosa avete bisogno. Il primo incontro è gratuito e senza impegno.', `question_en` = 'How does the first meeting work?', `answer_en` = 'We meet in person in Rome or on a video call: you tell me how you picture your day and together we work out what you need. The first meeting is free and with no obligation.' WHERE `id` = 5;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'generali'), `group_name` = 'generali', `sort_order` = 2, `active` = 1, `question_it` = 'Con quanto anticipo contattare una wedding planner?', `answer_it` = 'Prima si comincia, più scelta c’è su location e fornitori, soprattutto nei mesi più richiesti: in genere conviene partire con almeno un anno di anticipo. Se avete meno tempo, scrivetemi comunque e vediamo cosa è possibile.', `question_en` = 'How far ahead should we contact a wedding planner?', `answer_en` = 'The earlier you start, the more choice you have of venues and suppliers, especially in the busiest months: as a rule, it’s worth starting at least a year ahead. If you have less time, write to me anyway and we’ll see what’s possible.' WHERE `id` = 6;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'generali'), `group_name` = 'generali', `sort_order` = 3, `active` = 1, `question_it` = 'Organizzi solo matrimoni o anche altri eventi?', `answer_it` = 'No: oltre ai matrimoni seguo feste private, anniversari ed eventi aziendali, con la stessa cura e lo stesso metodo.', `question_en` = 'Do you only plan weddings?', `answer_en` = 'No: as well as weddings I plan private parties, anniversaries and corporate events, with the same care and the same method.' WHERE `id` = 7;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'generali'), `group_name` = 'generali', `sort_order` = 4, `active` = 1, `question_it` = 'Dove lavori?', `answer_it` = 'La mia base è Roma. Seguo matrimoni ed eventi anche nel resto d’Italia: raccontatemi dove sognate di sposarvi.', `question_en` = 'Where do you work?', `answer_en` = 'My base is Rome. I also plan weddings and events in the rest of Italy: tell me where you dream of getting married.' WHERE `id` = 14;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'generali'), `group_name` = 'generali', `sort_order` = 5, `active` = 1, `question_it` = 'Puoi consigliarci la location?', `answer_it` = 'Sì, è una delle prime cose che facciamo insieme. Vi propongo location che conosco e, prima di scegliere, le visitiamo insieme o le visito io per voi.', `question_en` = 'Can you recommend a venue?', `answer_en` = 'Yes, it’s one of the first things we do together. I suggest venues I know and, before you choose, we visit them together or I visit them for you.' WHERE `id` = 16;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'generali'), `group_name` = 'generali', `sort_order` = 6, `active` = 1 WHERE `id` = 22;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'servizi'), `group_name` = 'servizi', `sort_order` = 1, `active` = 1, `question_en` = 'Can we choose just some of your services?', `answer_en` = 'Of course: there’s no one-size-fits-all package. We sit down together and work out what you really need. Maybe you only need a hand with suppliers, or just coordination on the day. We decide together.' WHERE `id` = 9;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'servizi'), `group_name` = 'servizi', `sort_order` = 2, `active` = 1, `question_en` = 'Do you help choose the suppliers too?', `answer_en` = 'Yes, and I’ll confess it’s one of my favourite parts. Over the years I’ve built a network of photographers, florists, caterers and musicians I know personally. I introduce you to the ones that best suit your style, and your budget.' WHERE `id` = 10;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'costi'), `group_name` = 'costi', `sort_order` = 1, `active` = 1, `question_en` = 'How much does a wedding planner cost?', `answer_en` = 'It depends on many things: the type of service, the number of guests, the venue. I don’t have a fixed price list because every wedding is different. Tell me what you have in mind through Let’s talk, here on the site, or write to me: we’ll talk it over with no obligation.' WHERE `id` = 11;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'costi'), `group_name` = 'costi', `sort_order` = 2, `active` = 1, `question_it` = 'Come funziona il pagamento?', `answer_it` = 'Tempi e modalità di pagamento sono scritti nella proposta, prima di cominciare: sapete sempre cosa aspettarvi.', `question_en` = 'How does payment work?', `answer_en` = 'Payment timings and terms are set out in the proposal, before we start: you always know what to expect.' WHERE `id` = 13;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'destination'), `group_name` = 'destination', `sort_order` = 1, `active` = 1 WHERE `id` = 17;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'destination'), `group_name` = 'destination', `sort_order` = 2, `active` = 1 WHERE `id` = 18;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'destination'), `group_name` = 'destination', `sort_order` = 3, `active` = 1 WHERE `id` = 19;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'destination'), `group_name` = 'destination', `sort_order` = 4, `active` = 1 WHERE `id` = 20;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'destination'), `group_name` = 'destination', `sort_order` = 5, `active` = 1, `answer_it` = 'Sì. Visito le location per voi e ve le mostro in videochiamata, con foto e video onesti. Se volete vederle di persona, organizzo un viaggio di ispezione.', `answer_en` = 'Yes. I visit venues for you and show them on video calls, with honest photos and footage. If you’d like to see them in person, I’ll plan an inspection trip.' WHERE `id` = 21;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'servizi'), `group_name` = 'servizi', `sort_order` = 90, `active` = 0 WHERE `id` = 8;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'costi'), `group_name` = 'costi', `sort_order` = 90, `active` = 0 WHERE `id` = 12;
UPDATE `faqs` SET `group_id` = (SELECT id FROM `faq_groups` WHERE `chiave` = 'destination'), `group_name` = 'destination', `sort_order` = 90, `active` = 0 WHERE `id` = 15;

-- Ancore: dalla domanda di ciascuna lingua (solo se vuote: poi restano stabili).
-- Il calcolo lo fa il back office al salvataggio; qui si riempiono le esistenti.
UPDATE `faqs` SET `slug_it` = 'come-funziona-il-primo-incontro', `slug_en` = 'how-does-the-first-meeting-work' WHERE `id` = 5 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'con-quanto-anticipo-contattare-una-wedding-planner', `slug_en` = 'how-far-ahead-should-we-contact-a-wedding-planner' WHERE `id` = 6 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'organizzi-solo-matrimoni-o-anche-altri-eventi', `slug_en` = 'do-you-only-plan-weddings' WHERE `id` = 7 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'dove-lavori', `slug_en` = 'where-do-you-work' WHERE `id` = 14 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'puoi-consigliarci-la-location', `slug_en` = 'can-you-recommend-a-venue' WHERE `id` = 16 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'cosa-succede-dopo-che-vi-scrivo', `slug_en` = 'what-happens-after-i-get-in-touch' WHERE `id` = 22 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'posso-scegliere-solo-alcuni-servizi', `slug_en` = 'can-we-choose-just-some-of-your-services' WHERE `id` = 9 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'ti-occupi-anche-della-scelta-dei-fornitori', `slug_en` = 'do-you-help-choose-the-suppliers-too' WHERE `id` = 10 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'quanto-costa-un-wedding-planner', `slug_en` = 'how-much-does-a-wedding-planner-cost' WHERE `id` = 11 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'come-funziona-il-pagamento', `slug_en` = 'how-does-payment-work' WHERE `id` = 13 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'con-quanto-anticipo-dobbiamo-iniziare', `slug_en` = 'how-far-ahead-should-we-start-planning' WHERE `id` = 17 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'possiamo-sposarci-legalmente-in-italia', `slug_en` = 'can-we-get-legally-married-in-italy' WHERE `id` = 18 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'in-che-lingua-organizziamo-tutto', `slug_en` = 'what-language-will-we-plan-in' WHERE `id` = 19 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'vi-occupate-del-viaggio-e-dellalloggio-degli-ospiti', `slug_en` = 'can-you-help-with-our-guests-travel-and-accommodation' WHERE `id` = 20 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'possiamo-scegliere-la-location-senza-venire-in-italia', `slug_en` = 'can-we-choose-a-venue-without-coming-to-italy' WHERE `id` = 21 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'quali-servizi-offri', `slug_en` = NULL WHERE `id` = 8 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'la-consulenza-iniziale-e-a-pagamento', `slug_en` = NULL WHERE `id` = 12 AND `slug_it` IS NULL;
UPDATE `faqs` SET `slug_it` = 'organizzi-matrimoni-allestero', `slug_en` = NULL WHERE `id` = 15 AND `slug_it` IS NULL;

-- Conosciamoci, FAQ 3: allineata all'anticipo di «almeno un anno».
UPDATE `settings` SET
  `value_it` = 'Per un matrimonio conviene muoversi con almeno un anno di anticipo, soprattutto in alta stagione. Ma non è mai troppo tardi per chiedere: scrivetemi e vediamo insieme.',
  `value_en` = 'For a wedding it’s worth getting in touch at least a year ahead, especially in peak season. But it’s never too late to ask: write to me and we’ll see together.'
WHERE `setting_key` = 'quote.faq3.answer';

-- Pagina FAQ: titolo e riga.
UPDATE `pages` SET
  `title_it` = 'Domande frequenti', `title_en` = 'Frequently asked questions',
  `intro_it` = 'Le domande che mi fanno più spesso.', `intro_en` = 'The questions I’m asked most often.',
  `meta_title_it` = 'Domande frequenti: wedding planner a Roma',
  `meta_title_en` = 'FAQ: wedding planner in Rome',
  `meta_description_it` = 'Primo incontro, tempi, servizi, costi e destination wedding: le risposte alle domande più frequenti sul lavoro di Gaia Crognale, wedding planner a Roma.',
  `meta_description_en` = 'First meeting, timing, services, costs and destination weddings: answers to the most common questions about working with Gaia Crognale, wedding planner in Rome.'
WHERE `page_key` = 'faq';
