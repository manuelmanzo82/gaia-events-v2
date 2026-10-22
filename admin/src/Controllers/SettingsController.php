<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Auth;
use Gaia\Admin\Csrf;
use Gaia\Admin\Db;
use Gaia\Admin\Session;
use Gaia\Admin\View;

/**
 * Impostazioni e testi di sezione: tabella chiave/valore bilingue.
 *
 * Le chiavi usano la notazione con punto (hero.title, sections.about.text)
 * e l'export le ritrasforma in una struttura annidata uguale a
 * site/src/content/settings.json.
 */
final class SettingsController
{
    public function index(array $params = []): void
    {
        Auth::requireLogin();

        $rows = Db::all('SELECT * FROM settings ORDER BY group_name, sort_order, setting_key');

        $groups = [];
        foreach ($rows as $row) {
            $groups[(string) $row['group_name']][] = $row;
        }

        View::render('settings', [
            'title' => 'Impostazioni e testi',
            'groups' => $groups,
            'mediaOptions' => $this->mediaOptions(),
            'videoOptions' => $this->videoOptions(),
        ]);
    }

    public function save(array $params = []): void
    {
        Auth::requireLogin();
        Csrf::verify();

        $valuesIt = $_POST['value_it'] ?? [];
        $valuesEn = $_POST['value_en'] ?? [];

        if (!is_array($valuesIt)) {
            redirect('impostazioni');
        }

        Db::transaction(static function () use ($valuesIt, $valuesEn): void {
            foreach ($valuesIt as $id => $value) {
                $id = (int) $id;
                if ($id <= 0) {
                    continue;
                }

                // Aggiorniamo solo righe già esistenti: le chiavi nascono
                // dalla migrazione, non dal form.
                Db::run(
                    'UPDATE settings SET value_it = ?, value_en = ? WHERE id = ?',
                    [
                        self::clean($value),
                        self::clean(is_array($valuesEn) ? ($valuesEn[$id] ?? null) : null),
                        $id,
                    ],
                );
            }
        });

        Session::flash('success', 'Impostazioni salvate. Ricordati di pubblicare.');
        redirect('impostazioni');
    }

    private static function clean(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    /** @return array<int, string> Video della libreria, per le impostazioni di tipo «video». */
    private function videoOptions(): array
    {
        $options = [];

        foreach (Db::all('SELECT id, tipo, titolo_it FROM videos ORDER BY created_at DESC') as $row) {
            $options[(int) $row['id']] = (string) $row['titolo_it'] . ' (' . (string) $row['tipo'] . ')';
        }

        return $options;
    }

    /** @return array<int, string> */
    private function mediaOptions(): array
    {
        $options = [];

        foreach (Db::all('SELECT id, path FROM media ORDER BY created_at DESC LIMIT 500') as $row) {
            $options[(int) $row['id']] = (string) $row['path'];
        }

        return $options;
    }
}
