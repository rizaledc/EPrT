<?php
declare(strict_types=1);

final class Question
{
    public const JSON_FIELDS = ['options', 'underlines', 'why_wrong_id', 'why_wrong_en', 'rule_tags'];

    public static function hydrate(array $q): array
    {
        foreach (self::JSON_FIELDS as $f) {
            if (array_key_exists($f, $q)) {
                $q[$f] = json_col($q[$f], $f === 'underlines' ? null : []);
            }
        }
        if (isset($q['correct_index'])) {
            $q['correct_index'] = (int) $q['correct_index'];
        }
        return $q;
    }

    public static function find(int $id): ?array
    {
        $q = q_one('SELECT * FROM questions WHERE id = ?', [$id]);
        return $q ? self::hydrate($q) : null;
    }

    /** @return array<int,array> id => soal */
    public static function many(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($ids), '?'));
        $out = [];
        foreach (q_all("SELECT * FROM questions WHERE id IN ($in)", $ids) as $q) {
            $out[(int) $q['id']] = self::hydrate($q);
        }
        return $out;
    }

    public static function passages(array $questions): array
    {
        $pids = array_values(array_unique(array_filter(array_map(fn($q) => $q['passage_id'] ? (int) $q['passage_id'] : null, $questions))));
        if (!$pids) {
            return [];
        }
        $in = implode(',', array_fill(0, count($pids), '?'));
        $out = [];
        foreach (q_all("SELECT p.*, af.path AS audio_path FROM passages p LEFT JOIN audio_files af ON af.id = p.audio_talk_id WHERE p.id IN ($in)", $pids) as $p) {
            $p['script'] = json_col($p['script'], []);
            $out[(int) $p['id']] = $p;
        }
        return $out;
    }

    public static function render(array $q, ?int $chosen, ?array $passage = null): string
    {
        return partial('explain', ['q' => $q, 'chosen' => $chosen, 'passage' => $passage]);
    }
}
