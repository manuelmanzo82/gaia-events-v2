<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

final class FaqsController extends ResourceController
{
    protected function table(): string
    {
        return 'faqs';
    }

    protected function route(): string
    {
        return 'faq';
    }

    protected function title(): string
    {
        return 'FAQ';
    }

    protected function listColumns(): array
    {
        return ['question_it' => 'Domanda', 'group_name' => 'Gruppo', 'active' => 'Attiva'];
    }

    protected function fields(): array
    {
        return [
            'question' => [
                'label' => 'Domanda',
                'type' => 'text',
                'bilingual' => true,
                'rules' => 'required|max:500',
            ],
            'answer' => ['label' => 'Risposta', 'type' => 'textarea', 'bilingual' => true, 'rules' => 'required'],
            'group_name' => ['label' => 'Gruppo', 'type' => 'text'],
            'active' => ['label' => 'Attiva', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }
}
