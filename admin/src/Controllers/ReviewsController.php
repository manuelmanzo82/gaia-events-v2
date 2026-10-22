<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Db;

/**
 * Recensioni per «Dicono di me». Si pubblicano solo con il consenso
 * scritto di chi le ha scritte: senza la casella il salvataggio come
 * «Attiva» viene rifiutato. L'evento collegato (facoltativo) porta alla
 * sua pagina in I miei eventi.
 */
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
        return ['author' => 'Autore', 'rating' => 'Voto', 'consenso' => 'Consenso', 'active' => 'Attiva'];
    }

    protected function fields(): array
    {
        $eventi = ['' => '— nessuno —'];
        foreach (Db::all('SELECT id, title_it FROM stories ORDER BY sort_order, id') as $row) {
            $eventi[(string) $row['id']] = (string) $row['title_it'];
        }

        return [
            'author' => ['label' => 'Autore', 'type' => 'text', 'rules' => 'required|max:160'],
            'event_name' => ['label' => 'Evento', 'type' => 'text', 'bilingual' => true],
            'review_date' => ['label' => 'Data', 'type' => 'date'],
            'text' => ['label' => 'Testo', 'type' => 'textarea', 'bilingual' => true, 'rules' => 'required'],
            'rating' => ['label' => 'Voto da 1 a 5', 'type' => 'number'],
            'media_id' => ['label' => 'Immagine', 'type' => 'media'],
            'story_id' => ['label' => 'Evento collegato (facoltativo)', 'type' => 'select', 'options' => $eventi],
            'featured' => ['label' => 'In evidenza (citazione grande)', 'type' => 'bool'],
            'consenso' => [
                'label' => 'Consenso scritto ricevuto',
                'type' => 'bool',
                'help' => 'Serve il permesso scritto (email o messaggio) di chi ha scritto la recensione, con il nome come compare qui. Senza, non si può pubblicare.',
            ],
            'active' => ['label' => 'Attiva', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine', 'type' => 'number'],
        ];
    }

    /** @return array{0: array<string, mixed>, 1: list<string>} */
    protected function collect(): array
    {
        [$data, $errors] = parent::collect();

        if (!empty($data['active']) && empty($data['consenso'])) {
            $errors[] = 'Senza il consenso scritto la recensione non si può pubblicare: togli «Attiva» o spunta «Consenso scritto ricevuto».';
        }

        return [$data, $errors];
    }

    protected function castValue(string $type, mixed $raw, string $column): mixed
    {
        if ($column === 'story_id') {
            return (int) $raw > 0 ? (int) $raw : null;
        }

        return parent::castValue($type, $raw, $column);
    }
}
