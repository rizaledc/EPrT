<?php
declare(strict_types=1);

/**
 * Klien HTTP JSON kecil (cURL) untuk OpenRouter dan Mayar.
 * Untuk pengujian, HttpClient::$fake dapat diisi callable(method, url, headers, body): [status, array|null].
 */
final class HttpClient
{
    /** @var null|callable(string,string,array,?array):array */
    public static $fake = null;

    /**
     * @return array{0:int,1:?array} [status HTTP, isi JSON terdekode atau null]
     * @throws RuntimeException bila koneksi gagal
     */
    public static function json(string $method, string $url, array $headers = [], ?array $body = null, int $timeout = 20): array
    {
        if (self::$fake) {
            return (self::$fake)($method, $url, $headers, $body);
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('Ekstensi cURL tidak tersedia.');
        }
        $h = ['Accept: application/json'];
        foreach ($headers as $k => $v) {
            $h[] = $k . ': ' . $v;
        }
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
        ];
        if ($body !== null) {
            $h[] = 'Content-Type: application/json';
            $opts[CURLOPT_POSTFIELDS] = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $opts[CURLOPT_HTTPHEADER] = $h;
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($raw === false) {
            throw new RuntimeException('Koneksi gagal: ' . $err);
        }
        $data = json_decode((string) $raw, true);
        return [$status, is_array($data) ? $data : null];
    }
}
