<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Inquiry;
use Gaia\Admin\Session;
use Gaia\Admin\View;

/**
 * Richieste dal preventivatore e messaggi dai contatti.
 *
 * Stati: nuova · contattata · chiusa (new, contacted, closed). Aprire una
 * richiesta non ne cambia lo stato: lo decide Gaia.
 *
 * Contengono dati personali: niente export, niente dump in repo.
 */
final class InquiriesController
{
    public const STATI = ['new' => 'nuova', 'contacted' => 'contattata', 'closed' => 'chiusa'];

    public function index(array $params = []): void
    {
        Auth::requireLogin();
        // Conservazione: IP dopo 30 giorni, richieste dopo 24 mesi.
        Inquiry::pulizia();

        $status = (string) ($_GET['stato'] ?? '');

        $rows = array_key_exists($status, self::STATI)
            ? Db::all(
                'SELECT * FROM inquiries WHERE status = ? ORDER BY created_at DESC LIMIT 300',
                [$status],
            )
            : Db::all('SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 300');

        $conteggi = [];
        foreach (Db::all('SELECT status, COUNT(*) AS n FROM inquiries GROUP BY status') as $r) {
            $conteggi[(string) $r['status']] = (int) $r['n'];
        }

        View::render('inquiries/index', [
            'title' => 'Richieste',
            'rows' => $rows,
            'status' => $status,
            'stati' => self::STATI,
            'conteggi' => $conteggi,
        ]);
    }

    public function show(array $params = []): void
    {
        Auth::requireLogin();

        $id = (int) ($params['id'] ?? 0);
        $row = Db::one('SELECT * FROM inquiries WHERE id = ?', [$id]);

        if ($row === null) {
            http_response_code(404);
            View::render('error', [
                'title' => 'Richiesta non trovata',
                'message' => 'Questa richiesta non esiste più.',
            ]);

            return;
        }

        $payload = [];
        if (is_string($row['payload']) && $row['payload'] !== '') {
            $decoded = json_decode((string) $row['payload'], true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        View::render('inquiries/show', [
            'title' => 'Richiesta #' . $id,
            'row' => $row,
            'payload' => $payload,
            'stati' => self::STATI,
        ]);
    }

    public function update(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $id = (int) ($params['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        if (!array_key_exists($status, self::STATI)) {
            redirect('richieste/' . $id);
        }

        Db::update('inquiries', $id, [
            'status' => $status,
            'notes' => trim((string) ($_POST['notes'] ?? '')) ?: null,
        ]);

        Session::flash('success', 'Richiesta aggiornata.');
        redirect('richieste/' . $id);
    }
}
