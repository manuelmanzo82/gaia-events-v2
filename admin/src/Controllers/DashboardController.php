<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Inquiry;
use Gaia\Admin\ImageService;
use Gaia\Admin\Publisher;
use Gaia\Admin\Session;
use Gaia\Admin\View;

final class DashboardController
{
    public function index(array $params = []): void
    {
        Auth::requireLogin();
        // Conservazione: IP dopo 30 giorni, richieste dopo 24 mesi.
        Inquiry::pulizia();

        View::render('dashboard', [
            'title' => 'Pannello',
            'counts' => [
                'Servizi' => $this->count('services'),
                'Recensioni' => $this->count('reviews'),
                'Stampa' => $this->count('press'),
                'Clienti' => $this->count('clients'),
                'Magazine' => $this->count('posts'),
                'I miei eventi' => $this->count('stories'),
                'FAQ' => $this->count('faqs'),
                'Immagini' => $this->count('media'),
            ],
            'newInquiries' => (int) Db::value(
                "SELECT COUNT(*) FROM inquiries WHERE status = 'new'",
            ),
            'publishLog' => Publisher::recent(5),
            'ultimaPubblicazione' => Publisher::ultimaPubblicazione(),
            'avifSupported' => ImageService::avifSupported(),
        ]);
    }

    /** Il pulsante "Pubblica": avvia la GitHub Action. */
    public function publish(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $result = Publisher::dispatch();
        Session::flash($result['ok'] ? 'success' : 'error', $result['message']);

        redirect('');
    }

    private function count(string $table): int
    {
        return (int) Db::value(sprintf('SELECT COUNT(*) FROM `%s`', $table));
    }
}
