<?php
declare(strict_types=1);

/** Merender teks reading: paragraf, **kata yang ditanyakan** dicetak tebal, nomor baris tiap paragraf. */
final class ReadingText
{
    public static function html(string $body): string
    {
        $paras = preg_split("/\n\s*\n/", trim(str_replace("\r", '', $body)));
        $out = '';
        foreach ($paras as $i => $p) {
            $s = e(preg_replace('/\s+/', ' ', trim($p)));
            $s = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $s);
            $out .= '<p>' . $s . '</p>';
        }
        return $out;
    }

    public static function wordCount(string $body): int
    {
        return str_word_count(preg_replace('/\*\*/', '', $body));
    }
}
