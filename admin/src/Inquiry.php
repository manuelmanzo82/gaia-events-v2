<?php

declare(strict_types=1);

namespace Gaia\Admin;

/**
 * Ricezione delle richieste dal sito statico: preventivatore e contatti.
 *
 * Sostituisce Web3Forms. I dati finiscono nella tabella `inquiries`, poi
 * parte la notifica a Gaia (Mailer, SMTP da config.php) con Reply-To
 * l'email di chi scrive. Se la mail non parte la richiesta resta salvata:
 * l'esito finisce in mail_sent_at / mail_error ed è visibile nel back
 * office.
 *
 * Antispam invisibile, niente captcha: campo trappola `website`, tempo
 * minimo di compilazione (il preventivatore manda i secondi trascorsi) e
 * un limite di richieste per IP. Ai bot si risponde «ok», così non
 * capiscono di essere stati scartati.
 */
final class Inquiry
{
    private const MAX_PER_HOUR = ['quote' => 3, 'contact' => 6];

    /** Sotto questi secondi il preventivo è di un bot. */
    private const MIN_SECONDS_QUOTE = 8;

    private const KINDS = ['matrimonio', 'unione_civile', 'festa', 'aziendale'];
    private const KIND_LABELS = [
        'matrimonio' => 'matrimonio',
        'unione_civile' => 'unione civile',
        'festa' => 'festa privata',
        'aziendale' => 'evento aziendale',
    ];
    private const GUEST_RANGES = ['fino-50', '50-100', '100-150', '150-250', 'oltre-250'];
    private const MONTHS = ['gennaio', 'febbraio', 'marzo', 'aprile', 'maggio', 'giugno', 'luglio',
        'agosto', 'settembre', 'ottobre', 'novembre', 'dicembre'];

    /**
     * @param 'quote'|'contact' $type
     * @return array{ok: bool, status: int, message: string}
     */
    public static function receive(string $type): array
    {
        $input = self::input();

        // Campo trappola: un essere umano non lo vede mai.
        if (Validator::isBot($input)) {
            return self::ok();
        }

        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

        return $type === 'quote' ? self::receiveQuote($input, $ip) : self::receiveContact($input, $ip);
    }

    // -----------------------------------------------------------------
    // Preventivatore
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $in */
    private static function receiveQuote(array $in, string $ip): array
    {
        $lang = ($in['lang'] ?? 'it') === 'en' ? 'en' : 'it';

        // Compilato in pochi secondi: un bot.
        if ((float) ($in['elapsed'] ?? 0) < self::MIN_SECONDS_QUOTE) {
            return self::ok();
        }

        $errore = fn (string $it, string $en): array => [
            'ok' => false,
            'status' => 422,
            'message' => $lang === 'en' ? $en : $it,
        ];

        $kind = (string) ($in['kind'] ?? '');
        if (!in_array($kind, self::KINDS, true)) {
            return $errore('Scegliete il tipo di evento.', 'Please choose the type of event.');
        }

        $name = self::str($in['name'] ?? '', 160);
        $partner = self::str($in['partner'] ?? '', 160);
        $company = self::str($in['company'] ?? '', 160);
        $email = self::str($in['email'] ?? '', 190);
        $phone = self::str($in['phone'] ?? '', 60);
        $contactPref = in_array($in['contactPref'] ?? '', ['email', 'whatsapp', 'telefono'], true)
            ? (string) $in['contactPref'] : 'email';

        if ($name === '') {
            return $errore('Manca il nome.', 'Your name is missing.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return $errore('Indirizzo email non valido.', 'Invalid email address.');
        }
        if ($contactPref !== 'email' && $phone === '') {
            return $errore('Serve un numero di telefono per essere ricontattati così.', 'A phone number is needed to be contacted this way.');
        }
        if (($in['privacy'] ?? false) !== true) {
            return $errore('Serve il consenso alla privacy.', 'Privacy consent is required.');
        }

        // Quando
        $dateMode = in_array($in['dateMode'] ?? '', ['giorno', 'mese', 'da_definire'], true)
            ? (string) $in['dateMode'] : 'da_definire';
        $eventDate = null;
        if ($dateMode === 'giorno' && self::isDate((string) ($in['date'] ?? ''))) {
            $eventDate = (string) $in['date'];
        } elseif ($dateMode === 'mese' && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) ($in['month'] ?? '')) === 1) {
            $eventDate = $in['month'] . '-01';
        } else {
            $dateMode = 'da_definire';
        }

        // Dove
        $zoneMode = in_array($in['zoneMode'] ?? '', ['zone', 'libero', 'consigliateci', 'location'], true)
            ? (string) $in['zoneMode'] : null;
        $zones = self::strList($in['zones'] ?? [], 12, 120);
        if ($zoneMode === 'libero' && self::str($in['zoneText'] ?? '', 120) !== '') {
            $zones = [self::str($in['zoneText'], 120)];
        }
        $venue = self::str($in['venue'] ?? '', 190);

        // Ospiti
        $guestsRange = in_array($in['guestsRange'] ?? '', self::GUEST_RANGES, true) ? (string) $in['guestsRange'] : null;
        $guests = filter_var($in['guests'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5000]]);

        // Dal giro 9 il modulo manda un riepilogo, non più una lettera: la
        // colonna `letter` resta per le richieste vecchie.
        $letter = self::str($in['letter'] ?? '', 8000, false);

        if (self::tooMany($ip, 'quote')) {
            return [
                'ok' => false,
                'status' => 429,
                'message' => $lang === 'en'
                    ? 'Too many requests from this address. Please try again later.'
                    : 'Troppe richieste dallo stesso indirizzo. Riprova più tardi.',
            ];
        }

        // Il riepilogo leggibile (sempre in italiano, per Gaia) arriva dal
        // modulo: etichetta e valore, ripuliti.
        $summary = [];
        foreach (is_array($in['summary'] ?? null) ? $in['summary'] : [] as $riga) {
            if (!is_array($riga)) {
                continue;
            }
            $label = self::str($riga['label'] ?? '', 80);
            $value = self::str($riga['value'] ?? '', 600, false);
            if ($label !== '' && $value !== '') {
                $summary[] = ['label' => $label, 'value' => $value];
            }
        }

        $payload = $in;
        unset($payload['website'], $payload['letter']);

        $id = Db::insert('inquiries', [
            'type' => 'quote',
            'lang' => $lang,
            'event_kind' => $kind,
            'name' => $name,
            'partner_name' => $partner !== '' ? $partner : null,
            'company' => $company !== '' ? $company : null,
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'contact_pref' => $contactPref,
            'event_date' => $eventDate,
            'date_precision' => $dateMode,
            'event_type' => self::KIND_LABELS[$kind],
            'zone_mode' => $zoneMode,
            'zones' => $zones === [] ? null : json_encode($zones, JSON_UNESCAPED_UNICODE),
            'venue_name' => $venue !== '' ? $venue : null,
            'from_abroad' => !empty($in['abroad']) ? 1 : 0,
            'guests' => $guests === false ? null : $guests,
            'guests_range' => $guestsRange,
            'services' => ($s = self::strList($in['services'] ?? [], 12, 80)) === [] ? null : json_encode($s, JSON_UNESCAPED_UNICODE),
            'budget_range' => self::str($in['budget'] ?? '', 30) ?: null,
            'source' => self::str($in['source'] ?? '', 60) ?: null,
            'message' => self::str($in['message'] ?? '', 5000, false) ?: null,
            'letter' => $letter !== '' ? $letter : null,
            'privacy_at' => date('Y-m-d H:i:s'),
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ip' => $ip,
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        $chi = $kind === 'aziendale' && $company !== ''
            ? $name . ' (' . $company . ')'
            : ($partner !== '' ? $name . ' e ' . $partner : $name);
        $quando = match ($dateMode) {
            'giorno' => ', ' . self::dataItaliana((string) $eventDate),
            'mese' => ', ' . self::MONTHS[(int) substr((string) $eventDate, 5, 2) - 1] . ' ' . substr((string) $eventDate, 0, 4),
            default => '',
        };

        $plurale = $kind !== 'festa';
        $canale = match ($contactPref) {
            'whatsapp' => 'su WhatsApp al ' . $phone,
            'telefono' => 'con una telefonata al ' . $phone,
            default => 'via email a ' . $email,
        };
        // In evidenza in cima: come vogliono essere ricontattati.
        $preferenza = ($plurale ? 'Preferiscono essere contattati ' : 'Contatto preferito: ') . $canale . '.';
        $link = self::backOfficeUrl($id);

        $esito = Mailer::send([
            'to' => (string) Config::get('notify_email', ''),
            'subject' => 'Nuova richiesta: ' . $chi . ' — ' . self::KIND_LABELS[$kind] . $quando,
            'text' => implode("\n", [
                $preferenza,
                '',
                self::tabellaTesto($summary),
                '',
                'Rispondendo a questa mail scrivi direttamente a ' . $email . '.',
                'Nel back office: ' . $link,
            ]),
            'html' => self::paginaHtml($preferenza, $summary, [
                'Rispondendo a questa mail scrivi direttamente a ' . $email . '.',
                '<a href="' . htmlspecialchars($link, ENT_QUOTES) . '">Apri la richiesta nel back office</a>',
            ]),
            'reply_to' => $email,
            'reply_to_name' => $chi,
        ]);
        self::recordMail($id, $esito);

        // Copia del riepilogo alla coppia: dietro un'impostazione, spenta.
        if ((string) Db::value("SELECT value_it FROM settings WHERE setting_key = 'quote.sendCopy'") === '1') {
            $copia = Mailer::send([
                'to' => $email,
                'subject' => $lang === 'en' ? 'Your request to Gaia Events' : 'La vostra richiesta a Gaia Events',
                'text' => ($lang === 'en'
                    ? "Thank you! Here is a summary of your request. Gaia will reply soon.\n\n"
                    : "Grazie! Ecco il riepilogo della vostra richiesta. Gaia vi risponderà presto.\n\n") . self::tabellaTesto($summary),
                'reply_to' => (string) Config::get('notify_email', ''),
                'reply_to_name' => 'Gaia Events',
            ]);
            if (!$copia['ok']) {
                error_log('[quote] copia alla coppia non inviata per #' . $id . ': ' . $copia['error']);
            }
        }

        return ['ok' => true, 'status' => 201, 'message' => 'ok'];
    }

    // -----------------------------------------------------------------
    // Contatti (modulo semplice)
    // -----------------------------------------------------------------

    /** @param array<string, mixed> $input */
    private static function receiveContact(array $input, string $ip): array
    {
        $rules = [
            'name' => 'required|max:160',
            'email' => 'required|email|max:190',
            'phone' => 'max:60',
            'message' => 'required|max:5000',
            'lang' => 'in:it,en',
        ];

        $validator = new Validator($input, $rules);

        if ($validator->fails()) {
            return ['ok' => false, 'status' => 422, 'message' => (string) $validator->firstError()];
        }

        if (self::tooMany($ip, 'contact')) {
            return ['ok' => false, 'status' => 429, 'message' => 'Troppe richieste dallo stesso indirizzo. Riprova più tardi.'];
        }

        $data = $validator->validated();

        $id = Db::insert('inquiries', [
            'type' => 'contact',
            'lang' => $data['lang'] ?? 'it',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'ip' => $ip,
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        $esito = Mailer::send([
            'to' => (string) Config::get('notify_email', ''),
            'subject' => 'Messaggio dal sito: ' . $data['name'],
            'text' => implode("\n", [
                (string) ($data['message'] ?? ''),
                '',
                '— ' . $data['name'] . ' · ' . $data['email'] . (isset($data['phone']) ? ' · ' . $data['phone'] : ''),
                '',
                'Nel back office: ' . self::backOfficeUrl($id),
            ]),
            'reply_to' => (string) $data['email'],
            'reply_to_name' => (string) $data['name'],
        ]);
        self::recordMail($id, $esito);

        return ['ok' => true, 'status' => 201, 'message' => 'ok'];
    }

    // -----------------------------------------------------------------
    // Utilità
    // -----------------------------------------------------------------

    /**
     * Il riepilogo come tabella di testo: etichette allineate, i valori su
     * più righe rientrano sotto la colonna dei valori.
     *
     * @param list<array{label: string, value: string}> $righe
     */
    private static function tabellaTesto(array $righe): string
    {
        $larga = 0;
        foreach ($righe as $r) {
            $larga = max($larga, mb_strlen($r['label']));
        }
        $out = [];
        foreach ($righe as $r) {
            $valori = explode("\n", $r['value']);
            $out[] = $r['label'] . str_repeat(' ', $larga - mb_strlen($r['label']) + 3) . array_shift($valori);
            foreach ($valori as $v) {
                $out[] = str_repeat(' ', $larga + 3) . $v;
            }
        }

        return implode("\n", $out);
    }

    /**
     * La stessa tabella in HTML, per i client di posta che la mostrano.
     *
     * @param list<array{label: string, value: string}> $righe
     * @param list<string> $coda  righe finali (HTML già sicuro)
     */
    private static function paginaHtml(string $preferenza, array $righe, array $coda): string
    {
        $e = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
        $html = '<div style="font-family:Georgia,serif;color:#262019;max-width:640px">';
        $html .= '<p style="font-size:17px;margin:0 0 18px;padding:12px 14px;background:#f4efe8;border-left:3px solid #722f37">'
            . '<strong>' . $e($preferenza) . '</strong></p>';
        $html .= '<table cellpadding="0" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:15px">';
        foreach ($righe as $r) {
            $html .= '<tr><td style="padding:9px 16px 9px 0;border-bottom:1px solid #e8e2d9;vertical-align:top;'
                . 'font-family:Arial,sans-serif;font-size:11px;letter-spacing:.1em;text-transform:uppercase;color:#6f675d;white-space:nowrap">'
                . $e($r['label']) . '</td><td style="padding:9px 0;border-bottom:1px solid #e8e2d9;vertical-align:top;line-height:1.5">'
                . nl2br($e($r['value'])) . '</td></tr>';
        }
        $html .= '</table>';
        foreach ($coda as $riga) {
            $html .= '<p style="font-family:Arial,sans-serif;font-size:13px;color:#6f675d;margin:14px 0 0">' . $riga . '</p>';
        }

        return $html . '</div>';
    }

    /** @return array{ok: bool, status: int, message: string} */
    private static function ok(): array
    {
        return ['ok' => true, 'status' => 200, 'message' => 'ok'];
    }

    /** Accetta sia JSON sia form classico. */
    private static function input(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) && strlen($raw) <= 100000 ? json_decode($raw, true) : null;

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private static function tooMany(string $ip, string $type): bool
    {
        if ($ip === '') {
            return false;
        }

        $count = (int) Db::value(
            'SELECT COUNT(*) FROM inquiries WHERE ip = ? AND type = ? AND created_at > ?',
            [$ip, $type, date('Y-m-d H:i:s', time() - 3600)],
        );

        return $count >= self::MAX_PER_HOUR[$type];
    }

    /** @param array{ok: bool, error: ?string} $esito */
    private static function recordMail(int $id, array $esito): void
    {
        Db::update('inquiries', $id, $esito['ok']
            ? ['mail_sent_at' => date('Y-m-d H:i:s'), 'mail_error' => null]
            : ['mail_error' => mb_substr((string) $esito['error'], 0, 255)]);
    }

    private static function backOfficeUrl(int $id): string
    {
        return rtrim((string) Config::get('site_url'), '/')
            . rtrim((string) Config::get('base_path', '/admin'), '/') . '/richieste/' . $id;
    }

    /** Testo su una riga (o su più righe se $una = false), tagliato. */
    private static function str(mixed $value, int $max, bool $una = true): string
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            return '';
        }
        $value = trim((string) $value);
        if ($una) {
            $value = (string) preg_replace('/\s+/u', ' ', $value);
        } else {
            $value = str_replace(["\r\n", "\r"], "\n", $value);
        }

        return mb_substr($value, 0, $max);
    }

    /** @return list<string> */
    private static function strList(mixed $value, int $maxItems, int $maxLen): array
    {
        if (!is_array($value)) {
            return [];
        }
        $out = [];
        foreach (array_slice(array_values($value), 0, $maxItems) as $item) {
            $s = self::str($item, $maxLen);
            if ($s !== '') {
                $out[] = $s;
            }
        }

        return $out;
    }

    private static function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private static function dataItaliana(string $ymd): string
    {
        [$y, $m, $d] = array_map('intval', explode('-', $ymd));

        return $d . ' ' . self::MONTHS[$m - 1] . ' ' . $y;
    }
}
