<?php

/**
 * Front controller del back office.
 *
 * Tutte le richieste a /admin/* passano da qui grazie al .htaccess della
 * cartella. Gli endpoint pubblici per i form del sito stanno invece in
 * api/, fuori da questo router, perché non hanno sessione né login.
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

use Gaia\Admin\Controllers\AuthController;
use Gaia\Admin\Controllers\DashboardController;
use Gaia\Admin\Controllers\EventsController;
use Gaia\Admin\Controllers\FaqsController;
use Gaia\Admin\Controllers\InquiriesController;
use Gaia\Admin\Controllers\MediaController;
use Gaia\Admin\Controllers\PagesController;
use Gaia\Admin\Controllers\PostsController;
use Gaia\Admin\Controllers\ReviewsController;
use Gaia\Admin\Controllers\ServicesController;
use Gaia\Admin\Controllers\SettingsController;
use Gaia\Admin\Router;
use Gaia\Admin\Session;

Session::start();

// Il back office non va mai indicizzato, in nessun ambiente.
header('X-Robots-Tag: noindex, nofollow', true);
header('X-Frame-Options: DENY', true);
header('X-Content-Type-Options: nosniff', true);
header('Referrer-Policy: same-origin', true);

$router = new Router();

// Accesso
$router->get('/login', [AuthController::class, 'loginForm']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Pannello e pubblicazione
$router->get('/', [DashboardController::class, 'index']);
$router->post('/pubblica', [DashboardController::class, 'publish']);

// Impostazioni e testi
$router->get('/impostazioni', [SettingsController::class, 'index']);
$router->post('/impostazioni', [SettingsController::class, 'save']);

// Pagine statiche
$router->get('/pagine', [PagesController::class, 'index']);
$router->get('/pagine/{key}', [PagesController::class, 'edit']);
$router->post('/pagine/{key}', [PagesController::class, 'save']);

// Sezioni di contenuto: stesse rotte per tutte, cambia il controller.
$resources = [
    'servizi' => ServicesController::class,
    'eventi' => EventsController::class,
    'recensioni' => ReviewsController::class,
    'blog' => PostsController::class,
    'faq' => FaqsController::class,
];

foreach ($resources as $segment => $controller) {
    $router->get("/{$segment}", [$controller, 'index']);
    $router->get("/{$segment}/nuovo", [$controller, 'create']);
    $router->post("/{$segment}/nuovo", [$controller, 'save']);
    $router->get("/{$segment}/{id}/modifica", [$controller, 'edit']);
    $router->post("/{$segment}/{id}/modifica", [$controller, 'save']);
    $router->post("/{$segment}/{id}/elimina", [$controller, 'destroy']);
}

// Galleria degli eventi
$router->get('/eventi/{id}/galleria', [EventsController::class, 'gallery']);
$router->post('/eventi/{id}/galleria/aggiungi', [EventsController::class, 'galleryAdd']);
$router->post('/eventi/{id}/galleria/ordina', [EventsController::class, 'gallerySort']);
$router->post('/eventi/{id}/galleria/{image}/rimuovi', [EventsController::class, 'galleryRemove']);

// Immagini
$router->get('/immagini', [MediaController::class, 'index']);
$router->post('/immagini/carica', [MediaController::class, 'upload']);
$router->post('/immagini/{id}/aggiorna', [MediaController::class, 'update']);
$router->post('/immagini/{id}/elimina', [MediaController::class, 'destroy']);

// Richieste dal sito
$router->get('/richieste', [InquiriesController::class, 'index']);
$router->get('/richieste/{id}', [InquiriesController::class, 'show']);
$router->post('/richieste/{id}', [InquiriesController::class, 'update']);

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI'] ?? '/',
);
