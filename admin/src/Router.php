<?php

declare(strict_types=1);

namespace Gaia\Admin;

/**
 * Router minimo. Le rotte si dichiarano come 'GET /eventi/{id}/modifica'
 * e puntano a [Controller::class, 'metodo'].
 */
final class Router
{
    /** @var list<array{method: string, regex: string, params: list<string>, handler: array{0: class-string, 1: string}}> */
    private array $routes = [];

    /** @param array{0: class-string, 1: string} $handler */
    public function add(string $method, string $pattern, array $handler): void
    {
        $params = [];

        $regex = preg_replace_callback(
            '#\{([a-z_]+)\}#',
            static function (array $matches) use (&$params): string {
                $params[] = $matches[1];

                return '([^/]+)';
            },
            rtrim($pattern, '/'),
        );

        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => '#^' . ($regex === '' ? '/' : $regex) . '$#',
            'params' => $params,
            'handler' => $handler,
        ];
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function get(string $pattern, array $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    /** @param array{0: class-string, 1: string} $handler */
    public function post(string $pattern, array $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = rtrim('/' . trim($this->stripBasePath($uri), '/'), '/');
        if ($path === '') {
            $path = '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== strtoupper($method)) {
                continue;
            }

            if (preg_match($route['regex'], $path, $matches) !== 1) {
                continue;
            }

            array_shift($matches);
            $arguments = $route['params'] === []
                ? []
                : (array_combine($route['params'], $matches) ?: []);

            [$class, $action] = $route['handler'];
            (new $class())->{$action}($arguments);

            return;
        }

        http_response_code(404);
        View::render('error', [
            'title' => 'Pagina non trovata',
            'message' => 'La pagina richiesta non esiste nel back office.',
        ]);
    }

    /** Il back office può stare in una sottocartella: il base_path va via. */
    private function stripBasePath(string $uri): string
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) && $path !== '' ? $path : '/';

        $base = rtrim((string) Config::get('base_path', '/admin'), '/');

        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base));
        }

        return $path;
    }
}
