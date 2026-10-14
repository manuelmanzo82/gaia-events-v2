<?php

declare(strict_types=1);

namespace Gaia\Admin;

/** Token CSRF per ogni form che modifica dati. */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        $token = Session::get(self::KEY);

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            Session::set(self::KEY, $token);
        }

        return $token;
    }

    /** Campo nascosto da mettere in ogni form POST. */
    public static function field(): string
    {
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::KEY,
            e(self::token()),
        );
    }

    public static function isValid(?string $token): bool
    {
        $expected = Session::get(self::KEY);

        return is_string($expected)
            && is_string($token)
            && $token !== ''
            && hash_equals($expected, $token);
    }

    /** Blocca la richiesta se il token non è valido. */
    public static function verify(): void
    {
        $token = $_POST[self::KEY] ?? null;

        if (!self::isValid(is_string($token) ? $token : null)) {
            http_response_code(419);
            exit('Sessione scaduta: ricarica la pagina e riprova.');
        }
    }
}
