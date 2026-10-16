<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Session;

final class LuoghiController extends ResourceController
{
    use GestisceGalleria;

    protected function table(): string
    {
        return 'luoghi';
    }

    protected function route(): string
    {
        return 'luoghi';
    }

    protected function title(): string
    {
        return 'Luoghi';
    }

    protected function pivotTable(): string
    {
        return 'luogo_images';
    }

    protected function pivotForeignKey(): string
    {
        return 'luogo_id';
    }

    protected function listColumns(): array
    {
        return [
            'nome_it' => 'Nome',
            'tipo' => 'Tipo',
            'active' => 'Attivo',
        ];
    }

    protected function fields(): array
    {
        // Le regioni esistenti, per la tendina della zona.
        $regioni = ['' => '— nessuna (è una regione) —'];
        foreach (Db::all("SELECT id, nome_it FROM luoghi WHERE tipo = 'regione' ORDER BY sort_order, nome_it") as $row) {
            $regioni[(string) $row['id']] = (string) $row['nome_it'];
        }

        return [
            'tipo' => [
                'label' => 'Tipo',
                'type' => 'select',
                'options' => ['regione' => 'Regione', 'zona' => 'Città / zona'],
            ],
            'parent_id' => [
                'label' => 'Regione di appartenenza',
                'type' => 'select',
                'options' => $regioni,
                'help' => 'Solo per le zone: una regione resta senza.',
            ],
            'nome' => ['label' => 'Nome', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:190'],
            'slug' => ['label' => 'Slug', 'type' => 'slug', 'bilingual' => true, 'rules' => 'required|max:190'],
            'intro' => ['label' => 'Intro breve', 'type' => 'textarea', 'bilingual' => true],
            'testo' => ['label' => 'Testo', 'type' => 'html', 'bilingual' => true],
            'cover_media_id' => ['label' => 'Foto di copertina', 'type' => 'media'],
            'meta_title' => ['label' => 'Meta title', 'type' => 'text', 'bilingual' => true],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'bilingual' => true],
            'active' => ['label' => 'Pubblicato', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }

    protected function castValue(string $type, mixed $raw, string $column): mixed
    {
        // parent_id vuoto = regione: deve diventare NULL, non 0.
        if ($column === 'parent_id') {
            return (int) $raw > 0 ? (int) $raw : null;
        }

        return parent::castValue($type, $raw, $column);
    }

    /**
     * Niente cancellazioni silenziose: zone e location collegate vanno
     * spostate o eliminate prima (le FK sono RESTRICT apposta).
     */
    public function destroy(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $id = (int) ($params['id'] ?? 0);
        $this->find($id);

        $zone = (int) Db::value('SELECT COUNT(*) FROM luoghi WHERE parent_id = ?', [$id]);
        $location = (int) Db::value('SELECT COUNT(*) FROM locations WHERE luogo_id = ?', [$id]);

        if ($zone > 0 || $location > 0) {
            $parti = [];
            if ($zone > 0) {
                $parti[] = "{$zone} zone";
            }
            if ($location > 0) {
                $parti[] = "{$location} location";
            }

            Session::flash(
                'error',
                'Questo luogo ha ' . implode(' e ', $parti)
                    . ' collegate: spostale o eliminale prima di cancellarlo.',
            );
            redirect($this->route());
        }

        Db::delete($this->table(), $id);
        Session::flash('success', 'Luogo eliminato. Ricordati di pubblicare.');
        redirect($this->route());
    }
}
