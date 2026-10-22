<?php

declare(strict_types=1);

namespace Gaia\Admin;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Magazine: i blocchi degli articoli.
 *
 * Un articolo è una lista di blocchi (JSON in posts.blocchi), ognuno con i
 * testi IT ed EN affiancati:
 *
 *   paragrafo  {it, en}                       testo con **grassetto**, *corsivo*, [link](url)
 *   h2, h3     {it, en}                       titoli (con ancora sul sito)
 *   citazione  {it, en, firma}                citazione in evidenza
 *   immagine   {media, formato, didascalia_it, didascalia_en}
 *   video      {video}                        id della libreria video
 *   domanda    {domanda_it, domanda_en, risposta_it, risposta_en}   → FAQPage
 *
 * Gaia non scrive mai HTML: il server normalizza i blocchi (tipi, lunghezze,
 * id esistenti) e genera l'HTML con una lista di tag consentiti (p, h2, h3,
 * strong, em, a, blockquote, figure, figcaption, img, br). Tutto il resto
 * viene escapato. L'HTML salvato in body_it/body_en è sempre questo.
 */
final class Magazine
{
    public const TIPI = ['paragrafo', 'h2', 'h3', 'citazione', 'immagine', 'video', 'domanda'];
    public const FORMATI = ['orizzontale', 'verticale', 'quadrata', 'larga'];

    private const MAX_TESTO = 6000;
    private const MAX_BREVE = 300;

    /** Tag e attributi ammessi nell'HTML pulito (anche per i vecchi corpi). */
    private const TAG_CONSENTITI = [
        'p' => [], 'h2' => ['id'], 'h3' => ['id'], 'strong' => [], 'em' => [], 'b' => [], 'i' => [],
        'a' => ['href', 'rel'], 'blockquote' => [], 'figure' => [], 'figcaption' => [],
        'img' => ['src', 'alt', 'width', 'height'], 'br' => [], 'ul' => [], 'ol' => [], 'li' => [],
    ];

    /**
     * Blocchi dal form (JSON) a struttura pulita. Scarta i tipi sconosciuti,
     * i blocchi vuoti, gli id di media e video che non esistono.
     *
     * @return list<array<string, mixed>>
     */
    public static function normalizza(mixed $grezzo): array
    {
        if (is_string($grezzo)) {
            $grezzo = $grezzo === '' ? [] : json_decode($grezzo, true);
        }
        if (!is_array($grezzo)) {
            return [];
        }

        $puliti = [];
        foreach ($grezzo as $blocco) {
            if (!is_array($blocco)) {
                continue;
            }
            $tipo = (string) ($blocco['tipo'] ?? '');
            if (!in_array($tipo, self::TIPI, true)) {
                continue;
            }

            $pulito = ['tipo' => $tipo];
            switch ($tipo) {
                case 'paragrafo':
                case 'h2':
                case 'h3':
                    $max = $tipo === 'paragrafo' ? self::MAX_TESTO : self::MAX_BREVE;
                    $pulito['it'] = self::testo($blocco['it'] ?? '', $max);
                    $pulito['en'] = self::testo($blocco['en'] ?? '', $max);
                    if ($pulito['it'] === '' && $pulito['en'] === '') {
                        continue 2;
                    }
                    break;

                case 'citazione':
                    $pulito['it'] = self::testo($blocco['it'] ?? '', self::MAX_TESTO);
                    $pulito['en'] = self::testo($blocco['en'] ?? '', self::MAX_TESTO);
                    $pulito['firma'] = self::testo($blocco['firma'] ?? '', self::MAX_BREVE);
                    if ($pulito['it'] === '' && $pulito['en'] === '') {
                        continue 2;
                    }
                    break;

                case 'immagine':
                    $media = (int) ($blocco['media'] ?? 0);
                    if ($media <= 0 || Db::value('SELECT id FROM media WHERE id = ?', [$media]) === null) {
                        continue 2;
                    }
                    $formato = (string) ($blocco['formato'] ?? 'orizzontale');
                    $pulito['media'] = $media;
                    $pulito['formato'] = in_array($formato, self::FORMATI, true) ? $formato : 'orizzontale';
                    $pulito['didascalia_it'] = self::testo($blocco['didascalia_it'] ?? '', self::MAX_BREVE);
                    $pulito['didascalia_en'] = self::testo($blocco['didascalia_en'] ?? '', self::MAX_BREVE);
                    break;

                case 'video':
                    $video = (int) ($blocco['video'] ?? 0);
                    if ($video <= 0 || Db::value('SELECT id FROM videos WHERE id = ?', [$video]) === null) {
                        continue 2;
                    }
                    $pulito['video'] = $video;
                    break;

                case 'domanda':
                    foreach (['domanda_it', 'domanda_en'] as $chiave) {
                        $pulito[$chiave] = self::testo($blocco[$chiave] ?? '', self::MAX_BREVE);
                    }
                    foreach (['risposta_it', 'risposta_en'] as $chiave) {
                        $pulito[$chiave] = self::testo($blocco[$chiave] ?? '', self::MAX_TESTO);
                    }
                    if ($pulito['domanda_it'] === '' && $pulito['domanda_en'] === '') {
                        continue 2;
                    }
                    break;
            }

            $puliti[] = $pulito;
        }

        return $puliti;
    }

    /**
     * Testo in linea: escape di tutto, poi solo **grassetto**, *corsivo*,
     * [link](url) e gli a capo. Link ammessi: http(s), mailto, interni.
     */
    public static function inline(string $testo): string
    {
        $s = htmlspecialchars($testo, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        $s = (string) preg_replace_callback(
            '/\[([^\]\n]+)\]\(([^)\s]+)\)/u',
            static function (array $m): string {
                $url = html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                if (!self::urlConsentito($url)) {
                    return $m[1];
                }
                $esterno = preg_match('#^https?://#i', $url) === 1;

                return '<a href="' . htmlspecialchars($url, ENT_QUOTES | ENT_HTML5, 'UTF-8') . '"'
                    . ($esterno ? ' rel="noopener"' : '') . '>' . $m[1] . '</a>';
            },
            $s,
        );
        $s = (string) preg_replace('/\*\*(.+?)\*\*/us', '<strong>$1</strong>', $s);
        $s = (string) preg_replace('/(?<![*\p{L}\p{N}])\*(?!\s)(.+?)(?<!\s)\*(?![*\p{L}\p{N}])/us', '<em>$1</em>', $s);

        return str_replace("\n", "<br>\n", $s);
    }

    public static function urlConsentito(string $url): bool
    {
        if (str_starts_with($url, '//')) {
            return false;
        }

        return preg_match('#^(https?://|mailto:|/|\#)#i', $url) === 1;
    }

    /** Ancora per un titolo, unica nell'articolo. */
    public static function ancora(string $testo, array &$usate): string
    {
        $base = strtolower(trim((string) preg_replace('/[^a-z0-9]+/i', '-', self::senzaAccenti($testo)), '-'));
        $base = $base !== '' ? substr($base, 0, 60) : 'sezione';
        $ancora = $base;
        $n = 2;
        while (in_array($ancora, $usate, true)) {
            $ancora = $base . '-' . $n++;
        }
        $usate[] = $ancora;

        return $ancora;
    }

    /** L'articolo è completo in questa lingua? (tutti i blocchi di testo tradotti) */
    public static function completo(array $blocchi, string $lingua): bool
    {
        if ($blocchi === []) {
            return false;
        }
        foreach ($blocchi as $b) {
            $ok = match ($b['tipo']) {
                'paragrafo', 'h2', 'h3', 'citazione' => ($b[$lingua] ?? '') !== '',
                'domanda' => ($b["domanda_{$lingua}"] ?? '') !== '' && ($b["risposta_{$lingua}"] ?? '') !== '',
                default => true,
            };
            if (!$ok) {
                return false;
            }
        }

        return true;
    }

    /** Minuti di lettura: 200 parole al minuto, almeno 1. */
    public static function minutiLettura(array $blocchi, string $lingua = 'it'): int
    {
        $testo = '';
        foreach ($blocchi as $b) {
            $testo .= ' ' . ($b[$lingua] ?? '') . ' ' . ($b["domanda_{$lingua}"] ?? '') . ' ' . ($b["risposta_{$lingua}"] ?? '');
        }
        $parole = count(preg_split('/\s+/u', trim($testo), -1, PREG_SPLIT_NO_EMPTY) ?: []);

        return max(1, (int) ceil($parole / 200));
    }

    /**
     * HTML pulito dell'articolo in una lingua (body_it / body_en): serve al
     * feed RSS e come copia di sicurezza. Vuoto se la lingua non è completa.
     */
    public static function html(array $blocchi, string $lingua): string
    {
        if (!self::completo($blocchi, $lingua)) {
            return '';
        }

        $usate = [];
        $parti = [];
        foreach ($blocchi as $b) {
            switch ($b['tipo']) {
                case 'paragrafo':
                    $parti[] = '<p>' . self::inline($b[$lingua]) . '</p>';
                    break;
                case 'h2':
                case 'h3':
                    $id = self::ancora($b[$lingua], $usate);
                    $parti[] = "<{$b['tipo']} id=\"{$id}\">" . htmlspecialchars($b[$lingua], ENT_QUOTES | ENT_HTML5, 'UTF-8') . "</{$b['tipo']}>";
                    break;
                case 'citazione':
                    $parti[] = '<blockquote><p>' . self::inline($b[$lingua]) . '</p></blockquote>';
                    break;
                case 'immagine':
                    $media = Db::one('SELECT path, width, height, alt_it, alt_en FROM media WHERE id = ?', [(int) $b['media']]);
                    if ($media !== null) {
                        $didascalia = (string) ($b["didascalia_{$lingua}"] ?? '');
                        $parti[] = '<figure><img src="/uploads/' . htmlspecialchars((string) $media['path'], ENT_QUOTES) . '"'
                            . ' alt="' . htmlspecialchars((string) ($media["alt_{$lingua}"] ?? ''), ENT_QUOTES) . '"'
                            . ' width="' . (int) $media['width'] . '" height="' . (int) $media['height'] . '">'
                            . ($didascalia !== '' ? '<figcaption>' . htmlspecialchars($didascalia, ENT_QUOTES) . '</figcaption>' : '')
                            . '</figure>';
                    }
                    break;
                case 'domanda':
                    $parti[] = '<h3>' . htmlspecialchars($b["domanda_{$lingua}"], ENT_QUOTES | ENT_HTML5, 'UTF-8') . '</h3>';
                    $parti[] = '<p>' . self::inline($b["risposta_{$lingua}"]) . '</p>';
                    break;
            }
        }

        return implode("\n", $parti);
    }

    /**
     * HTML qualsiasi → HTML con i soli tag e attributi consentiti (per i
     * corpi vecchi senza blocchi). I tag non ammessi lasciano il testo.
     */
    public static function pulisciHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="radice">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $radice = $doc->getElementById('radice');
        if ($radice === null) {
            return htmlspecialchars(strip_tags($html), ENT_QUOTES);
        }
        self::pulisciNodo($radice);

        $uscita = '';
        foreach ($radice->childNodes as $figlio) {
            $uscita .= $doc->saveHTML($figlio);
        }

        return trim($uscita);
    }

    private static function pulisciNodo(DOMNode $nodo): void
    {
        foreach (iterator_to_array($nodo->childNodes) as $figlio) {
            if ($figlio instanceof DOMElement) {
                $tag = strtolower($figlio->tagName);
                if (!array_key_exists($tag, self::TAG_CONSENTITI)) {
                    // Tag non ammesso: script e style spariscono, il resto lascia il testo.
                    if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed'], true)) {
                        $nodo->removeChild($figlio);
                        continue;
                    }
                    self::pulisciNodo($figlio);
                    while ($figlio->firstChild !== null) {
                        $nodo->insertBefore($figlio->firstChild, $figlio);
                    }
                    $nodo->removeChild($figlio);
                    continue;
                }
                foreach (iterator_to_array($figlio->attributes) as $attributo) {
                    $nome = strtolower($attributo->nodeName);
                    $valore = (string) $attributo->nodeValue;
                    $ammesso = in_array($nome, self::TAG_CONSENTITI[$tag], true)
                        && (!in_array($nome, ['href', 'src'], true) || self::urlConsentito($valore));
                    if (!$ammesso) {
                        $figlio->removeAttribute($attributo->nodeName);
                    }
                }
                self::pulisciNodo($figlio);
            } elseif ($figlio->nodeType === XML_COMMENT_NODE || $figlio->nodeType === XML_PI_NODE) {
                $nodo->removeChild($figlio);
            }
        }
    }

    private static function testo(mixed $valore, int $max): string
    {
        $s = is_string($valore) ? $valore : '';
        $s = str_replace(["\r\n", "\r"], "\n", $s);
        $s = (string) preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $s);
        $s = trim($s);

        return mb_substr($s, 0, $max);
    }

    private static function senzaAccenti(string $s): string
    {
        $mappa = ['à' => 'a', 'á' => 'a', 'è' => 'e', 'é' => 'e', 'ì' => 'i', 'í' => 'i', 'ò' => 'o', 'ó' => 'o',
            'ù' => 'u', 'ú' => 'u', 'À' => 'A', 'È' => 'E', 'É' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U', '’' => '', "'" => ''];

        return strtr($s, $mappa);
    }
}
