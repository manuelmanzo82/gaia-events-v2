-- ---------------------------------------------------------------------
-- Tempi di risposta uniformi in tutto il sito, finché Gaia non conferma
-- (DA-CHIEDERE n. 17): «entro 48 ore nei giorni feriali» / «within 48
-- hours on weekdays». Prima: Contatti «entro 24 ore, dal lunedì al
-- venerdì», Conosciamoci «entro 48 ore» / «entro due giorni».
-- Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `settings` SET
  `value_it` = 'Rispondo entro 48 ore nei giorni feriali.',
  `value_en` = 'I reply within 48 hours on weekdays.'
WHERE `setting_key` = 'contact.text';

UPDATE `settings` SET
  `value_it` = 'Risposta entro 48 ore',
  `value_en` = 'A reply within 48 hours'
WHERE `setting_key` = 'quote.assurance1.title';

UPDATE `settings` SET
  `value_it` = 'Leggo ogni richiesta di persona e vi rispondo entro 48 ore nei giorni feriali.',
  `value_en` = 'I read every message myself and reply within 48 hours on weekdays.'
WHERE `setting_key` = 'quote.assurance1.text';

UPDATE `settings` SET `value_it` = '48', `value_en` = '48' WHERE `setting_key` = 'quote.replyHours';
