<?php

/**
 * Crea o aggiorna un utente del back office.
 *
 * Serve per il primo accesso: nel database non c'è nessun utente e nessuna
 * password di default.
 *
 *   php admin/bin/create-user.php --email=gaia@example.it --name="Gaia Crognale" --role=editor
 *
 * La password non si passa come argomento (resterebbe nella cronologia della
 * shell): lo script la chiede o, se il terminale non è interattivo, la genera
 * e la stampa una volta sola.
 */

declare(strict_types=1);

// Il controllo giusto non e' «sono in CLI» ma «non sono una richiesta web»:
// molti hosting condivisi, IONOS compreso, da shell offrono solo php-cgi, e
// con il controllo sulla SAPI questi script non partirebbero mai.
if (!empty($_SERVER['REQUEST_METHOD']) || !empty($_SERVER['HTTP_HOST'])) {
    http_response_code(403);
    exit("Questo script si usa solo da riga di comando.\n");
}

// php-cgi non definisce STDIN, STDOUT e STDERR: sono costanti della sola
// SAPI cli. Senza queste tre righe lo script muore sul primo fwrite.
foreach (['STDIN' => 'php://stdin', 'STDOUT' => 'php://stdout', 'STDERR' => 'php://stderr'] as $nome => $flusso) {
    if (!defined($nome)) {
        define($nome, fopen($flusso, str_starts_with($flusso, 'php://stdin') ? 'rb' : 'wb'));
    }
}

require __DIR__ . '/../src/bootstrap.php';

use Gaia\Admin\Auth;
use Gaia\Admin\Db;

$options = getopt('', ['email:', 'name:', 'role::']) ?: [];

$email = mb_strtolower(trim((string) ($options['email'] ?? '')));
$name = trim((string) ($options['name'] ?? ''));
$role = (string) ($options['role'] ?? 'editor');

if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    fwrite(STDERR, "Serve --email con un indirizzo valido.\n");
    exit(1);
}

if ($name === '') {
    fwrite(STDERR, "Serve --name.\n");
    exit(1);
}

if (!in_array($role, ['admin', 'editor'], true)) {
    fwrite(STDERR, "--role deve essere admin oppure editor.\n");
    exit(1);
}

/** Legge la password senza mostrarla a schermo, se il terminale lo permette. */
$readPassword = static function (): ?string {
    if (!stream_isatty(STDIN)) {
        // Non e' un terminale: puo' essere una pipe, per esempio da ssh.
        // Cosi' la password non passa dagli argomenti, dove finirebbe
        // nella cronologia della shell e nell'elenco dei processi.
        $riga = fgets(STDIN);
        $password = is_string($riga) ? trim($riga) : '';

        return $password === '' ? null : $password;
    }

    echo 'Password (non viene mostrata): ';
    shell_exec('stty -echo 2>/dev/null');
    $password = trim((string) fgets(STDIN));
    shell_exec('stty echo 2>/dev/null');
    echo "\n";

    return $password === '' ? null : $password;
};

$password = $readPassword();
$generated = false;

if ($password === null) {
    $password = bin2hex(random_bytes(9));
    $generated = true;
}

if (mb_strlen($password) < 12 && !$generated) {
    fwrite(STDERR, "La password deve avere almeno 12 caratteri.\n");
    exit(1);
}

$existing = Db::one('SELECT id FROM admin_users WHERE email = ?', [$email]);

if ($existing === null) {
    Db::insert('admin_users', [
        'email' => $email,
        'password_hash' => Auth::hash($password),
        'name' => $name,
        'role' => $role,
        'active' => 1,
    ]);

    echo "Utente creato: {$email} ({$role})\n";
} else {
    Db::update('admin_users', (int) $existing['id'], [
        'password_hash' => Auth::hash($password),
        'name' => $name,
        'role' => $role,
        'active' => 1,
    ]);

    echo "Utente aggiornato: {$email} ({$role})\n";
}

if ($generated) {
    echo "Password generata: {$password}\n";
    echo "Annotala adesso: non viene salvata in chiaro da nessuna parte.\n";
}
