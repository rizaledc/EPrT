<?php
declare(strict_types=1);

final class HomeController
{
    public function landing(): void
    {
        render('home/landing', [
            'title'       => '',
            'description' => t('meta.description'),
            'sponsors'    => Sponsor::all(),
            'demo'        => self::demoQuestion(),
            'scripts'     => ['landing'],
        ]);
    }

    public function glossary(): void
    {
        $terms = q_all('SELECT * FROM glossary ORDER BY sort, term_en');
        render('home/glossary', [
            'title'       => t('glossary.title'),
            'description' => t('glossary.meta'),
            'terms'       => $terms,
            'scripts'     => ['glossary'],
        ]);
    }

    public function setLang(string $lang): void
    {
        if (!in_array($lang, ['id', 'en'], true)) {
            abort(404);
        }
        setcookie('lang', $lang, [
            'expires'  => time() + 86400 * 365,
            'path'     => '/',
            'secure'   => is_https(),
            'httponly' => false,
            'samesite' => 'Lax',
        ]);
        $u = current_user();
        if ($u) {
            q('UPDATE users SET lang = ? WHERE id = ?', [$lang, $u['id']]);
        }
        redirect(safe_next((string) ($_GET['next'] ?? '/'), '/'));
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        if (NOINDEX) {
            echo "# Situs belum rilis (config noindex = true)\nUser-agent: *\nDisallow: /\n";
            exit;
        }
        echo "User-agent: *\nDisallow: /admin\nDisallow: /api/\nDisallow: /install\nDisallow: /hasil/\nDisallow: /rapor/\n\nSitemap: " . abs_url('sitemap.xml') . "\n";
        exit;
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');
        $urls = ['/', '/coba', '/kamus', '/daftar', '/masuk'];
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
        foreach ($urls as $u) {
            $loc = e(abs_url($u));
            echo "  <url><loc>$loc</loc>"
               . "<xhtml:link rel=\"alternate\" hreflang=\"id\" href=\"$loc?lang=id\"/>"
               . "<xhtml:link rel=\"alternate\" hreflang=\"en\" href=\"$loc?lang=en\"/></url>\n";
        }
        echo '</urlset>';
        exit;
    }

    /**
     * Soal demo landing (Error Recognition has retain), berjalan di sisi klien.
     * Teks pembahasan diambil dari soal yang sama di Rumah 4 agar hanya ada satu versi.
     */
    public static function demoQuestion(): array
    {
        $prompt = 'Although {A}numerous{/A} renovations were made, the old library {B}has retain{/B} its {C}classically{/C} simple {D}style{/D}.';
        $q = [
            'id' => 0, 'section' => 'structure', 'type' => 'structure_error', 'prompt' => $prompt,
            'options' => ['numerous', 'has retain', 'classically', 'style'], 'correct_index' => 1, 'rule_tags' => ['S-PERFECT-V3'],
            'key_sentence' => null, 'key_translation_id' => null, 'liaison_note' => null, 'evidence_quote' => null,
        ];
        $file = dirname(__DIR__) . '/database/seed/r04.json';
        $data = is_file($file) ? json_decode((string) file_get_contents($file), true) : null;
        foreach ($data['items'] ?? [] as $it) {
            if (($it['prompt'] ?? '') === $prompt) {
                foreach (['why_correct_id', 'why_correct_en', 'why_wrong_id', 'why_wrong_en', 'analogy_id', 'analogy_en', 'tip_id', 'tip_en', 'rule_tags'] as $f) {
                    $q[$f] = $it[$f] ?? ($q[$f] ?? null);
                }
                return $q;
            }
        }
        // Cadangan bila file seed tidak ada di server
        return $q + [
            'why_correct_id' => 'Cari dulu pelakunya: **the old library**. Terus cari kata kerjanya: **has retain**. Nah, ini biang keroknya. Habis **has**, kata kerjanya harus bentuk ketiga (V3). Jadi yang bener **has retained**.',
            'why_correct_en' => 'Find the subject first: **the old library**. Then the verb: **has retain**. That is the culprit. After **has**, the verb has to be V3. So it should be **has retained**.',
            'why_wrong_id' => ['0' => '**numerous** itu adjective (kata sifat) yang nemenin **renovations**. Aman, nggak ada yang salah.', '2' => '**classically** itu adverb (kata keterangan) yang nemenin **simple**. Ini juga aman.', '3' => '**style** itu kata benda setelah kata sifat **simple**. Pasangannya pas.'],
            'why_wrong_en' => ['0' => '**numerous** is an adjective going with **renovations**. Nothing wrong there.', '2' => '**classically** is an adverb going with **simple**. Also fine.', '3' => '**style** is a noun after the adjective **simple**. Good pair.'],
            'analogy_id' => 'Anggap aja **has** itu kernet yang cuma mau naik bus jurusan V3. Kata kerjanya masih V1? Ya ditolak, nggak boleh naik.',
            'analogy_en' => 'Think of **has** as a conductor who only rides the V3 bus. Verb still in V1? Rejected, no boarding.',
            'tip_id' => 'Tiap ketemu **has**, **have**, atau **had**, langsung lirik kata kerja sesudahnya. Udah V3 belum?',
            'tip_en' => 'Every time you spot **has**, **have**, or **had**, glance at the next verb. Is it V3 yet?',
        ];
    }
}
