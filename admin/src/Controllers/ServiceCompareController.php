<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

/**
 * Confronto servizi: le voci della tabella «Quale fa per voi?» della
 * pagina Servizi. Per ogni voce si indicano solo i servizi che la
 * comprendono direttamente; il sito aggiunge da solo quelli che li
 * includono (Servizi → «Include (altri servizi)»), come il preventivatore.
 */
final class ServiceCompareController extends ResourceController
{
    protected function table(): string
    {
        return 'service_compare';
    }

    protected function route(): string
    {
        return 'confronto-servizi';
    }

    protected function title(): string
    {
        return 'Confronto servizi';
    }

    protected function listColumns(): array
    {
        return [
            'label_it' => 'Voce',
            'service_slugs' => 'Servizi',
            'sort_order' => 'Ordine',
            'active' => 'Pubblicata',
        ];
    }

    protected function fields(): array
    {
        return [
            'label' => ['label' => 'Voce', 'type' => 'text', 'bilingual' => true, 'rules' => 'max:190'],
            'service_slugs' => [
                'label' => 'Servizi che la comprendono',
                'type' => 'text',
                'help' => 'Slug separati da virgola, es. «coordinamento» o «planning, design». Non serve '
                    . 'aggiungere i servizi che ne includono altri: il wedding planning completo eredita '
                    . 'da solo le voci di coordinamento e design.',
            ],
            'active' => ['label' => 'Pubblicata', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
