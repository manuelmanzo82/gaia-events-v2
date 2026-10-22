<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

/**
 * FAQ: gruppi (nome e ancora bilingui, ordine). La chiave è fissa: il sito
 * la usa per scegliere le FAQ di Servizi (servizi, costi) e di Destination.
 */
final class FaqGroupsController extends ResourceController
{
    protected function table(): string
    {
        return 'faq_groups';
    }

    protected function route(): string
    {
        return 'gruppi-faq';
    }

    protected function title(): string
    {
        return 'FAQ: gruppi';
    }

    protected function listColumns(): array
    {
        return ['nome_it' => 'Nome', 'chiave' => 'Chiave', 'sort_order' => 'Ordine'];
    }

    protected function fields(): array
    {
        return [
            'nome' => ['label' => 'Nome', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:120'],
            'slug' => ['label' => 'Ancora nella pagina FAQ', 'type' => 'slug', 'bilingual' => true, 'rules' => 'required|max:120'],
            'chiave' => ['label' => 'Chiave (non cambiarla: la usa il sito)', 'type' => 'text', 'rules' => 'required|max:40'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
