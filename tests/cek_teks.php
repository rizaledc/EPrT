<?php
declare(strict_types=1);
/**
 * Pemeriksa teks statis:
 *   1. tanda kutip penanda;
 *   2. istilah lama Rumah/Blok/House/Block (kecuali merek PerumahanInggris).
 *
 * Aturan: teks yang dilihat pengguna tidak boleh memakai " “ ” ‘ atau ' ’ untuk mengutip,
 * menandai, atau menegaskan kata. Kata dari soal ditulis **tebal**.
 * Apostrof di DALAM kata (I'm, didn't, professor's) adalah ejaan, jadi boleh:
 * ' atau ’ yang diapit dua huruf tidak dihitung.
 * Pengecualian: teks soal berbahasa Inggris (prompt, opsi, script, teks reading,
 * key_sentence, evidence_quote, judul teks reading).
 *
 * Pemakaian: php tests/cek_teks.php [--verbose]
 * Keluar dengan kode 1 jika ada temuan.
 */

$root = dirname(__DIR__);
$verbose = in_array('--verbose', $argv, true);
$found = [];

/** Kembalikan potongan teks yang memakai tanda kutip sebagai penanda. */
function quote_hits(string $s): array
{
    $hits = [];
    $len = mb_strlen($s);
    for ($i = 0; $i < $len; $i++) {
        $c = mb_substr($s, $i, 1);
        if (!in_array($c, ['"', '“', '”', '‘', "'", '’'], true)) {
            continue;
        }
        if ($c === "'" || $c === '’') {
            $prev = $i > 0 ? mb_substr($s, $i - 1, 1) : '';
            $next = $i + 1 < $len ? mb_substr($s, $i + 1, 1) : '';
            if (preg_match('/\p{L}/u', $prev) && preg_match('/\p{L}/u', $next)) {
                continue; // apostrof di dalam kata
            }
        }
        $hits[] = trim(mb_substr($s, max(0, $i - 25), 50));
    }
    return $hits;
}

function term_hits(string $s): array
{
    preg_match_all('/\b(rumah|blok|houses?|blocks?)\b/iu', $s, $m);
    return $m[0];
}

function report(string $area, string $where, string $text): void
{
    global $found;
    foreach (term_hits($text) as $t) {
        $found['istilah'][] = "$area · $where: $t → …" . str_replace("\n", ' ', mb_substr($text, 0, 60)) . '…';
        break;
    }
    $hits = quote_hits($text);
    if ($hits) {
        // Satu temuan per teks (bukan per karakter)
        $found[$area][] = "$where: …" . str_replace("\n", ' ', $hits[0]) . '…';
    }
}

/* ---------- 1. File bahasa ---------- */
foreach (glob("$root/app/lang/*.php") as $file) {
    $strings = require $file;
    $walk = function ($v, string $k) use (&$walk, $file) {
        if (is_array($v)) {
            foreach ($v as $kk => $vv) {
                $walk($vv, "$k.$kk");
            }
        } elseif (is_string($v)) {
            report('lang', basename($file) . " [$k]", $v);
        }
    };
    foreach ($strings as $k => $v) {
        $walk($v, (string) $k);
    }
}

/* ---------- 2. View (teks di luar PHP dan tag HTML, plus atribut yang terlihat) ---------- */
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/app/views"));
foreach ($rii as $f) {
    if (!$f->isFile() || $f->getExtension() !== 'php') {
        continue;
    }
    $src = file_get_contents($f->getPathname());
    $rel = substr($f->getPathname(), strlen($root) + 1);
    // Atribut yang terlihat pengguna dengan nilai literal
    if (preg_match_all('/\b(?:title|placeholder|alt|aria-label|data-confirm|value)="([^"<]*)"/u', $src, $m)) {
        foreach ($m[1] as $v) {
            report('views', $rel . ' (atribut)', $v);
        }
    }
    $txt = preg_replace('/<\?(?:php|=).*?\?>/s', ' ', $src);
    $txt = preg_replace('/<(script|style|code)\b.*?<\/\1>/s', ' ', $txt);
    $txt = preg_replace('/<!--.*?-->/s', ' ', $txt);
    $txt = preg_replace('/<[^>]*>/s', "\n", $txt);
    foreach (explode("\n", $txt) as $n => $line) {
        if (trim($line) !== '') {
            report('views', $rel, $line);
        }
    }
    // String literal PHP yang dicetak langsung: e('...') atau echo '...'
    if (preg_match_all('/\be\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'\s*\)/u', $src, $m2)) {
        foreach ($m2[1] as $v) {
            report('views', $rel . ' (e literal)', stripslashes($v));
        }
    }
}

/* ---------- 3. JavaScript (isi string literal yang berupa teks) ---------- */
foreach (glob("$root/public_html/assets/js/*.js") as $file) {
    $src = file_get_contents($file);
    $src = preg_replace('~/\*.*?\*/~s', '', $src);
    $src = preg_replace('~(^|[^:\\\\])//[^\n]*~', '$1', $src);
    if (preg_match_all('/\'((?:[^\'\\\\\n]|\\\\.)*)\'|"((?:[^"\\\\\n]|\\\\.)*)"|`((?:[^`\\\\]|\\\\.)*)`/u', $src, $m, PREG_SET_ORDER)) {
        foreach ($m as $mm) {
            $v = $mm[1] !== '' ? $mm[1] : ($mm[2] ?? '');
            if ($v === '' && isset($mm[3])) {
                $v = $mm[3];
            }
            $v = stripslashes($v);
            // Buang markup & selektor: atribut HTML dan [attr="x"] bukan teks pengguna
            $v = preg_replace('/<[^>]*>/', ' ', $v);
            $v = preg_replace('/\[[^\]]*\]/', ' ', $v);
            $v = preg_replace('/\$\{[^}]*\}/', ' ', $v);
            if (!preg_match('/\p{L}{2,}\s+\p{L}{2,}/u', $v)) {
                continue; // bukan kalimat
            }
            report('js', basename($file), $v);
        }
    }
}

/* ---------- 4. Seed: pembahasan, materi, kamus, aturan, petunjuk, rumah ---------- */
$exempt = ['prompt', 'options', 'script', 'body', 'key_sentence', 'evidence_quote', 'title', 'underlines', 'speaker', 'text', 'audio'];
foreach (glob("$root/app/database/seed/*.json") as $file) {
    $data = json_decode(file_get_contents($file), true);
    $base = basename($file);
    $walk = function ($v, string $path, bool $inQuestionText) use (&$walk, $base, $exempt) {
        if (is_array($v)) {
            foreach ($v as $k => $x) {
                $skip = $inQuestionText || (is_string($k) && in_array($k, $exempt, true));
                // Judul Rumah & nama tetap dicek; 'title' hanya dikecualikan untuk teks reading Inggris
                if ($k === 'title' && $base !== 'directions.json' && !preg_match('/^r\d\d|contoh/', $base)) {
                    $skip = $inQuestionText;
                }
                $walk($x, $path === '' ? (string) $k : "$path.$k", $skip);
            }
        } elseif (is_string($v) && !$inQuestionText) {
            report('seed', "$base [$path]", $v);
        }
    };
    $walk($data, '', false);
}

/* ---------- 5. Template PDF & pesan dari service/controller ---------- */
foreach (array_merge(glob("$root/app/services/*.php"), glob("$root/app/controllers/*.php")) as $file) {
    $src = file_get_contents($file);
    // Pesan yang dikirim ke pengguna: flash(), $errors[], throw new …Exception
    if (preg_match_all('/(?:flash\(\s*\'\w+\'\s*,\s*|errors\[[^\]]*\]\s*=\s*|Exception\()\s*\'((?:[^\'\\\\]|\\\\.)*)\'/u', $src, $m)) {
        foreach ($m[1] as $v) {
            report('pdf & pesan server', basename($file), stripslashes($v));
        }
    }
    if (preg_match_all('/<(?:p|h\d|td|th|div|span|li)[^>]*>([^<{$]+)</u', $src, $m)) {
        foreach ($m[1] as $v) {
            if (str_contains($v, "' .") || str_contains($v, ". '")) {
                continue; // potongan sintaks PHP, bukan teks
            }
            report('pdf & pesan server', basename($file) . ' (html)', $v);
        }
    }
}

/* ---------- Hasil ---------- */
$total = 0;
foreach (['lang', 'views', 'js', 'seed', 'pdf & pesan server', 'istilah'] as $area) {
    $n = count($found[$area] ?? []);
    $total += $n;
    printf("%-20s %d\n", $area, $n);
    if ($n && ($verbose || $n <= 40)) {
        foreach ($found[$area] as $line) {
            echo "   $line\n";
        }
    }
}
echo $total ? "GAGAL: $total temuan.\n" : "LULUS: tidak ada tanda kutip penanda maupun istilah Rumah/Blok.\n";
exit($total ? 1 : 0);
