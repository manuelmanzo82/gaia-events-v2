<?php

/**
 * Applica le migrazioni SQL non ancora eseguite.
 *
 *   php8.3 -q admin/bin/applica-migrazione.php            # applica tutte
 *   php8.3 -q admin/bin/applica-migrazione.php --lista    # mostra lo stato
 *
 * Legge i file .sql in admin/sql/migrations/ in ordine alfabetico (i nomi
 * iniziano con la data) e registra quelli applicati nella tabella
 * schema_migrations, così rilanciare lo script è sempre sicuro.
 *
 * Il client mysql da shell su IONOS non c'è: si passa da PDO, la stessa
 * connessione del back office. Ogni statement viene eseguito da solo
 * (PDO MySQL non gradisce i multi-statement con prepared emulati).
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

use Gaia\Admin\Db;

$options = getopt('', ['lista']) ?: [];

Db::run(
    'CREATE TABLE IF NOT EXISTS `schema_migrations` (
        `name`       VARCHAR(190) NOT NULL,
        `applied_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci',
);

$directory = __DIR__ . '/../sql/migrations';
$files = glob($directory . '/*.sql') ?: [];
sort($files);

$applicate = array_column(Db::all('SELECT name FROM schema_migrations'), 'name');

if (isset($options['lista'])) {
    foreach ($files as $file) {
        $nome = basename($file);
        echo (in_array($nome, $applicate, true) ? '[applicata] ' : '[da fare]   ') . $nome . "\n";
    }
    exit(0);
}

$eseguite = 0;

foreach ($files as $file) {
    $nome = basename($file);

    if (in_array($nome, $applicate, true)) {
        continue;
    }

    echo "Applico {$nome}…\n";

    $sql = (string) file_get_contents($file);

    // Togli i commenti riga per riga, poi spezza sugli statement: il
    // delimitatore è il ; a fine riga (nessuna stored procedure qui).
    $righe = array_filter(
        array_map('rtrim', preg_split('/\r\n|\r|\n/', $sql) ?: []),
        static fn (string $riga): bool => !str_starts_with(ltrim($riga), '--'),
    );
    $statements = array_filter(
        array_map('trim', explode(";\n", implode("\n", $righe) . "\n")),
        static fn (string $statement): bool => $statement !== '' && $statement !== ';',
    );

    foreach ($statements as $statement) {
        Db::pdo()->exec($statement);
    }

    Db::run('INSERT INTO schema_migrations (name) VALUES (?)', [$nome]);
    $eseguite++;

    echo "  ok (" . count($statements) . " statement)\n";
}

echo $eseguite === 0 ? "Niente da fare: tutte le migrazioni sono già applicate.\n" : "Fatto: {$eseguite} migrazione/i applicate.\n";
