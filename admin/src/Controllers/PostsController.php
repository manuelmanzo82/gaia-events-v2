<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Db;
use Gaia\Admin\Magazine;

/**
 * Magazine: articoli. Il contenuto si scrive con l'editor a blocchi
 * (views/_blocchi.php): paragrafi, titoli, citazioni, immagini dalla
 * libreria, video dalla libreria, domande e risposte. Al salvataggio il
 * server normalizza i blocchi (Magazine::normalizza) e rigenera body_it e
 * body_en con i soli tag consentiti; i minuti di lettura si calcolano dalle
 * parole. Niente HTML scritto a mano.
 */
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
        return 'Magazine';
    }

    protected function orderBy(): string
    {
        return 'post_date DESC, id DESC';
    }

    protected function listColumns(): array
    {
        return ['title_it' => 'Titolo', 'post_date' => 'Data', 'featured' => 'In evidenza', 'active' => 'Pubblicato'];
    }

    protected function fields(): array
    {
        $categorie = ['' => '— nessuna —'];
        foreach (Db::all('SELECT id, nome_it FROM post_categories ORDER BY sort_order, id') as $row) {
            $categorie[(string) $row['id']] = (string) $row['nome_it'];
        }

        $video = ['' => '— nessun video —'];
        foreach (Db::all('SELECT id, tipo, titolo_it FROM videos ORDER BY created_at DESC') as $row) {
            $video[(string) $row['id']] = (string) $row['titolo_it'] . ' (' . (string) $row['tipo'] . ')';
        }

        return [
            // Lo slug è bilingue: se manca quello EN, la pagina EN non nasce.
            'slug' => ['label' => 'Slug', 'type' => 'slug', 'bilingual' => true, 'rules' => 'required|max:190'],
            'title' => ['label' => 'Titolo', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:255',
                'help' => 'Maiuscola solo sulla prima parola (e sui nomi propri).'],
            'excerpt' => ['label' => 'Estratto', 'type' => 'textarea', 'bilingual' => true],
            'sommario' => ['label' => 'In breve (un punto per riga, 3–4 punti)', 'type' => 'textarea', 'bilingual' => true],
            'category_id' => ['label' => 'Categoria', 'type' => 'select', 'options' => $categorie],
            'post_date' => ['label' => 'Data di pubblicazione', 'type' => 'date', 'rules' => 'required|date'],
            'modified_date' => ['label' => 'Aggiornato il (facoltativo)', 'type' => 'date',
                'help' => 'Compare accanto alla data come «Aggiornato il …». Lasciare vuoto se l’articolo non è stato rivisto.'],
            'cover_media_id' => ['label' => 'Copertina (foto)', 'type' => 'media',
                'help' => 'Almeno 2000 px di larghezza: la copertina è larga. Senza foto nitida, meglio nessuna copertina.'],
            'cover_video_id' => ['label' => 'Copertina video (facoltativa)', 'type' => 'select', 'options' => $video,
                'help' => 'Se c’è, prende il posto della foto in testa all’articolo.'],
            'blocchi' => ['label' => 'Contenuto', 'type' => 'blocchi', 'options' => $video],
            'meta_title' => ['label' => 'Meta title', 'type' => 'text', 'bilingual' => true],
            'meta_description' => ['label' => 'Meta description', 'type' => 'textarea', 'bilingual' => true],
            'featured' => ['label' => 'In evidenza nell’indice', 'type' => 'bool'],
            'active' => ['label' => 'Pubblicato', 'type' => 'bool'],
        ];
    }

    /**
     * Come il generico, più i blocchi: normalizzati, salvati in JSON e
     * trasformati in body_it/body_en (HTML con i soli tag consentiti).
     *
     * @return array{0: array<string, mixed>, 1: list<string>}
     */
    protected function collect(): array
    {
        [$data, $errors] = parent::collect();

        $blocchi = Magazine::normalizza((string) ($_POST['blocchi'] ?? ''));
        $data['blocchi'] = json_encode($blocchi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $data['body_it'] = Magazine::html($blocchi, 'it');
        $data['body_en'] = Magazine::html($blocchi, 'en');
        $data['reading_time'] = Magazine::minutiLettura($blocchi, 'it');

        if ($blocchi === [] || !Magazine::completo($blocchi, 'it')) {
            $errors[] = 'Il contenuto italiano è incompleto: ogni blocco di testo deve avere il testo in italiano.';
        }

        return [$data, $errors];
    }

    protected function castValue(string $type, mixed $raw, string $column): mixed
    {
        if (in_array($column, ['category_id', 'cover_video_id'], true)) {
            return (int) $raw > 0 ? (int) $raw : null;
        }
        if ($column === 'modified_date') {
            return is_string($raw) && $raw !== '' ? $raw : null;
        }

        return parent::castValue($type, $raw, $column);
    }
}
