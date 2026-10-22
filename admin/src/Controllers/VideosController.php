<?php

declare(strict_types=1);

namespace Gaia\Admin\Controllers;

use Gaia\Admin\Config;
use Gaia\Admin\Db;
use Gaia\Admin\Session;

/**
 * Video: la libreria dei video del Magazine (blocchi e copertine).
 *
 *  - clip: file caricati qui, in uploads/video/ (mp4 H.264 obbligatorio,
 *    webm facoltativo), al massimo 8 MB l'uno come i video dell'hero,
 *    MIME controllato davvero;
 *  - youtube / vimeo: si incolla il link, l'id si estrae da solo; sul sito
 *    l'iframe (youtube-nocookie / player.vimeo.com con dnt=1) si carica
 *    solo al clic.
 *
 * Il poster è obbligatorio e viene dalla libreria media (niente miniature
 * scaricate da YouTube o Vimeo): larghezza e altezza del video sono quelle
 * del poster. Titolo e descrizione servono anche al JSON-LD VideoObject.
 */
final class VideosController extends ResourceController
{
    private const MAX_VIDEO_BYTES = 8 * 1024 * 1024;

    private int $idCorrente = 0;

    protected function table(): string
    {
        return 'videos';
    }

    protected function route(): string
    {
        return 'video';
    }

    protected function title(): string
    {
        return 'Video';
    }

    protected function orderBy(): string
    {
        return 'created_at DESC, id DESC';
    }

    protected function listColumns(): array
    {
        return ['titolo_it' => 'Titolo', 'tipo' => 'Tipo', 'created_at' => 'Caricato il'];
    }

    protected function fields(): array
    {
        return [
            'tipo' => [
                'label' => 'Tipo',
                'type' => 'select',
                'options' => ['clip' => 'Clip caricata qui', 'youtube' => 'YouTube', 'vimeo' => 'Vimeo'],
            ],
            'titolo' => ['label' => 'Titolo', 'type' => 'text', 'bilingual' => true, 'rules' => 'required|max:255'],
            'descrizione' => ['label' => 'Descrizione breve', 'type' => 'textarea', 'bilingual' => true,
                'help' => 'Una o due frasi: servono a Google per capire il video.'],
            'poster_media_id' => ['label' => 'Poster (obbligatorio)', 'type' => 'media',
                'help' => 'Una foto della libreria, delle stesse proporzioni del video.'],
            'file_mp4' => ['label' => 'Clip: file MP4 (H.264, max 8 MB)', 'type' => 'file',
                'help' => 'Breve, senza audio importante: sul sito parte muta e in loop quando è a schermo.'],
            'file_webm' => ['label' => 'Clip: file WebM (facoltativo, max 8 MB)', 'type' => 'file'],
            'sottotitoli_it' => ['label' => 'Clip: sottotitoli in italiano (.vtt, facoltativi)', 'type' => 'file',
                'help' => 'File WebVTT (inizia con «WEBVTT»), max 200 KB. Sul sito si vedono quando il video è muto.'],
            'sottotitoli_en' => ['label' => 'Clip: sottotitoli in inglese (.vtt, facoltativi)', 'type' => 'file'],
            'url' => ['label' => 'YouTube o Vimeo: link del video', 'type' => 'text',
                'help' => 'Es. https://www.youtube.com/watch?v=… oppure https://vimeo.com/…'],
            'durata_sec' => ['label' => 'Durata in secondi (facoltativa)', 'type' => 'number'],
        ];
    }

    public function save(array $params = []): void
    {
        $this->idCorrente = (int) ($params['id'] ?? 0);
        parent::save($params);
    }

    /** @return array{0: array<string, mixed>, 1: list<string>} */
    protected function collect(): array
    {
        [$data, $errors] = parent::collect();
        $esistente = $this->idCorrente > 0
            ? (Db::one('SELECT * FROM videos WHERE id = ?', [$this->idCorrente]) ?? [])
            : [];
        $tipo = in_array($data['tipo'] ?? '', ['clip', 'youtube', 'vimeo'], true) ? (string) $data['tipo'] : 'clip';
        $data['tipo'] = $tipo;

        if (empty($data['poster_media_id'])) {
            $errors[] = 'Il poster è obbligatorio.';
        }

        // File della clip: caricati ora, tolti su richiesta, altrimenti invariati.
        $limiti = [
            'file_mp4' => ['video/mp4', 'mp4', self::MAX_VIDEO_BYTES],
            'file_webm' => ['video/webm', 'webm', self::MAX_VIDEO_BYTES],
            'sottotitoli_it' => ['text/vtt', 'vtt', 200 * 1024],
            'sottotitoli_en' => ['text/vtt', 'vtt', 200 * 1024],
        ];
        foreach ($limiti as $colonna => [$mime, $estensione, $massimo]) {
            unset($data[$colonna]);
            if (!empty($_POST['rimuovi_' . $colonna]) && !empty($esistente[$colonna])) {
                @unlink(rtrim((string) Config::get('uploads_dir'), '/') . '/' . (string) $esistente[$colonna]);
                $data[$colonna] = null;
            }
            $file = $_FILES[$colonna] ?? null;
            if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ((int) $file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = strtoupper($estensione) . ': caricamento non riuscito (errore ' . (int) $file['error'] . ').';
                continue;
            }
            if ((int) $file['size'] > $massimo) {
                $mb = number_format((int) $file['size'] / 1048576, 1, ',', '');
                $limite = $estensione === 'vtt' ? '200 KB' : '8 MB';
                $errors[] = strtoupper($estensione) . ": il file pesa {$mb} MB, il limite è {$limite}.";
                continue;
            }
            $rilevato = (string) (new \finfo(FILEINFO_MIME_TYPE))->file((string) $file['tmp_name']);
            // WebVTT: finfo lo vede come testo; vale l'intestazione «WEBVTT».
            if ($estensione === 'vtt') {
                $inizio = (string) file_get_contents((string) $file['tmp_name'], false, null, 0, 16);
                $inizio = ltrim($inizio, "\xEF\xBB\xBF");
                if (!in_array($rilevato, ['text/plain', 'text/vtt'], true) || !str_starts_with($inizio, 'WEBVTT')) {
                    $errors[] = 'VTT: il file non è un sottotitolo WebVTT valido (deve iniziare con «WEBVTT»).';
                    continue;
                }
                $rilevato = $mime;
            }
            if ($rilevato !== $mime) {
                $errors[] = strtoupper($estensione) . ": il file non è {$mime} (rilevato {$rilevato}).";
                continue;
            }
            $cartella = rtrim((string) Config::get('uploads_dir'), '/') . '/video';
            if (!is_dir($cartella) && !mkdir($cartella, 0755, true) && !is_dir($cartella)) {
                $errors[] = 'Cartella uploads/video non creabile.';
                continue;
            }
            $nome = 'video/' . ($estensione === 'vtt' ? 'sottotitoli-' . substr($colonna, -2) . '-' : 'clip-')
                . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $estensione;
            if (!move_uploaded_file((string) $file['tmp_name'], rtrim((string) Config::get('uploads_dir'), '/') . '/' . $nome)) {
                $errors[] = strtoupper($estensione) . ': caricamento non riuscito.';
                continue;
            }
            $data[$colonna] = $nome;
        }

        if ($tipo === 'clip') {
            $mp4 = array_key_exists('file_mp4', $data) ? $data['file_mp4'] : ($esistente['file_mp4'] ?? null);
            if (empty($mp4)) {
                $errors[] = 'Per una clip serve il file MP4.';
            }
            $data['provider_id'] = null;
        } else {
            $id = self::idDaLink((string) ($data['url'] ?? ''), $tipo);
            if ($id === null) {
                $errors[] = 'Link ' . ($tipo === 'youtube' ? 'YouTube' : 'Vimeo') . ' non riconosciuto.';
            }
            $data['provider_id'] = $id;
        }

        return [$data, $errors];
    }

    /** Id del video da un link YouTube (11 caratteri) o Vimeo (numerico). */
    public static function idDaLink(string $url, string $tipo): ?string
    {
        $url = trim($url);
        if ($tipo === 'youtube'
            && preg_match('~(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{11})~', $url, $m) === 1) {
            return $m[1];
        }
        if ($tipo === 'vimeo' && preg_match('~vimeo\.com/(?:video/)?(\d{5,12})~', $url, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    protected function castValue(string $type, mixed $raw, string $column): mixed
    {
        if ($column === 'durata_sec') {
            return (int) $raw > 0 ? (int) $raw : null;
        }
        if ($type === 'file') {
            return null;
        }

        return parent::castValue($type, $raw, $column);
    }
}
