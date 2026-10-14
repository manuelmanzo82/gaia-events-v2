<?php

declare(strict_types=1);

namespace Gaia\Admin;

/**
 * Validazione dei dati in arrivo dai form e dagli endpoint pubblici.
 * Regole disponibili: required, email, max:n, min:n, int, date, url, in:a,b.
 */
final class Validator
{
    /** @var array<string, string> */
    private array $errors = [];

    /** @var array<string, mixed> */
    private array $clean = [];

    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $rules  campo => 'required|email|max:120'
     */
    public function __construct(
        private array $input,
        private array $rules,
    ) {
        $this->run();
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return $this->errors === [] ? null : reset($this->errors);
    }

    /** @return array<string, mixed> */
    public function validated(): array
    {
        return $this->clean;
    }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $raw = $this->input[$field] ?? null;
            $value = is_string($raw) ? trim($raw) : $raw;
            $rules = explode('|', $ruleString);

            $isRequired = in_array('required', $rules, true);
            $isEmpty = $value === null || $value === '' || $value === [];

            if ($isEmpty) {
                if ($isRequired) {
                    $this->errors[$field] = "Il campo «{$field}» è obbligatorio.";
                    continue;
                }

                $this->clean[$field] = $value === '' ? null : $value;
                continue;
            }

            foreach ($rules as $rule) {
                if ($rule === '' || $rule === 'required') {
                    continue;
                }

                [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);
                $error = $this->check((string) $name, $parameter, $field, $value);

                if ($error !== null) {
                    $this->errors[$field] = $error;
                    continue 2;
                }
            }

            $this->clean[$field] = $value;
        }
    }

    private function check(string $rule, ?string $parameter, string $field, mixed $value): ?string
    {
        return match ($rule) {
            'email' => filter_var((string) $value, FILTER_VALIDATE_EMAIL) === false
                ? 'Indirizzo email non valido.'
                : null,
            'url' => filter_var((string) $value, FILTER_VALIDATE_URL) === false
                ? 'Indirizzo web non valido.'
                : null,
            'int' => filter_var((string) $value, FILTER_VALIDATE_INT) === false
                ? "Il campo «{$field}» deve essere un numero intero."
                : null,
            'max' => mb_strlen((string) $value) > (int) $parameter
                ? "Il campo «{$field}» supera i {$parameter} caratteri."
                : null,
            'min' => mb_strlen((string) $value) < (int) $parameter
                ? "Il campo «{$field}» è troppo corto."
                : null,
            'date' => $this->isDate((string) $value) ? null : 'Data non valida.',
            'in' => in_array((string) $value, explode(',', (string) $parameter), true)
                ? null
                : "Valore non ammesso per «{$field}».",
            default => null,
        };
    }

    private function isDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    /** Honeypot: se il campo invisibile è pieno è un bot. */
    public static function isBot(array $input, string $field = 'website'): bool
    {
        return trim((string) ($input[$field] ?? '')) !== '';
    }

    /** Slug sicuro per URL, usato per eventi, servizi e articoli. */
    public static function slug(string $value): string
    {
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $slug = strtolower((string) $slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }
}
