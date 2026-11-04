<?php
declare(strict_types=1);

/** Halaman legal & informasi untuk verifikasi payment gateway: syarat, privasi, refund, kontak, harga. */
final class LegalController
{
    /** Tingkat harga Premium berdasarkan urutan pembeli. */
    public const PRICE_TIERS = [
        ['from' => 1, 'to' => 100, 'price' => 9999],
        ['from' => 101, 'to' => 300, 'price' => 14999],
        ['from' => 301, 'to' => null, 'price' => 19999],
    ];

    public static function content(): array
    {
        static $all = null;
        $all ??= require APP_PATH . '/content/legal.php';
        return $all[active_lang()] ?? $all['id'];
    }

    /** Kontak admin dari config; nilai bawaan hanya placeholder. */
    public static function contact(): array
    {
        $email = (string) config('contact.email', 'admin@perumahaninggris.my.id');
        $wa = (string) config('contact.whatsapp', '+62 812-0000-0000');
        return ['email' => $email, 'wa' => $wa, 'wa_link' => 'https://wa.me/' . preg_replace('/\D/', '', $wa)];
    }

    /** Ubah nama halaman di teks legal menjadi link (setelah di-escape). */
    public static function linkify(string $text): string
    {
    $s = e($text);
    $map = [
        'halaman Kontak' => 'kontak', 'Contact page' => 'kontak',
        'halaman Akun' => 'akun', 'Account page' => 'akun',
        'halaman Harga' => 'harga', 'Pricing page' => 'harga',
        'halaman Pengembalian Dana' => 'pengembalian-dana', 'Refund Policy page' => 'pengembalian-dana',
        'Syarat &amp; Ketentuan' => 'syarat-ketentuan', 'Terms &amp; Conditions' => 'syarat-ketentuan',
    ];
    foreach ($map as $label => $path) {
        $s = str_replace($label, '<a href="' . e(url($path)) . '">' . $label . '</a>', $s);
    }
    return $s;
    }

    public static function rupiah(int $n): string
    {
        return 'Rp' . number_format($n, 0, ',', '.');
    }

    /** Indeks tingkat harga yang berlaku sekarang (berdasarkan jumlah pembeli Premium). */
    public static function currentTier(): int
    {
        $sold = (int) setting('premium_sold', 0);
        foreach (self::PRICE_TIERS as $i => $t) {
            if ($t['to'] === null || $sold < $t['to']) {
                return $i;
            }
        }
        return count(self::PRICE_TIERS) - 1;
    }

    private function page(string $key): void
    {
        $c = self::content();
        render('legal/page', ['title' => $c[$key]['title'], 'description' => $c[$key]['lead'], 'page' => $c[$key], 'updated' => $c['updated']]);
    }

    public function terms(): void
    {
        $this->page('terms');
    }

    public function privacy(): void
    {
        $this->page('privacy');
    }

    public function refund(): void
    {
        $this->page('refund');
    }

    public function contactPage(): void
    {
        $c = self::content()['contact'];
        render('legal/contact', ['title' => $c['title'], 'description' => $c['lead'], 'c' => $c, 'contact' => self::contact()]);
    }

    public function pricing(): void
    {
        $c = self::content()['pricing'];
        render('legal/pricing', ['title' => $c['title'], 'description' => $c['lead'], 'c' => $c, 'tiers' => self::PRICE_TIERS, 'current' => self::currentTier()]);
    }
}
