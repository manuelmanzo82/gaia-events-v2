<?php
/**
 * @var string $title
 * @var array<string, string> $pages
 * @var array<string, array<string, mixed>> $rows
 */
?>
<h1><?= e($title) ?></h1>
<p class="muted">
  Titolo, sottotitolo, CTA, video desktop e foto per l'hero di ogni pagina.
  Il video è facoltativo: senza, l'hero usa le foto in dissolvenza.
</p>

<table>
  <tr><th>Pagina</th><th>Titolo (IT)</th><th>Video</th><th>Foto</th><th></th></tr>
  <?php foreach ($pages as $key => $label): ?>
    <?php $row = $rows[$key] ?? null; ?>
    <tr>
      <td><?= e($label) ?></td>
      <td><?= e((string) ($row['title_it'] ?? '—')) ?></td>
      <td><?= !empty($row['video_path']) ? 'MP4' . (!empty($row['video_webm_path']) ? ' + WebM' : '') : '—' ?></td>
      <td>
        <?php if (!empty($row['id'])): ?>
          <a href="<?= e(admin_url('hero/' . (int) $row['id'] . '/galleria')) ?>">galleria</a>
        <?php else: ?>
          —
        <?php endif; ?>
      </td>
      <td><a class="btn" href="<?= e(admin_url('hero/' . $key)) ?>">Modifica</a></td>
    </tr>
  <?php endforeach; ?>
</table>
