<?php

declare(strict_types=1);

namespace Gaia\Admin;

/**
 * Trasforma i contenuti del database negli stessi JSON che Astro legge da
 * site/src/content/.
 *
 * Le forme prodotte qui devono restare allineate ai tipi in
 * site/src/lib/types.ts: ogni campo di testo è { "it": …, "en": … } e un
 * valore vuoto diventa stringa vuota, non null, perché è il loader del
 * sito a decidere che una traduzione mancante esclude la pagina EN.
 *
 * Chiavi del risultato = nomi dei file: 'settings' → settings.json.
 */
final class Exporter
{
    /** @return array<string, mixed> */
    public static function all(): array
    {
        return [
            'settings' => self::settings(),
            'pages' => self::pages(),
            'services' => self::services(),
            'events' => self::events(),
            'reviews' => self::reviews(),
            'posts' => self::posts(),
            'faq' => self::faqs(),
            'destination-wedding' => self::destinationWedding(),
            'instagram' => self::instagram(),
            'luoghi' => self::luoghi(),
            'location' => self::locations(),
            'highlights' => self::highlights(),
            'partner' => self::partners(),
            'storie' => self::stories(),
            'hero' => self::hero(),
        ];
    }

    // -----------------------------------------------------------------
    // Fase 2: luoghi, location, highlights, partner, storie, hero
    // -----------------------------------------------------------------

    /**
     * Albero dei luoghi: regioni con le zone annidate. Gli slug sono
     * unici globalmente, quindi il sito può costruire /luoghi/[regione]/
     * e /luoghi/[regione]/[zona]/ senza ambiguità.
     *
     * @return list<array<string, mixed>>
     */
    public static function luoghi(): array
    {
        $rows = Db::all('SELECT * FROM luoghi ORDER BY sort_order, id');

        $galleries = [];
        foreach (
            Db::all(
                'SELECT li.luogo_id, m.*
                 FROM luogo_images li
                 JOIN media m ON m.id = li.media_id
                 ORDER BY li.luogo_id, li.sort_order, li.id',
            ) as $row
        ) {
            $galleries[(int) $row['luogo_id']][] = self::image($row);
        }

        $shape = static fn (array $row): array => [
            'id' => (int) $row['id'],
            'tipo' => (string) $row['tipo'],
            'nome' => self::text($row['nome_it'], $row['nome_en']),
            'slug' => self::text($row['slug_it'], $row['slug_en']),
            'intro' => self::text($row['intro_it'], $row['intro_en']),
            'testo' => self::text($row['testo_it'], $row['testo_en']),
            'cover' => self::image(self::mediaById((int) ($row['cover_media_id'] ?? 0))),
            'gallery' => $galleries[(int) $row['id']] ?? [],
            'metaTitle' => self::text($row['meta_title_it'], $row['meta_title_en']),
            'metaDescription' => self::text($row['meta_description_it'], $row['meta_description_en']),
            'active' => (bool) $row['active'],
            'order' => (int) $row['sort_order'],
        ];

        $result = [];
        foreach ($rows as $row) {
            if ($row['parent_id'] !== null) {
                continue;
            }

            $regione = $shape($row);
            $regione['zone'] = [];
            foreach ($rows as $figlio) {
                if ((int) ($figlio['parent_id'] ?? 0) === (int) $row['id']) {
                    $regione['zone'][] = $shape($figlio);
                }
            }

            $result[] = $regione;
        }

        return $result;
    }

    /** @return list<array<string, mixed>> */
    public static function locations(): array
    {
        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'nome' => (string) $row['nome'],
            'tipo' => (string) $row['tipo'],
            'luogoId' => (int) $row['luogo_id'],
            'descrizione' => self::text($row['descrizione_it'], $row['descrizione_en']),
            'website' => $row['website'],
            'ownPage' => (bool) $row['own_page'],
            'active' => (bool) $row['active'],
            'order' => (int) $row['sort_order'],
        ], Db::all('SELECT * FROM locations ORDER BY luogo_id, sort_order, nome'));
    }

    /**
     * L'alt vive sull'immagine (media.alt_it/alt_en), dentro image().
     * Nessuna didascalia con credito: i crediti stanno nei partner.
     *
     * @return list<array<string, mixed>>
     */
    public static function highlights(): array
    {
        $sections = [];
        foreach (
            Db::all('SELECT highlight_id, section, sort_order FROM highlight_sections ORDER BY section, sort_order') as $row
        ) {
            $sections[(int) $row['highlight_id']][] = [
                'section' => (string) $row['section'],
                'order' => (int) $row['sort_order'],
            ];
        }

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'image' => self::image(self::mediaById((int) $row['media_id'])),
            'luogoId' => $row['luogo_id'] !== null ? (int) $row['luogo_id'] : null,
            'sections' => $sections[(int) $row['id']] ?? [],
            'active' => (bool) $row['active'],
        ], Db::all('SELECT * FROM highlights ORDER BY id'));
    }

    /** @return list<array<string, mixed>> */
    public static function partners(): array
    {
        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'category' => (string) $row['category'],
            'logo' => self::image(self::mediaById((int) ($row['logo_media_id'] ?? 0))),
            'description' => self::text($row['description_it'], $row['description_en']),
            'website' => $row['website'],
            'active' => (bool) $row['active'],
            'order' => (int) $row['sort_order'],
        ], Db::all('SELECT * FROM partners ORDER BY sort_order, name'));
    }

    /** @return list<array<string, mixed>> */
    public static function stories(): array
    {
        $galleries = [];
        foreach (
            Db::all(
                'SELECT si.story_id, m.*
                 FROM story_images si
                 JOIN media m ON m.id = si.media_id
                 ORDER BY si.story_id, si.sort_order, si.id',
            ) as $row
        ) {
            $galleries[(int) $row['story_id']][] = self::image($row);
        }

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'slug' => self::text($row['slug_it'], $row['slug_en']),
            'title' => self::text($row['title_it'], $row['title_en']),
            'couple' => $row['couple'],
            'locationName' => $row['location_name'],
            'luogoId' => $row['luogo_id'] !== null ? (int) $row['luogo_id'] : null,
            'date' => self::date($row['story_date']),
            'cover' => self::image(self::mediaById((int) ($row['cover_media_id'] ?? 0))),
            'gallery' => $galleries[(int) $row['id']] ?? [],
            'body' => self::text($row['body_it'], $row['body_en']),
            'photographerId' => $row['photographer_partner_id'] !== null
                ? (int) $row['photographer_partner_id']
                : null,
            'metaTitle' => self::text($row['meta_title_it'], $row['meta_title_en']),
            'metaDescription' => self::text($row['meta_description_it'], $row['meta_description_en']),
            'active' => (bool) $row['active'],
            'order' => (int) $row['sort_order'],
        ], Db::all('SELECT * FROM stories ORDER BY sort_order, story_date DESC, id'));
    }

    /**
     * Hero per pagina, indicizzato per page_key. Il poster è l'elemento
     * LCP; il video non entra mai nel caricamento iniziale.
     *
     * @return array<string, mixed>
     */
    public static function hero(): array
    {
        $images = [];
        foreach (
            Db::all(
                'SELECT phi.hero_id, m.*
                 FROM page_hero_images phi
                 JOIN media m ON m.id = phi.media_id
                 ORDER BY phi.hero_id, phi.sort_order, phi.id',
            ) as $row
        ) {
            $images[(int) $row['hero_id']][] = self::image($row);
        }

        $result = [];
        foreach (Db::all('SELECT * FROM page_hero') as $row) {
            $video = null;
            if (!empty($row['video_path'])) {
                $video = [
                    'mp4' => (string) $row['video_path'],
                    'webm' => $row['video_webm_path'],
                    'poster' => self::image(self::mediaById((int) ($row['poster_media_id'] ?? 0))),
                ];
            }

            $result[(string) $row['page_key']] = [
                'title' => self::text($row['title_it'], $row['title_en']),
                'subtitle' => self::text($row['subtitle_it'], $row['subtitle_en']),
                'cta' => [
                    'label' => self::text($row['cta_label_it'], $row['cta_label_en']),
                    'route' => $row['cta_route'],
                ],
                'video' => $video,
                'videoUrl' => $row['video_url'],
                'poster' => self::image(self::mediaById((int) ($row['poster_media_id'] ?? 0))),
                'images' => $images[(int) ($row['id'] ?? 0)] ?? [],
            ];
        }

        return $result;
    }

    // -----------------------------------------------------------------
    // Impostazioni
    // -----------------------------------------------------------------

    /** @return array<string, mixed> */
    public static function settings(): array
    {
        $rows = Db::all('SELECT setting_key, field_type, value_it, value_en FROM settings');

        // Struttura di partenza: garantisce che le chiavi attese esistano
        // sempre, anche con il database appena creato.
        $data = [
            'siteName' => 'Gaia Events',
            'tagline' => self::emptyText(),
            'metaTitle' => self::emptyText(),
            'metaDescription' => self::emptyText(),
            'ogImage' => null,
            'hero' => [
                'eyebrow' => self::emptyText(),
                'title' => self::emptyText(),
                'subtitle' => self::emptyText(),
                'image' => null,
                'ctaLabel' => self::emptyText(),
            ],
            'sections' => [],
            'about' => ['title' => self::emptyText(), 'text' => self::emptyText(), 'image' => null],
            'contact' => [
                'title' => self::emptyText(),
                'text' => self::emptyText(),
                'address' => self::emptyText(),
            ],
        ];

        foreach ($rows as $row) {
            $key = (string) $row['setting_key'];

            // I campi immagine contengono l'id di un record media.
            $value = $row['field_type'] === 'media'
                ? self::image(self::mediaById((int) $row['value_it']))
                : self::text($row['value_it'], $row['value_en']);

            // 'sections.about.title' → $data['sections']['about']['title']
            self::setNested($data, explode('.', $key), $value);
        }

        return $data;
    }

    // -----------------------------------------------------------------
    // Pagine
    // -----------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public static function pages(): array
    {
        $rows = Db::all('SELECT * FROM pages ORDER BY page_key');

        return array_map(static fn (array $row): array => [
            'key' => $row['page_key'],
            'title' => self::text($row['title_it'], $row['title_en']),
            'intro' => self::text($row['intro_it'], $row['intro_en']),
            'body' => self::text($row['body_it'], $row['body_en']),
            'metaTitle' => self::text($row['meta_title_it'], $row['meta_title_en']),
            'metaDescription' => self::text($row['meta_description_it'], $row['meta_description_en']),
            'hero' => self::image(self::mediaById((int) ($row['hero_media_id'] ?? 0))),
            'ogImage' => self::image(self::mediaById((int) ($row['og_media_id'] ?? 0))),
            'blocks' => self::blocks((int) $row['id']),
            'updatedAt' => self::date($row['updated_at']),
        ], $rows);
    }

    // -----------------------------------------------------------------
    // Servizi
    // -----------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public static function services(): array
    {
        $rows = Db::all('SELECT * FROM services ORDER BY sort_order, id');

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'title' => self::text($row['title_it'], $row['title_en']),
            'subtitle' => self::text($row['subtitle_it'], $row['subtitle_en']),
            'description' => self::text($row['description_it'], $row['description_en']),
            'features' => [
                'it' => self::jsonList($row['features_it']),
                'en' => self::jsonList($row['features_en']),
            ],
            'icon' => $row['icon'],
            'image' => self::image(self::mediaById((int) ($row['media_id'] ?? 0))),
            'metaTitle' => self::text($row['meta_title_it'], $row['meta_title_en']),
            'metaDescription' => self::text($row['meta_description_it'], $row['meta_description_en']),
            'active' => (bool) $row['active'],
            'order' => (int) $row['sort_order'],
        ], $rows);
    }

    // -----------------------------------------------------------------
    // Eventi
    // -----------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public static function events(): array
    {
        $rows = Db::all('SELECT * FROM events ORDER BY sort_order, event_date DESC, id');

        $galleries = [];
        foreach (
            Db::all(
                'SELECT ei.event_id, m.*
                 FROM event_images ei
                 JOIN media m ON m.id = ei.media_id
                 ORDER BY ei.event_id, ei.sort_order, ei.id',
            ) as $row
        ) {
            $galleries[(int) $row['event_id']][] = self::image($row);
        }

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'slug' => $row['slug'],
            'macroCategory' => $row['macro_category'],
            'title' => self::text($row['title_it'], $row['title_en']),
            'location' => self::text($row['location_it'], $row['location_en']),
            'date' => self::date($row['event_date']) ?? '',
            'category' => self::text($row['category_it'], $row['category_en']),
            'excerpt' => self::text($row['excerpt_it'], $row['excerpt_en']),
            'description' => self::text($row['description_it'], $row['description_en']),
            'cover' => self::image(self::mediaById((int) ($row['cover_media_id'] ?? 0))),
            'hero' => self::image(self::mediaById((int) ($row['hero_media_id'] ?? 0))),
            'video' => $row['video_url'],
            'gallery' => $galleries[(int) $row['id']] ?? [],
            'metaTitle' => self::text($row['meta_title_it'], $row['meta_title_en']),
            'metaDescription' => self::text($row['meta_description_it'], $row['meta_description_en']),
            'featured' => (bool) $row['featured'],
            'active' => (bool) $row['active'],
            'order' => (int) $row['sort_order'],
        ], $rows);
    }

    // -----------------------------------------------------------------
    // Recensioni
    // -----------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public static function reviews(): array
    {
        $rows = Db::all('SELECT * FROM reviews ORDER BY sort_order, review_date DESC, id');

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'author' => $row['author'],
            'eventName' => self::text($row['event_name_it'], $row['event_name_en']),
            'date' => self::date($row['review_date']) ?? '',
            'text' => self::text($row['text_it'], $row['text_en']),
            'rating' => (int) $row['rating'],
            'image' => self::image(self::mediaById((int) ($row['media_id'] ?? 0))),
            'featured' => (bool) $row['featured'],
            'active' => (bool) $row['active'],
            'order' => (int) $row['sort_order'],
        ], $rows);
    }

    // -----------------------------------------------------------------
    // Blog
    // -----------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public static function posts(): array
    {
        $rows = Db::all('SELECT * FROM posts ORDER BY post_date DESC, id DESC');

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'slug' => self::text($row['slug_it'], $row['slug_en']),
            'title' => self::text($row['title_it'], $row['title_en']),
            'excerpt' => self::text($row['excerpt_it'], $row['excerpt_en']),
            'category' => self::text($row['category_it'], $row['category_en']),
            'date' => self::date($row['post_date']) ?? '',
            'readingTime' => (int) $row['reading_time'],
            'cover' => self::image(self::mediaById((int) ($row['cover_media_id'] ?? 0))),
            'body' => self::text($row['body_it'], $row['body_en']),
            'metaTitle' => self::text($row['meta_title_it'], $row['meta_title_en']),
            'metaDescription' => self::text($row['meta_description_it'], $row['meta_description_en']),
            'active' => (bool) $row['active'],
        ], $rows);
    }

    // -----------------------------------------------------------------
    // FAQ
    // -----------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public static function faqs(): array
    {
        $rows = Db::all('SELECT * FROM faqs ORDER BY sort_order, id');

        return array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'question' => self::text($row['question_it'], $row['question_en']),
            'answer' => self::text($row['answer_it'], $row['answer_en']),
            'group' => $row['group_name'],
            'active' => (bool) $row['active'],
            'order' => (int) $row['sort_order'],
        ], $rows);
    }

    // -----------------------------------------------------------------
    // Destination wedding: pagina + blocchi
    // -----------------------------------------------------------------

    /** @return array<string, mixed> */
    public static function destinationWedding(): array
    {
        $page = Db::one('SELECT * FROM pages WHERE page_key = ?', ['destinationWedding']);

        if ($page === null) {
            return [
                'title' => self::emptyText(),
                'intro' => self::emptyText(),
                'metaTitle' => self::emptyText(),
                'metaDescription' => self::emptyText(),
                'hero' => null,
                'ogImage' => null,
                'blocks' => [],
            ];
        }

        $blocks = self::blocks((int) $page['id']);

        return [
            'title' => self::text($page['title_it'], $page['title_en']),
            'intro' => self::text($page['intro_it'], $page['intro_en']),
            'metaTitle' => self::text($page['meta_title_it'], $page['meta_title_en']),
            'metaDescription' => self::text($page['meta_description_it'], $page['meta_description_en']),
            'hero' => self::image(self::mediaById((int) ($page['hero_media_id'] ?? 0))),
            'ogImage' => self::image(self::mediaById((int) ($page['og_media_id'] ?? 0))),
            'blocks' => $blocks,
        ];
    }

    // -----------------------------------------------------------------
    // Blocchi di pagina
    // -----------------------------------------------------------------

    /**
     * Blocchi ripetibili di una pagina: destination wedding, ma anche
     * timeline e valori di «chi sono».
     *
     * @return list<array<string, mixed>>
     */
    private static function blocks(int $pageId): array
    {
        $rows = Db::all(
            'SELECT * FROM page_sections WHERE page_id = ? ORDER BY sort_order, id',
            [$pageId],
        );

        return array_map(static function (array $row): array {
            $itemsIt = self::jsonItems($row['items_it']);
            $itemsEn = self::jsonItems($row['items_en']);

            // Le voci sono allineate per posizione: la riga n di items_it
            // e quella di items_en descrivono la stessa voce.
            $count = max(count($itemsIt), count($itemsEn));
            $items = [];

            for ($index = 0; $index < $count; $index++) {
                $item = [
                    'title' => self::text(
                        $itemsIt[$index]['title'] ?? null,
                        $itemsEn[$index]['title'] ?? null,
                    ),
                    'text' => self::text(
                        $itemsIt[$index]['text'] ?? null,
                        $itemsEn[$index]['text'] ?? null,
                    ),
                ];

                // Etichetta breve non tradotta, es. l'anno nella timeline.
                $label = $itemsIt[$index]['label'] ?? null;
                if (is_string($label) && $label !== '') {
                    $item['label'] = $label;
                }

                $items[] = $item;
            }

            return [
                'kind' => $row['kind'],
                'title' => self::text($row['title_it'], $row['title_en']),
                'text' => self::text($row['text_it'], $row['text_en']),
                'items' => $items,
                'image' => self::image(self::mediaById((int) ($row['media_id'] ?? 0))),
                'order' => (int) $row['sort_order'],
            ];
        }, $rows);
    }

    // -----------------------------------------------------------------
    // Instagram
    // -----------------------------------------------------------------

    /** @return list<array<string, mixed>> */
    public static function instagram(): array
    {
        $rows = Db::all(
            'SELECT * FROM instagram_items WHERE active = 1 ORDER BY sort_order, posted_at DESC',
        );

        return array_map(static fn (array $row): array => [
            'id' => (string) $row['ig_id'],
            'type' => $row['media_type'],
            'image' => self::image(self::mediaById((int) ($row['media_id'] ?? 0))),
            'caption' => self::text($row['caption_it'], $row['caption_en']),
            'permalink' => $row['permalink'],
            'postedAt' => self::datetime($row['posted_at']) ?? '',
        ], $rows);
    }

    // -----------------------------------------------------------------
    // Utilità
    // -----------------------------------------------------------------

    /** @return array{it: string, en: string} */
    private static function text(mixed $it, mixed $en): array
    {
        return [
            'it' => trim((string) ($it ?? '')),
            'en' => trim((string) ($en ?? '')),
        ];
    }

    /** @return array{it: string, en: string} */
    private static function emptyText(): array
    {
        return ['it' => '', 'en' => ''];
    }

    /** @var array<int, array<string, mixed>|null> */
    private static array $mediaCache = [];

    /** @return array<string, mixed>|null */
    private static function mediaById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        return self::$mediaCache[$id] ??= Db::one('SELECT * FROM media WHERE id = ?', [$id]);
    }

    /**
     * @param array<string, mixed>|null $row
     * @return array<string, mixed>|null
     */
    private static function image(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }

        return [
            'src' => (string) $row['path'],
            'width' => (int) $row['width'],
            'height' => (int) $row['height'],
            // Limite di visualizzazione: il sito non deve mai mostrare
            // l'immagine piu' larga di cosi', lightbox compresa.
            'maxWidth' => (int) ($row['max_css_width'] ?? intdiv((int) $row['width'], 2)),
            'usage' => (string) ($row['usage_tier'] ?? 'thumbnail'),
            'alt' => self::text($row['alt_it'], $row['alt_en']),
            'caption' => self::text($row['caption_it'], $row['caption_en']),
            'formats' => [
                'webp' => (bool) $row['has_webp'],
                'avif' => (bool) $row['has_avif'],
            ],
            'widths' => self::jsonList($row['variant_widths'], true),
            'focal' => [
                'x' => (float) $row['focal_x'],
                'y' => (float) $row['focal_y'],
            ],
        ];
    }

    /** @return list<mixed> */
    private static function jsonList(mixed $raw, bool $asInt = false): array
    {
        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $values = array_values($decoded);

        return $asInt ? array_map(static fn (mixed $v): int => (int) $v, $values) : $values;
    }

    /** @return list<array<string, mixed>> */
    private static function jsonItems(mixed $raw): array
    {
        $items = self::jsonList($raw);

        return array_values(array_filter($items, static fn (mixed $i): bool => is_array($i)));
    }

    private static function date(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return substr($value, 0, 10);
    }

    private static function datetime(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return str_replace(' ', 'T', substr($value, 0, 19));
    }

    /**
     * Scrive un valore in una struttura annidata seguendo le parti della chiave.
     *
     * @param array<string, mixed> $target
     * @param list<string> $path
     */
    private static function setNested(array &$target, array $path, mixed $value): void
    {
        $cursor = &$target;

        foreach ($path as $index => $segment) {
            if ($index === count($path) - 1) {
                $cursor[$segment] = $value;
                break;
            }

            if (!isset($cursor[$segment]) || !is_array($cursor[$segment])) {
                $cursor[$segment] = [];
            }

            $cursor = &$cursor[$segment];
        }
    }
}
