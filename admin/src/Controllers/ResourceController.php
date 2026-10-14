<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Session;
use Gaia\Admin\Validator;
use Gaia\Admin\View;

/**
 * CRUD generico per le sezioni di contenuto.
 *
 * Una sottoclasse dichiara tabella, rotta e campi; elenco, form, salvataggio
 * e cancellazione arrivano da qui. Serve a non riscrivere nove volte lo
 * stesso codice per servizi, eventi, recensioni, articoli e FAQ.
 *
 * Tipi di campo: text, textarea, html, slug, date, number, bool, select,
 * list (elenco di stringhe, una per riga), media (id di un record media).
 * Un campo con 'bilingual' => true genera due colonne, *_it e *_en.
 */
abstract class ResourceController
{
    /** Tabella del database. */
    abstract protected function table(): string;

    /** Segmento di URL, es. 'servizi'. */
    abstract protected function route(): string;

    /** Titolo mostrato nelle schermate. */
    abstract protected function title(): string;

    /**
     * @return array<string, array{
     *     label: string,
     *     type: string,
     *     bilingual?: bool,
     *     rules?: string,
     *     options?: array<string, string>,
     *     help?: string
     * }>
     */
    abstract protected function fields(): array;

    protected function orderBy(): string
    {
        return 'sort_order, id';
    }

    /** Colonne mostrate nell'elenco. */
    protected function listColumns(): array
    {
        return ['title_it' => 'Titolo', 'active' => 'Attivo'];
    }

    // -----------------------------------------------------------------
    // Azioni
    // -----------------------------------------------------------------

    public function index(array $params = []): void
    {
        Auth::requireLogin();

        $rows = Db::all(sprintf('SELECT * FROM `%s` ORDER BY %s', $this->table(), $this->orderBy()));

        View::render('resource/index', [
            'title' => $this->title(),
            'route' => $this->route(),
            'columns' => $this->listColumns(),
            'rows' => $rows,
        ]);
    }

    public function create(array $params = []): void
    {
        Auth::requireLogin();

        View::render('resource/form', [
            'title' => 'Nuovo — ' . $this->title(),
            'route' => $this->route(),
            'fields' => $this->fields(),
            'row' => [],
            'mediaOptions' => $this->mediaOptions(),
        ]);
    }

    public function edit(array $params = []): void
    {
        Auth::requireLogin();

        $row = $this->find((int) ($params['id'] ?? 0));

        View::render('resource/form', [
            'title' => 'Modifica — ' . $this->title(),
            'route' => $this->route(),
            'fields' => $this->fields(),
            'row' => $row,
            'mediaOptions' => $this->mediaOptions(),
        ]);
    }

    public function save(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $id = (int) ($params['id'] ?? 0);
        [$data, $errors] = $this->collect();

        if ($errors !== []) {
            Session::flash('error', implode(' ', $errors));
            redirect($this->route() . ($id > 0 ? "/{$id}/modifica" : '/nuovo'));
        }

        if ($id > 0) {
            $this->find($id);
            Db::update($this->table(), $id, $data);
            Session::flash('success', 'Modifiche salvate. Ricordati di pubblicare.');
        } else {
            $id = Db::insert($this->table(), $data);
            Session::flash('success', 'Elemento creato. Ricordati di pubblicare.');
        }

        redirect($this->route() . "/{$id}/modifica");
    }

    public function destroy(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $id = (int) ($params['id'] ?? 0);
        $this->find($id);

        Db::delete($this->table(), $id);
        Session::flash('success', 'Elemento eliminato. Ricordati di pubblicare.');

        redirect($this->route());
    }

    // -----------------------------------------------------------------
    // Supporto
    // -----------------------------------------------------------------

    /** @return array<string, mixed> */
    protected function find(int $id): array
    {
        $row = Db::one(sprintf('SELECT * FROM `%s` WHERE id = ?', $this->table()), [$id]);

        if ($row === null) {
            http_response_code(404);
            View::render('error', [
                'title' => 'Non trovato',
                'message' => 'Questo elemento non esiste più.',
            ]);
            exit;
        }

        return $row;
    }

    /**
     * Legge il POST secondo la dichiarazione dei campi.
     *
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    protected function collect(): array
    {
        $data = [];
        $errors = [];

        foreach ($this->fields() as $name => $field) {
            $columns = ($field['bilingual'] ?? false)
                ? ["{$name}_it", "{$name}_en"]
                : [$name];

            foreach ($columns as $column) {
                $raw = $_POST[$column] ?? null;
                $data[$column] = $this->castValue($field['type'], $raw, $column);
            }

            // Le regole si applicano alla versione italiana: l'inglese è opzionale.
            $rules = $field['rules'] ?? null;
            if ($rules !== null) {
                $target = ($field['bilingual'] ?? false) ? "{$name}_it" : $name;
                $validator = new Validator([$target => $data[$target]], [$target => $rules]);

                if ($validator->fails()) {
                    $errors[] = str_replace($target, $field['label'], (string) $validator->firstError());
                }
            }
        }

        return [$data, $errors];
    }

    protected function castValue(string $type, mixed $raw, string $column): mixed
    {
        $value = is_string($raw) ? trim($raw) : $raw;

        return match ($type) {
            'bool' => !empty($raw) ? 1 : 0,
            'number' => $value === '' || $value === null ? 0 : (int) $value,
            'media' => (int) $value > 0 ? (int) $value : null,
            'date' => is_string($value) && $value !== '' ? $value : null,
            'slug' => Validator::slug((string) $value),
            // Una stringa per riga nella textarea diventa un array JSON.
            'list' => json_encode(
                array_values(array_filter(array_map(
                    'trim',
                    preg_split('/\r\n|\r|\n/', (string) $value) ?: [],
                ), static fn (string $line): bool => $line !== '')),
                JSON_UNESCAPED_UNICODE,
            ),
            default => $value === '' ? null : $value,
        };
    }

    /**
     * Immagini disponibili, per le tendine dei campi media.
     *
     * @return array<int, string>
     */
    protected function mediaOptions(): array
    {
        $options = [];

        foreach (Db::all('SELECT id, path FROM media ORDER BY created_at DESC LIMIT 500') as $row) {
            $options[(int) $row['id']] = (string) $row['path'];
        }

        return $options;
    }
}
