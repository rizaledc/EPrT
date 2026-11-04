<?php
declare(strict_types=1);

final class Speaker
{
    /** Kode speaker: N (narator), M1..M4 (pria), W1..W4 (wanita), LEC (dosen) */
    public static function label(string $code): string
    {
        if ($code === 'N') {
            return t('speaker.narrator');
        }
        if ($code === 'LEC' || str_starts_with($code, 'LEC')) {
            return t('speaker.lecturer');
        }
        if ($code[0] === 'M') {
            return t('speaker.man');
        }
        if ($code[0] === 'W') {
            return t('speaker.woman');
        }
        return $code;
    }

    public static function gender(string $code): string
    {
        if ($code === 'N' || $code[0] === 'M') {
            return 'm';
        }
        if ($code[0] === 'W') {
            return 'f';
        }
        return 'm';
    }
}
