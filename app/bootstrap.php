<?php
declare(strict_types=1);

/**
 * Bootstrap PerumahanInggris: konfigurasi, error handling, session, autoload, helper.
 */

define('APP_PATH', __DIR__);
define('APP_START', microtime(true));

$configFile = APP_PATH . '/config.php';
if (!is_file($configFile)) {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "app/config.php belum ada. Salin app/config.example.php menjadi app/config.php lalu isi.\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><title>Konfigurasi belum lengkap</title>'
       . '<p style="font-family:sans-serif;padding:2rem">File <code>app/config.php</code> belum ada. '
       . 'Salin <code>app/config.example.php</code> menjadi <code>app/config.php</code> lalu isi. Lihat DEPLOY.md.</p>';
    exit;
}
$GLOBALS['__config'] = require $configFile;

function config(string $key, $default = null)
{
    $value = $GLOBALS['__config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

define('IS_DEV', config('env') === 'development');
if (!defined('PUBLIC_PATH')) {
    // Web: didefinisikan public_html/index.php. CLI: dari config public_path (atau ../public_html).
    define('PUBLIC_PATH', rtrim((string) (config('public_path') ?: dirname(APP_PATH) . '/public_html'), '/'));
}
define('NOINDEX', (bool) config('noindex', false));
define('STORAGE_PATH', APP_PATH . '/storage');

date_default_timezone_set((string) config('timezone', 'Asia/Jakarta'));
mb_internal_encoding('UTF-8');

ini_set('display_errors', IS_DEV ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-error.log');
error_reporting(E_ALL);

spl_autoload_register(function (string $class): void {
    foreach (['controllers', 'models', 'services'] as $dir) {
        $file = APP_PATH . "/$dir/$class.php";
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});

$vendor = APP_PATH . '/vendor/autoload.php';
if (is_file($vendor)) {
    require $vendor;
}

require APP_PATH . '/helpers.php';

set_error_handler(function (int $no, string $str, string $file, int $line): bool {
    if (!(error_reporting() & $no)) {
        return false;
    }
    throw new ErrorException($str, 0, $no, $file, $line);
});

set_exception_handler(function (Throwable $e): void {
    app_log('error', get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() . "\n" . $e->getTraceAsString());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $e->getMessage() . "\n");
        exit(1);
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (is_api_request()) {
        json_response(['ok' => false, 'error' => IS_DEV ? $e->getMessage() : 'server_error'], 500);
    }
    http_response_code(500);
    try {
        echo view('errors/500', ['error' => IS_DEV ? $e : null, 'title' => t('error.500.title')]);
    } catch (Throwable $inner) {
        echo '<!doctype html><meta charset="utf-8"><title>500</title><p>Terjadi kesalahan server.</p>';
    }
    exit;
});

if (PHP_SAPI !== 'cli') {
    start_session();
    send_security_headers();
}
