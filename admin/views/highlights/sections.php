<?php
/**
 * @var string $title
 * @var string $route
 * @var array<string, mixed> $row
 * @var string $mediaPath
 * @var array<string, string> $sections
 * @var array<string, int> $attive
 */

$uploadsUrl = rtrim((string) Gaia\Admin\Config::get('uploads_url', '/uploads'), '/');
?>
<h1><?= e($title) ?></h1>
<p><a href="<?= e(admin_url($route . '/' . (int) $row['id'] . '/modifica')) ?>">Torna all'highlight</a></p>

<?php if ($mediaPath !== ''): ?>
  <p>
    <img src="<?= e($uploadsUrl . '/' . $mediaPath) ?>" alt=""
         width="240" height="160" style="object-fit: cover" loading="lazy">
  </p>
<?php endif; ?>

<form method="post" action="<?= e(admin_url($route . '/' . (int) $row['id'] . '/sezioni')) ?>">
  <?= Gaia\Admin\Csrf::field() ?>

  <table>
    <tr><th>Compare in</th><th>Ordine nella sezione</th></tr>
    <?php foreach ($sections as $key => $label): ?>
      <tr>
        <td>
          <label>
            <input type="checkbox" name="sections[<?= e($key) ?>]" value="1"
                   <?= array_key_exists($key, $attive) ? 'checked' : '' ?>>
            <?= e($label) ?>
          </label>
        </td>
        <td>
          <input type="number" name="order[<?= e($key) ?>]"
                 value="<?= (int) ($attive[$key] ?? 0) ?>" style="width: 5rem">
        </td>
      </tr>
    <?php endforeach; ?>
  </table>

  <div class="actions">
    <button type="submit">Salva le sezioni</button>
  </div>
</form>
