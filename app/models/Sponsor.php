<?php
declare(strict_types=1);

final class Sponsor
{
    public static function all(): array
    {
        static $rows = null;
        if ($rows === null) {
            try {
                $rows = q_all('SELECT * FROM sponsors ORDER BY is_pinned DESC, sort_order ASC, id ASC');
            } catch (Throwable $e) {
                $rows = [];
            }
        }
        return $rows;
    }
}
