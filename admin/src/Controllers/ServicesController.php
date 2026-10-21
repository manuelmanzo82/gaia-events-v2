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
            'include_slugs' => [
                'label' => 'Include (altri servizi)',
                'type' => 'text',
                'rules' => 'max:255',
                'help' => 'Slug dei servizi compresi in questo, separati da virgola (es. coordinamento,design): nel preventivatore si segnano da soli come «Incluso».',
            ],
            'icon' => ['label' => 'Icona', 'type' => 'text'],
            'page_media_id' => [
                'label' => 'Foto grande nella pagina Servizi',
                'type' => 'media',
                'help' => 'Capitolo del servizio (per la destination: la fascia a tutta larghezza).',
            ],
            'page_media2_id' => [
                'label' => 'Foto piccola nella pagina Servizi',
                'type' => 'media',
                'help' => 'Sovrapposta alla grande, con il bordo del colore di fondo.',
            ],
            'media_id' => ['label' => 'Immagine', 'type' => 'media'],
            'meta_title' => ['label' => 'Meta title', 'type' => 'text', 'bilingual' => true],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'bilingual' => true],
            'active' => ['label' => 'Attivo', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
