<?php
/** Pagina di accesso: fuori dal layout, non mostra la navigazione. */
$flash = Gaia\Admin\Session::takeFlash();
?>
<!doctype html>
<html lang="it">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Accedi — Gaia Events</title>
<style>
  body { margin: 0; min-height: 100vh; display: grid; place-items: center; font: 15px/1.6 system-ui, sans-serif; background: #faf8f5; color: #2d2d2d; }
  form { width: min(22rem, 92vw); padding: 1.5rem; background: #fff; border: 1px solid #e8e2d9; }
  h1 { font-size: 1.2rem; margin: 0 0 1rem; }
  label { display: block; margin-top: .9rem; font-size: .8rem; text-transform: uppercase; letter-spacing: .04em; }
  input { width: 100%; padding: .5rem; border: 1px solid #e8e2d9; font: inherit; }
  button { margin-top: 1.2rem; width: 100%; padding: .6rem; border: 0; background: #722f37; color: #fff; font: inherit; cursor: pointer; }
  .flash { padding: .6rem .8rem; margin-bottom: 1rem; border-left: 4px solid #722f37; background: #faf8f5; }
</style>
</head>
<body>
<form method="post" action="<?= e(admin_url('login')) ?>">
  <h1>Back office Gaia Events</h1>

  <?php foreach ($flash as $message): ?>
    <p class="flash"><?= e($message['message']) ?></p>
  <?php endforeach; ?>

  <?= Gaia\Admin\Csrf::field() ?>

  <label for="email">Email</label>
  <input id="email" type="email" name="email" autocomplete="username" required autofocus>

  <label for="password">Password</label>
  <input id="password" type="password" name="password" autocomplete="current-password" required>

  <button type="submit">Accedi</button>
</form>
</body>
</html>
