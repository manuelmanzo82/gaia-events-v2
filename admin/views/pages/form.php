<?php
/**
 * @var string $title
 * @var string $pageKey
 * @var array<string, mixed> $row
 * @var list<array<string, mixed>> $sections
 * @var array<int, string> $mediaOptions
 */

$pair = static function (string $column, string $label, string $type = 'text') use ($row): void {
    $it = (string) ($row[$column . '_it'] ?? '');
    $en = (string) ($row[$column . '_en'] ?? '');
    ?>
    <label><?= e($label) ?></label>
    <div class="pair">
      <div><span>Italiano</span>
        <?php if ($type === 'textarea'): ?>
          <textarea name="<?= e($column) ?>_it" rows="<?= $column === 'body' ? 14 : 4 ?>"><?= e($it) ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($column) ?>_it" value="<?= e($it) ?>">
        <?php endif; ?>
      </div>
      <div><span>English</span>
        <?php if ($type === 'textarea'): ?>
          <textarea name="<?= e($column) ?>_en" rows="<?= $column === 'body' ? 14 : 4 ?>"><?= e($en) ?></textarea>
        <?php else: ?>
          <input type="text" name="<?= e($column) ?>_en" value="<?= e($en) ?>">
        <?php endif; ?>
      </div>
    </div>
    <?php
};
?>
<h1><?= e($title) ?></h1>

<form method="post" action="<?= e(admin_url('pagine/' . $pageKey)) ?>">
  <?= Gaia\Admin\Csrf::field() ?>

  <?php $pair('title', 'Titolo'); ?>
  <?php $pair('intro', 'Introduzione', 'textarea'); ?>
  <?php $pair('body', 'Contenuto', 'textarea'); ?>
  <?php $pair('meta_title', 'Meta title'); ?>
  <?php $pair('meta_description', 'Meta description', 'textarea'); ?>

  <label for="hero_media_id">Immagine principale</label>
  <select id="hero_media_id" name="hero_media_id">
    <option value="">— nessuna —</option>
    <?php foreach ($mediaOptions as $mediaId => $path): ?>
      <option value="<?= (int) $mediaId ?>"<?= (int) ($row['hero_media_id'] ?? 0) === (int) $mediaId ? ' selected' : '' ?>>
        <?= e($path) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label for="og_media_id">Immagine per le condivisioni (OG)</label>
  <select id="og_media_id" name="og_media_id">
    <option value="">— nessuna —</option>
    <?php foreach ($mediaOptions as $mediaId => $path): ?>
      <option value="<?= (int) $mediaId ?>"<?= (int) ($row['og_media_id'] ?? 0) === (int) $mediaId ? ' selected' : '' ?>>
        <?= e($path) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <div class="actions">
    <button type="submit">Salva</button>
    <a class="btn ghost" href="<?= e(admin_url('pagine')) ?>">Torna all'elenco</a>
  </div>
</form>

<?php if ($pageKey === 'destinationWedding'): ?>
  <h2>Blocchi della pagina</h2>
  <p class="muted">
    Servizi, destinazioni, processo, FAQ, testimonial e CTA.
    La gestione dei blocchi arriva insieme al design della pagina.
  </p>
  <?php if ($sections !== []): ?>
    <table>
      <tr><th>Tipo</th><th>Titolo</th><th>Ordine</th></tr>
      <?php foreach ($sections as $section): ?>
        <tr>
          <td><?= e((string) $section['kind']) ?></td>
          <td><?= e((string) ($section['title_it'] ?? '')) ?></td>
          <td><?= (int) $section['sort_order'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php endif; ?>
<?php endif; ?>
