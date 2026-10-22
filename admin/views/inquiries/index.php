<?php
/**
 * @var list<array<string, mixed>> $rows
 * @var string $status
 * @var array<string, string> $stati
 * @var array<string, int> $conteggi
 */

$tipi = [
    'matrimonio' => 'Matrimonio',
    'unione_civile' => 'Unione civile',
    'festa' => 'Festa privata',
    'aziendale' => 'Evento aziendale',
];
$mesi = ['gen', 'feb', 'mar', 'apr', 'mag', 'giu', 'lug', 'ago', 'set', 'ott', 'nov', 'dic'];

/** Chi scrive: i due nomi, oppure nome e azienda. */
$chi = static function (array $r): string {
    if (!empty($r['company'])) {
        return $r['name'] . ' — ' . $r['company'];
    }

    return !empty($r['partner_name']) ? $r['name'] . ' e ' . $r['partner_name'] : (string) $r['name'];
};

/** Quando, secondo la precisione data. */
$quando = static function (array $r) use ($mesi): string {
    if (empty($r['event_date'])) {
        return $r['type'] === 'quote' ? 'da definire' : '';
    }
    [$y, $m, $d] = explode('-', (string) $r['event_date']);
    if (($r['date_precision'] ?? '') === 'mese') {
        return $mesi[(int) $m - 1] . ' ' . $y;
    }

    return (int) $d . ' ' . $mesi[(int) $m - 1] . ' ' . $y;
};
?>
<h1>Richieste dal sito</h1>

<p class="muted">
  <a href="<?= e(admin_url('richieste')) ?>"<?= $status === '' ? ' aria-current="page"' : '' ?>>Tutte (<?= array_sum($conteggi) ?>)</a>
  <?php foreach ($stati as $key => $label): ?>
    · <a href="<?= e(admin_url('richieste?stato=' . $key)) ?>"<?= $status === $key ? ' aria-current="page"' : '' ?>><?= e($label === 'nuova' ? 'nuove' : ($label === 'contattata' ? 'contattate' : 'chiuse')) ?> (<?= (int) ($conteggi[$key] ?? 0) ?>)</a>
  <?php endforeach; ?>
</p>

<?php if ($rows === []): ?>
  <p>Nessuna richiesta<?= $status !== '' ? ' con questo stato' : '' ?>.</p>
<?php else: ?>
  <table>
    <tr><th>Ricevuta</th><th>Chi</th><th>Tipo</th><th>Quando</th><th>Ospiti</th><th>Stato</th><th>Mail</th></tr>
    <?php foreach ($rows as $row): ?>
      <tr>
        <td class="muted"><?= e(substr((string) $row['created_at'], 0, 16)) ?></td>
        <td><a href="<?= e(admin_url('richieste/' . (int) $row['id'])) ?>"><?= e($chi($row)) ?></a></td>
        <td><?= e($row['type'] === 'quote' ? ($tipi[(string) $row['event_kind']] ?? 'Dal tool Conosciamoci') : 'Contatto') ?></td>
        <td><?= e($quando($row)) ?></td>
        <td><?= e((string) ($row['guests'] ?? str_replace(['fino-', 'oltre-', '-'], ['fino a ', 'oltre ', '–'], (string) ($row['guests_range'] ?? '')))) ?></td>
        <td><strong><?= e($stati[(string) $row['status']] ?? (string) $row['status']) ?></strong></td>
        <td><?= !empty($row['mail_error']) ? '<span title="' . e((string) $row['mail_error']) . '">non inviata</span>' : (!empty($row['mail_sent_at']) ? 'inviata' : '—') ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>
