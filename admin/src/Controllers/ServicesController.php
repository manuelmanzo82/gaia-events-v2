<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

final class ServicesController extends ResourceController
{
    protected function table(): string
    {
        return 'services';
    }

    protected function route(): string
    {
        return 'servizi';
    }

    protected function title(): string
    {
        return 'Servizi';
    }

    protected function fields(): array
    {
        return [
            'slug' => ['label' => 'Slug', 'type' => 'slug', 'rules' => 'required|max:120'],
            'title' => ['label' => 'Titolo', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:255'],
            'subtitle' => ['label' => 'Sottotitolo', 'type' => 'text', 'bilingual' => true],
            'description' => ['label' => 'Descrizione', 'type' => 'textarea', 'bilingual' => true],
            'features' => [
                'label' => 'Caratteristiche',
                'type' => 'list',
                'bilingual' => true,
                'help' => 'Una voce per riga.',
            ],
            'icon' => ['label' => 'Icona', 'type' => 'text'],
            'media_id' => ['label' => 'Immagine', 'type' => 'media'],
            'meta_title' => ['label' => 'Meta title', 'type' => 'text', 'bilingual' => true],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'bilingual' => true],
            'active' => ['label' => 'Attivo', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
