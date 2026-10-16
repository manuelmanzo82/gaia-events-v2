<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Db;

final class LocationsController extends ResourceController
{
    protected function table(): string
    {
        return 'locations';
    }

    protected function route(): string
    {
        return 'location';
    }

    protected function title(): string
    {
        return 'Location';
    }

    protected function orderBy(): string
    {
        return 'luogo_id, sort_order, nome';
    }

    protected function listColumns(): array
    {
        return [
            'nome' => 'Nome',
            'tipo' => 'Tipo',
            'luogo_id' => 'Luogo',
            'active' => 'Attivo',
        ];
    }

    protected function fields(): array
    {
        $luoghi = [];
        foreach (Db::all('SELECT id, nome_it, tipo FROM luoghi ORDER BY parent_id IS NULL DESC, sort_order, nome_it') as $row) {
            $luoghi[(string) $row['id']] = ($row['tipo'] === 'regione' ? '[regione] ' : '') . (string) $row['nome_it'];
        }

        return [
            'nome' => ['label' => 'Nome', 'type' => 'text', 'rules' => 'required|max:190'],
            'tipo' => [
                'label' => 'Tipo',
                'type' => 'select',
                'options' => [
                    'villa' => 'Villa',
                    'castello' => 'Castello',
                    'chiesa' => 'Chiesa',
                    'terrazza' => 'Terrazza',
                    'tenuta' => 'Tenuta',
                    'hotel' => 'Hotel',
                    'ristorante' => 'Ristorante',
                    'altro' => 'Altro',
                ],
            ],
            'luogo_id' => [
                'label' => 'Luogo',
                'type' => 'select',
                'options' => $luoghi,
                'rules' => 'required',
            ],
            'descrizione' => [
                'label' => 'Descrizione breve (opzionale)',
                'type' => 'textarea',
                'bilingual' => true,
            ],
            'website' => ['label' => 'Sito web (opzionale)', 'type' => 'text'],
            'own_page' => [
                'label' => 'Pagina propria (futuro)',
                'type' => 'bool',
                'help' => 'Per ora non genera nulla: segna solo le location da trasformare in pagina più avanti.',
            ],
            'active' => ['label' => 'Pubblicata', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }

    protected function castValue(string $type, mixed $raw, string $column): mixed
    {
        if ($column === 'luogo_id') {
            return (int) $raw > 0 ? (int) $raw : null;
        }

        return parent::castValue($type, $raw, $column);
    }
}
