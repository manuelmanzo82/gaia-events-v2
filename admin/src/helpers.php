<?php

declare(strict_types=1);

/** Escape per l'HTML. Da usare su OGNI valore stampato in una view. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL interno al back office, costruito sul base_path configurato. */
function admin_url(string $path = ''): string
{
    $base = rtrim((string) Gaia\Admin\Config::get('base_path', '/admin'), '/');

    return $base . '/' . ltrim($path, '/');
}

/**
 * Redirect e stop.
 *
 * Accetta tre forme: un URL completo, un percorso gia' assoluto come
 * '/admin/richieste' (per esempio la pagina che l'utente stava cercando di
 * aprire prima del login) e un percorso relativo al back office come
 * 'login'. Solo l'ultimo va prefissato con base_path: prefissare anche gli
 * assoluti produceva /admin/admin/.
 */
function redirect(string $path): never
{
    if (str_starts_with($path, 'http') || str_starts_with($path, '/')) {
        $url = $path;
    } else {
        $url = admin_url($path);
    }

    header('Location: ' . $url, true, 302);
    exit;
}

/** Valore da un array annidato: array_get($data, 'db.host') */
function array_get(array $array, string $key, mixed $default = null): mixed
{
    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
        }
        $array = $array[$segment];
    }

    return $array;
}
