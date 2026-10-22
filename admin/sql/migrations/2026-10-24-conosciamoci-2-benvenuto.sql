-- ---------------------------------------------------------------------
-- Conosciamoci: schermata di benvenuto prima del primo passo (passo zero,
-- fuori dalla barra di avanzamento). Testi in BOZZA, da confermare con
-- Gaia (DA-CHIEDERE n. 17). Foto: la 107, Gaia nella serra (il viso).
-- Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('quote.welcome.title',    'preventivo', 'Benvenuto — titolo',            'text',     'Piacere, sono Gaia', 'Lovely to meet you, I’m Gaia', 40),
('quote.welcome.point1',   'preventivo', 'Benvenuto — punto 1',           'textarea', 'Vi faccio qualche domanda veloce: tipo di evento, periodo, luogo, ospiti, cosa vi serve. Tre minuti.', 'A few quick questions: type of event, timing, place, guests, what you need. Three minutes.', 41),
('quote.welcome.point2',   'preventivo', 'Benvenuto — punto 2',           'textarea', 'Serve a me per arrivare alla nostra prima chiacchierata, in videochiamata o di persona, sapendo già cosa avete in mente.', 'It helps me come to our first chat, on a video call or in person, already knowing what you have in mind.', 42),
('quote.welcome.point3',   'preventivo', 'Benvenuto — punto 3',           'textarea', 'Compilare non vi impegna a nulla e nemmeno la nostra prima chiacchierata: è gratuita e senza impegno. Nessun prezzo automatico: vi ricontatto io e decidiamo insieme se e come andare avanti.', 'Filling this in commits you to nothing, and neither does our first chat: it’s free and with no obligation. No automatic prices: I’ll get back to you and we’ll decide together if and how to go ahead.', 43),
('quote.welcome.start',    'preventivo', 'Benvenuto — bottone',           'text',     'Cominciamo', 'Let’s begin', 44),
('quote.welcome.back',     'preventivo', 'Benvenuto — chi torna',         'text',     'Bentornati, riprendiamo da dove eravate rimasti.', 'Welcome back, let’s pick up where you left off.', 45),
('quote.welcome.continue', 'preventivo', 'Benvenuto — Continua',          'text',     'Continua', 'Continue', 46),
('quote.welcome.restart',  'preventivo', 'Benvenuto — Ricomincia',        'text',     'Ricomincia', 'Start over', 47)
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`)
SELECT 'quote.photos.welcome', 'preventivo', 'Foto — benvenuto (ritratto di Gaia)', 'media',
       (SELECT CAST(id AS CHAR) FROM `media` WHERE path = 'foto/planner-serra-tavoli.png'), NULL, 48
ON DUPLICATE KEY UPDATE `label` = VALUES(`label`);
