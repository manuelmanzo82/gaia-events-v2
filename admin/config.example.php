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

        // Token fine-grained limitato a questa sola repo, con il permesso
        // «Actions: Read and write». Non serve altro: il pulsante Pubblica
        // avvia un workflow, non scrive codice.
        //
        // Non incollarlo a mano: usa
        //   php bin/imposta-token-github.php
        // che lo legge senza mostrarlo e lo prova prima di salvarlo.
        'token' => '',

        // Quale workflow avviare, su quale ramo e verso quale ambiente.
        'workflow' => 'deploy.yml',
        'ref' => 'main',
        'target' => 'production',
    ],

    // ------------------------------------------------------------------
    // Notifiche
    // ------------------------------------------------------------------

    // Dove arrivano le richieste di preventivo e i messaggi dal sito.
    'notify_email' => 'gaiacrognale@gmail.com',

    // Mittente di riserva: usato solo se manca smtp.from.
    'mail_from' => 'noreply@gaiaevents.it',

    // Invio della posta via SMTP autenticato (src/Mailer.php). Su IONOS
    // mail() di PHP non spedisce senza un account impostato nel pannello,
    // quindi si usa sempre l'SMTP di una casella vera sul dominio del
    // mittente (SPF e DKIM allineati).
    //
    //   staging     smtp.ionos.it · 587 · tls · noreply@incognito.work
    //   produzione  smtps.aruba.it · 465 · ssl · noreply@gaiaevents.it
    //               (al passaggio di dominio: la posta di gaiaevents.it è
    //               su Aruba)
    //
    // La password la scrive a mano chi gestisce il server, direttamente in
    // config.php: mai in repo, mai in chat. Senza password nessun invio, e
    // la richiesta resta comunque salvata nel back office con l'errore.
    'smtp' => [
        'host' => 'smtps.aruba.it',
        'port' => 465,
        'secure' => 'ssl',
        'username' => 'noreply@gaiaevents.it',
        'password' => '',
        'from' => 'noreply@gaiaevents.it',
        'from_name' => 'Gaia Events',
    ],

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
