<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

/**
 * Magazine: categorie degli articoli, con nome e slug bilingui (il filtro
 * dell'indice usa lo slug: /magazine/?categoria=location).
 */
final class PostCategoriesController extends ResourceController
{
    protected function table(): string
    {
        return 'post_categories';
    }

    protected function route(): string
    {
        return 'categorie-magazine';
    }

    protected function title(): string
    {
        return 'Magazine: categorie';
    }

    protected function listColumns(): array
    {
        return ['nome_it' => 'Nome', 'slug_it' => 'Slug', 'sort_order' => 'Ordine'];
    }

    protected function fields(): array
    {
        return [
            'nome' => ['label' => 'Nome', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:120'],
            'slug' => ['label' => 'Slug (per il filtro)', 'type' => 'slug', 'bilingual' => true, 'rules' => 'required|max:120'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
