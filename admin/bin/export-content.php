<?php

/**
 * Export dei contenuti da riga di comando.
 *
 * Utile sul server o in locale, quando non si vuole passare per
 * api/export.php. Scrive un file JSON per sezione.
 *
 *   php admin/bin/export-content.php --out=../site/src/content
 *
 * Senza --out stampa tutto su stdout.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Questo script si usa solo da riga di comando.\n");
}

require __DIR__ . '/../src/bootstrap.php';

use Gaia\Admin\Exporter;

$options = getopt('', ['out::', 'pretty']) ?: [];
$out = isset($options['out']) ? (string) $options['out'] : null;

$flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
if ($out !== null || isset($options['pretty'])) {
    $flags |= JSON_PRETTY_PRINT;
}

$content = Exporter::all();

if ($out === null) {
    echo json_encode($content, $flags), "\n";
    exit(0);
}

// Il percorso è relativo alla cartella dello script, non alla cwd.
$directory = str_starts_with($out, '/') || preg_match('/^[A-Za-z]:/', $out) === 1
    ? $out
    : __DIR__ . '/' . $out;

if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
    fwrite(STDERR, "Cartella di destinazione non creabile: {$directory}\n");
    exit(1);
}

foreach ($content as $name => $data) {
    $file = rtrim($directory, '/\\') . '/' . $name . '.json';

    if (file_put_contents($file, json_encode($data, $flags) . "\n") === false) {
        fwrite(STDERR, "Scrittura non riuscita: {$file}\n");
        exit(1);
    }

    echo "scritto {$name}.json\n";
}

echo "Fatto.\n";
