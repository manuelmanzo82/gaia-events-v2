<?php

declare(strict_types=1);

namespace Gaia\Admin;

/**
 * Ricezione delle richieste dal sito statico: preventivo e contatti.
 *
 * Sostituisce Web3Forms. I dati finiscono nella tabella `inquiries` e
 * una notifica parte via mail verso l'indirizzo in config.
 */
final class Inquiry
{
    private const MAX_PER_HOUR = 6;

    /**
     * @param 'quote'|'contact' $type
     * @return array{ok: bool, status: int, message: string}
     */
    public static function receive(string $type): array
    {
        $input = self::input();

        // Honeypot: un campo che un essere umano non vede mai.
        if (Validator::isBot($input)) {
            // Rispondiamo ok: il bot non deve capire di essere stato scartato.
            return ['ok' => true, 'status' => 200, 'message' => 'ok'];
        }

        $rules = [
            'name' => 'required|max:160',
            'email' => 'required|email|max:190',
            'phone' => 'max:60',
            'event_date' => 'date',
            'event_type' => 'max:120',
            'guests' => 'int',
            'message' => 'max:5000',
            'lang' => 'in:it,en',
        ];

        if ($type === 'contact') {
            $rules['message'] = 'required|max:5000';
        }

        $validator = new Validator($input, $rules);

        if ($validator->fails()) {
            return [
                'ok' => false,
                'status' => 422,
                'message' => (string) $validator->firstError(),
            ];
        }

        $ip = substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45);

        if (self::tooMany($ip)) {
            return [
                'ok' => false,
                'status' => 429,
                'message' => 'Troppe richieste dallo stesso indirizzo. Riprova più tardi.',
            ];
        }

        $data = $validator->validated();

        // Tutto ciò che il preventivatore manda oltre ai campi fissi.
        $extra = array_diff_key($input, $rules, ['website' => true]);

        $id = Db::insert('inquiries', [
            'type' => $type,
            'lang' => $data['lang'] ?? 'it',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'event_date' => $data['event_date'] ?? null,
            'event_type' => $data['event_type'] ?? null,
            'guests' => isset($data['guests']) ? (int) $data['guests'] : null,
            'message' => $data['message'] ?? null,
            'payload' => $extra === [] ? null : json_encode($extra, JSON_UNESCAPED_UNICODE),
            'ip' => $ip,
            'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
        ]);

        self::notify($type, $id, $data);

        return ['ok' => true, 'status' => 201, 'message' => 'ok'];
    }

    /** Accetta sia JSON sia form classico. */
    private static function input(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = is_string($raw) ? json_decode($raw, true) : null;

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }

    private static function tooMany(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }

        $count = (int) Db::value(
            'SELECT COUNT(*) FROM inquiries WHERE ip = ? AND created_at > ?',
            [$ip, date('Y-m-d H:i:s', time() - 3600)],
        );

        return $count >= self::MAX_PER_HOUR;
    }

    /** @param array<string, mixed> $data */
    private static function notify(string $type, int $id, array $data): void
    {
        $to = (string) Config::get('notify_email', '');
        $from = (string) Config::get('mail_from', '');

        if ($to === '' || !function_exists('mail')) {
            return;
        }

        $label = $type === 'quote' ? 'Richiesta di preventivo' : 'Messaggio dal sito';

        $body = implode("\n", [
            $label . ' #' . $id,
            '',
            'Nome: ' . ($data['name'] ?? ''),
            'Email: ' . ($data['email'] ?? ''),
            'Telefono: ' . ($data['phone'] ?? '—'),
            'Data evento: ' . ($data['event_date'] ?? '—'),
            'Tipo evento: ' . ($data['event_type'] ?? '—'),
            'Invitati: ' . ($data['guests'] ?? '—'),
            '',
            'Messaggio:',
            (string) ($data['message'] ?? ''),
            '',
            'Aperta nel back office: ' . rtrim((string) Config::get('site_url'), '/')
                . rtrim((string) Config::get('base_path', '/admin'), '/') . '/richieste/' . $id,
        ]);

        $headers = [
            'From: Gaia Events <' . ($from !== '' ? $from : $to) . '>',
            'Content-Type: text/plain; charset=utf-8',
        ];

        // Reply-To con l'indirizzo di chi scrive: rispondere è un clic.
        if (isset($data['email']) && is_string($data['email'])) {
            $headers[] = 'Reply-To: ' . $data['email'];
        }

        @mail($to, $label . ' #' . $id, $body, implode("\r\n", $headers));
    }
}
