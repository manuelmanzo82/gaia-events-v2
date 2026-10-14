<?php

declare(strict_types=1);

namespace Gaia\Admin;

/**
 * Il pulsante "Pubblica" del back office.
 *
 * Non fa il build: manda un repository_dispatch a GitHub. È la Action che
 * poi scarica i contenuti da api/export.php, fa il build di Astro e carica
 * il sito via SFTP.
 *
 * Il token GitHub sta solo in config.php sul server.
 */
final class Publisher
{
    /** @return array{ok: bool, status: int, message: string} */
    public static function dispatch(): array
    {
        $owner = (string) Config::get('github.owner', '');
        $repo = (string) Config::get('github.repo', '');
        $token = (string) Config::get('github.token', '');
        $eventType = (string) Config::get('github.event_type', 'publish');

        if ($owner === '' || $repo === '' || $token === '') {
            return self::log('failed', 0, 'Configurazione GitHub incompleta in config.php.');
        }

        if (!function_exists('curl_init')) {
            return self::log('failed', 0, 'Estensione cURL non disponibile su questo hosting.');
        }

        $user = Auth::user();

        $payload = json_encode([
            'event_type' => $eventType,
            'client_payload' => [
                'triggered_by' => $user['email'] ?? 'sconosciuto',
                'triggered_at' => date('c'),
            ],
        ], JSON_UNESCAPED_SLASHES);

        $handle = curl_init(sprintf('https://api.github.com/repos/%s/%s/dispatches', $owner, $repo));

        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $token,
                'X-GitHub-Api-Version: 2022-11-28',
                'Content-Type: application/json',
                'User-Agent: gaia-events-admin',
            ],
        ]);

        $response = curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $error = curl_error($handle);
        curl_close($handle);

        if ($response === false || $error !== '') {
            return self::log('failed', $status, 'Chiamata a GitHub non riuscita: ' . $error);
        }

        // repository_dispatch risponde 204 quando accetta la richiesta.
        if ($status === 204) {
            return self::log(
                'accepted',
                $status,
                'Pubblicazione avviata. Il sito si aggiorna entro pochi minuti.',
            );
        }

        return self::log(
            'failed',
            $status,
            'GitHub ha risposto ' . $status . ': ' . substr((string) $response, 0, 300),
        );
    }

    /** @return array{ok: bool, status: int, message: string} */
    private static function log(string $outcome, int $status, string $message): array
    {
        $user = Auth::user();

        try {
            Db::insert('publish_log', [
                'user_id' => isset($user['id']) ? (int) $user['id'] : null,
                'status' => $outcome,
                'http_status' => $status !== 0 ? $status : null,
                'message' => mb_substr($message, 0, 500),
            ]);
        } catch (\Throwable $exception) {
            error_log('[publish] log non scritto: ' . $exception->getMessage());
        }

        return ['ok' => $outcome === 'accepted', 'status' => $status, 'message' => $message];
    }

    /** @return list<array<string, mixed>> */
    public static function recent(int $limit = 10): array
    {
        return Db::all(
            'SELECT p.*, u.name AS user_name
             FROM publish_log p
             LEFT JOIN admin_users u ON u.id = p.user_id
             ORDER BY p.id DESC
             LIMIT ' . max(1, min(100, $limit)),
        );
    }
}
