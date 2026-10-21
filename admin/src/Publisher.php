<?php

declare(strict_types=1);

namespace Gaia\Admin;

/**
 * Il pulsante "Pubblica" del back office.
 *
 * Non fa il build: chiede a GitHub di avviare il workflow. È poi la Action
 * a scaricare i contenuti da api/export.php, fare il build di Astro e
 * caricare il sito via SFTP.
 *
 * Usa workflow_dispatch e non repository_dispatch: a parità di risultato,
 * un token fine-grained per workflow_dispatch richiede solo
 * «Actions: Read and write», mentre repository_dispatch pretende
 * «Contents: Read and write», cioè il permesso di scrivere codice nella
 * repo. Questo token vive su un hosting condiviso: meno può fare, meglio è.
 *
 * Il token sta solo in config.php sul server, sotto github.token.
 *
 * Esito: la Action non scrive niente sul server, quindi il pannello legge
 * l'ultima run dall'API di GitHub (ultimaPubblicazione): la run di una
 * pubblicazione dal back office si chiama «Pubblicazione <ambiente>»
 * (run-name in deploy.yml). Lo stesso token basta: «Actions: Read and
 * write» comprende la lettura delle run.
 */
final class Publisher
{
    public const WORKFLOW_PREDEFINITO = 'deploy.yml';
    public const REF_PREDEFINITO = 'main';

    /** @return array{ok: bool, status: int, message: string} */
    public static function dispatch(): array
    {
        $owner = (string) Config::get('github.owner', '');
        $repo = (string) Config::get('github.repo', '');
        $token = (string) Config::get('github.token', '');
        $workflow = (string) Config::get('github.workflow', self::WORKFLOW_PREDEFINITO);
        $ref = (string) Config::get('github.ref', self::REF_PREDEFINITO);
        $target = (string) Config::get('github.target', 'production');

        if ($owner === '' || $repo === '' || $token === '') {
            return self::log('failed', 0, 'Configurazione GitHub incompleta in config.php.');
        }

        if (!function_exists('curl_init')) {
            return self::log('failed', 0, 'Estensione cURL non disponibile su questo hosting.');
        }

        // Gli input di un workflow_dispatch viaggiano come stringhe: anche
        // le caselle di spunta vogliono "true" o "false", non un booleano.
        $payload = json_encode([
            'ref' => $ref,
            'inputs' => [
                'target' => $target,
                'skip_content' => 'false',
                'dry_run' => 'false',
            ],
        ], JSON_UNESCAPED_SLASHES);

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/actions/workflows/%s/dispatches',
            rawurlencode($owner),
            rawurlencode($repo),
            rawurlencode($workflow),
        );

        $risposta = self::chiamaGitHub($url, $token, $payload);

        if ($risposta['errore'] !== '') {
            return self::log('failed', $risposta['stato'], 'Chiamata a GitHub non riuscita: ' . $risposta['errore']);
        }

        // workflow_dispatch risponde 204 quando accetta la richiesta.
        if ($risposta['stato'] === 204) {
            return self::log(
                'accepted',
                204,
                'Pubblicazione avviata su ' . $target . '. Il sito si aggiorna entro pochi minuti.',
            );
        }

        return self::log('failed', $risposta['stato'], self::spiega($risposta, $workflow, $ref));
    }

    /**
     * L'ultima pubblicazione avviata dal back office per questo ambiente,
     * letta da GitHub: in corso, riuscita o non riuscita. Null se GitHub
     * non risponde (in 5 s) o non c'è ancora nessuna pubblicazione: il
     * pannello allora non mostra niente, non è un errore di Gaia.
     *
     * @return array{stato: 'in_corso'|'riuscita'|'non_riuscita', quando: string, url: string}|null
     */
    public static function ultimaPubblicazione(): ?array
    {
        $owner = (string) Config::get('github.owner', '');
        $repo = (string) Config::get('github.repo', '');
        $token = (string) Config::get('github.token', '');
        $workflow = (string) Config::get('github.workflow', self::WORKFLOW_PREDEFINITO);
        $ref = (string) Config::get('github.ref', self::REF_PREDEFINITO);
        $target = (string) Config::get('github.target', 'production');

        if ($owner === '' || $repo === '' || $token === '' || !function_exists('curl_init')) {
            return null;
        }

        $url = sprintf(
            'https://api.github.com/repos/%s/%s/actions/workflows/%s/runs?%s',
            rawurlencode($owner),
            rawurlencode($repo),
            rawurlencode($workflow),
            http_build_query(['branch' => $ref, 'event' => 'workflow_dispatch', 'per_page' => 20]),
        );

        $risposta = self::chiamaGitHub($url, $token, null, 5);
        if ($risposta['errore'] !== '' || $risposta['stato'] !== 200) {
            return null;
        }

        $dati = json_decode($risposta['corpo'], true);
        $runs = is_array($dati['workflow_runs'] ?? null) ? $dati['workflow_runs'] : [];

        foreach ($runs as $run) {
            if (!is_array($run) || ($run['display_title'] ?? '') !== 'Pubblicazione ' . $target) {
                continue;
            }

            $stato = match (true) {
                ($run['status'] ?? '') !== 'completed' => 'in_corso',
                ($run['conclusion'] ?? '') === 'success' => 'riuscita',
                default => 'non_riuscita',
            };

            $quando = '';
            try {
                $data = new \DateTimeImmutable((string) ($run['run_started_at'] ?? $run['created_at'] ?? 'now'));
                $quando = $data->setTimezone(new \DateTimeZone('Europe/Rome'))->format('d/m H:i');
            } catch (\Throwable) {
            }

            return ['stato' => $stato, 'quando' => $quando, 'url' => (string) ($run['html_url'] ?? '')];
        }

        return null;
    }

    /**
     * Traduce gli errori più comuni in qualcosa di leggibile, invece di
     * lasciare a Gaia un numero e un JSON.
     *
     * @param array{stato: int, corpo: string, errore: string} $risposta
     */
    private static function spiega(array $risposta, string $workflow, string $ref): string
    {
        $corpo = substr($risposta['corpo'], 0, 300);

        return match ($risposta['stato']) {
            401 => 'Il token GitHub non è valido o è scaduto. Va rigenerato.',
            403 => 'Il token non ha il permesso «Actions: Read and write» su questa repo.',
            404 => 'GitHub non trova il workflow ' . $workflow . ' sul ramo ' . $ref
                . '. Può anche voler dire che il token non è autorizzato su questa repo.',
            422 => 'GitHub ha rifiutato i parametri: ' . $corpo,
            default => 'GitHub ha risposto ' . $risposta['stato'] . ': ' . $corpo,
        };
    }

    /**
     * POST con $payload, GET se $payload è null.
     *
     * @return array{stato: int, corpo: string, errore: string}
     */
    private static function chiamaGitHub(string $url, string $token, ?string $payload, int $timeout = 20): array
    {
        $handle = curl_init($url);

        // Le costanti CURLOPT_* sono interi: niente spread (rinumererebbe
        // le chiavi), si impostano a parte.
        if ($payload !== null) {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $payload);
        } else {
            curl_setopt($handle, CURLOPT_HTTPGET, true);
        }

        curl_setopt_array($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_HTTPHEADER => [
                'Accept: application/vnd.github+json',
                'Authorization: Bearer ' . $token,
                'X-GitHub-Api-Version: 2022-11-28',
                'Content-Type: application/json',
                'User-Agent: gaia-events-admin',
            ],
        ]);

        $corpo = curl_exec($handle);
        $stato = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $errore = curl_error($handle);
        curl_close($handle);

        return [
            'stato' => $stato,
            'corpo' => is_string($corpo) ? $corpo : '',
            'errore' => $corpo === false ? ($errore !== '' ? $errore : 'risposta vuota') : '',
        ];
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
