<?php
/**
 * @var string $title
 * @var string $pageKey
 * @var array<string, mixed> $row
 * @var list<array<string, mixed>> $images
 * @var array<int, string> $mediaOptions
 */

$uploadsUrl = rtrim((string) Gaia\Admin\Config::get('uploads_url', '/uploads'), '/');
?>
<h1><?= e($title) ?></h1>
<p><a href="<?= e(admin_url('hero')) ?>">Torna all'elenco</a></p>

<form method="post" action="<?= e(admin_url('hero/' . $pageKey)) ?>" enctype="multipart/form-data">
  <?= Gaia\Admin\Csrf::field() ?>

  <h2>Testi</h2>
  <?php foreach ([
      'title' => 'Titolo',
      'subtitle' => 'Sottotitolo',
      'cta_label' => 'Etichetta CTA',
  ] as $campo => $label): ?>
    <div class="grid2">
      <p>
        <label for="<?= e($campo) ?>_it"><?= e($label) ?> (IT)</label>
        <input type="text" id="<?= e($campo) ?>_it" name="<?= e($campo) ?>_it"
               value="<?= e((string) ($row[$campo . '_it'] ?? '')) ?>">
      </p>
      <p>
        <label for="<?= e($campo) ?>_en"><?= e($label) ?> (EN)</label>
        <input type="text" id="<?= e($campo) ?>_en" name="<?= e($campo) ?>_en"
               value="<?= e((string) ($row[$campo . '_en'] ?? '')) ?>">
      </p>
    </div>
  <?php endforeach; ?>

  <p>
    <label for="cta_route">Destinazione della CTA</label>
    <input type="text" id="cta_route" name="cta_route"
           value="<?= e((string) ($row['cta_route'] ?? 'quote')) ?>">
    <span class="muted">Chiave di rotta del sito, es. quote, contact, stories.</span>
  </p>

  <h2>Video desktop (facoltativo)</h2>
  <p class="muted">
    File già pronti: MP4 (H.264) e WebM, 1080p, 15-20 secondi, <strong>max 8 MB l'uno</strong>.
    Su mobile il video non parte mai: ruotano le foto qui sotto. Il poster è
    l'immagine che si vede per prima ed è quella che conta per la velocità.
  </p>

  <div class="grid2">
    <p>
      <label for="video_mp4">MP4<?= !empty($row['video_path']) ? ' — attuale: ' . e((string) $row['video_path']) : '' ?></label>
      <input type="file" id="video_mp4" name="video_mp4" accept="video/mp4">
      <?php if (!empty($row['video_path'])): ?>
        <label><input type="checkbox" name="rimuovi_mp4" value="1"> rimuovi l'MP4 attuale</label>
      <?php endif; ?>
    </p>
    <p>
      <label for="video_webm">WebM<?= !empty($row['video_webm_path']) ? ' — attuale: ' . e((string) $row['video_webm_path']) : '' ?></label>
      <input type="file" id="video_webm" name="video_webm" accept="video/webm">
      <?php if (!empty($row['video_webm_path'])): ?>
        <label><input type="checkbox" name="rimuovi_webm" value="1"> rimuovi il WebM attuale</label>
      <?php endif; ?>
    </p>
  </div>

  <p>
    <label for="video_url">URL video esterno (futuro, es. Vimeo)</label>
    <input type="text" id="video_url" name="video_url"
           value="<?= e((string) ($row['video_url'] ?? '')) ?>">
    <span class="muted">Per ora resta vuoto: si userà quando si passerà a un player esterno.</span>
  </p>

  <h2>Poster del video / prima immagine</h2>
  <div class="grid2">
    <p>
      <label for="poster_media_id">Immagine già caricata</label>
      <select id="poster_media_id" name="poster_media_id">
        <option value="">—</option>
        <?php foreach ($mediaOptions as $mediaId => $path): ?>
          <option value="<?= (int) $mediaId ?>" <?= (int) ($row['poster_media_id'] ?? 0) === (int) $mediaId ? 'selected' : '' ?>>
            <?= e($path) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </p>
    <p>
      <label for="poster">…oppure caricane una nuova</label>
      <input type="file" id="poster" name="poster" accept="image/*">
      <input type="text" name="poster_alt_it" placeholder="Alt italiano">
      <input type="text" name="poster_alt_en" placeholder="Alt inglese">
    </p>
  </div>

  <div class="actions">
    <button type="submit">Salva</button>
  </div>
</form>

<h2>Foto dell'hero (mobile e fallback)</h2>
<?php if (!empty($row['id'])): ?>
  <p>
    <?php foreach ($images as $image): ?>
      <img src="<?= e($uploadsUrl . '/' . (string) $image['path']) ?>" alt=""
           width="120" height="80" style="object-fit: cover" loading="lazy">
    <?php endforeach; ?>
  </p>
  <p><a class="btn" href="<?= e(admin_url('hero/' . (int) $row['id'] . '/galleria')) ?>">Gestisci le foto</a></p>
<?php else: ?>
  <p class="muted">Salva una prima volta: poi compare la galleria delle foto.</p>
<?php endif; ?>
