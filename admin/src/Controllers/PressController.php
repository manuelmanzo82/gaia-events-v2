<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

/**
 * Stampa: articoli e interviste che parlano di Gaia, per il blocco
 * «Stampa» della pagina Dicono di me. Testata e titolo non si traducono;
 * l'estratto sì.
 */
final class PressController extends ResourceController
{
    protected function table(): string
    {
        return 'press';
    }

    protected function route(): string
    {
        return 'stampa';
    }

    protected function title(): string
    {
        return 'Stampa';
    }

    protected function orderBy(): string
    {
        return 'sort_order, published_on DESC, id';
    }

    protected function listColumns(): array
    {
        return [
            'outlet' => 'Testata',
            'title' => 'Titolo',
            'published_on' => 'Data',
            'active' => 'Pubblicato',
        ];
    }

    protected function fields(): array
    {
        return [
            'outlet' => ['label' => 'Testata', 'type' => 'text', 'rules' => 'required|max:190'],
            'title' => ['label' => 'Titolo dell\'articolo', 'type' => 'text', 'rules' => 'required|max:255'],
            'published_on' => ['label' => 'Data', 'type' => 'date'],
            'url' => ['label' => 'Link all\'articolo', 'type' => 'text', 'rules' => 'max:500'],
            'logo_media_id' => ['label' => 'Logo della testata (opzionale)', 'type' => 'media'],
            'excerpt' => ['label' => 'Estratto', 'type' => 'textarea', 'bilingual' => true],
            'active' => ['label' => 'Pubblicato', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
