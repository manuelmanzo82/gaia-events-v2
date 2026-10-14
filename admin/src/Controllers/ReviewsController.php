<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

final class ReviewsController extends ResourceController
{
    protected function table(): string
    {
        return 'reviews';
    }

    protected function route(): string
    {
        return 'recensioni';
    }

    protected function title(): string
    {
        return 'Recensioni';
    }

    protected function listColumns(): array
    {
        return ['author' => 'Autore', 'rating' => 'Voto', 'active' => 'Attiva'];
    }

    protected function fields(): array
    {
        return [
            'author' => ['label' => 'Autore', 'type' => 'text', 'rules' => 'required|max:160'],
            'event_name' => ['label' => 'Evento', 'type' => 'text', 'bilingual' => true],
            'review_date' => ['label' => 'Data', 'type' => 'date'],
            'text' => ['label' => 'Testo', 'type' => 'textarea', 'bilingual' => true, 'rules' => 'required'],
            'rating' => ['label' => 'Voto da 1 a 5', 'type' => 'number'],
            'media_id' => ['label' => 'Immagine', 'type' => 'media'],
            'featured' => ['label' => 'In evidenza', 'type' => 'bool'],
            'active' => ['label' => 'Attiva', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
