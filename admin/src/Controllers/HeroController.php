<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Config;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\ImageService;
use Gaia\Admin\Session;
use Gaia\Admin\View;
use RuntimeException;

/**
 * Hero per pagina: titolo, sottotitolo, CTA, video desktop e foto.
 *
 * I video NON passano da ffmpeg (su IONOS non c'è): Gaia carica MP4,
 * WebM e poster già pronti dal form. MP4 e WebM max 8 MB l'uno, con un
 * messaggio chiaro se li superano; il poster passa da ImageService come
 * ogni altra immagine. video_url (esterno) per un futuro Vimeo.
 */
final class HeroController
{
    use GestisceGalleria;

    /** Pagine che hanno un hero gestibile. */
    public const PAGE_KEYS = [
        'home' => 'Home',
        'about' => 'Chi sono',
        'services' => 'Servizi',
        'destinationWedding' => 'Destination wedding',
        'places' => 'Luoghi',
        'stories' => 'Storie',
    ];

    private const MAX_VIDEO_BYTES = 8 * 1024 * 1024;

    protected function route(): string
    {
        return 'hero';
    }

    protected function pivotTable(): string
    {
        return 'page_hero_images';
    }

    protected function pivotForeignKey(): string
    {
        return 'hero_id';
    }

    public function index(array $params = []): void
    {
        Auth::requireLogin();

        $righe = [];
        foreach (Db::all('SELECT * FROM page_hero') as $row) {
            $righe[(string) $row['page_key']] = $row;
        }

        View::render('hero/index', [
            'title' => 'Hero delle pagine',
            'pages' => self::PAGE_KEYS,
            'rows' => $righe,
        ]);
    }

    public function edit(array $params = []): void
    {
        Auth::requireLogin();

        $key = $this->pageKey($params);
        $row = Db::one('SELECT * FROM page_hero WHERE page_key = ?', [$key]) ?? ['page_key' => $key];

        $images = [];
        if (!empty($row['id'])) {
            $images = Db::all(
                'SELECT p.id, p.sort_order, m.path FROM page_hero_images p
                 JOIN media m ON m.id = p.media_id
                 WHERE p.hero_id = ? ORDER BY p.sort_order, p.id',
                [(int) $row['id']],
            );
        }

        View::render('hero/form', [
            'title' => 'Hero — ' . self::PAGE_KEYS[$key],
            'pageKey' => $key,
            'row' => $row,
            'images' => $images,
            'mediaOptions' => $this->mediaOptions(),
        ]);
    }

    public function save(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $key = $this->pageKey($params);
        $existing = Db::one('SELECT * FROM page_hero WHERE page_key = ?', [$key]);

        $data = [
            'page_key' => $key,
            'title_it' => $this->campo('title_it'),
            'title_en' => $this->campo('title_en'),
            'subtitle_it' => $this->campo('subtitle_it'),
            'subtitle_en' => $this->campo('subtitle_en'),
            'cta_label_it' => $this->campo('cta_label_it'),
            'cta_label_en' => $this->campo('cta_label_en'),
            'cta_route' => $this->campo('cta_route'),
            'video_url' => $this->campo('video_url'),
            'poster_media_id' => (int) ($_POST['poster_media_id'] ?? 0) > 0
                ? (int) $_POST['poster_media_id']
                : null,
        ];

        // Poster caricato ora: passa da ImageService come ogni immagine.
        if ($this->fileArrivato('poster')) {
            try {
                $media = ImageService::store($_FILES['poster'], 'hero');
                $media['alt_it'] = $this->campo('poster_alt_it');
                $media['alt_en'] = $this->campo('poster_alt_en');
                $data['poster_media_id'] = Db::insert('media', $media);
            } catch (RuntimeException $exception) {
                Session::flash('error', 'Poster: ' . $exception->getMessage());
                redirect('hero/' . $key);
            }
        }

        // Video: file già pronti, max 8 MB l'uno, MIME controllato davvero.
        foreach ([
            'video_mp4' => ['mime' => 'video/mp4', 'colonna' => 'video_path', 'estensione' => 'mp4'],
            'video_webm' => ['mime' => 'video/webm', 'colonna' => 'video_webm_path', 'estensione' => 'webm'],
        ] as $campo => $regole) {
            if (!$this->fileArrivato($campo)) {
                continue;
            }

            $file = $_FILES[$campo];

            if ((int) $file['size'] > self::MAX_VIDEO_BYTES) {
                $pesoMb = number_format((int) $file['size'] / 1048576, 1, ',', '');
                Session::flash(
                    'error',
                    strtoupper($regole['estensione']) . ": il file pesa {$pesoMb} MB, il limite è 8 MB. "
                        . 'Comprimilo (1080p, 15-20 s) e ricaricalo.',
                );
                redirect('hero/' . $key);
            }

            $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
            if ($mime !== $regole['mime']) {
                Session::flash(
                    'error',
                    strtoupper($regole['estensione']) . ": il file non è {$regole['mime']} (rilevato {$mime}).",
                );
                redirect('hero/' . $key);
            }

            $cartella = rtrim((string) Config::get('uploads_dir'), '/') . '/video';
            if (!is_dir($cartella) && !mkdir($cartella, 0755, true) && !is_dir($cartella)) {
                Session::flash('error', 'Cartella uploads/video non creabile.');
                redirect('hero/' . $key);
            }

            $nome = 'video/' . $key . '.' . $regole['estensione'];
            if (!move_uploaded_file((string) $file['tmp_name'], rtrim((string) Config::get('uploads_dir'), '/') . '/' . $nome)) {
                Session::flash('error', strtoupper($regole['estensione']) . ': caricamento non riuscito.');
                redirect('hero/' . $key);
            }

            $data[$regole['colonna']] = $nome;
        }

        // Rimozione esplicita dei video correnti.
        foreach (['rimuovi_mp4' => 'video_path', 'rimuovi_webm' => 'video_webm_path'] as $flag => $colonna) {
            if (!empty($_POST[$flag]) && !empty($existing[$colonna])) {
                @unlink(rtrim((string) Config::get('uploads_dir'), '/') . '/' . (string) $existing[$colonna]);
                $data[$colonna] = null;
            }
        }

        if ($existing !== null) {
            Db::update('page_hero', (int) $existing['id'], $data);
        } else {
            Db::insert('page_hero', $data);
        }

        Session::flash('success', 'Hero salvato. Ricordati di pubblicare.');
        redirect('hero/' . $key);
    }

    // -----------------------------------------------------------------
    // Supporto (per il trait galleria e per i form)
    // -----------------------------------------------------------------

    /** @return array<string, mixed> */
    protected function find(int $id): array
    {
        $row = Db::one('SELECT * FROM page_hero WHERE id = ?', [$id]);

        if ($row === null) {
            http_response_code(404);
            View::render('error', ['title' => 'Non trovato', 'message' => 'Hero inesistente.']);
            exit;
        }

        return $row;
    }

    /** @return array<int, string> */
    protected function mediaOptions(): array
    {
        $options = [];
        foreach (Db::all('SELECT id, path FROM media ORDER BY created_at DESC LIMIT 500') as $row) {
            $options[(int) $row['id']] = (string) $row['path'];
        }

        return $options;
    }

    private function pageKey(array $params): string
    {
        $key = (string) ($params['key'] ?? '');

        if (!array_key_exists($key, self::PAGE_KEYS)) {
            http_response_code(404);
            View::render('error', ['title' => 'Non trovato', 'message' => 'Pagina senza hero gestibile.']);
            exit;
        }

        return $key;
    }

    private function campo(string $nome): ?string
    {
        $valore = trim((string) ($_POST[$nome] ?? ''));

        return $valore === '' ? null : $valore;
    }

    private function fileArrivato(string $campo): bool
    {
        return isset($_FILES[$campo])
            && is_array($_FILES[$campo])
            && (int) $_FILES[$campo]['error'] === UPLOAD_ERR_OK;
    }
}
