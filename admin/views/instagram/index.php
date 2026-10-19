<?php
/**
 * @var list<array<string, mixed>> $posts
 * @var array<string, string> $tipi
 * @var string $handle
 * @var int $numero
 */

$uploadsUrl = rtrim((string) Gaia\Admin\Config::get('uploads_url', '/uploads'), '/');
$visibili = count(array_filter($posts, static fn (array $p): bool => (int) $p['nascosto'] === 0));
?>
<h1>Instagram</h1>
<p class="muted">
  Le foto dei tuoi post, per la sezione Instagram della home: compaiono gli
  ultimi <?= (int) $numero ?> post visibili, dal più recente
  (<?= e($handle !== '' ? $handle : 'profilo non impostato') ?> —
  profilo e numero di post si cambiano in <a href="<?= e(admin_url('impostazioni')) ?>">Impostazioni</a>).
  Senza link al post, la foto porta al profilo.
</p>

<h2>Aggiungi post</h2>
<form method="post" action="<?= e(admin_url('instagram/carica')) ?>" enctype="multipart/form-data">
  <?= Gaia\Admin\Csrf::field() ?>

  <label for="images">Foto (anche più di una insieme)</label>
  <input id="images" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required>
  <p class="help">Basta la foto. Meglio poche alla volta (fino a 8): ogni foto viene preparata nelle varie misure.</p>

  <div class="pair">
    <div>
      <label for="taken_at">Data del post (facoltativa)</label>
      <input id="taken_at" type="date" name="taken_at">
      <p class="help">Vuota = oggi.</p>
    </div>
    <div>
      <label for="tipo">Tipo</label>
      <select id="tipo" name="tipo">
        <?php foreach ($tipi as $valore => $etichetta): ?>
          <option value="<?= e($valore) ?>"><?= e($etichetta) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </div>

  <div class="actions"><button type="submit">Carica</button></div>
</form>

<h2>Post (<?= count($posts) ?>, visibili <?= (int) $visibili ?>)</h2>

<?php if ($posts === []): ?>
  <p>Nessun post: finché non ce n'è almeno uno visibile, la sezione Instagram non compare sul sito.</p>
<?php else: ?>
  <p class="help">
    Trascina dalla maniglia ⠿ (o usa ↑ ↓) per cambiare l'ordine: sul sito conta prima
    la data del post, poi quest'ordine (per esempio tra le foto caricate insieme).
  </p>

  <form id="ig-ordine" method="post" action="<?= e(admin_url('instagram/ordina')) ?>">
    <?= Gaia\Admin\Csrf::field() ?>
    <input type="hidden" name="ordine" value="">
  </form>

  <ol id="ig-lista" class="ig-lista">
    <?php foreach ($posts as $post): ?>
      <?php $id = (int) $post['id']; ?>
      <li class="ig-post<?= (int) $post['nascosto'] === 1 ? ' ig-nascosto' : '' ?>" id="post-<?= $id ?>" data-id="<?= $id ?>">
        <div class="ig-maniglia" aria-hidden="true" title="Trascina per spostare">⠿</div>

        <img src="<?= e($uploadsUrl . '/' . (string) $post['path']) ?>"
             alt="<?= e((string) ($post['alt_it'] ?? '')) ?>"
             width="120" height="150" loading="lazy">

        <form method="post" action="<?= e(admin_url('instagram/' . $id . '/modifica')) ?>" class="ig-campi">
          <?= Gaia\Admin\Csrf::field() ?>
          <div class="pair">
            <div>
              <label for="permalink-<?= $id ?>">Link al post</label>
              <input id="permalink-<?= $id ?>" type="text" name="permalink" placeholder="https://www.instagram.com/p/…"
                     value="<?= e((string) ($post['permalink'] ?? '')) ?>">
            </div>
            <div class="pair">
              <div>
                <label for="data-<?= $id ?>">Data</label>
                <input id="data-<?= $id ?>" type="date" name="taken_at"
                       value="<?= e($post['taken_at'] ? substr((string) $post['taken_at'], 0, 10) : '') ?>">
              </div>
              <div>
                <label for="tipo-<?= $id ?>">Tipo</label>
                <select id="tipo-<?= $id ?>" name="tipo">
                  <?php foreach ($tipi as $valore => $etichetta): ?>
                    <option value="<?= e($valore) ?>" <?= $post['tipo'] === $valore ? 'selected' : '' ?>><?= e($etichetta) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
          </div>
          <div class="pair">
            <div>
              <label for="alt-it-<?= $id ?>">Descrizione della foto (IT)</label>
              <input id="alt-it-<?= $id ?>" type="text" name="alt_it" value="<?= e((string) ($post['alt_it'] ?? '')) ?>">
            </div>
            <div>
              <label for="alt-en-<?= $id ?>">Descrizione della foto (EN)</label>
              <input id="alt-en-<?= $id ?>" type="text" name="alt_en" value="<?= e((string) ($post['alt_en'] ?? '')) ?>">
            </div>
          </div>
          <label for="note-<?= $id ?>">Nota interna (non va sul sito)</label>
          <input id="note-<?= $id ?>" type="text" name="note_interne" value="<?= e((string) ($post['note_interne'] ?? '')) ?>">

          <div class="actions ig-azioni">
            <label class="ig-check"><input type="checkbox" name="nascosto" value="1" <?= (int) $post['nascosto'] === 1 ? 'checked' : '' ?>> Nascosto</label>
            <button type="submit">Salva</button>
            <button type="button" class="ghost" data-sposta="-1" aria-label="Sposta su">↑</button>
            <button type="button" class="ghost" data-sposta="1" aria-label="Sposta giù">↓</button>
            <button type="submit" class="ghost"
                    formaction="<?= e(admin_url('instagram/' . $id . '/visibilita')) ?>">
              <?= (int) $post['nascosto'] === 1 ? 'Mostra' : 'Nascondi' ?>
            </button>
            <button type="submit" class="ghost"
                    formaction="<?= e(admin_url('instagram/' . $id . '/elimina')) ?>">
              Elimina
            </button>
            <?php if ((string) $post['source'] === 'api'): ?>
              <span class="muted">da Instagram</span>
            <?php endif; ?>
          </div>
        </form>
      </li>
    <?php endforeach; ?>
  </ol>
<?php endif; ?>

<style>
  .ig-lista { list-style: none; margin: 1rem 0 0; padding: 0; display: grid; gap: .75rem; }
  .ig-post { display: grid; grid-template-columns: 1.5rem 120px 1fr; gap: 1rem; align-items: start;
             padding: .8rem; background: #fff; border: 1px solid var(--bordo); }
  .ig-post img { width: 120px; height: 150px; object-fit: cover; }
  .ig-nascosto { opacity: .55; }
  .ig-maniglia { cursor: grab; font-size: 1.2rem; line-height: 1; padding-top: .2rem; user-select: none; }
  .ig-trascina { outline: 2px dashed var(--accento); }
  .ig-campi label { margin-top: .4rem; }
  .ig-azioni { margin-top: .8rem; align-items: center; }
  .ig-check { display: inline-flex; gap: .4rem; align-items: center; margin: 0 .5rem 0 0; text-transform: none; letter-spacing: 0; font-size: .9rem; }
  @media (max-width: 40rem) { .ig-post { grid-template-columns: 1.5rem 1fr; } .ig-post .ig-campi { grid-column: 1 / -1; } }
</style>

<script>
  // Ordinamento: trascinando dalla maniglia (mouse) o con ↑ ↓ (tastiera);
  // ogni spostamento salva subito il nuovo ordine.
  (() => {
    const lista = document.getElementById('ig-lista');
    const form = document.getElementById('ig-ordine');
    if (!lista || !form) return;

    const salva = () => {
      form.ordine.value = [...lista.children].map((li) => li.dataset.id).join(',');
      form.submit();
    };

    let trascinato = null;
    for (const li of lista.children) {
      const maniglia = li.querySelector('.ig-maniglia');
      maniglia.addEventListener('mousedown', () => { li.draggable = true; });
      li.addEventListener('dragstart', (e) => { trascinato = li; li.classList.add('ig-trascina'); e.dataTransfer.effectAllowed = 'move'; });
      li.addEventListener('dragend', () => { li.draggable = false; li.classList.remove('ig-trascina'); if (trascinato) { trascinato = null; salva(); } });
      li.addEventListener('dragover', (e) => {
        if (!trascinato || trascinato === li) return;
        e.preventDefault();
        const r = li.getBoundingClientRect();
        lista.insertBefore(trascinato, e.clientY < r.top + r.height / 2 ? li : li.nextSibling);
      });
    }

    lista.addEventListener('click', (e) => {
      const b = e.target.closest('[data-sposta]');
      if (!b) return;
      const li = b.closest('li');
      if (b.dataset.sposta === '-1' && li.previousElementSibling) lista.insertBefore(li, li.previousElementSibling);
      else if (b.dataset.sposta === '1' && li.nextElementSibling) lista.insertBefore(li.nextElementSibling, li);
      else return;
      salva();
    });
  })();
</script>
