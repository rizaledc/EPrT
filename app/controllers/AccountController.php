<?php
declare(strict_types=1);

final class AccountController
{
    public function index(): void
    {
        $u = require_login();
        $errors = [];
        $newCode = null;
        if (is_post()) {
            $action = (string) input('action');
            switch ($action) {
                case 'password':
                    $old = (string) ($_POST['old_password'] ?? '');
                    $new = (string) ($_POST['password'] ?? '');
                    if (!password_verify($old, $u['password_hash'])) {
                        $errors[] = t('account.err.old_wrong');
                    }
                    foreach (Auth::validatePassword($new, $u['username']) as $k) {
                        $errors[] = t($k);
                    }
                    if ($new !== (string) ($_POST['password_confirm'] ?? '')) {
                        $errors[] = t('auth.err.pw_mismatch');
                    }
                    if ($old === $new) {
                        $errors[] = t('account.err.same');
                    }
                    if (!$errors) {
                        q('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?', [Auth::hash($new), $u['id']]);
                        q('DELETE FROM remember_tokens WHERE user_id = ?', [$u['id']]);
                        // Semua perangkat lain keluar; perangkat ini tetap masuk
                        UserSession::revokeAll((int) $u['id'], UserSession::currentId(), 'password');
                        session_regenerate_id(true);
                        flash('success', t('account.pw_changed'));
                        redirect('/akun');
                    }
                    break;
                case 'revoke_session':
                    $sid = (int) input('session_id');
                    if ($sid && $sid !== UserSession::currentId() && q_val('SELECT 1 FROM user_sessions WHERE id = ? AND user_id = ? AND revoked_at IS NULL', [$sid, $u['id']])) {
                        UserSession::revokeIds([$sid], 'user');
                        flash('success', t('account.devices.revoked'));
                    }
                    redirect('/akun#perangkat');
                case 'revoke_others':
                    $n = UserSession::revokeAll((int) $u['id'], UserSession::currentId(), 'user');
                    flash('success', $n ? t('account.devices.revoked_n', ['n' => $n]) : t('account.devices.none_other'));
                    redirect('/akun#perangkat');
                case 'prefs':
                    $lang = in_array(input('lang'), ['id', 'en'], true) ? input('lang') : 'id';
                    $target = max(310, min(677, (int) input('target_score', 500)));
                    q('UPDATE users SET lang = ?, target_score = ? WHERE id = ?', [$lang, $target, $u['id']]);
                    setcookie('lang', $lang, ['expires' => time() + 86400 * 365, 'path' => '/', 'secure' => is_https(), 'samesite' => 'Lax']);
                    flash('success', t('account.saved', [], ));
                    redirect('/akun');
                case 'recovery':
                    if (!password_verify((string) ($_POST['confirm_password'] ?? ''), $u['password_hash'])) {
                        $errors[] = t('account.err.old_wrong');
                        break;
                    }
                    $newCode = Auth::setRecovery((int) $u['id']);
                    break;
                case 'delete':
                    if (strtolower((string) input('confirm_username')) !== strtolower($u['username'])) {
                        $errors[] = t('account.err.delete_confirm');
                        break;
                    }
                    if ($u['role'] === 'admin') {
                        $errors[] = t('account.err.admin_delete');
                        break;
                    }
                    q('DELETE FROM users WHERE id = ?', [$u['id']]);
                    Auth::logout();
                    flash('info', t('account.deleted'));
                    redirect('/');
            }
        }
        render('account/index', [
            'title'   => t('account.title'),
            'user'    => current_user() ?? $u,
            'errors'  => $errors,
            'newCode' => $newCode,
            'devices' => UserSession::active((int) $u['id']),
            'currentSession' => UserSession::currentId(),
            'sessionLimit' => UserSession::limit(),
            'scripts' => ['password'],
            'noindex' => true,
        ]);
    }
}
