<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\ImageService;
use Gaia\Admin\Session;
use Gaia\Admin\View;
use RuntimeException;

final class MediaController
{
    public function index(array $params = []): void
    {
        Auth::requireLogin();

        View::render('media/index', [
            'title' => 'Immagini',
            'rows' => Db::all('SELECT * FROM media ORDER BY created_at DESC LIMIT 300'),
            'avifSupported' => ImageService::avifSupported(),
        ]);
    }

    /**
     * Carica un'immagine e ne genera le varianti.
     * Il crop è opzionale: arriva in pixel sull'originale.
     */
    public function upload(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $file = $_FILES['image'] ?? null;

        if (!is_array($file)) {
            Session::flash('error', 'Nessun file selezionato.');
            redirect('immagini');
        }

        $crop = null;
        if (isset($_POST['crop_width'], $_POST['crop_height'])
            && (int) $_POST['crop_width'] > 0
            && (int) $_POST['crop_height'] > 0
        ) {
            $crop = [
                'x' => (int) ($_POST['crop_x'] ?? 0),
                'y' => (int) ($_POST['crop_y'] ?? 0),
                'width' => (int) $_POST['crop_width'],
                'height' => (int) $_POST['crop_height'],
            ];
        }

        try {
            /** @var array{name: string, type: string, tmp_name: string, error: int, size: int} $file */
            $data = ImageService::store($file, (string) ($_POST['folder'] ?? 'generiche'), $crop);

            $data['alt_it'] = trim((string) ($_POST['alt_it'] ?? '')) ?: null;
            $data['alt_en'] = trim((string) ($_POST['alt_en'] ?? '')) ?: null;

            Db::insert('media', $data);

            $message = 'Immagine caricata.';
            if ($data['has_avif'] === 0) {
                // Non è un errore: su molti hosting condivisi l'AVIF non c'è.
                $message .= ' Varianti WebP generate; AVIF non supportato da questo server.';
            }

            Session::flash('success', $message);
        } catch (RuntimeException $exception) {
            Session::flash('error', $exception->getMessage());
        }

        redirect('immagini');
    }

    /** Aggiorna i testi alternativi e il punto focale. */
    public function update(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $id = (int) ($params['id'] ?? 0);

        Db::update('media', $id, [
            'alt_it' => trim((string) ($_POST['alt_it'] ?? '')) ?: null,
            'alt_en' => trim((string) ($_POST['alt_en'] ?? '')) ?: null,
            'caption_it' => trim((string) ($_POST['caption_it'] ?? '')) ?: null,
            'caption_en' => trim((string) ($_POST['caption_en'] ?? '')) ?: null,
            'note_interne' => trim((string) ($_POST['note_interne'] ?? '')) ?: null,
            'focal_x' => self::focal($_POST['focal_x'] ?? null),
            'focal_y' => self::focal($_POST['focal_y'] ?? null),
        ]);

        Session::flash('success', 'Immagine aggiornata. Ricordati di pubblicare.');
        redirect('immagini');
    }

    public function destroy(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $id = (int) ($params['id'] ?? 0);
        $row = Db::one('SELECT path FROM media WHERE id = ?', [$id]);

        if ($row !== null) {
            // Prima i file, poi la riga: le chiavi esterne mettono a NULL
            // i riferimenti nei contenuti.
            ImageService::delete((string) $row['path']);
            Db::delete('media', $id);

            Session::flash('success', 'Immagine eliminata. Controlla i contenuti che la usavano.');
        }

        redirect('immagini');
    }

    private static function focal(mixed $value): float
    {
        $number = is_numeric($value) ? (float) $value : 0.5;

        return max(0.0, min(1.0, $number));
    }
}
