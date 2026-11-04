<?php
declare(strict_types=1);

final class Router
{
    public function __construct(private array $routes)
    {
    }

    public function dispatch(string $method, string $path): void
    {
        if ($method === 'HEAD') {
            $method = 'GET';
        }
        $allowedElsewhere = false;
        foreach ($this->routes as [$methods, $pattern, $handler]) {
            $regex = '#^' . preg_replace('#\\\{([a-z]+)\\\}#', '(?P<$1>[^/]+?)', preg_quote($pattern, '#')) . '$#';
            if (!preg_match($regex, $path, $m)) {
                continue;
            }
            if (!in_array($method, explode('|', $methods), true)) {
                $allowedElsewhere = true;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            if ($method === 'POST') {
                verify_csrf();
            }
            [$class, $fn] = explode('@', $handler);
            (new $class())->$fn(...array_values($params));
            return;
        }
        if ($allowedElsewhere) {
            abort(405, 'Method not allowed');
        }
        abort(404);
    }
}
