<?php

/**
 * Avvio del back office: configurazione, autoload, sessione.
 * Da includere una volta sola, all'inizio di ogni entry point.
 */

declare(strict_types=1);

const ADMIN_ROOT = __DIR__ . '/..';

$configFile = ADMIN_ROOT . '/config.php';

if (!is_file($configFile)) {
    http_response_code(500);
    exit("config.php mancante: copia config.example.php in config.php e compilalo.\n");
}

/** @var array<string, mixed> $config */
$config = require $configFile;

// ---------------------------------------------------------------------
// Errori: a schermo solo in debug, sempre nel log.
// ---------------------------------------------------------------------
error_reporting(E_ALL);
ini_set('display_errors', !empty($config['debug']) ? '1' : '0');
ini_set('log_errors', '1');

$logDir = ADMIN_ROOT . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0750, true);
}
ini_set('error_log', $logDir . '/php-error.log');

date_default_timezone_set('Europe/Rome');
mb_internal_encoding('UTF-8');

// ---------------------------------------------------------------------
// Autoload: Gaia\Admin\Foo → src/Foo.php
// ---------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix = 'Gaia\\Admin\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/' . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});

require __DIR__ . '/helpers.php';

Gaia\Admin\Config::init($config);
Gaia\Admin\Db::init($config['db']);
