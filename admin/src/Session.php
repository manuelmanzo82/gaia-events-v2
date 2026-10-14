<?php

declare(strict_types=1);

namespace Gaia\Admin;

/** Sessione con cookie restrittivi. */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = ($_SERVER['HTTPS'] ?? '') === 'on'
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

        session_name((string) Config::get('session_name', 'gaia_admin'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => rtrim((string) Config::get('base_path', '/admin'), '/') . '/',
            'httponly' => true,
            'secure' => $secure,
            'samesite' => 'Lax',
        ]);

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /** Legge e cancella: per i messaggi flash. */
    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = self::get($key, $default);
        self::forget($key);

        return $value;
    }

    public static function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'],
                'httponly' => true,
                'secure' => $params['secure'],
                'samesite' => 'Lax',
            ]);
        }

        session_destroy();
    }

    public static function flash(string $type, string $message): void
    {
        $messages = self::get('_flash', []);
        $messages[] = ['type' => $type, 'message' => $message];
        self::set('_flash', $messages);
    }

    /** @return list<array{type: string, message: string}> */
    public static function takeFlash(): array
    {
        return self::pull('_flash', []);
    }
}
