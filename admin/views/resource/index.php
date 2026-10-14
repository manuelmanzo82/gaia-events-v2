<?php
/**
 * @var string $title
 * @var string $route
 * @var array<string, string> $columns
 * @var list<array<string, mixed>> $rows
 */
?>
<h1><?= e($title) ?></h1>

<p><a class="btn" href="<?= e(admin_url($route . '/nuovo')) ?>">Aggiungi</a></p>

<?php if ($rows === []): ?>
  <p>Ancora nessun elemento.</p>
<?php else: ?>
  <table>
    <tr>
      <?php foreach ($columns as $label): ?><th><?= e($label) ?></th><?php endforeach; ?>
      <th>Azioni</th>
    </tr>
    <?php foreach ($rows as $row): ?>
      <tr>
        <?php foreach (array_keys($columns) as $column): ?>
          <td>
            <?php $value = $row[$column] ?? ''; ?>
            <?php if ($column === 'active' || $column === 'featured'): ?>
              <?= !empty($value) ? 'sì' : 'no' ?>
            <?php else: ?>
              <?= e((string) $value) ?>
            <?php endif; ?>
          </td>
        <?php endforeach; ?>
        <td>
          <a href="<?= e(admin_url($route . '/' . (int) $row['id'] . '/modifica')) ?>">Modifica</a>
          <?php if ($route === 'eventi'): ?>
            · <a href="<?= e(admin_url($route . '/' . (int) $row['id'] . '/galleria')) ?>">Galleria</a>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
