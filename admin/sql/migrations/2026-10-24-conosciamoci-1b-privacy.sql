-- ---------------------------------------------------------------------
-- Privacy policy: le altre tre frasi con «preventivo» (oltre a quella
-- della migrazione 1-rinomina), allineate al modulo Conosciamoci.
-- Idempotente (REPLACE su testi che dopo non ci sono più).
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `pages` SET `body_it` =
  REPLACE(REPLACE(REPLACE(`body_it`,
    'gestire richieste di preventivo, informazioni e contatto inviate tramite i form del sito.',
    'gestire le richieste di informazioni e di contatto inviate tramite il modulo Conosciamoci e il form di contatto del sito.'),
    'per la gestione delle richieste di preventivo e contatto.',
    'per la gestione delle richieste inviate dal modulo Conosciamoci e dal form di contatto.'),
    'gestione dei form di contatto e preventivo.',
    'gestione del form di contatto e del modulo Conosciamoci.')
WHERE `page_key` = 'privacy';
