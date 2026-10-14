<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Session;

final class EventsController extends ResourceController
{
    protected function table(): string
    {
        return 'events';
    }

    protected function route(): string
    {
        return 'eventi';
    }

    protected function title(): string
    {
        return 'Eventi';
    }

    protected function listColumns(): array
    {
        return [
            'title_it' => 'Titolo',
            'event_date' => 'Data',
            'macro_category' => 'Tipo',
            'active' => 'Attivo',
        ];
    }

    protected function fields(): array
    {
        return [
            'slug' => ['label' => 'Slug', 'type' => 'slug', 'rules' => 'required|max:160'],
            'macro_category' => [
                'label' => 'Macrocategoria',
                'type' => 'select',
                'options' => [
                    'wedding' => 'Matrimonio',
                    'corporate' => 'Aziendale',
                    'celebrations' => 'Celebrazione',
                ],
            ],
            'title' => ['label' => 'Titolo', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:255'],
            'location' => ['label' => 'Location', 'type' => 'text', 'bilingual' => true],
            'event_date' => ['label' => 'Data', 'type' => 'date'],
            'category' => ['label' => 'Categoria', 'type' => 'text', 'bilingual' => true],
            'excerpt' => ['label' => 'Estratto', 'type' => 'textarea', 'bilingual' => true],
            'description' => ['label' => 'Descrizione', 'type' => 'html', 'bilingual' => true],
            'cover_media_id' => ['label' => 'Copertina', 'type' => 'media'],
            'hero_media_id' => ['label' => 'Immagine grande', 'type' => 'media'],
            'video_url' => [
                'label' => 'Video',
                'type' => 'text',
                'help' => 'Solo YouTube o Vimeo: il CSP del sito non consente altri domini.',
            ],
            'meta_title' => ['label' => 'Meta title', 'type' => 'text', 'bilingual' => true],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'bilingual' => true],
            'featured' => ['label' => 'In evidenza in home', 'type' => 'bool'],
            'active' => ['label' => 'Attivo', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }

    /** Galleria dell'evento: elenco delle immagini con il loro ordine. */
    public function gallery(array $params = []): void
    {
        Auth::requireLogin();

        $event = $this->find((int) ($params['id'] ?? 0));

        $images = Db::all(
            'SELECT ei.id, ei.sort_order, m.id AS media_id, m.path, m.width, m.height,
                    m.alt_it, m.alt_en
             FROM event_images ei
             JOIN media m ON m.id = ei.media_id
             WHERE ei.event_id = ?
             ORDER BY ei.sort_order, ei.id',
            [(int) $event['id']],
        );

        \Gaia\Admin\View::render('resource/gallery', [
            'title' => 'Galleria — ' . ($event['title_it'] ?? ''),
            'route' => $this->route(),
            'event' => $event,
            'images' => $images,
            'mediaOptions' => $this->mediaOptions(),
        ]);
    }

    /** Aggiunge un'immagine alla galleria. */
    public function galleryAdd(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $event = $this->find((int) ($params['id'] ?? 0));
        $mediaId = (int) ($_POST['media_id'] ?? 0);

        if ($mediaId > 0) {
            $next = (int) Db::value(
                'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM event_images WHERE event_id = ?',
                [(int) $event['id']],
            );

            // La chiave unica evita i doppioni se si clicca due volte.
            Db::run(
                'INSERT IGNORE INTO event_images (event_id, media_id, sort_order) VALUES (?, ?, ?)',
                [(int) $event['id'], $mediaId, $next],
            );

            Session::flash('success', 'Immagine aggiunta alla galleria.');
        }

        redirect($this->route() . '/' . (int) $event['id'] . '/galleria');
    }

    /** Rimuove un'immagine dalla galleria, senza cancellare il file. */
    public function galleryRemove(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $event = $this->find((int) ($params['id'] ?? 0));

        Db::run(
            'DELETE FROM event_images WHERE event_id = ? AND id = ?',
            [(int) $event['id'], (int) ($params['image'] ?? 0)],
        );

        Session::flash('success', 'Immagine rimossa dalla galleria.');
        redirect($this->route() . '/' . (int) $event['id'] . '/galleria');
    }

    /** Salva il nuovo ordine delle immagini. */
    public function gallerySort(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $event = $this->find((int) ($params['id'] ?? 0));
        $order = $_POST['order'] ?? [];

        if (is_array($order)) {
            Db::transaction(static function () use ($order, $event): void {
                foreach ($order as $imageId => $position) {
                    Db::run(
                        'UPDATE event_images SET sort_order = ? WHERE id = ? AND event_id = ?',
                        [(int) $position, (int) $imageId, (int) $event['id']],
                    );
                }
            });

            Session::flash('success', 'Ordine della galleria aggiornato.');
        }

        redirect($this->route() . '/' . (int) $event['id'] . '/galleria');
    }
}
