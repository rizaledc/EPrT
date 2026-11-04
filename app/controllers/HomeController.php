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

    /** Soal demo landing (Error Recognition "has retain"), berjalan di sisi klien. */
    public static function demoQuestion(): array
    {
        return [
            'id' => 0,
            'section' => 'structure',
            'type' => 'structure_error',
            'prompt' => 'Although {A}numerous{/A} renovations were made, the old library {B}has retain{/B} its {C}classically{/C} simple {D}style{/D}.',
            'options' => ['numerous', 'has retain', 'classically', 'style'],
            'correct_index' => 1,
            'rule_tags' => ['S-PERFECT-V3'],
            'why_correct_id' => "Langkah 1: cari subject (pelaku) induk kalimat, yaitu 'the old library'. Langkah 2: cari verb (kata kerja)-nya: 'has retain'. Setelah auxiliary (kata kerja bantu) have/has, main verb (kata kerja utama) wajib berbentuk V3 (bentuk ketiga). Jadi 'has retain' harus menjadi **has retained**.",
            'why_correct_en' => "Step 1: find the main subject, 'the old library'. Step 2: find its verb, 'has retain'. After the auxiliary have/has, the main verb must be in its past participle (V3) form, so 'has retain' should be **has retained**.",
            'why_wrong_id' => [
                '0' => "'numerous' (banyak) adalah adjective (kata sifat) yang menerangkan noun (kata benda) 'renovations'. Sudah benar.",
                '2' => "'classically' adalah adverb (kata keterangan) yang menerangkan adjective 'simple'. Adverb boleh menerangkan adjective, jadi benar.",
                '3' => "'style' adalah noun setelah adjective 'simple'. Polanya adjective + noun, sudah benar.",
            ],
            'why_wrong_en' => [
                '0' => "'numerous' is an adjective describing the noun 'renovations'. It is correct.",
                '2' => "'classically' is an adverb modifying the adjective 'simple'. Adverbs can modify adjectives, so it is correct.",
                '3' => "'style' is a noun following the adjective 'simple'. Adjective + noun is the right pattern.",
            ],
            'analogy_id' => 'have/has itu seperti kernet yang hanya mau naik bus jurusan V3. Kalau kata kerjanya masih V1 (retain), kernetnya menolak ikut.',
            'analogy_en' => "Think of have/has as a conductor who only boards the 'V3 bus'. If the verb is still in V1 (retain), the conductor refuses to get on.",
            'tip_id' => 'Lihat have/has/had? Langsung cek kata kerja sesudahnya, apakah sudah V3.',
            'tip_en' => 'Spot have/has/had? Check right away that the next verb is V3.',
            'key_sentence' => null, 'key_translation_id' => null, 'liaison_note' => null, 'evidence_quote' => null,
        ];
    }
}
