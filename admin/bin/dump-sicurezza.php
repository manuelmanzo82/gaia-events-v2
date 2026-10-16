<?php

/**
 * Dump di sicurezza del database in admin/storage/ (mai servito dal web:
 * il .htaccess blocca storage/ con mod_rewrite [F]).
 *
 *   php8.3 -q admin/bin/dump-sicurezza.php
 *
 * Scrive storage/dump-YYYYmmdd-HHMMSS.sql e ne stampa percorso e peso.
 * Usa PDO, non mysqldump: niente credenziali sulla riga di comando.
 * Pensato per la rete di sicurezza prima di una migrazione: a migrazione
 * riuscita il file va cancellato.
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

$pdo = Db::pdo();

$directory = __DIR__ . '/../storage';
if (!is_dir($directory) && !mkdir($directory, 0750, true) && !is_dir($directory)) {
    fwrite(STDERR, "storage/ non creabile.\n");
    exit(1);
}

$file = $directory . '/dump-' . date('Ymd-His') . '.sql';
$out = fopen($file, 'wb');
if ($out === false) {
    fwrite(STDERR, "File di dump non creabile: {$file}\n");
    exit(1);
}

fwrite($out, "-- Dump di sicurezza " . date('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

$tabelle = array_map(static fn (array $row): string => (string) array_values($row)[0], Db::all('SHOW TABLES'));

foreach ($tabelle as $tabella) {
    $create = Db::one("SHOW CREATE TABLE `{$tabella}`");
    fwrite($out, "DROP TABLE IF EXISTS `{$tabella}`;\n" . (string) ($create['Create Table'] ?? '') . ";\n\n");

    $statement = $pdo->query("SELECT * FROM `{$tabella}`");
    if ($statement === false) {
        continue;
    }

    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $colonne = '`' . implode('`, `', array_keys($row)) . '`';
        $valori = implode(', ', array_map(
            static fn (mixed $valore): string => $valore === null ? 'NULL' : $pdo->quote((string) $valore),
            array_values($row),
        ));
        fwrite($out, "INSERT INTO `{$tabella}` ({$colonne}) VALUES ({$valori});\n");
    }

    fwrite($out, "\n");
}

fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");
fclose($out);

echo $file . ' (' . number_format((float) filesize($file) / 1024, 1, ',', '.') . " KB)\n";
