<?php
/**
 * @var array<string, string> $keys
 * @var array<string, array<string, mixed>> $existing
 */
?>
<h1>Pagine</h1>
<p class="muted">
  Ogni voce corrisponde a una pagina del sito. La chiave è la stessa che il
  sito usa in <code>src/i18n/routes.ts</code>.
</p>

<table>
  <tr><th>Pagina</th><th>Chiave</th><th>Ultima modifica</th><th></th></tr>
  <?php foreach ($keys as $key => $label): ?>
    <tr>
      <td><?= e($label) ?></td>
      <td class="muted"><code><?= e($key) ?></code></td>
      <td class="muted"><?= e((string) ($existing[$key]['updated_at'] ?? 'mai')) ?></td>
      <td><a href="<?= e(admin_url('pagine/' . $key)) ?>">Modifica</a></td>
    </tr>
  <?php endforeach; ?>
</table>
