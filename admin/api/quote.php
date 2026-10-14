<?php

/**
 * Endpoint pubblico per il preventivatore del sito statico.
 *
 * Il sito e il back office stanno sullo stesso dominio, quindi non serve
 * CORS e il CSP del sito puo' restare con form-action 'self'.
 *
 *   POST /admin/api/quote.php
 *   Content-Type: application/json oppure form classico
 *
 * Campi: name, email, phone, event_date, event_type, guests, message, lang
 * piu' il campo trappola `website`, che deve restare vuoto.
 */

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use Gaia\Admin\Inquiry;
use Gaia\Admin\View;

header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store', true);

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    View::json(['ok' => false, 'error' => 'Metodo non consentito.'], 405);
}

try {
    $result = Inquiry::receive('quote');
} catch (Throwable $exception) {
    error_log('[quote] ' . $exception->getMessage());
    View::json(['ok' => false, 'error' => 'Invio non riuscito. Riprova.'], 500);
}

View::json(
    $result['ok']
        ? ['ok' => true]
        : ['ok' => false, 'error' => $result['message']],
    $result['status'],
);
