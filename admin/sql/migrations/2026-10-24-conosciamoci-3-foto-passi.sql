-- ---------------------------------------------------------------------
-- Conosciamoci: foto dei passi senza watermark né nomi leggibili.
-- La cornice desktop è fino a 640 × 736 px: tutte reggono (≥ 1178 px CSS
-- o, le orizzontali, ≥ 1575 px di altezza del file).
--   when    43 torta-albero-luci-sospese (VD IMAGE) → 38 scalinata-villa-tavoli-tramonto
--   help    22 planner-sistema-tavolo   (VD IMAGE) → 59 tavolo-candele-sera
--   budget  24 sala-dipinti-tavoli-rotondi (VD IMAGE) → 33 tavolo-viale-alberato-luci
--   contact 46 musicisti-violino-chitarra (VD IMAGE) → 62 cesto-fiori-pesca
--   letter  36 tableau-tessuto-ospiti (nomi leggibili) → 30 composizione-sgabello-drappo
--   thanks  47 tavolo-lungo-candele-calici (VD IMAGE) → 60 cerimonia-prato-vista-mare
-- Idempotente.
-- ---------------------------------------------------------------------

SET NAMES utf8mb4;

UPDATE `settings` s JOIN `media` m ON m.path = CASE s.setting_key
    WHEN 'quote.photos.when'    THEN 'foto/scalinata-villa-tavoli-tramonto.jpg'
    WHEN 'quote.photos.help'    THEN 'foto/tavolo-candele-sera.jpg'
    WHEN 'quote.photos.budget'  THEN 'foto/tavolo-viale-alberato-luci.jpg'
    WHEN 'quote.photos.contact' THEN 'foto/cesto-fiori-pesca.jpg'
    WHEN 'quote.photos.letter'  THEN 'foto/composizione-sgabello-drappo.jpg'
    WHEN 'quote.photos.thanks'  THEN 'foto/cerimonia-prato-vista-mare.jpg'
  END
SET s.value_it = CAST(m.id AS CHAR)
WHERE s.setting_key IN ('quote.photos.when', 'quote.photos.help', 'quote.photos.budget',
                        'quote.photos.contact', 'quote.photos.letter', 'quote.photos.thanks');
