<?php
declare(strict_types=1);

final class Rumah
{
    public static function all(): array
    {
        static $rows = null;
        if ($rows === null) {
            $rows = q_all('SELECT r.*, b.code AS blok_code, b.name_id AS blok_name_id, b.name_en AS blok_name_en
                           FROM rumahs r LEFT JOIN bloks b ON b.id = r.blok_id ORDER BY r.sort, r.id');
        }
        return $rows;
    }

    public static function bySlug(string $slug): ?array
    {
        foreach (self::all() as $r) {
            if ($r['slug'] === $slug) {
                return $r;
            }
        }
        return null;
    }

    public static function byId(int $id): ?array
    {
        foreach (self::all() as $r) {
            if ((int) $r['id'] === $id) {
                return $r;
            }
        }
        return null;
    }

    /** URL /latihan/level-a/hari-1 (slug internal tetap r01; hanya tampilan yang memakai Level/Hari) */
    public static function url(array $r, ?string $section = null): string
    {
        if ($r['kind'] === 'simulation') {
            $n = (int) $r['number'];
            return url('simulasi/' . $n . ($section ? '/' . $section : ''));
        }
        if ($r['kind'] === 'sample') {
            return url('coba');
        }
        return url('latihan/level-' . strtolower((string) $r['blok_code']) . '/hari-' . (int) $r['number'] . ($section ? '/' . $section : ''));
    }

    public static function fromRoute(string $blok, string $rumah): ?array
    {
        // Pola baru level-a/hari-1; pola lama blok-a/rumah-1 tetap dikenali (lalu dialihkan 301)
        if (!preg_match('/^(?:level|blok)-([a-d])$/', $blok, $b) || !preg_match('/^(?:hari|rumah)-(\d{1,2})$/', $rumah, $m)) {
            return null;
        }
        foreach (self::all() as $r) {
            if (in_array($r['kind'], ['practice', 'adaptive'], true) && (int) $r['number'] === (int) $m[1] && strtolower((string) $r['blok_code']) === $b[1]) {
                return $r;
            }
        }
        return null;
    }

    /** true jika URL memakai pola lama /latihan/blok-a/rumah-1 */
    public static function isLegacyRoute(string $blok, string $rumah): bool
    {
        return str_starts_with($blok, 'blok-') || str_starts_with($rumah, 'rumah-');
    }

    public static function title(array $r): string
    {
        return tr($r, 'title');
    }

    public static function questionCounts(): array
    {
        static $c = null;
        if ($c === null) {
            $c = [];
            foreach (q_all("SELECT rumah_id, section, COUNT(*) n FROM questions WHERE status = 'published' GROUP BY rumah_id, section") as $row) {
                $c[(int) $row['rumah_id']][$row['section']] = (int) $row['n'];
            }
        }
        return $c;
    }

    public static function total(array $r): int
    {
        $c = self::questionCounts()[(int) $r['id']] ?? [];
        if ($r['kind'] === 'adaptive') {
            return 30;
        }
        return array_sum($c);
    }

    /** Ambang selesai: 30 soal unik (atau seluruh soal bila Rumah punya kurang dari 30 / simulasi). */
    public static function completionThreshold(array $r): int
    {
        $total = self::total($r);
        if ($r['kind'] === 'simulation') {
            return max(1, $total);
        }
        return max(1, min(30, $total ?: 30));
    }
}
