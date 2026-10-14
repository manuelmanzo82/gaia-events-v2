<?php

declare(strict_types=1);

namespace Gaia\Admin;

/** Accesso in sola lettura alla configurazione. */
final class Config
{
    /** @var array<string, mixed> */
    private static array $values = [];

    /** @param array<string, mixed> $values */
    public static function init(array $values): void
    {
        self::$values = $values;
    }

    /** Supporta la notazione con punto: Config::get('db.host') */
    public static function get(string $key, mixed $default = null): mixed
    {
        return array_get(self::$values, $key, $default);
    }

    public static function isDebug(): bool
    {
        return (bool) self::get('debug', false);
    }
}
