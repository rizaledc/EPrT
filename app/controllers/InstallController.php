<?php
declare(strict_types=1);

/**
 * /install: pemasangan sekali pakai untuk hosting tanpa SSH.
 * Membuat tabel (schema.sql), mengimpor konten seed, membuat admin dari config,
 * lalu mengunci diri dengan app/installed.lock.
 */
final class InstallController
{
    public function index(): void
    {
        $lock = APP_PATH . '/installed.lock';
        if (is_file($lock)) {
            abort(404);
        }
        $log = [];
        $done = false;
        if (is_post()) {
            if (!rate_hit('install:' . ip_hash(), 10, 3600)) {
                abort(429, 'Too many attempts');
            }
            $password = (string) config('ADMIN_INITIAL_PASSWORD', '');
            $provided = (string) ($_POST['admin_password'] ?? '');
            if ($password === '' || !hash_equals($password, $provided)) {
                $log[] = ['error', t('install.err.confirm')];
            } else {
                try {
                    Installer::runSchema();
                    $log[] = ['ok', t('install.step.schema')];
                    $r = (new ContentImporter())->importDirectory(APP_PATH . '/database/seed', false);
                    $log[] = [$r['errors'] ? 'error' : 'ok', t('install.step.seed', ['n' => $r['questions']])];
                    foreach (array_slice($r['errors'], 0, 20) as $err) {
                        $log[] = ['error', $err];
                    }
                    [$ok, $msg] = Auth::createAdminFromConfig();
                    $log[] = [$ok ? 'ok' : 'error', t($msg)];
                    if ($ok || $msg === 'install.err.exists') {
                        file_put_contents($lock, date('c'));
                        $done = true;
                        $log[] = ['ok', t('install.locked')];
                        $log[] = ['warn', t('install.remove_password')];
                    }
                } catch (Throwable $e) {
                    app_log('install', $e->getMessage());
                    $log[] = ['error', $e->getMessage()];
                }
            }
        }
        render('install', ['title' => 'Install', 'log' => $log, 'done' => $done, 'noindex' => true, 'hideFooter' => true]);
    }
}
