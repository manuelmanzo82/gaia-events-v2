-- ---------------------------------------------------------------------
-- Chi sono, timeline: la tappa 2018 lascia la foto 60
-- (cerimonia-prato-vista-mare, ora apertura della pagina Destination
-- wedding e fascia di Servizi) per la 111 (castello-notte-sposi-torta,
-- 1139 px CSS: regge la foto della tappa, min(49vw, 76vh)).
-- Idempotente: cambia la voce solo se è ancora la 2018 con la foto 60.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `page_sections` ps
JOIN `pages` p ON p.id = ps.page_id
SET ps.items_it = JSON_SET(ps.items_it, '$[2].media',
      (SELECT id FROM `media` WHERE path = 'foto/castello-notte-sposi-torta.png'))
WHERE p.page_key = 'about' AND ps.kind = 'timeline'
  AND JSON_UNQUOTE(JSON_EXTRACT(ps.items_it, '$[2].label')) = '2018'
  AND JSON_EXTRACT(ps.items_it, '$[2].media') = (SELECT id FROM `media` WHERE path = 'foto/cerimonia-prato-vista-mare.jpg');
