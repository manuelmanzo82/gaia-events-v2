<?php
/**
 * @var string $title
 * @var string $route
 * @var array<string, array<string, mixed>> $fields
 * @var array<string, mixed> $row
 * @var array<int, string> $mediaOptions
 */

$id = (int) ($row['id'] ?? 0);
$action = admin_url($route . ($id > 0 ? "/{$id}/modifica" : '/nuovo'));
?>
<h1><?= e($title) ?></h1>
<p class="muted">
  I campi in due colonne sono bilingui: a sinistra l'italiano, a destra l'inglese.
  Lasciare vuoto l'inglese significa non pubblicare la versione inglese.
</p>

<form method="post" action="<?= e($action) ?>">
  <?= Gaia\Admin\Csrf::field() ?>

  <?php foreach ($fields as $name => $field): ?>
    <?php require ADMIN_ROOT . '/views/_fields.php'; ?>
  <?php endforeach; ?>

  <div class="actions">
    <button type="submit">Salva</button>
    <a class="btn ghost" href="<?= e(admin_url($route)) ?>">Torna all'elenco</a>
  </div>
</form>

<?php if ($id > 0): ?>
  <form method="post" action="<?= e(admin_url($route . "/{$id}/elimina")) ?>"
        onsubmit="return confirm('Eliminare definitivamente questo elemento?')">
    <?= Gaia\Admin\Csrf::field() ?>
    <div class="actions"><button class="ghost" type="submit">Elimina</button></div>
  </form>
<?php endif; ?>
