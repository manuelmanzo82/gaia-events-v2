-- ---------------------------------------------------------------------
-- Chi sono, timeline: la tappa 2018 lascia la 111 (castello-notte-sposi-
-- torta), che è anche la fascia Destination della home, per la 101
-- (serra-fontana-sera: 1134 px CSS, nessuna persona, non usata in Chi
-- sono). Idempotente: cambia la voce solo se è ancora la 2018 con la 111.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id
SET ps.items_it = JSON_SET(ps.items_it, '$[2].media',
      (SELECT id FROM `media` WHERE path = 'foto/serra-fontana-sera.png'))
WHERE p.page_key = 'about' AND ps.kind = 'timeline'
  AND JSON_UNQUOTE(JSON_EXTRACT(ps.items_it, '$[2].label')) = '2018'
  AND JSON_EXTRACT(ps.items_it, '$[2].media') = (SELECT id FROM `media` WHERE path = 'foto/castello-notte-sposi-torta.png');
