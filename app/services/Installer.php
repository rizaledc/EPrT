<?php
declare(strict_types=1);

final class Installer
{
    /** Jalankan schema.sql (aman diulang: CREATE TABLE IF NOT EXISTS + INSERT IGNORE). */
    public static function runSchema(): void
    {
        $sql = (string) file_get_contents(APP_PATH . '/database/schema.sql');
        $sql = preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (preg_split('/;\s*(\r?\n|$)/', $sql) as $stmt) {
            $stmt = trim($stmt);
            if ($stmt !== '') {
                db()->exec($stmt);
            }
        }
        self::migrate();
    }

    /** Migrasi ringan untuk database yang dibuat dari versi skema lebih lama. */
    public static function migrate(): void
    {
        $cols = array_column(q_all('SHOW COLUMNS FROM attempts'), 'Field');
        if (!in_array('tick_at', $cols, true)) {
            db()->exec('ALTER TABLE attempts ADD COLUMN tick_at DATETIME NULL AFTER remaining_s');
        }
        $rt = array_column(q_all('SHOW COLUMNS FROM remember_tokens'), 'Field');
        if (!in_array('user_session_id', $rt, true)) {
            db()->exec('ALTER TABLE remember_tokens ADD COLUMN user_session_id INT UNSIGNED NULL AFTER expires_at, ADD KEY idx_rt_us (user_session_id)');
        }
        $uc = array_column(q_all('SHOW COLUMNS FROM users'), 'Field');
        if (!in_array('coin_balance', $uc, true)) {
            db()->exec('ALTER TABLE users ADD COLUMN coin_balance INT NOT NULL DEFAULT 0 AFTER target_score');
        }
        if (!in_array('premium_until', $uc, true)) {
            db()->exec('ALTER TABLE users ADD COLUMN premium_until DATETIME NULL AFTER coin_balance');
        }
        $oc = array_column(q_all('SHOW COLUMNS FROM coin_orders'), 'Field');
        if (!in_array('product', $oc, true)) {
            db()->exec("ALTER TABLE coin_orders ADD COLUMN product VARCHAR(16) NOT NULL DEFAULT 'coins' AFTER code");
        }
    }
}
