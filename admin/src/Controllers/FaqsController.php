<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Db;
use Gaia\Admin\Magazine;
use Gaia\Admin\Validator;

/**
 * FAQ. Il gruppo viene da «FAQ: gruppi» (Generali, Servizi, Costi e
 * pagamenti, Destination): la pagina FAQ li mostra in quest'ordine, la
 * pagina Servizi mostra Servizi e Costi, Destination il suo. L'ancora
 * (link condivisibile /faq/#ancora) si crea dalla domanda al primo
 * salvataggio e poi resta stabile anche se la domanda cambia.
 */
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

    protected function orderBy(): string
    {
        return 'group_id, sort_order, id';
    }

    protected function listColumns(): array
    {
        return ['question_it' => 'Domanda', 'group_name' => 'Gruppo', 'sort_order' => 'Ordine', 'active' => 'Pubblicata'];
    }

    protected function fields(): array
    {
        $gruppi = [];
        foreach (Db::all('SELECT id, nome_it FROM faq_groups ORDER BY sort_order, id') as $row) {
            $gruppi[(string) $row['id']] = (string) $row['nome_it'];
        }

        return [
            'question' => [
                'label' => 'Domanda',
                'type' => 'text',
                'bilingual' => true,
                'rules' => 'required|max:500',
            ],
            'answer' => ['label' => 'Risposta', 'type' => 'textarea', 'bilingual' => true, 'rules' => 'required'],
            'group_id' => ['label' => 'Gruppo', 'type' => 'select', 'options' => $gruppi],
            'slug' => [
                'label' => 'Ancora del link (facoltativa)',
                'type' => 'slug',
                'bilingual' => true,
                'help' => 'Vuota = si crea dalla domanda. Una volta pubblicata meglio non cambiarla: i link condivisi smetterebbero di aprire la domanda.',
            ],
            'active' => ['label' => 'Pubblicata', 'type' => 'bool'],
            'sort_order' => ['label' => 'Ordine nel gruppo', 'type' => 'number'],
        ];
    }

    /** @return array{0: array<string, mixed>, 1: list<string>} */
    protected function collect(): array
    {
        [$data, $errors] = parent::collect();

        // Il nome del gruppo resta anche nella colonna di testo (compatibilità).
        $gruppo = Db::one('SELECT chiave FROM faq_groups WHERE id = ?', [(int) ($data['group_id'] ?? 0)]);
        $data['group_name'] = $gruppo !== null ? (string) $gruppo['chiave'] : 'generali';

        foreach (['it', 'en'] as $lingua) {
            if (($data["slug_{$lingua}"] ?? '') === '' && ($data["question_{$lingua}"] ?? '') !== '') {
                $usate = [];
                $data["slug_{$lingua}"] = Magazine::ancora((string) $data["question_{$lingua}"], $usate);
            }
            if (($data["slug_{$lingua}"] ?? '') === '') {
                $data["slug_{$lingua}"] = null;
            }
        }

        return [$data, $errors];
    }

    protected function castValue(string $type, mixed $raw, string $column): mixed
    {
        if ($column === 'group_id') {
            return (int) $raw > 0 ? (int) $raw : null;
        }
        if ($type === 'slug') {
            $valore = Validator::slug((string) $raw);

            return $valore;
        }

        return parent::castValue($type, $raw, $column);
    }
}
