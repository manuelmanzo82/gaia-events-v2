<?php
/**
 * @var string $title
 * @var string $route
 * @var array<string, mixed> $event
 * @var list<array<string, mixed>> $images
 * @var array<int, string> $mediaOptions
 */

$eventId = (int) $event['id'];
$uploadsUrl = rtrim((string) Gaia\Admin\Config::get('uploads_url', '/uploads'), '/');
?>
<h1><?= e($title) ?></h1>
<p><a href="<?= e(admin_url($route . "/{$eventId}/modifica")) ?>">Torna all'evento</a></p>

<h2>Aggiungi un'immagine</h2>
<form method="post" action="<?= e(admin_url($route . "/{$eventId}/galleria/aggiungi")) ?>">
  <?= Gaia\Admin\Csrf::field() ?>
  <label for="media_id">Immagine già caricata</label>
  <select id="media_id" name="media_id">
    <?php foreach ($mediaOptions as $mediaId => $path): ?>
      <option value="<?= (int) $mediaId ?>"><?= e($path) ?></option>
    <?php endforeach; ?>
  </select>
  <div class="actions">
    <button type="submit">Aggiungi</button>
    <a class="btn ghost" href="<?= e(admin_url('immagini')) ?>">Carica una nuova immagine</a>
  </div>
</form>

<h2>Immagini della galleria</h2>

<?php if ($images === []): ?>
  <p>Galleria vuota.</p>
<?php else: ?>
  <form method="post" action="<?= e(admin_url($route . "/{$eventId}/galleria/ordina")) ?>">
    <?= Gaia\Admin\Csrf::field() ?>
    <table>
      <tr><th>Anteprima</th><th>File</th><th>Ordine</th><th>Azioni</th></tr>
      <?php foreach ($images as $image): ?>
        <tr>
          <td>
            <img src="<?= e($uploadsUrl . '/' . (string) $image['path']) ?>"
                 alt="<?= e((string) ($image['alt_it'] ?? '')) ?>"
                 width="120" height="80" style="object-fit: cover" loading="lazy">
          </td>
          <td class="muted"><?= e((string) $image['path']) ?></td>
          <td>
            <input type="number" name="order[<?= (int) $image['id'] ?>]"
                   value="<?= (int) $image['sort_order'] ?>" style="width: 5rem">
          </td>
          <td>
            <button class="ghost" type="submit"
                    formaction="<?= e(admin_url($route . "/{$eventId}/galleria/" . (int) $image['id'] . '/rimuovi')) ?>">
              Rimuovi
            </button>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
    <div class="actions"><button type="submit">Salva l'ordine</button></div>
  </form>
<?php endif; ?>
