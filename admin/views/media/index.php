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
          <img src="<?= e($uploadsUrl . '/' . (string) $row['path']) ?>"
               alt="<?= e((string) ($row['alt_it'] ?? '')) ?>"
               width="200" height="133" style="object-fit: cover" loading="lazy">

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

          <label>Punto focale (da 0 a 1)</label>
          <div class="pair">
            <div><span>X</span>
              <input type="text" name="focal_x" value="<?= e((string) $row['focal_x']) ?>"></div>
            <div><span>Y</span>
              <input type="text" name="focal_y" value="<?= e((string) $row['focal_y']) ?>"></div>
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
<?php endif; ?>
