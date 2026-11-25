<?php
declare(strict_types=1);

/** Klien OpenRouter (chat completions). Kunci API dari app/config.php (openrouter.api_key). */
final class OpenRouter
{
    public const DEFAULT_MODEL = 'anthropic/claude-haiku-5.5';

    public static function configured(): bool
    {
        return trim((string) config('openrouter.api_key', '')) !== '';
    }

    public static function model(): string
    {
        $m = trim((string) setting('ai_model', ''));
        if ($m === '') {
            $m = trim((string) config('openrouter.model', '')) ?: self::DEFAULT_MODEL;
        }
        return $m;
    }

    /**
     * @return array{text:string,model:string,prompt_tokens:int,completion_tokens:int,cost:float}
     * @throws RuntimeException
     */
    public static function chat(array $messages, int $maxTokens = 1200): array
    {
        if (!self::configured()) {
            throw new RuntimeException('OpenRouter belum dikonfigurasi.');
        }
        $model = self::model();
        [$status, $json] = HttpClient::json('POST', 'https://openrouter.ai/api/v1/chat/completions', [
            'Authorization' => 'Bearer ' . config('openrouter.api_key'),
            'HTTP-Referer'  => base_url() ?: 'https://perumahaninggris.my.id',
            'X-Title'       => 'PerumahanInggris',
        ], [
            'model'                 => $model,
            'messages'              => $messages,
            'max_completion_tokens' => $maxTokens,
            'temperature'           => 0.4,
            'reasoning'             => ['effort' => 'low', 'exclude' => true],
            'usage'                 => ['include' => true],
        ], 60);
        if ($status < 200 || $status >= 300 || !is_array($json)) {
            $msg = is_array($json) ? (string) ($json['error']['message'] ?? '') : '';
            throw new RuntimeException('OpenRouter HTTP ' . $status . ($msg !== '' ? ': ' . mb_substr($msg, 0, 150) : ''));
        }
        $text = $json['choices'][0]['message']['content'] ?? '';
        if (is_array($text)) {
            $text = implode('', array_map(fn($p) => is_array($p) ? (string) ($p['text'] ?? '') : (string) $p, $text));
        }
        $text = trim((string) $text);
        if ($text === '') {
            throw new RuntimeException('OpenRouter mengembalikan jawaban kosong.');
        }
        $u = is_array($json['usage'] ?? null) ? $json['usage'] : [];
        return [
            'text'              => $text,
            'model'             => (string) ($json['model'] ?? $model),
            'prompt_tokens'     => (int) ($u['prompt_tokens'] ?? 0),
            'completion_tokens' => (int) ($u['completion_tokens'] ?? 0),
            'cost'              => (float) ($u['cost'] ?? 0),
        ];
    }
}
