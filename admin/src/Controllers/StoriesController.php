<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Db;

final class StoriesController extends ResourceController
{
    use GestisceGalleria;

    protected function table(): string
    {
        return 'stories';
    }

    protected function route(): string
    {
        return 'storie';
    }

    protected function title(): string
    {
        return 'I miei eventi';
    }

    protected function pivotTable(): string
    {
        return 'story_images';
    }

    protected function pivotForeignKey(): string
    {
        return 'story_id';
    }

    protected function listColumns(): array
    {
        return [
            'title_it' => 'Titolo',
            'couple' => 'Coppia',
            'story_date' => 'Data',
            'active' => 'Attivo',
        ];
    }

    protected function fields(): array
    {
        $luoghi = ['' => '— nessuno —'];
        foreach (Db::all('SELECT id, nome_it FROM luoghi ORDER BY sort_order, nome_it') as $row) {
            $luoghi[(string) $row['id']] = (string) $row['nome_it'];
        }

        $fotografi = ['' => '— nessuno —'];
        foreach (Db::all("SELECT id, name FROM partners WHERE category = 'fotografo' ORDER BY sort_order, name") as $row) {
            $fotografi[(string) $row['id']] = (string) $row['name'];
        }

        return [
            'title' => ['label' => 'Titolo', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:255'],
            'slug' => ['label' => 'Slug', 'type' => 'slug', 'bilingual' => true, 'rules' => 'required|max:190'],
            'couple' => ['label' => 'Coppia / nomi', 'type' => 'text', 'help' => 'Es. «Andrea & Federica».'],
            'location_name' => ['label' => 'Location (testo)', 'type' => 'text'],
            'luogo_id' => ['label' => 'Luogo collegato (opzionale)', 'type' => 'select', 'options' => $luoghi],
            'story_date' => ['label' => 'Data', 'type' => 'date'],
            'cover_media_id' => ['label' => 'Copertina', 'type' => 'media'],
            'body' => ['label' => 'Racconto', 'type' => 'html', 'bilingual' => true],
            'photographer_partner_id' => [
                'label' => 'Fotografo (partner)',
                'type' => 'select',
                'options' => $fotografi,
                'help' => 'Il credito compare nella pagina Partner, mai sotto le foto.',
            ],
            'meta_title' => ['label' => 'Meta title', 'type' => 'text', 'bilingual' => true],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'bilingual' => true],
            'active' => ['label' => 'Pubblicata', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }

    protected function castValue(string $type, mixed $raw, string $column): mixed
    {
        if ($column === 'luogo_id' || $column === 'photographer_partner_id') {
            return (int) $raw > 0 ? (int) $raw : null;
        }

        return parent::castValue($type, $raw, $column);
    }
}
