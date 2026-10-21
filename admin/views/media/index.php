<?php
/**
 * @var list<array<string, mixed>> $rows
 * @var bool $avifSupported
 */

$uploadsUrl = rtrim((string) Gaia\Admin\Config::get('uploads_url', '/uploads'), '/');
?>
<h1>Immagini</h1>
<p class="muted">
  Ogni caricamento genera le varianti WebP<?= $avifSupported ? ' e AVIF' : '' ?>
  nelle larghezze configurate. I file restano in <code>uploads/</code> sul
  server e il deploy del sito non li tocca.
</p>

<h2>Carica</h2>
<form method="post" action="<?= e(admin_url('immagini/carica')) ?>" enctype="multipart/form-data">
  <?= Gaia\Admin\Csrf::field() ?>

  <label for="image">File (JPG, PNG o WebP)</label>
  <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" required>

  <label for="folder">Cartella</label>
  <input id="folder" type="text" name="folder" value="eventi" placeholder="eventi">

  <label>Testo alternativo</label>
  <div class="pair">
    <div><span>Italiano</span><input type="text" name="alt_it"></div>
    <div><span>English</span><input type="text" name="alt_en"></div>
  </div>

  <fieldset>
    <legend>Ritaglio (opzionale, in pixel sull'originale)</legend>
    <div style="display:grid;gap:.5rem;grid-template-columns:repeat(4,1fr);padding:0 .8rem .8rem">
      <div><label for="crop_x">X</label><input id="crop_x" type="number" name="crop_x" value="0"></div>
      <div><label for="crop_y">Y</label><input id="crop_y" type="number" name="crop_y" value="0"></div>
      <div><label for="crop_width">Larghezza</label><input id="crop_width" type="number" name="crop_width"></div>
      <div><label for="crop_height">Altezza</label><input id="crop_height" type="number" name="crop_height"></div>
    </div>
  </fieldset>

  <div class="actions"><button type="submit">Carica</button></div>
</form>

<h2>Caricate</h2>

<?php if ($rows === []): ?>
  <p>Nessuna immagine.</p>
<?php else: ?>
  <?php foreach ($rows as $row): ?>
    <form method="post" action="<?= e(admin_url('immagini/' . (int) $row['id'] . '/aggiorna')) ?>">
      <?= Gaia\Admin\Csrf::field() ?>
      <fieldset>
        <legend><?= e((string) $row['path']) ?></legend>
        <div style="padding: 0 .8rem .8rem">
          <?php $fx = (float) ($row['focal_x'] ?? 0.5); $fy = (float) ($row['focal_y'] ?? 0.5); ?>
          <div class="focale" data-focale title="Clicca sulla foto per impostare il punto focale">
            <img src="<?= e($uploadsUrl . '/' . (string) $row['path']) ?>"
                 alt="<?= e((string) ($row['alt_it'] ?? '')) ?>"
                 width="<?= (int) $row['width'] ?>" height="<?= (int) $row['height'] ?>" loading="lazy">
            <span class="focale-punto" style="left: <?= $fx * 100 ?>%; top: <?= $fy * 100 ?>%"></span>
          </div>

          <p class="muted">
            <?= (int) $row['width'] ?>×<?= (int) $row['height'] ?> px ·
            WebP <?= !empty($row['has_webp']) ? 'sì' : 'no' ?> ·
            AVIF <?= !empty($row['has_avif']) ? 'sì' : 'no' ?>
          </p>

          <?php
          $maxCss = (int) ($row['max_css_width'] ?? 0);
          $tier = (string) ($row['usage_tier'] ?? 'thumbnail');
          ?>
          <p class="limite limite-<?= e($tier) ?>">
            Si può mostrare fino a <strong><?= $maxCss ?> px</strong> di larghezza:
            <?= e(Gaia\Admin\ImageService::usageLabel($tier)) ?>.
          </p>
          <p class="help">
            Oltre questa misura si sgrana. È un limite tecnico della foto, non una
            preferenza: il sito e la lightbox non la ingrandiranno mai di più.
          </p>

          <label>Testo alternativo</label>
          <div class="pair">
            <div><span>Italiano</span>
              <input type="text" name="alt_it" value="<?= e((string) ($row['alt_it'] ?? '')) ?>"></div>
            <div><span>English</span>
              <input type="text" name="alt_en" value="<?= e((string) ($row['alt_en'] ?? '')) ?>"></div>
          </div>

          <label>Didascalia</label>
          <div class="pair">
            <div><span>Italiano</span>
              <input type="text" name="caption_it" value="<?= e((string) ($row['caption_it'] ?? '')) ?>"></div>
            <div><span>English</span>
              <input type="text" name="caption_en" value="<?= e((string) ($row['caption_en'] ?? '')) ?>"></div>
          </div>

          <label for="note_interne_<?= (int) $row['id'] ?>">Nota interna</label>
          <?php if (!empty($row['note_interne'])): ?>
            <p class="limite limite-thumbnail"><?= e((string) $row['note_interne']) ?></p>
          <?php endif; ?>
          <input id="note_interne_<?= (int) $row['id'] ?>" type="text" name="note_interne"
                 value="<?= e((string) ($row['note_interne'] ?? '')) ?>"
                 placeholder="Solo per il back office: non va sul sito">

          <label>Punto focale</label>
          <p class="help">Clicca sulla foto qui sopra: il punto resta sempre visibile quando la
            foto viene ritagliata (hero, schede, fasce). Valori da 0 a 1, centro = 0,5.</p>
          <div class="pair">
            <div><span>X</span>
              <input type="text" name="focal_x" value="<?= e((string) $row['focal_x']) ?>" data-focale-x></div>
            <div><span>Y</span>
              <input type="text" name="focal_y" value="<?= e((string) $row['focal_y']) ?>" data-focale-y></div>
          </div>

          <div class="actions">
            <button type="submit">Salva</button>
            <button class="ghost" type="submit"
                    formaction="<?= e(admin_url('immagini/' . (int) $row['id'] . '/elimina')) ?>"
                    onclick="return confirm('Eliminare l\'immagine e tutte le sue varianti?')">
              Elimina
            </button>
          </div>
        </div>
      </fieldset>
    </form>
  <?php endforeach; ?>

<style>
  .focale { position: relative; display: inline-block; max-width: 260px; cursor: crosshair; margin-bottom: .5rem; }
  .focale img { display: block; width: 100%; height: auto; }
  .focale-punto { position: absolute; width: 18px; height: 18px; margin: -9px 0 0 -9px; border: 2px solid #fff; border-radius: 50%;
    background: rgba(114, 47, 55, .85); box-shadow: 0 0 0 1px rgba(0,0,0,.4); pointer-events: none; }
</style>
<script>
  // Punto focale: un clic sulla foto scrive X e Y (0..1) nei campi e sposta il punto.
  document.querySelectorAll('[data-focale]').forEach(function (box) {
    box.addEventListener('click', function (e) {
      var r = box.getBoundingClientRect();
      var x = Math.min(Math.max((e.clientX - r.left) / r.width, 0), 1);
      var y = Math.min(Math.max((e.clientY - r.top) / r.height, 0), 1);
      var form = box.closest('form');
      form.querySelector('[data-focale-x]').value = x.toFixed(3);
      form.querySelector('[data-focale-y]').value = y.toFixed(3);
      var p = box.querySelector('.focale-punto');
      p.style.left = (x * 100) + '%';
      p.style.top = (y * 100) + '%';
    });
  });
</script>
<?php endif; ?>
