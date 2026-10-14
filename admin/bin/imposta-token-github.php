<?php

/**
 * Scrive il token GitHub dentro config.php, senza farlo passare dagli
 * argomenti (resterebbero nella cronologia della shell e nell'elenco dei
 * processi) e senza mai stamparlo.
 *
 * Il token serve al pulsante "Pubblica" del back office, che avvia il
 * workflow con una chiamata di workflow_dispatch.
 *
 *   ssh -t UTENTE@HOST
 *   cd gaia-staging && php8.3 -q admin/bin/imposta-token-github.php
 *
 * Lo si può anche passare in pipe, per esempio da un gestore di password:
 *   cat token.txt | php8.3 -q admin/bin/imposta-token-github.php
 *
 * Prima di salvare, il token viene provato davvero: se non ha il permesso
 * giusto lo scopriamo adesso, non la prima volta che Gaia preme Pubblica.
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
    fwrite(STDERR, 'config.php non trovato in ' . dirname($configFile) . "\n");
    exit(1);
}

/** @var array<string, mixed> $config */
$config = require $configFile;

$owner = (string) ($config['github']['owner'] ?? '');
$repo = (string) ($config['github']['repo'] ?? '');
$workflow = (string) ($config['github']['workflow'] ?? 'deploy.yml');
$ref = (string) ($config['github']['ref'] ?? 'main');
$target = (string) ($config['github']['target'] ?? 'production');

if ($owner === '' || $repo === '') {
    fwrite(STDERR, "In config.php mancano github.owner o github.repo.\n");
    exit(1);
}

echo "Repo:     {$owner}/{$repo}\n";
echo "Workflow: {$workflow} sul ramo {$ref}, ambiente {$target}\n\n";

// ------------------------------------------------------------------ lettura

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
if (preg_match('/^(github_pat_[A-Za-z0-9_]+|ghp_[A-Za-z0-9]+)$/', $token) !== 1) {
    fwrite(STDERR, "Questo non sembra un token GitHub: atteso github_pat_... o ghp_...\n");
    fwrite(STDERR, "Controlla di averlo incollato per intero, senza spazi.\n");
    exit(1);
}

echo 'Token ricevuto: ' . strlen($token) . ' caratteri, inizia per '
    . substr($token, 0, 11) . "...\n\n";

// ------------------------------------------------------------------ prova

if (!function_exists('curl_init')) {
    fwrite(STDERR, "cURL non disponibile: non posso provare il token.\n");
    exit(1);
}

/** @return array{stato: int, corpo: string} */
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

/** Scrive l'errore e si ferma senza aver toccato config.php. */
function rinuncia(string $messaggio): never
{
    fwrite(STDERR, $messaggio . "\nNon salvo niente.\n");
    exit(1);
}

$urlWorkflow = "https://api.github.com/repos/{$owner}/{$repo}/actions/workflows/"
    . rawurlencode($workflow);

// 1. Il token vede il workflow? Questo prova gia' Actions in lettura.
echo "Cerco il workflow...\n";

$r = chiama($urlWorkflow, $token);

match (true) {
    $r['stato'] === 401 => rinuncia("Il token non e' valido o e' scaduto (401)."),
    $r['stato'] === 403 => rinuncia(
        "Il token non ha il permesso «Actions» (403).\n"
        . 'Rigeneralo con «Actions: Read and write».'
    ),
    $r['stato'] === 404 => rinuncia(
        "Non trovo il workflow {$workflow} (404). Puo' voler dire due cose:\n"
        . "  - il token non e' autorizzato su {$owner}/{$repo}: controlla\n"
        . "    «Repository access» quando lo generi\n"
        . "  - il file .github/workflows/{$workflow} non e' sul ramo {$ref}"
    ),
    $r['stato'] !== 200 => rinuncia(
        "Risposta inattesa da GitHub ({$r['stato']}):\n" . substr($r['corpo'], 0, 300)
    ),
    default => null,
};

echo "  trovato: il token legge le Actions\n\n";

// 2. Il permesso di SCRITTURA non si puo' dedurre da una GET: GitHub non
//    espone un endpoint che dica «questo token puo' avviare un workflow».
//    L'unico modo onesto e' provarci, e lo facciamo con dry_run attivo:
//    parte un run che non scrive niente sul server.
echo "Provo ad avviarlo in prova a vuoto...\n";

$payload = json_encode([
    'ref' => $ref,
    'inputs' => [
        'target' => $target,
        'skip_content' => 'true',
        'dry_run' => 'true',
    ],
], JSON_UNESCAPED_SLASHES);

$d = chiama($urlWorkflow . '/dispatches', $token, $payload);

match (true) {
    $d['stato'] === 403 => rinuncia(
        "Il token LEGGE le Actions ma non puo' avviarle (403).\n"
        . 'Serve «Actions: Read and write», non solo Read.'
    ),
    $d['stato'] === 422 => rinuncia(
        "GitHub ha rifiutato i parametri (422):\n" . substr($d['corpo'], 0, 300) . "\n"
        . "Controlla che il ramo {$ref} esista e che il workflow accetti gli\n"
        . 'input target, skip_content e dry_run.'
    ),
    $d['stato'] !== 204 => rinuncia(
        "Avvio non riuscito ({$d['stato']}):\n" . substr($d['corpo'], 0, 300)
    ),
    default => null,
};

echo "  avviato: il token puo' far partire il deploy\n";
echo "  (e' una prova a vuoto, non tocca il server: la vedi in Actions)\n\n";

// ------------------------------------------------------------------ salvataggio

$sorgente = (string) file_get_contents($configFile);

$nuovo = preg_replace_callback(
    "/('github'\s*=>\s*\[.*?'token'\s*=>\s*)'[^']*'/s",
    static fn (array $m): string => $m[1] . "'" . $token . "'",
    $sorgente,
    1,
    $sostituzioni
);

if ($nuovo === null || $sostituzioni !== 1) {
    rinuncia(
        "Non ho trovato la voce github.token in config.php.\n"
        . "Aggiungila a mano: dentro 'github' => [ ... ] la riga 'token' => ''."
    );
}

// Una copia prima di toccare il file: se qualcosa va storto si torna indietro.
$backup = $configFile . '.bak-' . date('Ymd-His');

if (!copy($configFile, $backup)) {
    rinuncia('Non riesco a fare il backup di config.php.');
}

if (file_put_contents($configFile, $nuovo) === false) {
    fwrite(STDERR, "Scrittura di config.php non riuscita. Il backup e' in {$backup}\n");
    exit(1);
}

@chmod($configFile, 0640);

// Rileggere il file e' l'unico modo onesto di dire che ha funzionato.
$riletto = require $configFile;
$ok = ($riletto['github']['token'] ?? '') === $token;

if (!$ok) {
    fwrite(STDERR, "ATTENZIONE: file scritto ma il valore riletto non corrisponde.\n");
    fwrite(STDERR, "Controlla config.php a mano. Backup: " . basename($backup) . "\n");
    exit(1);
}

echo "Token salvato in config.php, permessi 640.\n";
echo 'Backup: ' . basename($backup) . "\n\n";
echo "Il pulsante «Pubblica» del back office adesso avvia il deploy.\n";
