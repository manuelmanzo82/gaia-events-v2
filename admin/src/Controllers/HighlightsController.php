<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Session;
use Gaia\Admin\View;

final class HighlightsController extends ResourceController
{
    /** Le sezioni del sito in cui un highlight può comparire. */
    public const SECTIONS = [
        'home' => 'Home',
        'chi-sono' => 'Chi sono',
        'servizi' => 'Servizi',
        'destination' => 'Destination wedding',
        'luogo' => 'Pagina del luogo collegato',
        'cta' => 'CTA finale della home (foto accanto al testo)',
    ];

    protected function table(): string
    {
        return 'highlights';
    }

    protected function route(): string
    {
        return 'highlights';
    }

    protected function title(): string
    {
        return 'Highlights';
    }

    protected function orderBy(): string
    {
        return 'id DESC';
    }

    protected function listColumns(): array
    {
        return [
            'media_id' => 'Immagine (id)',
            'luogo_id' => 'Luogo (id)',
            'active' => 'Attivo',
        ];
    }

    protected function fields(): array
    {
        $luoghi = ['' => '— nessuno —'];
        foreach (Db::all('SELECT id, nome_it FROM luoghi ORDER BY sort_order, nome_it') as $row) {
            $luoghi[(string) $row['id']] = (string) $row['nome_it'];
        }

        return [
            'media_id' => [
                'label' => 'Immagine',
                'type' => 'media',
                'rules' => 'required',
                'help' => "L'alt si modifica nella scheda dell'immagine (sezione Immagini): è unico per foto.",
            ],
            'luogo_id' => ['label' => 'Luogo collegato (opzionale)', 'type' => 'select', 'options' => $luoghi],
            'active' => ['label' => 'Pubblicato', 'type' => 'bool'],
        ];
    }

    protected function castValue(string $type, mixed $raw, string $column): mixed
    {
        if ($column === 'luogo_id') {
            return (int) $raw > 0 ? (int) $raw : null;
        }

        return parent::castValue($type, $raw, $column);
    }

    // -----------------------------------------------------------------
    // Sezioni: in quali punti del sito compare la foto, con che ordine.
    // -----------------------------------------------------------------

    public function sections(array $params = []): void
    {
        Auth::requireLogin();

        $row = $this->find((int) ($params['id'] ?? 0));

        $attive = [];
        foreach (Db::all('SELECT section, sort_order FROM highlight_sections WHERE highlight_id = ?', [(int) $row['id']]) as $sezione) {
            $attive[(string) $sezione['section']] = (int) $sezione['sort_order'];
        }

        $media = Db::one('SELECT path FROM media WHERE id = ?', [(int) $row['media_id']]);

        View::render('highlights/sections', [
            'title' => 'Sezioni — highlight #' . (int) $row['id'],
            'route' => $this->route(),
            'row' => $row,
            'mediaPath' => (string) ($media['path'] ?? ''),
            'sections' => self::SECTIONS,
            'attive' => $attive,
        ]);
    }

    public function sectionsSave(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $row = $this->find((int) ($params['id'] ?? 0));
        $scelte = $_POST['sections'] ?? [];
        $ordini = $_POST['order'] ?? [];

        Db::transaction(function () use ($row, $scelte, $ordini): void {
            Db::run('DELETE FROM highlight_sections WHERE highlight_id = ?', [(int) $row['id']]);

            if (!is_array($scelte)) {
                return;
            }

            foreach (array_keys(self::SECTIONS) as $section) {
                if (empty($scelte[$section])) {
                    continue;
                }

                Db::run(
                    'INSERT INTO highlight_sections (highlight_id, section, sort_order) VALUES (?, ?, ?)',
                    [(int) $row['id'], $section, (int) (is_array($ordini) ? ($ordini[$section] ?? 0) : 0)],
                );
            }
        });

        Session::flash('success', 'Sezioni aggiornate. Ricordati di pubblicare.');
        redirect($this->route() . '/' . (int) $row['id'] . '/sezioni');
    }
}
