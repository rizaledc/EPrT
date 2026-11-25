<?php
declare(strict_types=1);

/**
 * Mayar Headless API V2 (payment request).
 * Produksi: https://api.mayar.id/hl/v2 · Sandbox: https://api.mayar.io/hl/v2
 * Kunci API dibaca dari app/config.php (mayar.api_key), tidak pernah dari repo.
 */
final class Mayar
{
    public static function configured(): bool
    {
        return trim((string) config('mayar.api_key', '')) !== '';
    }

    public static function sandbox(): bool
    {
        return (bool) config('mayar.sandbox', true);
    }

    private static function base(): string
    {
        return self::sandbox() ? 'https://api.mayar.io/hl/v2' : 'https://api.mayar.id/hl/v2';
    }

    /** @return array isi data dari respons */
    private static function call(string $method, string $path, ?array $body = null): array
    {
        if (!self::configured()) {
            throw new RuntimeException('Mayar belum dikonfigurasi.');
        }
        [$status, $json] = HttpClient::json($method, self::base() . $path, ['Authorization' => 'Bearer ' . config('mayar.api_key')], $body, 20);
        if ($status < 200 || $status >= 300 || !is_array($json)) {
            $msg = is_array($json) ? (string) ($json['messages'] ?? $json['message'] ?? '') : '';
            throw new RuntimeException('Mayar HTTP ' . $status . ($msg !== '' ? ': ' . mb_substr($msg, 0, 150) : ''));
        }
        $data = $json['data'] ?? null;
        return is_array($data) ? $data : [];
    }

    /**
     * Buat payment request.
     * @return array{id:string,transactionId:string,link:string}
     */
    public static function createPayment(string $name, int $amount, string $description, DateTimeInterface $expiredAt, array $extra = []): array
    {
        $data = self::call('POST', '/payments/create', [
            'name'        => $name,
            'amount'      => $amount,
            'description' => $description,
            'expiredAt'   => $expiredAt->format('Y-m-d\TH:i:s.vP'),
            'extraData'   => (object) $extra,
        ]);
        $id = (string) ($data['id'] ?? '');
        $link = (string) ($data['link'] ?? '');
        if ($id === '' || !preg_match('#^https://#', $link)) {
            throw new RuntimeException('Respons Mayar tidak lengkap.');
        }
        return ['id' => $id, 'transactionId' => (string) ($data['transactionId'] ?? ''), 'link' => $link];
    }

    /** @return array{status:string,amount:int} status: unpaid, paid, closed */
    public static function getPayment(string $id): array
    {
        $data = self::call('GET', '/payments/' . rawurlencode($id));
        return ['status' => strtolower((string) ($data['status'] ?? '')), 'amount' => (int) ($data['amount'] ?? 0)];
    }
}
