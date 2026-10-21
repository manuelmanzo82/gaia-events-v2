<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

/**
 * Hanno scelto Gaia: aziende clienti, per la fila di loghi della pagina
 * Dicono di me. Solo con il logo e il permesso dell'azienda
 * (DA-CHIEDERE-A-GAIA n. 11); senza logo compare il nome.
 */
final class ClientsController extends ResourceController
{
    protected function table(): string
    {
        return 'clients';
    }

    protected function route(): string
    {
        return 'clienti';
    }

    protected function title(): string
    {
        return 'Hanno scelto Gaia';
    }

    protected function listColumns(): array
    {
        return [
            'name' => 'Azienda',
            'sector' => 'Settore',
            'sort_order' => 'Ordine',
            'active' => 'Pubblicato',
        ];
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Nome dell\'azienda', 'type' => 'text', 'rules' => 'required|max:190'],
            'logo_media_id' => ['label' => 'Logo', 'type' => 'media'],
            'sector' => ['label' => 'Settore', 'type' => 'text', 'rules' => 'max:120'],
            'website' => ['label' => 'Sito web (opzionale)', 'type' => 'text', 'rules' => 'max:300'],
            'active' => ['label' => 'Pubblicato', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
