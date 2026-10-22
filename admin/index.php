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
use Gaia\Admin\Controllers\ClientsController;
use Gaia\Admin\Controllers\DashboardController;
use Gaia\Admin\Controllers\FaqsController;
use Gaia\Admin\Controllers\HeroController;
use Gaia\Admin\Controllers\HighlightsController;
use Gaia\Admin\Controllers\InquiriesController;
use Gaia\Admin\Controllers\InstagramController;
use Gaia\Admin\Controllers\LocationsController;
use Gaia\Admin\Controllers\LuoghiController;
use Gaia\Admin\Controllers\MediaController;
use Gaia\Admin\Controllers\PagesController;
use Gaia\Admin\Controllers\PartnersController;
use Gaia\Admin\Controllers\PostsController;
use Gaia\Admin\Controllers\PressController;
use Gaia\Admin\Controllers\ReviewsController;
use Gaia\Admin\Controllers\ServiceCompareController;
use Gaia\Admin\Controllers\DestinationRegioniController;
use Gaia\Admin\Controllers\PostCategoriesController;
use Gaia\Admin\Controllers\VideosController;
use Gaia\Admin\Controllers\ServicesController;
use Gaia\Admin\Controllers\SettingsController;
use Gaia\Admin\Controllers\StoriesController;
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
    'confronto-servizi' => ServiceCompareController::class,
    'regioni-destination' => DestinationRegioniController::class,
    'categorie-magazine' => PostCategoriesController::class,
    'video' => VideosController::class,
    'recensioni' => ReviewsController::class,
    'stampa' => PressController::class,
    'clienti' => ClientsController::class,
    'blog' => PostsController::class,
    'faq' => FaqsController::class,
    'luoghi' => LuoghiController::class,
    'location' => LocationsController::class,
    'highlights' => HighlightsController::class,
    'partner' => PartnersController::class,
    'storie' => StoriesController::class,
];

foreach ($resources as $segment => $controller) {
    $router->get("/{$segment}", [$controller, 'index']);
    $router->get("/{$segment}/nuovo", [$controller, 'create']);
    $router->post("/{$segment}/nuovo", [$controller, 'save']);
    $router->get("/{$segment}/{id}/modifica", [$controller, 'edit']);
    $router->post("/{$segment}/{id}/modifica", [$controller, 'save']);
    $router->post("/{$segment}/{id}/elimina", [$controller, 'destroy']);
}


// Gallerie di luoghi e storie (stesso schema della galleria eventi).
foreach (['luoghi' => LuoghiController::class, 'storie' => StoriesController::class] as $segment => $controller) {
    $router->get("/{$segment}/{id}/galleria", [$controller, 'gallery']);
    $router->post("/{$segment}/{id}/galleria/aggiungi", [$controller, 'galleryAdd']);
    $router->post("/{$segment}/{id}/galleria/ordina", [$controller, 'gallerySort']);
    $router->post("/{$segment}/{id}/galleria/{image}/rimuovi", [$controller, 'galleryRemove']);
}

// Sezioni di un highlight: dove compare e con che ordine.
$router->get('/highlights/{id}/sezioni', [HighlightsController::class, 'sections']);
$router->post('/highlights/{id}/sezioni', [HighlightsController::class, 'sectionsSave']);

// Instagram: post caricati a mano (pronti per la sincronizzazione futura).
$router->get('/instagram', [InstagramController::class, 'index']);
$router->post('/instagram/carica', [InstagramController::class, 'upload']);
$router->post('/instagram/ordina', [InstagramController::class, 'sort']);
$router->post('/instagram/{id}/modifica', [InstagramController::class, 'update']);
$router->post('/instagram/{id}/visibilita', [InstagramController::class, 'toggle']);
$router->post('/instagram/{id}/elimina', [InstagramController::class, 'destroy']);

// Hero delle pagine: testi, video, poster e foto.
$router->get('/hero', [HeroController::class, 'index']);
$router->get('/hero/{id}/galleria', [HeroController::class, 'gallery']);
$router->post('/hero/{id}/galleria/aggiungi', [HeroController::class, 'galleryAdd']);
$router->post('/hero/{id}/galleria/ordina', [HeroController::class, 'gallerySort']);
$router->post('/hero/{id}/galleria/{image}/rimuovi', [HeroController::class, 'galleryRemove']);
$router->get('/hero/{key}', [HeroController::class, 'edit']);
$router->post('/hero/{key}', [HeroController::class, 'save']);

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
