<?php

/**
 * Export dei contenuti per il build del sito.
 *
 * La GitHub Action chiama questo endpoint con il bearer token (secret
 * EXPORT_TOKEN), riceve un unico JSON con tutti i contenuti e lo divide
 * nei file di site/src/content/.
 *
 *   GET /admin/api/export.php
 *   Authorization: Bearer <token>
 *
 * Con ?file=settings restituisce solo quella sezione.
 *
 * Il token sta in config.php sul server, mai nel codice.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Gaia\Admin\Config;
use Gaia\Admin\Exporter;
use Gaia\Admin\View;

header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store', true);

$expected = (string) Config::get('export_token', '');

if ($expected === '') {
    View::json(['error' => 'export_token non configurato sul server.'], 503);
}

/** Il token può arrivare nell'header o, se il server lo mangia, come parametro. */
$provided = '';

$header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (is_string($header) && preg_match('/^Bearer\s+(.+)$/i', trim($header), $matches) === 1) {
    $provided = trim($matches[1]);
}

// Alcuni hosting condivisi non passano Authorization a PHP in CGI.
if ($provided === '' && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $fallback = (string) $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    if (preg_match('/^Bearer\s+(.+)$/i', trim($fallback), $matches) === 1) {
        $provided = trim($matches[1]);
    }
}

if ($provided === '' || !hash_equals($expected, $provided)) {
    // Nessun dettaglio: non diciamo se il token è assente o sbagliato.
    View::json(['error' => 'Non autorizzato.'], 401);
}

try {
    $all = Exporter::all();
} catch (Throwable $exception) {
    error_log('[export] ' . $exception->getMessage());
    View::json(['error' => 'Export non riuscito.'], 500);
}

$file = $_GET['file'] ?? null;

if (is_string($file) && $file !== '') {
    if (!array_key_exists($file, $all)) {
        View::json(['error' => 'Sezione sconosciuta: ' . $file], 404);
    }

    View::json($all[$file]);
}

View::json([
    'generated_at' => date('c'),
    'site_url' => Config::get('site_url'),
    'content' => $all,
]);
