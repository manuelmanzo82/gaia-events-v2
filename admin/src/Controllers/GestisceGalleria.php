<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Session;
use Gaia\Admin\View;

/**
 * Galleria ordinabile collegata a una risorsa (luoghi, storie, hero…),
 * sullo stesso schema della galleria eventi: tabella pivot con
 * {fk, media_id, sort_order} e la vista condivisa resource/gallery.
 *
 * Chi usa il trait dichiara la tabella pivot e la colonna FK; find(),
 * route() e mediaOptions() arrivano dal controller.
 */
trait GestisceGalleria
{
    /** Tabella pivot, es. 'luogo_images'. */
    abstract protected function pivotTable(): string;

    /** Colonna FK della pivot, es. 'luogo_id'. */
    abstract protected function pivotForeignKey(): string;

    public function gallery(array $params = []): void
    {
        Auth::requireLogin();

        $row = $this->find((int) ($params['id'] ?? 0));

        $images = Db::all(
            sprintf(
                'SELECT p.id, p.sort_order, m.id AS media_id, m.path, m.width, m.height,
                        m.alt_it, m.alt_en
                 FROM `%s` p
                 JOIN media m ON m.id = p.media_id
                 WHERE p.`%s` = ?
                 ORDER BY p.sort_order, p.id',
                $this->pivotTable(),
                $this->pivotForeignKey(),
            ),
            [(int) $row['id']],
        );

        View::render('resource/gallery', [
            'title' => 'Galleria — ' . (string) ($row['nome_it'] ?? $row['title_it'] ?? $row['page_key'] ?? ''),
            'route' => $this->route(),
            'event' => $row,
            'images' => $images,
            'mediaOptions' => $this->mediaOptions(),
        ]);
    }

    public function galleryAdd(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $row = $this->find((int) ($params['id'] ?? 0));
        $mediaId = (int) ($_POST['media_id'] ?? 0);

        if ($mediaId > 0) {
            $next = (int) Db::value(
                sprintf(
                    'SELECT COALESCE(MAX(sort_order), 0) + 1 FROM `%s` WHERE `%s` = ?',
                    $this->pivotTable(),
                    $this->pivotForeignKey(),
                ),
                [(int) $row['id']],
            );

            Db::run(
                sprintf(
                    'INSERT IGNORE INTO `%s` (`%s`, media_id, sort_order) VALUES (?, ?, ?)',
                    $this->pivotTable(),
                    $this->pivotForeignKey(),
                ),
                [(int) $row['id'], $mediaId, $next],
            );

            Session::flash('success', 'Immagine aggiunta alla galleria.');
        }

        redirect($this->route() . '/' . (int) $row['id'] . '/galleria');
    }

    public function gallerySort(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $row = $this->find((int) ($params['id'] ?? 0));
        $order = $_POST['order'] ?? [];

        if (is_array($order)) {
            Db::transaction(function () use ($order, $row): void {
                foreach ($order as $imageId => $position) {
                    Db::run(
                        sprintf(
                            'UPDATE `%s` SET sort_order = ? WHERE id = ? AND `%s` = ?',
                            $this->pivotTable(),
                            $this->pivotForeignKey(),
                        ),
                        [(int) $position, (int) $imageId, (int) $row['id']],
                    );
                }
            });

            Session::flash('success', 'Ordine della galleria aggiornato.');
        }

        redirect($this->route() . '/' . (int) $row['id'] . '/galleria');
    }

    public function galleryRemove(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $row = $this->find((int) ($params['id'] ?? 0));

        Db::run(
            sprintf(
                'DELETE FROM `%s` WHERE `%s` = ? AND id = ?',
                $this->pivotTable(),
                $this->pivotForeignKey(),
            ),
            [(int) $row['id'], (int) ($params['image'] ?? 0)],
        );

        Session::flash('success', 'Immagine rimossa dalla galleria.');
        redirect($this->route() . '/' . (int) $row['id'] . '/galleria');
    }
}
