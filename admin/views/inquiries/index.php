<?php
/**
 * @var list<array<string, mixed>> $rows
 * @var string $status
 */

$labels = ['new' => 'nuove', 'read' => 'lette', 'answered' => 'risposte', 'archived' => 'archiviate'];
?>
<h1>Richieste dal sito</h1>

<p class="muted">
  <a href="<?= e(admin_url('richieste')) ?>">Tutte</a>
  <?php foreach ($labels as $key => $label): ?>
    · <a href="<?= e(admin_url('richieste?stato=' . $key)) ?>"><?= e($label) ?></a>
  <?php endforeach; ?>
</p>

<?php if ($rows === []): ?>
  <p>Nessuna richiesta<?= $status !== '' ? ' con questo stato' : '' ?>.</p>
<?php else: ?>
  <table>
    <tr><th>Data</th><th>Tipo</th><th>Nome</th><th>Email</th><th>Evento</th><th>Stato</th></tr>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td><?= e((string) $row['created_at']) ?></td>
        <td><?= $row['type'] === 'quote' ? 'preventivo' : 'contatto' ?></td>
        <td><a href="<?= e(admin_url('richieste/' . (int) $row['id'])) ?>"><?= e((string) $row['name']) ?></a></td>
        <td class="muted"><?= e((string) $row['email']) ?></td>
        <td class="muted"><?= e((string) ($row['event_date'] ?? '')) ?></td>
        <td><?= e((string) $row['status']) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
