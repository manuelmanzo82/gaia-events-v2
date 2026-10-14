<?php

declare(strict_types=1);

namespace Gaia\Admin;

use PDO;
use PDOStatement;
use RuntimeException;

/**
 * Wrapper minimo su PDO. Query sempre con parametri: niente concatenazione
 * di stringhe SQL da nessuna parte.
 */
final class Db
{
    private static ?PDO $pdo = null;

    /** @var array<string, mixed> */
    private static array $config = [];

    /** @param array<string, mixed> $config */
    public static function init(array $config): void
    {
        self::$config = $config;
    }

    public static function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            self::$config['host'] ?? 'localhost',
            self::$config['name'] ?? '',
            self::$config['charset'] ?? 'utf8mb4',
        );

        try {
            self::$pdo = new PDO(
                $dsn,
                (string) (self::$config['user'] ?? ''),
                (string) (self::$config['pass'] ?? ''),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
            );
        } catch (\PDOException $exception) {
            error_log('[db] connessione fallita: ' . $exception->getMessage());
            throw new RuntimeException('Connessione al database non riuscita.', 0, $exception);
        }

        return self::$pdo;
    }

    /** @param array<string|int, mixed> $params */
    public static function run(string $sql, array $params = []): PDOStatement
    {
        $statement = self::pdo()->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return array<string, mixed>|null
     */
    public static function one(string $sql, array $params = []): ?array
    {
        $row = self::run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /**
     * @param array<string|int, mixed> $params
     * @return list<array<string, mixed>>
     */
    public static function all(string $sql, array $params = []): array
    {
        return self::run($sql, $params)->fetchAll();
    }

    /** @param array<string|int, mixed> $params */
    public static function value(string $sql, array $params = []): mixed
    {
        $value = self::run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /** @param array<string, mixed> $data */
    public static function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (%s) VALUES (%s)',
            $table,
            implode(', ', array_map(static fn (string $c): string => "`{$c}`", $columns)),
            implode(', ', array_map(static fn (string $c): string => ":{$c}", $columns)),
        );

        self::run($sql, $data);

        return (int) self::pdo()->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public static function update(string $table, int $id, array $data): void
    {
        if ($data === []) {
            return;
        }

        $assignments = implode(
            ', ',
            array_map(static fn (string $c): string => "`{$c}` = :{$c}", array_keys($data)),
        );

        self::run(
            sprintf('UPDATE `%s` SET %s WHERE id = :__id', $table, $assignments),
            [...$data, '__id' => $id],
        );
    }

    public static function delete(string $table, int $id): void
    {
        self::run(sprintf('DELETE FROM `%s` WHERE id = ?', $table), [$id]);
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();

        try {
            $result = $callback();
            $pdo->commit();

            return $result;
        } catch (\Throwable $exception) {
            $pdo->rollBack();
            throw $exception;
        }
    }
}
