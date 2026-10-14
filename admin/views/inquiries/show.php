<?php
/**
 * @var array<string, mixed> $row
 * @var array<string, mixed> $payload
 */

$id = (int) $row['id'];
?>
<h1>Richiesta #<?= $id ?></h1>
<p><a href="<?= e(admin_url('richieste')) ?>">Torna all'elenco</a></p>

<table>
  <tr><th>Ricevuta</th><td><?= e((string) $row['created_at']) ?></td></tr>
  <tr><th>Tipo</th><td><?= $row['type'] === 'quote' ? 'preventivo' : 'contatto' ?></td></tr>
  <tr><th>Lingua</th><td><?= e((string) $row['lang']) ?></td></tr>
  <tr><th>Nome</th><td><?= e((string) $row['name']) ?></td></tr>
  <tr><th>Email</th><td><a href="mailto:<?= e((string) $row['email']) ?>"><?= e((string) $row['email']) ?></a></td></tr>
  <tr><th>Telefono</th><td><?= e((string) ($row['phone'] ?? '')) ?></td></tr>
  <tr><th>Data evento</th><td><?= e((string) ($row['event_date'] ?? '')) ?></td></tr>
  <tr><th>Tipo evento</th><td><?= e((string) ($row['event_type'] ?? '')) ?></td></tr>
  <tr><th>Invitati</th><td><?= e((string) ($row['guests'] ?? '')) ?></td></tr>
  <tr><th>Messaggio</th><td><?= nl2br(e((string) ($row['message'] ?? ''))) ?></td></tr>
</table>

<?php if ($payload !== []): ?>
  <h2>Risposte del preventivatore</h2>
  <table>
    <?php foreach ($payload as $key => $value): ?>
      <tr>
        <th><?= e((string) $key) ?></th>
        <td><?= e(is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE)) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<h2>Gestione</h2>
<form method="post" action="<?= e(admin_url('richieste/' . $id)) ?>">
  <?= Gaia\Admin\Csrf::field() ?>

  <label for="status">Stato</label>
  <select id="status" name="status">
    <?php foreach (['new' => 'nuova', 'read' => 'letta', 'answered' => 'risposta', 'archived' => 'archiviata'] as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= $row['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="notes">Note interne</label>
  <textarea id="notes" name="notes"><?= e((string) ($row['notes'] ?? '')) ?></textarea>

  <div class="actions"><button type="submit">Salva</button></div>
</form>
