<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Session;
use Gaia\Admin\View;

/**
 * Richieste di preventivo e messaggi dai contatti.
 *
 * Contengono dati personali: niente export, niente dump in repo.
 */
final class InquiriesController
{
    public function index(array $params = []): void
    {
        Auth::requireLogin();

        $status = (string) ($_GET['stato'] ?? '');
        $allowed = ['new', 'read', 'answered', 'archived'];

        $rows = in_array($status, $allowed, true)
            ? Db::all(
                'SELECT * FROM inquiries WHERE status = ? ORDER BY created_at DESC LIMIT 200',
                [$status],
            )
            : Db::all('SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 200');

        View::render('inquiries/index', [
            'title' => 'Richieste',
            'rows' => $rows,
            'status' => $status,
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

        // Aprirla la marca come letta.
        if ($row['status'] === 'new') {
            Db::update('inquiries', $id, ['status' => 'read']);
            $row['status'] = 'read';
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
        ]);
    }

    public function update(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $id = (int) ($params['id'] ?? 0);
        $status = (string) ($_POST['status'] ?? '');

        if (!in_array($status, ['new', 'read', 'answered', 'archived'], true)) {
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
