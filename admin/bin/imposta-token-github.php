<?php

/**
 * Scrive il token GitHub dentro config.php, senza farlo passare dagli
 * argomenti (resterebbero nella cronologia della shell e nell'elenco dei
 * processi) e senza mai stamparlo.
 *
 * Il token serve al pulsante "Pubblica" del back office, che chiama la
 * GitHub API con un repository_dispatch.
 *
 *   ssh -t UTENTE@HOST
 *   cd gaia-staging && php8.3 -q admin/bin/imposta-token-github.php
 *
 * Lo si può anche passare in pipe, per esempio da un gestore di password:
 *   cat token.txt | php8.3 -q admin/bin/imposta-token-github.php
 *
 * Prima di salvare, il token viene provato davvero sulla repo configurata:
 * se non ha il permesso giusto lo scopriamo adesso, non la prima volta che
 * Gaia preme Pubblica.
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

$configFile = __DIR__ . '/../config.php';

if (!is_file($configFile)) {
    fwrite(STDERR, "config.php non trovato in " . dirname($configFile) . "\n");
    exit(1);
}

/** @var array<string, mixed> $config */
$config = require $configFile;

$owner = (string) ($config['github']['owner'] ?? '');
$repo = (string) ($config['github']['repo'] ?? '');

if ($owner === '' || $repo === '') {
    fwrite(STDERR, "In config.php mancano github.owner o github.repo.\n");
    exit(1);
}

echo "Repo di destinazione: {$owner}/{$repo}\n\n";

// ---------------------------------------------------------------- lettura

if (stream_isatty(STDIN)) {
    echo "Incolla il token GitHub (non viene mostrato) e premi Invio:\n> ";
    shell_exec('stty -echo 2>/dev/null');
    $token = trim((string) fgets(STDIN));
    shell_exec('stty echo 2>/dev/null');
    echo "\n\n";
} else {
    $riga = fgets(STDIN);
    $token = is_string($riga) ? trim($riga) : '';
}

if ($token === '') {
    fwrite(STDERR, "Nessun token ricevuto.\n");
    exit(1);
}

// Un fine-grained token inizia per github_pat_, uno classico per ghp_.
if (!preg_match('/^(github_pat_[A-Za-z0-9_]+|ghp_[A-Za-z0-9]+)$/', $token)) {
    fwrite(STDERR, "Questo non sembra un token GitHub. Atteso github_pat_... o ghp_...\n");
    fwrite(STDERR, "Controlla di averlo incollato per intero, senza spazi.\n");
    exit(1);
}

echo "Token ricevuto: " . strlen($token) . " caratteri, inizia per "
    . substr($token, 0, 11) . "…\n\n";

// ---------------------------------------------------------------- prova

if (!function_exists('curl_init')) {
    fwrite(STDERR, "cURL non disponibile: non posso provare il token.\n");
    exit(1);
}

/**
 * @return array{stato: int, corpo: string}
 */
function chiama(string $url, string $token, ?string $payload = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Accept: application/vnd.github+json',
            'Authorization: Bearer ' . $token,
            'X-GitHub-Api-Version: 2022-11-28',
            'Content-Type: application/json',
            'User-Agent: gaia-events-admin',
        ],
    ]);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $corpo = curl_exec($ch);
    $stato = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    return ['stato' => $stato, 'corpo' => is_string($corpo) ? $corpo : ''];
}

echo "Provo il token sulla repo…\n";
$r = chiama("https://api.github.com/repos/{$owner}/{$repo}", $token);

if ($r['stato'] === 401) {
    fwrite(STDERR, "Il token non è valido o è scaduto (401). Non salvo niente.\n");
    exit(1);
}

if ($r['stato'] === 404) {
    fwrite(STDERR, "Repo non raggiungibile (404). Succede se il token non è stato\n"
        . "autorizzato su questa repo: controlla «Repository access» e che\n"
        . "{$owner}/{$repo} sia fra quelle selezionate. Non salvo niente.\n");
    exit(1);
}

if ($r['stato'] !== 200) {
    fwrite(STDERR, "Risposta inattesa da GitHub ({$r['stato']}). Non salvo niente.\n");
    fwrite(STDERR, substr($r['corpo'], 0, 300) . "\n");
    exit(1);
}

echo "  repo raggiungibile\n";

// Il permesso che serve davvero e' Contents: write. Lo verifichiamo
// guardando cosa dice GitHub dei permessi dell'utente sulla repo.
$dati = json_decode($r['corpo'], true);
$push = is_array($dati) && !empty($dati['permissions']['push']);

if (!$push) {
    fwrite(STDERR, "\nIl token NON ha permesso di scrittura sui contenuti.\n"
        . "Per il repository_dispatch serve «Contents: Read and write».\n"
        . "Non salvo niente: rigenera il token con quel permesso.\n");
    exit(1);
}

echo "  permesso di scrittura sui contenuti: presente\n\n";

// ---------------------------------------------------------------- salvataggio

$sorgente = (string) file_get_contents($configFile);

$nuovo = preg_replace_callback(
    "/('github'\s*=>\s*\[.*?'token'\s*=>\s*)'[^']*'/s",
    static fn (array $m): string => $m[1] . "'" . $token . "'",
    $sorgente,
    1,
    $sostituzioni
);

if ($nuovo === null || $sostituzioni !== 1) {
    fwrite(STDERR, "Non ho trovato la voce github.token in config.php.\n"
        . "Aggiungila a mano: dentro 'github' => [ ... ] la riga 'token' => '...'.\n");
    exit(1);
}

// Una copia prima di toccare il file: se qualcosa va storto si torna indietro.
$backup = $configFile . '.bak-' . date('Ymd-His');
if (!copy($configFile, $backup)) {
    fwrite(STDERR, "Non riesco a fare il backup di config.php. Mi fermo.\n");
    exit(1);
}

if (file_put_contents($configFile, $nuovo) === false) {
    fwrite(STDERR, "Scrittura di config.php non riuscita. Il backup è in {$backup}\n");
    exit(1);
}

@chmod($configFile, 0640);

// Rileggere il file e' l'unico modo onesto di dire che ha funzionato.
$riletto = require $configFile;
$ok = ($riletto['github']['token'] ?? '') === $token;

echo $ok
    ? "Token salvato in config.php (permessi 640).\nBackup: " . basename($backup) . "\n\n"
      . "Adesso il pulsante «Pubblica» del back office può avviare il deploy.\n"
    : "ATTENZIONE: il file è stato scritto ma il valore riletto non corrisponde.\n"
      . "Controlla config.php a mano. Backup: " . basename($backup) . "\n";

exit($ok ? 0 : 1);
