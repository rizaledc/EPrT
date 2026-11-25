<?php
declare(strict_types=1);

/**
 * Front controller PerumahanInggris.
 *
 * Lokasi folder app/ dicari berurutan:
 *  1. public_html/app-path.php  → file kecil di server (tidak ada di repo, tidak ikut rsync):
 *         <?php return '/home/u123456789/apps/perumahaninggris/app';
 *  2. ../app                     → struktur repo / server dengan app di samping public_html
 *  3. ./app                      → cadangan (tidak disarankan)
 */

// Server bawaan PHP (localhost): sajikan file statis secara langsung
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($file !== __DIR__ . '/' && is_file($file) && !preg_match('/\.(php|json|sql|lock|env)$/i', $file)) {
        return false;
    }
}

define('PUBLIC_PATH', __DIR__);

$appPath = null;
if (is_file(__DIR__ . '/app-path.php')) {
    $appPath = require __DIR__ . '/app-path.php';
}
foreach ([$appPath, __DIR__ . '/../app', __DIR__ . '/app'] as $candidate) {
    if (is_string($candidate) && is_file(rtrim($candidate, '/') . '/bootstrap.php')) {
        $appPath = rtrim($candidate, '/');
        break;
    }
    $appPath = null;
}
if ($appPath === null) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    exit("Folder app/ tidak ditemukan. Buat public_html/app-path.php (lihat DEPLOY.md).\n");
}

require $appPath . '/bootstrap.php';
require $appPath . '/Router.php';

enforce_user_session();

(new Router(require $appPath . '/routes.php'))->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', request_path());
