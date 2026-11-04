<?php
declare(strict_types=1);

final class RuleTag
{
    public static function map(): array
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            try {
                foreach (q_all('SELECT * FROM rule_tags ORDER BY section, code') as $r) {
                    $map[$r['code']] = $r;
                }
            } catch (Throwable $e) {
            }
        }
        return $map;
    }
}
