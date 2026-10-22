-- ---------------------------------------------------------------------
-- Preventivo → Conosciamoci (24/10/2026). La parola «preventivo» faceva
-- aspettare un prezzo che non arriva. Restano solo i «preventivo» che
-- indicano il documento inviato dopo (Come lavoro, articolo «10 domande»,
-- FAQ del primo incontro).
-- Idempotente: ogni UPDATE agisce solo sul testo vecchio.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

-- Pagina del tool (/conosciamoci/ ↔ /en/lets-talk/)
UPDATE `pages` SET
  `title_it` = 'Raccontatemi di voi',
  `title_en` = 'Tell me about you',
  `meta_title_it` = 'Conosciamoci: raccontatemi il vostro evento',
  `meta_title_en` = 'Let’s talk: tell me about your wedding or event',
  `meta_description_it` = 'Qualche domanda veloce su evento, periodo, luogo e ospiti: tre minuti, gratis e senza impegno. Poi vi ricontatto io per una prima chiacchierata.',
  `meta_description_en` = 'A few quick questions about your event, timing, place and guests: three minutes, free and with no obligation. Then I’ll get back to you for a first chat.'
WHERE `page_key` = 'quote' AND `title_it` = 'Richiedi un preventivo';

UPDATE `settings` SET `value_it` = 'Conosciamoci', `value_en` = 'Let’s talk'
WHERE `setting_key` = 'quote.eyebrow' AND `value_it` = 'Preventivo';
UPDATE `settings` SET `value_it` = 'Raccontatemi di voi', `value_en` = 'Tell me about you'
WHERE `setting_key` = 'quote.title' AND `value_it` = 'Raccontatemi il vostro giorno';

-- Home: bottone dell'hero e sottotitolo della sezione dei link
UPDATE `page_hero` SET `cta_label_it` = 'Conosciamoci', `cta_label_en` = 'Let’s talk'
WHERE `page_key` = 'home' AND `cta_label_it` = 'Contattami';
UPDATE `settings` SET `value_it` = 'Servizi, luoghi, eventi e contatti', `value_en` = 'Services, places, events and contact'
WHERE `setting_key` = 'sections.portfolio.text' AND `value_it` = 'Servizi, luoghi, storie e preventivo';

-- FAQ
UPDATE `faqs` SET `answer_it` = REPLACE(`answer_it`,
  'Potete farvi un''idea con il preventivo online qui sul sito, oppure scrivetemi e ne parliamo senza impegno.',
  'Raccontatemi cosa avete in mente con Conosciamoci, qui sul sito, oppure scrivetemi: ne parliamo senza impegno.')
WHERE `question_it` = 'Quanto costa un wedding planner?';

UPDATE `faqs` SET
  `question_it` = 'Cosa succede dopo che vi scrivo?',
  `question_en` = 'What happens after I get in touch?',
  `answer_it` = 'Mi raccontate data indicativa, regione, numero di ospiti e cosa vi serve, con Conosciamoci o in videochiamata. Vi ricontatto io e fissiamo una prima chiacchierata, gratuita e senza impegno; se decidiamo di andare avanti, vi mando una proposta su misura, chiara e senza sorprese.',
  `answer_en` = 'Tell me your rough date, region, guest count and what you need, through Let’s talk or on a video call. I’ll get back to you and we’ll set up a first chat, free and with no obligation; if we decide to go ahead, I’ll send you a tailored proposal, clear and with no surprises.'
WHERE `group_name` = 'destination' AND `question_it` = 'Come funziona il preventivo?';

-- Privacy policy
UPDATE `pages` SET `body_it` = REPLACE(`body_it`,
  'nei form di contatto e preventivo.',
  'nel form di contatto e nel modulo Conosciamoci.')
WHERE `page_key` = 'privacy';
