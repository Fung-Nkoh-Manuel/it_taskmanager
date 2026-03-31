<?php

class Router
{
    private static array $routes = [];

    // ── Registration helpers ──────────────────────────────────────────────────

    public static function get(string $pattern, string $controller, string $method): void
    {
        self::$routes[] = ['GET', $pattern, $controller, $method];
    }

    public static function post(string $pattern, string $controller, string $method): void
    {
        self::$routes[] = ['POST', $pattern, $controller, $method];
    }

    // ── Dispatch ─────────────────────────────────────────────────────────────

    public static function dispatch(): void
    {
        // If the request is for an existing PHP file, serve it directly
        $requestFile = $_SERVER['DOCUMENT_ROOT'] . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        if (file_exists($requestFile) && pathinfo($requestFile, PATHINFO_EXTENSION) === 'php') {
            // Let PHP-FPM handle it directly
            return;
        }
        
        $httpMethod = $_SERVER['REQUEST_METHOD'];
        $uri        = self::getUri();

        // Support _method override for DELETE / PUT via hidden form field
        if ($httpMethod === 'POST' && isset($_POST['_method'])) {
            $httpMethod = strtoupper($_POST['_method']);
        }

        foreach (self::$routes as [$routeMethod, $pattern, $controller, $method]) {

            if ($routeMethod !== $httpMethod && $routeMethod !== 'ANY') {
                continue;
            }

            $params = self::match($pattern, $uri);

            if ($params !== false) {
                // Instantiate controller and call method
                // Pass named params as a single array argument, not spread
                $ctrl = new $controller();
                $ctrl->$method($params);
                return;
            }
        }

        // No route matched — 404
        http_response_code(404);
        $message = 'Page not found.';
        require_once VIEW_PATH . '/partials/error.php';
    }
    // ── Helpers ──────────────────────────────────────────────────────────────

    private static function getUri(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Strip the sub-folder prefix so routes are always relative
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($base && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }

        return '/' . ltrim($uri, '/');
    }

    /**
     * Match a route pattern against a URI.
     * Patterns use {param} placeholders.
     * Returns an array of matched params or false.
     */
    private static function match(string $pattern, string $uri): array|false
    {
        // Convert {param} to named regex groups
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $uri, $matches)) {
            // Return only named captures
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return false;
    }
}
