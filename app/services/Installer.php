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
    }
}
