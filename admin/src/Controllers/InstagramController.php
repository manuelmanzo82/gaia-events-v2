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

/**
 * Instagram, a mano: Gaia carica le foto dei suoi post (anche più insieme),
 * e se vuole aggiunge link, data e tipo. Obbligatoria solo la foto.
 *
 * Le foto passano da ImageService come tutte le altre (varianti WebP/AVIF,
 * maxWidth, nessun metadato) e finiscono in media, con l'alt IT/EN.
 * Il giorno in cui arriverà la sincronizzazione via API, i post entreranno
 * nella stessa tabella con source = 'api' e ig_media_id valorizzato: da
 * qui si potranno comunque nascondere o riordinare.
 *
 * Ordine sul sito: per data del post (taken_at, dal più recente), poi per
 * l'ordine deciso qui trascinando — che quindi conta tra post della
 * stessa data, per esempio quelli caricati insieme.
 */
final class InstagramController
{
    private const TIPI = ['foto' => 'Foto', 'reel' => 'Reel', 'carosello' => 'Carosello'];

    public function index(array $params = []): void
    {
        Auth::requireLogin();

        $posts = Db::all(
            'SELECT p.*, m.path, m.width, m.height, m.alt_it, m.alt_en
             FROM instagram_posts p
             JOIN media m ON m.id = p.media_id
             ORDER BY p.taken_at IS NULL, p.taken_at DESC, p.sort_order, p.id',
        );

        $handle = (string) (Db::value("SELECT value_it FROM settings WHERE setting_key = 'instagram.handle'") ?? '');
        $numero = (int) (Db::value("SELECT value_it FROM settings WHERE setting_key = 'instagram.numero_post'") ?? 8);

        View::render('instagram/index', [
            'title' => 'Instagram',
            'posts' => $posts,
            'tipi' => self::TIPI,
            'handle' => $handle,
            'numero' => $numero > 0 ? $numero : 8,
        ]);
    }

    /** Caricamento rapido: una o più foto, ognuna diventa un post. */
    public function upload(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $files = self::filesArray($_FILES['images'] ?? null);
        if ($files === []) {
            Session::flash('error', 'Scegli almeno una foto.');
            redirect('instagram');
        }

        $data = self::dataOra((string) ($_POST['taken_at'] ?? ''));
        $tipo = array_key_exists((string) ($_POST['tipo'] ?? ''), self::TIPI) ? (string) $_POST['tipo'] : 'foto';
        $prossimo = (int) Db::value('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM instagram_posts');

        $ok = 0;
        $errori = [];
        foreach ($files as $file) {
            try {
                $media = ImageService::store($file, 'instagram');
                $mediaId = Db::insert('media', $media);
                Db::insert('instagram_posts', [
                    'media_id' => $mediaId,
                    'taken_at' => $data,
                    'tipo' => $tipo,
                    'source' => 'manuale',
                    'sort_order' => $prossimo++,
                ]);
                $ok++;
            } catch (RuntimeException $exception) {
                $errori[] = $file['name'] . ': ' . $exception->getMessage();
            }
        }

        if ($ok > 0) {
            Session::flash(
                'success',
                ($ok === 1 ? '1 post aggiunto.' : "{$ok} post aggiunti.")
                    . ' Se vuoi, aggiungi il link al post e il testo alternativo. Ricordati di pubblicare.',
            );
        }
        if ($errori !== []) {
            Session::flash('error', 'Non caricate: ' . implode(' — ', $errori));
        }

        redirect('instagram');
    }

    public function update(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $post = $this->find((int) ($params['id'] ?? 0));

        $permalink = trim((string) ($_POST['permalink'] ?? ''));
        if ($permalink !== '' && preg_match('#^https://(www\.)?instagram\.com/#i', $permalink) !== 1) {
            Session::flash('error', 'Il link deve essere quello del post su instagram.com (https://www.instagram.com/p/…).');
            redirect('instagram#post-' . (int) $post['id']);
        }

        $tipo = (string) ($_POST['tipo'] ?? 'foto');

        Db::update('instagram_posts', (int) $post['id'], [
            'permalink' => $permalink !== '' ? $permalink : null,
            // Data lasciata vuota: resta quella che c'era.
            'taken_at' => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_POST['taken_at'] ?? '')) === 1
                ? self::dataOra((string) $_POST['taken_at'])
                : ($post['taken_at'] ?? self::dataOra('')),
            'tipo' => array_key_exists($tipo, self::TIPI) ? $tipo : 'foto',
            'nascosto' => !empty($_POST['nascosto']) ? 1 : 0,
            'note_interne' => trim((string) ($_POST['note_interne'] ?? '')) ?: null,
        ]);

        // L'alt vive sulla foto, come per tutte le immagini del sito.
        Db::update('media', (int) $post['media_id'], [
            'alt_it' => trim((string) ($_POST['alt_it'] ?? '')) ?: null,
            'alt_en' => trim((string) ($_POST['alt_en'] ?? '')) ?: null,
        ]);

        Session::flash('success', 'Post aggiornato. Ricordati di pubblicare.');
        redirect('instagram#post-' . (int) $post['id']);
    }

    /** Nascondi / mostra con un clic. */
    public function toggle(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $post = $this->find((int) ($params['id'] ?? 0));
        Db::update('instagram_posts', (int) $post['id'], ['nascosto' => (int) $post['nascosto'] === 1 ? 0 : 1]);

        Session::flash('success', (int) $post['nascosto'] === 1 ? 'Post di nuovo visibile.' : 'Post nascosto.');
        redirect('instagram#post-' . (int) $post['id']);
    }

    /** Nuovo ordine dal trascinamento: elenco di id separati da virgola. */
    public function sort(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $ids = array_values(array_filter(array_map('intval', explode(',', (string) ($_POST['ordine'] ?? '')))));

        Db::transaction(static function () use ($ids): void {
            foreach ($ids as $posizione => $id) {
                Db::run('UPDATE instagram_posts SET sort_order = ? WHERE id = ?', [$posizione + 1, $id]);
            }
        });

        Session::flash('success', 'Ordine salvato. Ricordati di pubblicare.');
        redirect('instagram');
    }

    /** Toglie il post; la foto resta nella libreria (sezione Immagini). */
    public function destroy(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $post = $this->find((int) ($params['id'] ?? 0));
        Db::delete('instagram_posts', (int) $post['id']);

        Session::flash('success', 'Post eliminato (la foto resta tra le Immagini). Ricordati di pubblicare.');
        redirect('instagram');
    }

    // -----------------------------------------------------------------

    /** @return array<string, mixed> */
    private function find(int $id): array
    {
        $row = Db::one('SELECT * FROM instagram_posts WHERE id = ?', [$id]);

        if ($row === null) {
            http_response_code(404);
            View::render('error', ['title' => 'Non trovato', 'message' => 'Questo post non esiste più.']);
            exit;
        }

        return $row;
    }

    /**
     * $_FILES['images'] con multiple arriva «per colonne» (name[], tmp_name[]…):
     * qui diventa un elenco di file nel formato che ImageService si aspetta.
     *
     * @return list<array{name: string, type: string, tmp_name: string, error: int, size: int}>
     */
    private static function filesArray(mixed $campo): array
    {
        if (!is_array($campo) || !isset($campo['name'])) {
            return [];
        }

        if (!is_array($campo['name'])) {
            return (int) $campo['error'] === UPLOAD_ERR_NO_FILE ? [] : [$campo];
        }

        $files = [];
        foreach ($campo['name'] as $i => $nome) {
            if ((int) $campo['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = [
                'name' => (string) $nome,
                'type' => (string) $campo['type'][$i],
                'tmp_name' => (string) $campo['tmp_name'][$i],
                'error' => (int) $campo['error'][$i],
                'size' => (int) $campo['size'][$i],
            ];
        }

        return $files;
    }

    /** Data del form (YYYY-MM-DD) a mezzogiorno; vuota = adesso. */
    private static function dataOra(string $data): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1) {
            return $data . ' 12:00:00';
        }

        return date('Y-m-d H:i:s');
    }
}
