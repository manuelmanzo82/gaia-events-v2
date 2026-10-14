<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

final class PostsController extends ResourceController
{
    protected function table(): string
    {
        return 'posts';
    }

    protected function route(): string
    {
        return 'blog';
    }

    protected function title(): string
    {
        return 'Blog';
    }

    protected function orderBy(): string
    {
        return 'post_date DESC, id DESC';
    }

    protected function listColumns(): array
    {
        return ['title_it' => 'Titolo', 'post_date' => 'Data', 'active' => 'Attivo'];
    }

    protected function fields(): array
    {
        return [
            // Lo slug è bilingue: se manca quello EN, la pagina EN non nasce.
            'slug' => ['label' => 'Slug', 'type' => 'slug', 'bilingual' => true, 'rules' => 'required|max:190'],
            'title' => ['label' => 'Titolo', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:255'],
            'excerpt' => ['label' => 'Estratto', 'type' => 'textarea', 'bilingual' => true],
            'category' => ['label' => 'Categoria', 'type' => 'text', 'bilingual' => true],
            'post_date' => ['label' => 'Data', 'type' => 'date', 'rules' => 'required|date'],
            'reading_time' => ['label' => 'Minuti di lettura', 'type' => 'number'],
            'cover_media_id' => ['label' => 'Copertina', 'type' => 'media'],
            'body' => ['label' => 'Contenuto', 'type' => 'html', 'bilingual' => true],
            'meta_title' => ['label' => 'Meta title', 'type' => 'text', 'bilingual' => true],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'bilingual' => true],
            'active' => ['label' => 'Attivo', 'type' => 'bool'],
        ];
    }
}
