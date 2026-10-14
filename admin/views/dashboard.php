<?php
/**
 * @var array<string, int> $counts
 * @var int $newInquiries
 * @var list<array<string, mixed>> $publishLog
 * @var bool $avifSupported
 */
?>
<h1>Pannello</h1>

<?php if ($newInquiries > 0): ?>
  <p class="flash success">
    <a href="<?= e(admin_url('richieste?stato=new')) ?>">
      <?= (int) $newInquiries ?> richieste nuove da leggere
    </a>
  </p>
<?php endif; ?>

<div class="cards">
  <?php foreach ($counts as $label => $value): ?>
    <div class="card"><b><?= (int) $value ?></b><?= e((string) $label) ?></div>
  <?php endforeach; ?>
</div>

<h2>Pubblicazione</h2>
<p class="muted">
  Il pulsante avvia la GitHub Action: scarica i contenuti, ricostruisce il sito
  e lo carica sull'hosting. Ci vogliono pochi minuti.
</p>

<form method="post" action="<?= e(admin_url('pubblica')) ?>">
  <?= Gaia\Admin\Csrf::field() ?>
  <button type="submit">Pubblica il sito</button>
</form>

<?php if ($publishLog !== []): ?>
  <h2>Ultime pubblicazioni</h2>
  <table>
    <tr><th>Quando</th><th>Chi</th><th>Esito</th><th>Dettaglio</th></tr>
    <?php foreach ($publishLog as $row): ?>
      <tr>
        <td><?= e((string) $row['created_at']) ?></td>
        <td><?= e((string) ($row['user_name'] ?? '—')) ?></td>
        <td><?= e((string) $row['status']) ?></td>
        <td class="muted"><?= e((string) ($row['message'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<h2>Server</h2>
<p class="muted">
  Varianti immagine: WebP sempre attivo.
  AVIF <?= $avifSupported ? 'supportato' : 'non supportato su questo hosting (nessun problema: resta il WebP)' ?>.
</p>
