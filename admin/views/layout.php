<?php
/**
 * Layout del back office. Nessun design: conta che sia chiaro e veloce.
 *
 * @var string $title
 * @var string $content
 * @var array<string, mixed>|null $user
 * @var list<array{type: string, message: string}> $flash
 */
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title ?? 'Back office') ?> — Gaia Events</title>
<style>
  :root { --bordo: #e8e2d9; --testo: #2d2d2d; --accento: #722f37; }
  * { box-sizing: border-box; }
  body { margin: 0; font: 15px/1.6 system-ui, sans-serif; color: var(--testo); background: #faf8f5; }
  header.top { display: flex; gap: 1rem; align-items: center; padding: .75rem 1rem; background: #fff; border-bottom: 1px solid var(--bordo); }
  header.top strong { font-size: 1rem; }
  nav.side { display: flex; flex-wrap: wrap; gap: .25rem 1rem; padding: .5rem 1rem; background: #fff; border-bottom: 1px solid var(--bordo); font-size: .875rem; }
  main { max-width: 70rem; margin: 0 auto; padding: 1.5rem 1rem 4rem; }
  h1 { font-size: 1.4rem; margin-top: 0; }
  h2 { font-size: 1.1rem; }
  a { color: var(--accento); }
  table { width: 100%; border-collapse: collapse; background: #fff; }
  th, td { padding: .5rem .6rem; border-bottom: 1px solid var(--bordo); text-align: left; vertical-align: top; }
  th { font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
  label { display: block; margin-top: .9rem; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
  input[type=text], input[type=email], input[type=password], input[type=number], input[type=date], select, textarea {
    width: 100%; padding: .45rem .55rem; border: 1px solid var(--bordo); background: #fff; font: inherit;
  }
  textarea { min-height: 6rem; }
  .pair { display: grid; gap: .75rem; grid-template-columns: 1fr 1fr; }
  .pair > div > span { font-size: .7rem; color: #8b9a7d; }
  .help { font-size: .78rem; color: #666; margin: .2rem 0 0; }
  button, .btn { display: inline-block; padding: .5rem .9rem; border: 1px solid var(--accento); background: var(--accento); color: #fff; font: inherit; cursor: pointer; text-decoration: none; }
  .btn.ghost, button.ghost { background: #fff; color: var(--accento); }
  .flash { padding: .6rem .8rem; margin-bottom: 1rem; border-left: 4px solid; background: #fff; }
  .flash.success { border-color: #8b9a7d; }
  .flash.error { border-color: #722f37; }
  .cards { display: grid; gap: .75rem; grid-template-columns: repeat(auto-fill, minmax(10rem, 1fr)); }
  .card { padding: .8rem; background: #fff; border: 1px solid var(--bordo); }
  .card b { display: block; font-size: 1.5rem; }
  .actions { margin-top: 1.5rem; display: flex; gap: .5rem; flex-wrap: wrap; }
  .muted { color: #666; font-size: .85rem; }
  fieldset { border: 1px solid var(--bordo); background: #fff; margin: 1rem 0; }
  .limite { margin: .4rem 0 0; padding: .35rem .6rem; border-left: 4px solid #8b9a7d; background: #f6f5f2; font-size: .85rem; }
  .limite-card { border-color: #c9a962; }
  .limite-thumbnail { border-color: #722f37; }
  legend { padding: 0 .4rem; font-size: .8rem; text-transform: uppercase; }
</style>
</head>
<body>

<header class="top">
  <strong>Gaia Events — back office</strong>
  <?php if (is_array($user ?? null)): ?>
    <span class="muted"><?= e((string) $user['name']) ?> (<?= e((string) $user['role']) ?>)</span>
    <form method="post" action="<?= e(admin_url('logout')) ?>" style="margin-left:auto">
      <?= Gaia\Admin\Csrf::field() ?>
      <button class="ghost" type="submit">Esci</button>
    </form>
  <?php endif; ?>
</header>

<?php if (is_array($user ?? null)): ?>
<nav class="side">
  <a href="<?= e(admin_url()) ?>">Pannello</a>
  <a href="<?= e(admin_url('impostazioni')) ?>">Impostazioni</a>
  <a href="<?= e(admin_url('pagine')) ?>">Pagine</a>
  <a href="<?= e(admin_url('hero')) ?>">Hero</a>
  <a href="<?= e(admin_url('luoghi')) ?>">Luoghi</a>
  <a href="<?= e(admin_url('location')) ?>">Location</a>
  <a href="<?= e(admin_url('highlights')) ?>">Highlights</a>
  <a href="<?= e(admin_url('storie')) ?>">I miei eventi</a>
  <a href="<?= e(admin_url('partner')) ?>">Partner</a>
  <a href="<?= e(admin_url('instagram')) ?>">Instagram</a>
  <a href="<?= e(admin_url('eventi')) ?>">Eventi</a>
  <a href="<?= e(admin_url('servizi')) ?>">Servizi</a>
  <a href="<?= e(admin_url('recensioni')) ?>">Recensioni</a>
  <a href="<?= e(admin_url('stampa')) ?>">Stampa</a>
  <a href="<?= e(admin_url('clienti')) ?>">Clienti</a>
  <a href="<?= e(admin_url('blog')) ?>">Magazine</a>
  <a href="<?= e(admin_url('faq')) ?>">FAQ</a>
  <a href="<?= e(admin_url('immagini')) ?>">Immagini</a>
  <a href="<?= e(admin_url('richieste')) ?>">Richieste</a>
</nav>
<?php endif; ?>

<main>
  <?php foreach (($flash ?? []) as $message): ?>
    <p class="flash <?= e($message['type']) ?>"><?= e($message['message']) ?></p>
  <?php endforeach; ?>

  <?= $content ?>
</main>

</body>
</html>
