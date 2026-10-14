<?php

/**
 * Copia questo file in config.php sul server e compila i valori.
 *
 * config.php è gitignorato: NON deve mai finire in repo.
 * Permessi consigliati sul server: 640, proprietario l'utente del web server.
 */

declare(strict_types=1);

return [
    // ------------------------------------------------------------------
    // Database
    // ------------------------------------------------------------------
    'db' => [
        'host' => 'localhost',
        // Opzionale: lascia 0 per la 3306 standard.
        'port' => 0,
        'name' => 'gaia_events',
        'user' => '',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],

    // ------------------------------------------------------------------
    // Percorsi
    // ------------------------------------------------------------------

    // Percorso da cui è servito il back office, senza slash finale.
    'base_path' => '/admin',

    // URL pubblico del sito. In staging: https://gaia.incognito.work
    'site_url' => 'https://gaiaevents.it',

    // Cartella fisica degli upload. Sta FUORI da admin/ e il deploy non la tocca.
    'uploads_dir' => dirname(__DIR__) . '/uploads',

    // URL pubblico degli upload, come lo scrive il sito statico.
    'uploads_url' => '/uploads',

    // ------------------------------------------------------------------
    // Export dei contenuti per il build
    // ------------------------------------------------------------------

    // Bearer token che la GitHub Action usa su api/export.php.
    // Generane uno con: php -r "echo bin2hex(random_bytes(32));"
    // Lo stesso valore va nel secret EXPORT_TOKEN. Mai nel codice.
    'export_token' => '',

    // ------------------------------------------------------------------
    // Pubblicazione: repository_dispatch verso GitHub
    // ------------------------------------------------------------------
    'github' => [
        'owner' => 'manuelmanzo82',
        'repo' => 'gaia-events-v2',
        // Personal access token con permesso "contents: write" sulla repo.
        'token' => '',
        'event_type' => 'publish',
    ],

    // ------------------------------------------------------------------
    // Notifiche
    // ------------------------------------------------------------------

    // Dove arrivano le richieste di preventivo e i messaggi dal sito.
    'notify_email' => 'gaiacrognale@gmail.com',

    // Mittente delle notifiche: deve stare sul dominio dell'hosting,
    // altrimenti finisce in spam.
    'mail_from' => 'no-reply@gaiaevents.it',

    // ------------------------------------------------------------------
    // Immagini
    // ------------------------------------------------------------------
    'images' => [
        // Larghezze delle varianti generate all'upload.
        'widths' => [480, 800, 1200, 1600, 2400],
        'webp_quality' => 82,
        'avif_quality' => 60,
        // Lato massimo dell'originale conservato, in pixel.
        //
        // ATTENZIONE: limita il LATO LUNGO, quindi su una foto verticale 2:3
        // la larghezza risultante e' due terzi di questo valore. Siccome
        // l'uso consentito dipende dalla larghezza, con 3000 una verticale
        // arriverebbe a soli 2000 px di larghezza e non potrebbe mai fare da
        // hero. Con 4500 una verticale conserva 3000 px di larghezza, che
        // bastano per una fascia a tutta larghezza su retina.
        'max_original' => 4500,
        'max_upload_bytes' => 12 * 1024 * 1024,
    ],

    // ------------------------------------------------------------------
    // Sessione e ambiente
    // ------------------------------------------------------------------
    'session_name' => 'gaia_admin',

    // true solo in sviluppo: mostra gli errori a schermo.
    'debug' => false,
];
