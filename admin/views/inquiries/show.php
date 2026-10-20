<?php
/**
 * @var array<string, mixed> $row
 * @var array<string, mixed> $payload
 * @var array<string, string> $stati
 */

$id = (int) $row['id'];

$tipi = [
    'matrimonio' => 'Matrimonio',
    'unione_civile' => 'Unione civile',
    'festa' => 'Festa privata',
    'aziendale' => 'Evento aziendale',
];
$contatti = ['email' => 'email', 'whatsapp' => 'WhatsApp', 'telefono' => 'telefono'];

$chi = !empty($row['company'])
    ? $row['name'] . ' — ' . $row['company']
    : (!empty($row['partner_name']) ? $row['name'] . ' e ' . $row['partner_name'] : (string) $row['name']);

// Collegamenti diretti: mail, WhatsApp (solo cifre), telefono.
$cifre = preg_replace('/\D+/', '', (string) ($row['phone'] ?? ''));
if ($cifre !== '' && str_starts_with($cifre, '00')) {
    $cifre = substr($cifre, 2);
} elseif ($cifre !== '' && strlen($cifre) <= 10 && str_starts_with($cifre, '3')) {
    $cifre = '39' . $cifre; // cellulare italiano senza prefisso
}

// Il riepilogo leggibile mandato dal modulo (etichette in italiano).
$riepilogo = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
?>
<h1><?= e($chi) ?></h1>
<p class="muted">
  Richiesta #<?= $id ?> · <?= e($row['type'] === 'quote' ? ($tipi[(string) $row['event_kind']] ?? 'Preventivo') : 'Messaggio dai contatti') ?>
  · ricevuta il <?= e(substr((string) $row['created_at'], 0, 16)) ?> · lingua <?= e((string) $row['lang']) ?>
  · <a href="<?= e(admin_url('richieste')) ?>">torna all'elenco</a>
</p>

<p>
  <a class="btn ghost" href="mailto:<?= e((string) $row['email']) ?>?subject=<?= rawurlencode('Il vostro ' . ($tipi[(string) $row['event_kind']] ?? 'evento') . ' — Gaia Events') ?>">Scrivi una mail</a>
  <?php if ($cifre !== ''): ?>
    <a class="btn ghost" href="https://wa.me/<?= e($cifre) ?>" target="_blank" rel="noopener">WhatsApp</a>
    <a class="btn ghost" href="tel:+<?= e($cifre) ?>">Chiama</a>
  <?php endif; ?>
  <?php if (!empty($row['contact_pref'])): ?>
    <span class="muted"> · preferiscono essere ricontattati via <strong><?= e($contatti[(string) $row['contact_pref']] ?? '') ?></strong></span>
  <?php endif; ?>
</p>

<?php if (!empty($row['mail_error'])): ?>
  <p class="flash error">La notifica via mail non è partita: <?= e((string) $row['mail_error']) ?>. La richiesta è comunque salvata qui.</p>
<?php endif; ?>

<?php if ($riepilogo !== []): ?>
  <h2>Riepilogo</h2>
  <table>
    <?php foreach ($riepilogo as $voce): ?>
      <?php if (is_array($voce) && isset($voce['label'], $voce['value'])): ?>
        <tr><th style="width:12rem"><?= e((string) $voce['label']) ?></th><td><?= nl2br(e((string) $voce['value'])) ?></td></tr>
      <?php endif; ?>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<?php if (!empty($row['letter'])): ?>
  <h2>La lettera</h2>
  <blockquote style="white-space: pre-wrap; font-family: Georgia, serif; font-size: 1.05rem; line-height: 1.6; margin: 0 0 1.5rem; padding: 1rem 1.25rem; border-left: 3px solid #7a2e3a; background: #faf8f5"><?= e((string) $row['letter']) ?></blockquote>
<?php endif; ?>

<h2>Recapiti</h2>
<table>
  <tr><th style="width:12rem">Email</th><td><a href="mailto:<?= e((string) $row['email']) ?>"><?= e((string) $row['email']) ?></a></td></tr>
  <?php if (!empty($row['phone'])): ?>
    <tr><th>Telefono</th><td><?= e((string) $row['phone']) ?></td></tr>
  <?php endif; ?>
  <?php if ($riepilogo === [] && !empty($row['message'])): ?>
    <tr><th>Messaggio</th><td><?= nl2br(e((string) $row['message'])) ?></td></tr>
  <?php endif; ?>
  <?php if (!empty($row['privacy_at'])): ?>
    <tr><th>Consenso privacy</th><td class="muted"><?= e((string) $row['privacy_at']) ?></td></tr>
  <?php endif; ?>
  <tr><th>Notifica a Gaia</th><td class="muted"><?= !empty($row['mail_sent_at']) ? 'inviata il ' . e((string) $row['mail_sent_at']) : (!empty($row['mail_error']) ? 'non inviata' : '—') ?></td></tr>
</table>

<h2>Gestione</h2>
<form method="post" action="<?= e(admin_url('richieste/' . $id)) ?>">
  <?= Gaia\Admin\Csrf::field() ?>

  <label for="status">Stato</label>
  <select id="status" name="status">
    <?php foreach ($stati as $key => $label): ?>
      <option value="<?= e($key) ?>"<?= $row['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
    <?php endforeach; ?>
  </select>

  <label for="notes">Note interne</label>
  <textarea id="notes" name="notes"><?= e((string) ($row['notes'] ?? '')) ?></textarea>

  <div class="actions"><button type="submit">Salva</button></div>
</form>
