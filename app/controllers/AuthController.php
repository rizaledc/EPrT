<?php
declare(strict_types=1);

final class AuthController
{
    public function register(): void
    {
        if (current_user()) {
            redirect('/latihan');
        }
        $open = setting('registration_open', true) !== false;
        $errors = [];
        $suggest = [];
        if (is_post() && $open) {
            $username = (string) input('username');
            $password = (string) ($_POST['password'] ?? '');
            $confirm = (string) ($_POST['password_confirm'] ?? '');
            $lang = in_array(input('lang'), ['id', 'en'], true) ? input('lang') : lang();
            $_SESSION['old'] = ['username' => $username, 'lang' => $lang];

            if (!rate_hit('register:' . ip_hash(), 5, 3600)) {
                $errors[] = t('auth.err.register_rate');
            } else {
                foreach (Auth::validateUsername($username) as $k) {
                    $errors[] = t($k);
                    if ($k === 'auth.err.username_taken') {
                        $suggest = Auth::suggestUsernames($username);
                    }
                }
                foreach (Auth::validatePassword($password, $username) as $k) {
                    $errors[] = t($k);
                }
                if ($password !== $confirm) {
                    $errors[] = t('auth.err.pw_mismatch');
                }
                if (empty($_POST['agree'])) {
                    $errors[] = t('auth.err.agree');
                }
            }
            if ($suggest) {
                $errors = array_map(fn($e) => $e === t('auth.err.username_taken') ? t('auth.err.username_taken_suggest', ['a' => $suggest[0], 'b' => $suggest[1] ?? $suggest[0]]) : $e, $errors);
            }
            if (!$errors) {
                try {
                    $id = Auth::createUser($username, $password, $lang);
                } catch (PDOException $e) {
                    $id = 0;
                    $errors[] = t('auth.err.username_taken');
                }
                if ($id) {
                    $code = Auth::setRecovery($id);
                    $user = q_one('SELECT * FROM users WHERE id = ?', [$id]);
                    Auth::login($user);
                    setcookie('lang', $lang, ['expires' => time() + 86400 * 365, 'path' => '/', 'secure' => is_https(), 'samesite' => 'Lax']);
                    $_SESSION['new_recovery_code'] = $code;
                    $_SESSION['after_recovery'] = safe_next((string) input('next'), '/latihan');
                    unset($_SESSION['old']);
                    redirect('/daftar/kode-pemulihan');
                }
            }
        }
        render('auth/register', [
            'title'   => t('auth.register.title'),
            'errors'  => $errors,
            'open'    => $open,
            'next'    => safe_next((string) input('next'), '/latihan'),
            'scripts' => ['password'],
            'hideFooter' => true,
        ]);
    }

    public function recoveryCode(): void
    {
        $u = require_login();
        $code = $_SESSION['new_recovery_code'] ?? null;
        if (is_post()) {
            if (empty($_POST['saved'])) {
                flash('error', t('auth.recovery.must_confirm'));
                redirect('/daftar/kode-pemulihan');
            }
            unset($_SESSION['new_recovery_code']);
            $next = $_SESSION['after_recovery'] ?? '/latihan';
            unset($_SESSION['after_recovery']);
            flash('success', t('auth.welcome', ['name' => $u['username']]));
            redirect($next);
        }
        if (!$code) {
            redirect('/latihan');
        }
        render('auth/recovery', ['title' => t('auth.recovery.title'), 'code' => $code, 'noindex' => true, 'scripts' => ['password'], 'hideFooter' => true]);
    }

    public function login(): void
    {
        if (current_user()) {
            redirect(safe_next((string) input('next'), '/latihan'));
        }
        $error = null;
        $next = safe_next((string) input('next'), '/latihan');
        if (is_post()) {
            $username = (string) input('username');
            $password = (string) ($_POST['password'] ?? '');
            $_SESSION['old'] = ['username' => $username];
            $until = Auth::lockedUntil($username);
            if ($until) {
                $error = t('auth.err.locked', ['time' => fmt_time($until)]);
            } else {
                $user = ($username !== '' && $password !== '') ? Auth::attempt($username, $password) : null;
                Auth::recordAttempt($username, (bool) $user);
                if ($user) {
                    Auth::login($user, !empty($_POST['remember']) && $user['role'] !== 'admin');
                    unset($_SESSION['old']);
                    if ((int) $user['must_change_password'] === 1) {
                        flash('info', t('account.must_change'));
                        redirect('/akun');
                    }
                    redirect($next);
                }
                $until = Auth::lockedUntil($username);
                $error = $until ? t('auth.err.locked', ['time' => fmt_time($until)]) : t('auth.err.invalid');
            }
        }
        render('auth/login', ['title' => t('auth.login.title'), 'error' => $error, 'next' => $next, 'scripts' => ['password'], 'hideFooter' => true]);
    }

    public function logout(): void
    {
        Auth::logout();
        flash('info', t('auth.logged_out'));
        redirect('/');
    }

    public function forgot(): void
    {
        $errors = [];
        if (is_post()) {
            $username = (string) input('username');
            $code = Auth::normalizeRecovery((string) input('code'));
            $password = (string) ($_POST['password'] ?? '');
            $_SESSION['old'] = ['username' => $username];
            if (!rate_hit('forgot:' . ip_hash(), 10, 3600)) {
                $errors[] = t('auth.err.register_rate');
            } else {
                $user = q_one('SELECT * FROM users WHERE username_lower = ? AND status = ?', [strtolower($username), 'active']);
                $ok = $user && $user['recovery_hash'] && password_verify($code, $user['recovery_hash']);
                if (!$ok) {
                    $errors[] = t('auth.forgot.invalid');
                } else {
                    foreach (Auth::validatePassword($password, $user['username']) as $k) {
                        $errors[] = t($k);
                    }
                    if ($password !== (string) ($_POST['password_confirm'] ?? '')) {
                        $errors[] = t('auth.err.pw_mismatch');
                    }
                }
                if (!$errors && $user) {
                    q('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [Auth::hash($password), $user['id']]);
                    q('DELETE FROM remember_tokens WHERE user_id = ?', [$user['id']]);
                    UserSession::revokeAll((int) $user['id'], null, 'password');
                    $newCode = Auth::setRecovery((int) $user['id']);
                    Auth::login($user);
                    $_SESSION['new_recovery_code'] = $newCode;
                    $_SESSION['after_recovery'] = '/latihan';
                    unset($_SESSION['old']);
                    redirect('/daftar/kode-pemulihan');
                }
            }
        }
        render('auth/forgot', ['title' => t('auth.forgot.title'), 'errors' => $errors, 'scripts' => ['password'], 'hideFooter' => true]);
    }
}
