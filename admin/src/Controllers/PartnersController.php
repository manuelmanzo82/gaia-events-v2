<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

final class PartnersController extends ResourceController
{
    protected function table(): string
    {
        return 'partners';
    }

    protected function route(): string
    {
        return 'partner';
    }

    protected function title(): string
    {
        return 'Partner';
    }

    protected function listColumns(): array
    {
        return [
            'name' => 'Nome',
            'category' => 'Categoria',
            'active' => 'Attivo',
        ];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|max:190'],
            'category' => [
                'label' => 'Categoria',
                'type' => 'select',
                'options' => [
                    'fotografo' => 'Fotografo',
                    'fiori' => 'Fiori',
                    'catering' => 'Catering',
                    'musica' => 'Musica',
                    'location' => 'Location',
                    'altro' => 'Altro',
                ],
            ],
            'logo_media_id' => ['label' => 'Logo (opzionale)', 'type' => 'media'],
            'description' => ['label' => 'Descrizione', 'type' => 'textarea', 'bilingual' => true],
            'website' => ['label' => 'Sito web', 'type' => 'text'],
            'active' => ['label' => 'Pubblicato', 'type' => 'bool'],
            'consenso_citazione' => [
                'label' => 'Consenso a essere citato',
                'type' => 'bool',
                'help' => 'Senza consenso il partner non compare nella pagina Servizi.',
            ],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
