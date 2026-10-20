<?php
/**
 * @var array<string, list<array<string, mixed>>> $groups
 * @var array<int, string> $mediaOptions
 */
?>
<h1>Impostazioni e testi</h1>
<p class="muted">
  Colonna di sinistra italiano, colonna di destra inglese.
  Se lasci vuoto l'inglese, quella parte non compare nel sito in lingua.
</p>

<?php if ($groups === []): ?>
  <p>Nessuna impostazione nel database: carica <code>admin/sql/seed.example.sql</code>.</p>
<?php endif; ?>

<form method="post" action="<?= e(admin_url('impostazioni')) ?>">
  <?= Gaia\Admin\Csrf::field() ?>

  <?php foreach ($groups as $group => $rows): ?>
    <fieldset>
      <legend><?= e((string) $group) ?></legend>
      <div style="padding: 0 .8rem .8rem">
        <?php foreach ($rows as $row): ?>
          <?php $id = (int) $row['id']; ?>
          <label><?= e((string) ($row['label'] ?: $row['setting_key'])) ?>
            <span class="muted"> — <?= e((string) $row['setting_key']) ?></span>
          </label>

          <?php if ($row['field_type'] === 'media'): ?>
            <select name="value_it[<?= $id ?>]">
              <option value="">— nessuna immagine —</option>
              <?php foreach ($mediaOptions as $mediaId => $path): ?>
                <option value="<?= (int) $mediaId ?>"<?= (int) $row['value_it'] === (int) $mediaId ? ' selected' : '' ?>>
                  <?= e($path) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php elseif ($row['field_type'] === 'boolean'): ?>
            <select name="value_it[<?= $id ?>]">
              <option value="0"<?= (string) $row['value_it'] !== '1' ? ' selected' : '' ?>>No</option>
              <option value="1"<?= (string) $row['value_it'] === '1' ? ' selected' : '' ?>>Sì</option>
            </select>
          <?php elseif (in_array($row['field_type'], ['textarea', 'html'], true)): ?>
            <div class="pair">
              <div><span>Italiano</span>
                <textarea name="value_it[<?= $id ?>]"><?= e((string) $row['value_it']) ?></textarea>
              </div>
              <div><span>English</span>
                <textarea name="value_en[<?= $id ?>]"><?= e((string) $row['value_en']) ?></textarea>
              </div>
            </div>
          <?php else: ?>
            <div class="pair">
              <div><span>Italiano</span>
                <input type="text" name="value_it[<?= $id ?>]" value="<?= e((string) $row['value_it']) ?>">
              </div>
              <div><span>English</span>
                <input type="text" name="value_en[<?= $id ?>]" value="<?= e((string) $row['value_en']) ?>">
              </div>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </fieldset>
  <?php endforeach; ?>

  <div class="actions"><button type="submit">Salva</button></div>
</form>
