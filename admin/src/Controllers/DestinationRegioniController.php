<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Db;

/**
 * Destination: le regioni della sezione «Dove» della pagina Destination
 * wedding. Ogni regione accende la sua zona sulla mappa (codice regione)
 * e, se c'è, un punto per una zona più precisa (es. la Costiera dentro la
 * Campania). Il collegamento a Luoghi è facoltativo: il link compare sul
 * sito solo se la pagina di quel luogo esiste nella lingua.
 * Si pubblicano in produzione solo le regioni confermate da Gaia.
 */
final class DestinationRegioniController extends ResourceController
{
    /** Codici delle regioni disegnate sulla mappa del sito. */
    public const REGIONI = [
        'abruzzo' => 'Abruzzo',
        'basilicata' => 'Basilicata',
        'calabria' => 'Calabria',
        'campania' => 'Campania',
        'emilia-romagna' => 'Emilia-Romagna',
        'friuli-venezia-giulia' => 'Friuli-Venezia Giulia',
        'lazio' => 'Lazio',
        'liguria' => 'Liguria',
        'lombardia' => 'Lombardia',
        'marche' => 'Marche',
        'molise' => 'Molise',
        'piemonte' => 'Piemonte',
        'puglia' => 'Puglia',
        'sardegna' => 'Sardegna',
        'sicilia' => 'Sicilia',
        'toscana' => 'Toscana',
        'trentino-alto-adige' => 'Trentino-Alto Adige',
        'umbria' => 'Umbria',
        'valle-d-aosta' => "Valle d'Aosta",
        'veneto' => 'Veneto',
    ];

    protected function table(): string
    {
        return 'destination_regioni';
    }

    protected function route(): string
    {
        return 'regioni-destination';
    }

    protected function title(): string
    {
        return 'Destination: regioni';
    }

    protected function listColumns(): array
    {
        return [
            'nome_it' => 'Nome',
            'regione_mappa' => 'Sulla mappa',
            'sort_order' => 'Ordine',
            'active' => 'Pubblicata',
        ];
    }

    protected function fields(): array
    {
        $luoghi = ['' => '— nessuno —'];
        foreach (Db::all("SELECT id, nome_it FROM luoghi WHERE tipo = 'regione' ORDER BY sort_order, nome_it") as $row) {
            $luoghi[(string) $row['id']] = (string) $row['nome_it'];
        }

        return [
            'nome' => ['label' => 'Nome', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:190'],
            'testo' => [
                'label' => 'Testo breve (due righe)',
                'type' => 'textarea',
                'bilingual' => true,
                'rules' => 'max:500',
            ],
            'media_id' => ['label' => 'Foto', 'type' => 'media'],
            'regione_mappa' => [
                'label' => 'Regione da accendere sulla mappa',
                'type' => 'select',
                'options' => self::REGIONI,
            ],
            'lat' => [
                'label' => 'Punto sulla mappa: latitudine (facoltativa)',
                'type' => 'text',
                'help' => 'Per una zona dentro la regione, es. Positano 40.628. Vuoto = nessun punto.',
            ],
            'lng' => [
                'label' => 'Punto sulla mappa: longitudine (facoltativa)',
                'type' => 'text',
                'help' => 'Es. Positano 14.484.',
            ],
            'luogo_id' => [
                'label' => 'Pagina in Luoghi (facoltativa)',
                'type' => 'select',
                'options' => $luoghi,
                'help' => 'Il link compare solo se la pagina del luogo esiste nella lingua.',
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
        if ($column === 'lat' || $column === 'lng') {
            $valore = str_replace(',', '.', trim((string) $raw));
            return is_numeric($valore) ? round((float) $valore, 5) : null;
        }

        return parent::castValue($type, $raw, $column);
    }
}
