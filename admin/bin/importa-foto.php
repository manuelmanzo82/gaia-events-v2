<?php

/**
 * Importa le foto dei fotografi gia' caricate via SFTP in admin/storage/import/
 * come se fossero passate dall'upload del back office.
 *
 *   php8.3 -q admin/bin/importa-foto.php            # importa
 *   php8.3 -q admin/bin/importa-foto.php --prova    # mostra cosa farebbe, non scrive
 *   php8.3 -q admin/bin/importa-foto.php --verifica # originali e varianti tutti sul disco?
 *   php8.3 -q admin/bin/importa-foto.php --ripara   # cancella righe incomplete e file orfani
 *
 * Sullo staging IONOS la shell ha un tetto di memoria virtuale (ulimit -v,
 * 768 MB, non alzabile) che un solo processo su 110 foto supera a meta':
 * li' si lancia un processo per foto e poi una volta senza --solo per i
 * collegamenti, con stdin da /dev/null (php-cgi senza stdin va in segfault):
 *
 *   cd gaia-staging
 *   for f in $(ls admin/storage/import | grep -v json); do
 *     php8.3 -q admin/bin/importa-foto.php --solo=$f < /dev/null
 *   done
 *   php8.3 -q admin/bin/importa-foto.php < /dev/null
 *   php8.3 -q admin/bin/importa-foto.php --verifica < /dev/null
 *
 * Legge admin/storage/import/manifesto.json, che per ogni file dice il nome
 * parlante da dare in uploads/, l'alt IT/EN, la nota interna e i ruoli:
 * ordine nella galleria hero della home, ordine tra gli highlights della
 * home, ordine tra quelli della pagina Chi sono. In coda al manifesto i
 * partner (fotografi) da creare, non pubblicati.
 *
 * Ogni file passa da ImageService::storeFile(): stesse varianti WebP/AVIF,
 * stesso max_css_width e usage_tier dell'upload dal back office, e nessun
 * metadato dell'originale (EXIF, GPS, fotocamera, autore) arriva in
 * uploads/, perche' GD riscrive solo i pixel. Lo script lo verifica sul
 * primo file generato e si ferma se trova ancora qualcosa.
 *
 * Rilanciarlo e' sicuro: una foto gia' in `media` (stesso path) viene
 * saltata, i collegamenti (highlights, sezioni, hero, partner) vengono
 * riallineati al manifesto senza duplicati.
 *
 * A import finito la cartella storage/import/ va cancellata a mano: gli
 * originali con i metadati non devono restare sul server.
 */

declare(strict_types=1);

if (!empty($_SERVER['REQUEST_METHOD']) || !empty($_SERVER['HTTP_HOST'])) {
    http_response_code(403);
    exit("Questo script si usa solo da riga di comando.\n");
}

foreach (['STDIN' => 'php://stdin', 'STDOUT' => 'php://stdout', 'STDERR' => 'php://stderr'] as $nome => $flusso) {
    if (!defined($nome)) {
        define($nome, fopen($flusso, str_starts_with($flusso, 'php://stdin') ? 'rb' : 'wb'));
    }
}

require __DIR__ . '/../src/bootstrap.php';

use Gaia\Admin\Config;
use Gaia\Admin\Db;
use Gaia\Admin\ImageService;
use Gaia\Admin\Validator;

$options = getopt('', ['prova', 'verifica', 'ripara', 'solo::', 'manifesto::']) ?: [];
$prova = isset($options['prova']);

// --solo=NNN.jpg importa quel solo file e si ferma prima dei collegamenti.
// Serve sul server: la shell ha un tetto di memoria virtuale (ulimit -v)
// che non si puo' alzare e un processo che macina 110 foto lo supera a
// meta' strada, lasciando varianti vuote. Un processo per foto riparte
// ogni volta da zero. Il ciclo sta nel commento in testa al file.
$solo = isset($options['solo']) ? basename((string) $options['solo']) : null;

$importDir = realpath(__DIR__ . '/../storage/import') ?: __DIR__ . '/../storage/import';
$manifestoFile = isset($options['manifesto'])
    ? (string) $options['manifesto']
    : $importDir . '/manifesto.json';

// ---------------------------------------------------------------------
// --verifica / --ripara: controlla che ogni riga di `media` abbia davvero
// originale e varianti sul disco. Una variante puo' mancare se l'encoder
// GD fallisce su una larghezza (succede, di rado, con imagewebp) o se lo
// script e' stato interrotto a meta'. Con --ripara le righe incomplete
// vengono cancellate insieme ai loro file, cosi' il rilancio dell'import
// le rigenera da capo; i file orfani (senza riga) vengono tolti.
// ---------------------------------------------------------------------

if (isset($options['verifica']) || isset($options['ripara'])) {
    $ripara = isset($options['ripara']);
    $uploads = rtrim((string) Config::get('uploads_dir'), '/');
    $rotte = 0;

    foreach (Db::all('SELECT * FROM media ORDER BY id') as $riga) {
        $path = (string) $riga['path'];
        $base = preg_replace('/\.[^.\/]+$/', '', $path) ?? $path;
        $mancanti = [];

        // Un file a 0 byte conta come mancante: e' quello che lascia
        // imagewebp() quando l'encoder fallisce a meta'.
        $manca = static fn (string $file): bool => !is_file($file) || (int) filesize($file) === 0;

        if ($manca($uploads . '/' . $path)) {
            $mancanti[] = $path;
        }

        $widths = json_decode((string) ($riga['variant_widths'] ?? '[]'), true) ?: [];
        foreach ($widths as $w) {
            foreach (['webp' => (bool) $riga['has_webp'], 'avif' => (bool) $riga['has_avif']] as $formato => $atteso) {
                if ($atteso && $manca(sprintf('%s/%s-%d.%s', $uploads, $base, (int) $w, $formato))) {
                    $mancanti[] = sprintf('%s-%d.%s', $base, (int) $w, $formato);
                }
            }
        }

        if ($mancanti === []) {
            continue;
        }

        $rotte++;
        echo "  #{$riga['id']} {$path}: mancano " . implode(', ', $mancanti) . "\n";

        if ($ripara) {
            ImageService::delete($path);
            Db::delete('media', (int) $riga['id']);
            echo "    riga e file cancellati: il prossimo import la rigenera.\n";
        }
    }

    // File in uploads/ senza una riga: resti di un'esecuzione interrotta.
    $cartellaManifesto = 'foto';
    if (is_file($manifestoFile)) {
        $m = json_decode((string) file_get_contents($manifestoFile), true);
        $cartellaManifesto = trim((string) ($m['cartella'] ?? 'foto'), '/') ?: 'foto';
    }
    $basiNote = [];
    foreach (Db::all('SELECT path FROM media') as $riga) {
        $basiNote[preg_replace('/\.[^.\/]+$/', '', (string) $riga['path'])] = true;
    }
    $orfani = 0;
    foreach (glob($uploads . '/' . $cartellaManifesto . '/*') ?: [] as $file) {
        $nome = basename($file);
        $base = $cartellaManifesto . '/' . (preg_replace('/(-\d+)?\.[^.]+$/', '', $nome) ?? $nome);
        if (isset($basiNote[$base])) {
            continue;
        }
        $orfani++;
        echo "  orfano: {$cartellaManifesto}/{$nome}" . ($ripara ? ' (cancellato)' : '') . "\n";
        if ($ripara) {
            @unlink($file);
        }
    }

    echo "\nRighe incomplete: {$rotte}. File orfani: {$orfani}." . ($ripara ? " Ora rilancia l'import.\n" : "\n");
    exit($rotte + $orfani === 0 ? 0 : 1);
}

if (!is_file($manifestoFile)) {
    fwrite(STDERR, "Manifesto non trovato: {$manifestoFile}\n");
    exit(1);
}

$manifesto = json_decode((string) file_get_contents($manifestoFile), true);
if (!is_array($manifesto) || !is_array($manifesto['foto'] ?? null)) {
    fwrite(STDERR, "Manifesto non valido: serve una chiave 'foto' con l'elenco dei file.\n");
    exit(1);
}

$cartella = trim((string) ($manifesto['cartella'] ?? 'foto'), '/');
if ($cartella === '') {
    $cartella = 'foto';
}

echo ($prova ? '[PROVA] ' : '') . "Import da {$importDir} nella cartella uploads/{$cartella}/\n";
echo 'AVIF: ' . (ImageService::avifSupported() ? 'supportato' : 'NON supportato, solo WebP') . "\n\n";

// ---------------------------------------------------------------------
// 1. Immagini -> media
// ---------------------------------------------------------------------

$idPerFile = [];      // file del manifesto -> media.id
$importate = 0;
$saltate = 0;
$errori = [];
$metadatiVerificati = false;

foreach ($manifesto['foto'] as $indice => $foto) {
    $file = (string) ($foto['file'] ?? '');
    if ($solo !== null && $file !== $solo) {
        continue;
    }
    $nome = Validator::slug((string) ($foto['nome'] ?? pathinfo($file, PATHINFO_FILENAME)));

    if ($file === '' || $nome === '') {
        $errori[] = "Voce #{$indice}: manca 'file' o 'nome'.";
        continue;
    }

    $sorgente = $importDir . '/' . basename($file);
    $estensione = strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'png' ? 'png' : 'jpg';
    $pathAtteso = $cartella . '/' . $nome . '.' . $estensione;

    $esistente = Db::one('SELECT id FROM media WHERE path = ?', [$pathAtteso]);
    if ($esistente !== null) {
        $idPerFile[$file] = (int) $esistente['id'];
        $saltate++;
        echo "  = {$file} -> {$pathAtteso} (gia' in media #{$esistente['id']})\n";
        continue;
    }

    if (!is_file($sorgente)) {
        $errori[] = "{$file}: non trovato in storage/import/.";
        continue;
    }

    if ($prova) {
        echo "  + {$file} -> {$pathAtteso}\n";
        continue;
    }

    try {
        $riga = ImageService::storeFile($sorgente, $nome . '.' . $estensione, $cartella);
    } catch (\Throwable $exception) {
        $errori[] = "{$file}: " . $exception->getMessage();
        echo "  ! {$file}: " . $exception->getMessage() . "\n";
        continue;
    }

    $riga['alt_it'] = trim((string) ($foto['alt_it'] ?? '')) ?: null;
    $riga['alt_en'] = trim((string) ($foto['alt_en'] ?? '')) ?: null;
    $riga['note_interne'] = trim((string) ($foto['note'] ?? '')) ?: null;

    $id = Db::insert('media', $riga);
    $idPerFile[$file] = $id;
    $importate++;

    echo sprintf(
        "  + %s -> %s  #%d  %dx%d  %s (max %d px)  webp=%s avif=%s\n",
        $file,
        $riga['path'],
        $id,
        $riga['width'],
        $riga['height'],
        $riga['usage_tier'],
        $riga['max_css_width'],
        $riga['has_webp'] ? 'si' : 'no',
        $riga['has_avif'] ? 'si' : 'no',
    );

    // Controllo sui metadati, una volta sola, sul primo JPEG generato:
    // se GD avesse conservato qualcosa dell'originale ci fermiamo subito.
    if (!$metadatiVerificati && $estensione === 'jpg' && function_exists('exif_read_data')) {
        $metadatiVerificati = true;
        $generato = rtrim((string) Config::get('uploads_dir'), '/') . '/' . $riga['path'];
        $exif = @exif_read_data($generato, 'ANY_TAG', true) ?: [];
        $sospetti = array_intersect(array_keys($exif), ['IFD0', 'EXIF', 'GPS', 'MAKERNOTE', 'INTEROP']);

        if ($sospetti !== []) {
            fwrite(STDERR, "Metadati ancora presenti in {$riga['path']}: " . implode(', ', $sospetti) . ". Mi fermo.\n");
            exit(1);
        }

        echo "    verifica metadati su {$riga['path']}: nessun blocco EXIF/GPS/fotocamera.\n";
    }
}

echo "\nImmagini: {$importate} importate, {$saltate} gia' presenti, " . count($errori) . " errori.\n";

if ($solo !== null) {
    foreach ($errori as $errore) {
        echo "  ! {$errore}\n";
    }
    exit($errori === [] ? 0 : 1);
}

if ($prova) {
    echo "\n[PROVA] Collegamenti e partner non toccati.\n";
    foreach ($errori as $errore) {
        echo "  ! {$errore}\n";
    }
    exit($errori === [] ? 0 : 1);
}

// ---------------------------------------------------------------------
// 2. Highlights e sezioni (home, chi-sono)
// ---------------------------------------------------------------------

$sezioni = ['home' => 'home', 'chi_sono' => 'chi-sono'];
$highlights = 0;

Db::transaction(function () use ($manifesto, $idPerFile, $sezioni, &$highlights): void {
    foreach ($manifesto['foto'] as $foto) {
        $file = (string) ($foto['file'] ?? '');
        $mediaId = $idPerFile[$file] ?? null;
        if ($mediaId === null) {
            continue;
        }

        $ruoli = [];
        foreach ($sezioni as $chiave => $sezione) {
            if (isset($foto[$chiave]) && (int) $foto[$chiave] > 0) {
                $ruoli[$sezione] = (int) $foto[$chiave];
            }
        }

        if ($ruoli === []) {
            continue;
        }

        $riga = Db::one('SELECT id FROM highlights WHERE media_id = ?', [$mediaId]);
        $highlightId = $riga !== null
            ? (int) $riga['id']
            : Db::insert('highlights', ['media_id' => $mediaId, 'active' => 1]);

        foreach ($ruoli as $sezione => $ordine) {
            Db::run(
                'INSERT INTO highlight_sections (highlight_id, section, sort_order) VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order)',
                [$highlightId, $sezione, $ordine],
            );
        }

        $highlights++;
    }
});

echo "Highlights collegati: {$highlights}.\n";

// ---------------------------------------------------------------------
// 3. Galleria hero della home
// ---------------------------------------------------------------------

$heroFoto = array_filter(
    $manifesto['foto'],
    static fn (array $foto): bool => isset($foto['hero'])
        && (int) $foto['hero'] > 0
        && isset($idPerFile[(string) ($foto['file'] ?? '')]),
);

if ($heroFoto !== []) {
    $hero = Db::one('SELECT id FROM page_hero WHERE page_key = ?', ['home']);
    $heroId = $hero !== null ? (int) $hero['id'] : Db::insert('page_hero', ['page_key' => 'home']);

    foreach ($heroFoto as $foto) {
        Db::run(
            'INSERT INTO page_hero_images (hero_id, media_id, sort_order) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order)',
            [$heroId, $idPerFile[(string) $foto['file']], (int) $foto['hero']],
        );
    }

    echo 'Galleria hero "home": ' . count($heroFoto) . " foto.\n";
}

// ---------------------------------------------------------------------
// 4. Partner (fotografi), non pubblicati
// ---------------------------------------------------------------------

$partnerCreati = 0;
foreach ((array) ($manifesto['partner'] ?? []) as $partner) {
    $nome = trim((string) ($partner['name'] ?? ''));
    if ($nome === '') {
        continue;
    }

    if (Db::one('SELECT id FROM partners WHERE name = ?', [$nome]) !== null) {
        continue;
    }

    Db::insert('partners', [
        'name' => $nome,
        'category' => (string) ($partner['category'] ?? 'fotografo'),
        'active' => (int) ($partner['active'] ?? 0),
        'sort_order' => (int) ($partner['sort_order'] ?? 0),
    ]);
    $partnerCreati++;
}

echo "Partner creati: {$partnerCreati}.\n";

foreach ($errori as $errore) {
    echo "  ! {$errore}\n";
}

echo "\nFatto. Ora cancella admin/storage/import/: gli originali con i metadati non devono restare sul server.\n";

exit($errori === [] ? 0 : 1);
