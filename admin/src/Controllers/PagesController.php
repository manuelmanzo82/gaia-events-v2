<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Session;
use Gaia\Admin\View;

/**
 * Pagine statiche: chi sono, privacy, cookie, intro di servizi e portfolio,
 * destination wedding.
 *
 * `page_key` corrisponde a una chiave di ROUTES in site/src/i18n/routes.ts:
 * cambiarla qui senza cambiarla là significa una pagina senza contenuti.
 */
final class PagesController
{
    /** Le pagine previste dal sito, con l'etichetta mostrata a Gaia. */
    private const KEYS = [
        'about' => 'Chi sono',
        'services' => 'Servizi (introduzione)',
        'portfolio' => 'Portfolio (introduzione)',
        'reviews' => 'Dicono di me (introduzione)',
        'blog' => 'Blog (introduzione)',
        'faq' => 'FAQ (introduzione)',
        'quote' => 'Preventivo (introduzione)',
        'contact' => 'Contatti',
        'destinationWedding' => 'Destination wedding',
        'privacy' => 'Privacy policy',
        'cookie' => 'Cookie policy',
    ];

    public function index(array $params = []): void
    {
        Auth::requireLogin();

        $existing = [];
        foreach (Db::all('SELECT id, page_key, updated_at FROM pages') as $row) {
            $existing[(string) $row['page_key']] = $row;
        }

        View::render('pages/index', [
            'title' => 'Pagine',
            'keys' => self::KEYS,
            'existing' => $existing,
        ]);
    }

    public function edit(array $params = []): void
    {
        Auth::requireLogin();

        $key = (string) ($params['key'] ?? '');

        if (!isset(self::KEYS[$key])) {
            http_response_code(404);
            View::render('error', [
                'title' => 'Pagina sconosciuta',
                'message' => 'Questa chiave di pagina non è prevista dal sito.',
            ]);

            return;
        }

        $row = Db::one('SELECT * FROM pages WHERE page_key = ?', [$key]) ?? [];

        $sections = [];
        if (isset($row['id'])) {
            $sections = Db::all(
                'SELECT * FROM page_sections WHERE page_id = ? ORDER BY sort_order, id',
                [(int) $row['id']],
            );
        }

        View::render('pages/form', [
            'title' => 'Pagina — ' . self::KEYS[$key],
            'pageKey' => $key,
            'row' => $row,
            'sections' => $sections,
            'mediaOptions' => $this->mediaOptions(),
        ]);
    }

    public function save(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $key = (string) ($params['key'] ?? '');

        if (!isset(self::KEYS[$key])) {
            http_response_code(404);
            exit('Chiave di pagina non prevista.');
        }

        $data = [
            'title_it' => self::clean($_POST['title_it'] ?? null),
            'title_en' => self::clean($_POST['title_en'] ?? null),
            'intro_it' => self::clean($_POST['intro_it'] ?? null),
            'intro_en' => self::clean($_POST['intro_en'] ?? null),
            'body_it' => self::clean($_POST['body_it'] ?? null),
            'body_en' => self::clean($_POST['body_en'] ?? null),
            'meta_title_it' => self::clean($_POST['meta_title_it'] ?? null),
            'meta_title_en' => self::clean($_POST['meta_title_en'] ?? null),
            'meta_description_it' => self::clean($_POST['meta_description_it'] ?? null),
            'meta_description_en' => self::clean($_POST['meta_description_en'] ?? null),
            'hero_media_id' => (int) ($_POST['hero_media_id'] ?? 0) ?: null,
            'og_media_id' => (int) ($_POST['og_media_id'] ?? 0) ?: null,
        ];

        $existing = Db::one('SELECT id FROM pages WHERE page_key = ?', [$key]);

        if ($existing === null) {
            Db::insert('pages', [...$data, 'page_key' => $key]);
        } else {
            Db::update('pages', (int) $existing['id'], $data);
        }

        Session::flash('success', 'Pagina salvata. Ricordati di pubblicare.');
        redirect('pagine/' . $key);
    }

    private static function clean(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** @return array<int, string> */
    private function mediaOptions(): array
    {
        $options = [];

        foreach (Db::all('SELECT id, path FROM media ORDER BY created_at DESC LIMIT 500') as $row) {
            $options[(int) $row['id']] = (string) $row['path'];
        }

        return $options;
    }
}
