<?php
declare(strict_types=1);

/**
 * Front controller PerumahanInggris.
 * Kode aplikasi berada di ../app (di luar public_html).
 */

// Server bawaan PHP (localhost): sajikan file statis secara langsung
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file) && !preg_match('/\.(php|json|sql|lock|env)$/i', $file)) {
        return false;
    }
}

$appPath = __DIR__ . '/../app';
if (!is_dir($appPath)) {
    // Alternatif: app/ diletakkan di dalam folder yang sama (tidak disarankan)
    $appPath = __DIR__ . '/app';
}
require $appPath . '/bootstrap.php';
require $appPath . '/Router.php';

(new Router(require $appPath . '/routes.php'))->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
