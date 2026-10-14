<?php

declare(strict_types=1);

namespace Gaia\Admin;

/**
 * Autenticazione: password hashate con l'algoritmo di default di PHP,
 * throttling per email + IP, rigenerazione dell'ID di sessione al login.
 *
 * Due ruoli: 'admin' (tutto, incluse impostazioni tecniche e pubblicazione)
 * e 'editor' (contenuti).
 */
final class Auth
{
    private const SESSION_KEY = 'user_id';
    private const MAX_ATTEMPTS = 5;
    private const LOCK_SECONDS = 900;

    /** @var array<string, mixed>|null */
    private static ?array $user = null;

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }

        $id = Session::get(self::SESSION_KEY);
        if (!is_int($id)) {
            return null;
        }

        $user = Db::one(
            'SELECT id, email, name, role, active FROM admin_users WHERE id = ? AND active = 1',
            [$id],
        );

        if ($user === null) {
            Session::forget(self::SESSION_KEY);

            return null;
        }

        return self::$user = $user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function is(string $role): bool
    {
        return (self::user()['role'] ?? null) === $role;
    }

    /** Manda al login chi non è autenticato, conservando la pagina richiesta. */
    public static function requireLogin(): void
    {
        if (self::check()) {
            return;
        }

        Session::set('_intended', $_SERVER['REQUEST_URI'] ?? admin_url());
        redirect('login');
    }

    /** Solo per le sezioni riservate agli admin. */
    public static function requireAdmin(): void
    {
        self::requireLogin();

        if (!self::is('admin')) {
            http_response_code(403);
            exit('Questa sezione è riservata agli amministratori.');
        }
    }

    /**
     * Tenta il login. Restituisce null se riuscito, altrimenti il messaggio
     * d'errore (generico: non riveliamo se l'email esiste).
     */
    public static function attempt(string $email, string $password): ?string
    {
        $email = mb_strtolower(trim($email));
        $ip = self::ip();

        if (self::isLocked($email, $ip)) {
            return 'Troppi tentativi. Riprova fra qualche minuto.';
        }

        $user = Db::one('SELECT * FROM admin_users WHERE email = ? LIMIT 1', [$email]);

        $hash = is_array($user) ? (string) $user['password_hash'] : '';
        // Verifica sempre, anche senza utente: evita di far capire dai tempi
        // di risposta se l'email esiste.
        $ok = password_verify($password, $hash !== '' ? $hash : '$2y$10$' . str_repeat('x', 53));

        if (!is_array($user) || !$ok || (int) $user['active'] !== 1) {
            self::recordFailure($email, $ip);

            return 'Email o password non corretti.';
        }

        if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
            Db::update('admin_users', (int) $user['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        self::clearFailures($email, $ip);

        session_regenerate_id(true);
        Session::set(self::SESSION_KEY, (int) $user['id']);

        Db::update('admin_users', (int) $user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ip,
        ]);

        return null;
    }

    public static function logout(): void
    {
        self::$user = null;
        Session::destroy();
    }

    public static function hash(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    private static function isLocked(string $email, string $ip): bool
    {
        $since = date('Y-m-d H:i:s', time() - self::LOCK_SECONDS);

        $count = (int) Db::value(
            'SELECT COUNT(*) FROM login_attempts
             WHERE (email = ? OR ip = ?) AND attempted_at > ?',
            [$email, $ip, $since],
        );

        return $count >= self::MAX_ATTEMPTS;
    }

    private static function recordFailure(string $email, string $ip): void
    {
        Db::insert('login_attempts', [
            'email' => $email,
            'ip' => $ip,
            'attempted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private static function clearFailures(string $email, string $ip): void
    {
        Db::run('DELETE FROM login_attempts WHERE email = ? OR ip = ?', [$email, $ip]);
    }

    private static function ip(): string
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        return is_string($ip) ? substr($ip, 0, 45) : '';
    }
}
