-- Testi della fascia orizzontale della home (giro 7, ritocchi): eyebrow e
-- titolo piccolo sopra le foto, modificabili dal back office come le altre
-- sezioni (settings → sections.moments.* → settings.json sections.moments).
-- Idempotente: se le chiavi esistono già non le tocca.

INSERT INTO `settings` (`setting_key`, `group_name`, `label`, `field_type`, `value_it`, `value_en`, `sort_order`) VALUES
('sections.moments.text',  'home', 'Fascia foto in home — eyebrow', 'text', 'Istanti',                    'Moments',                 32),
('sections.moments.title', 'home', 'Fascia foto in home — titolo',  'text', 'Frammenti di giorni felici', 'Fragments of happy days', 33)
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
