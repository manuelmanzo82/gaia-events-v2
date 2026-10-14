<?php

declare(strict_types=1);

namespace Gaia\Admin;

use RuntimeException;

/**
 * Template PHP semplici, senza motore esterno.
 * Nelle view ogni valore stampato passa da e(): niente HTML non filtrato.
 */
final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $template, array $data = [], ?string $layout = 'layout'): void
    {
        echo self::capture($template, $data, $layout);
    }

    /** @param array<string, mixed> $data */
    public static function capture(string $template, array $data = [], ?string $layout = 'layout'): string
    {
        $content = self::renderFile($template, $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderFile($layout, [
            ...$data,
            'content' => $content,
            'user' => Auth::user(),
            'flash' => Session::takeFlash(),
        ]);
    }

    /** @param array<string, mixed> $data */
    private static function renderFile(string $template, array $data): string
    {
        $file = ADMIN_ROOT . '/views/' . $template . '.php';

        if (!is_file($file)) {
            throw new RuntimeException("Template mancante: {$template}");
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $file;

        return (string) ob_get_clean();
    }

    /** Risposta JSON per gli endpoint di api/. */
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
        );

        exit;
    }
}
