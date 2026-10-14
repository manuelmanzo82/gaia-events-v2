<?php
/**
 * Partial condiviso: disegna un campo del form a partire dalla sua
 * dichiarazione nel controller.
 *
 * @var string $name
 * @var array<string, mixed> $field
 * @var array<string, mixed> $row
 * @var array<int, string> $mediaOptions
 */

$type = (string) $field['type'];
$bilingual = !empty($field['bilingual']);

/** Valore della colonna, pronto per l'attributo value. */
$valueOf = static function (string $column) use ($row, $type): string {
    $value = $row[$column] ?? '';

    // Le liste sono JSON nel database e una riga per voce nel form.
    if ($type === 'list') {
        $decoded = is_string($value) && $value !== '' ? json_decode($value, true) : [];

        return is_array($decoded) ? implode("\n", array_map('strval', $decoded)) : '';
    }

    return (string) $value;
};

$columns = $bilingual ? ["{$name}_it" => 'Italiano', "{$name}_en" => 'English'] : [$name => ''];
?>

<label><?= e((string) $field['label']) ?></label>

<?php if ($type === 'bool'): ?>
  <input type="checkbox" name="<?= e($name) ?>" value="1"<?= !empty($row[$name]) ? ' checked' : '' ?>>

<?php elseif ($type === 'media'): ?>
  <select name="<?= e($name) ?>">
    <option value="">— nessuna immagine —</option>
    <?php foreach ($mediaOptions as $mediaId => $path): ?>
      <option value="<?= (int) $mediaId ?>"<?= (int) ($row[$name] ?? 0) === (int) $mediaId ? ' selected' : '' ?>>
        <?= e($path) ?>
      </option>
    <?php endforeach; ?>
  </select>

<?php elseif ($type === 'select'): ?>
  <select name="<?= e($name) ?>">
    <?php foreach (($field['options'] ?? []) as $optionValue => $optionLabel): ?>
      <option value="<?= e((string) $optionValue) ?>"<?= (string) ($row[$name] ?? '') === (string) $optionValue ? ' selected' : '' ?>>
        <?= e((string) $optionLabel) ?>
      </option>
    <?php endforeach; ?>
  </select>

<?php else: ?>
  <div class="<?= $bilingual ? 'pair' : '' ?>">
    <?php foreach ($columns as $column => $langLabel): ?>
      <div>
        <?php if ($langLabel !== ''): ?><span><?= e($langLabel) ?></span><?php endif; ?>

        <?php if (in_array($type, ['textarea', 'html', 'list'], true)): ?>
          <textarea name="<?= e($column) ?>"<?= $type === 'html' ? ' rows="12"' : '' ?>><?= e($valueOf($column)) ?></textarea>
        <?php elseif ($type === 'date'): ?>
          <input type="date" name="<?= e($column) ?>" value="<?= e(substr($valueOf($column), 0, 10)) ?>">
        <?php elseif ($type === 'number'): ?>
          <input type="number" name="<?= e($column) ?>" value="<?= e($valueOf($column)) ?>">
        <?php else: ?>
          <input type="text" name="<?= e($column) ?>" value="<?= e($valueOf($column)) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!empty($field['help'])): ?>
  <p class="help"><?= e((string) $field['help']) ?></p>
<?php endif; ?>
