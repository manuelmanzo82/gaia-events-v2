-- ---------------------------------------------------------------------
-- Magazine, articoli 3 (Toscana) e 7 (Costiera Amalfitana): nella
-- conversione automatica dei titoli H2/H3 i nomi propri erano finiti in
-- minuscolo. Ripristino: Toscana, Val d'Orcia, Costiera Amalfitana.
-- Idempotente (REPLACE su testi che dopo la correzione non ci sono più).
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `posts` SET `blocchi` =
  REPLACE(REPLACE(REPLACE(`blocchi`,
    'Perché la toscana è la meta', 'Perché la Toscana è la meta'),
    'per sposarsi in toscana', 'per sposarsi in Toscana'),
    'Val d''orcia: il paesaggio', 'Val d''Orcia: il paesaggio')
WHERE `id` = 3;

UPDATE `posts` SET `blocchi` =
  REPLACE(`blocchi`, 'La costiera amalfitana: un sogno', 'La Costiera Amalfitana: un sogno')
WHERE `id` = 7;
