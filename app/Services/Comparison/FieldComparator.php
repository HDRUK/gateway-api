<?php

namespace App\Services\Comparison;

use App\Services\TypesenseService;

/**
 * Scores how alike two values of one field are, from 0 to 1, by a method that
 * suits the field's type. A value missing on either side is "missing", never
 * "different": not recording something says nothing about the datasets.
 *
 * Touches no database, so it is unit tested on its own. Stop words are the
 * search's own (TypesenseService::DEFAULT_STOPWORDS).
 */
class FieldComparator
{
    /** How the Gateway joins multi-valued strings ("Serum;,;DNA"). */
    public const DELIMITER = ';,;';

    /** @param array<string, float> $similar the score from which values are "similar", by FieldType value */
    public function __construct(private readonly array $similar)
    {
    }

    /** @return array<string, float> */
    public function thresholds(): array
    {
        return $this->similar;
    }

    /** Null, blank, an empty list, or a delimited string with nothing in it. 0 and false are values. */
    public static function isAbsent(mixed $value): bool
    {
        return match (true) {
            $value === null => true,
            is_string($value) => trim(str_replace(self::DELIMITER, '', $value)) === '',
            is_array($value) => array_filter($value, fn ($item) => ! self::isAbsent($item)) === [],
            default => false,
        };
    }

    /** @return array{outcome: Outcome, score: ?float} */
    public function compare(FieldType $type, mixed $a, mixed $b): array
    {
        $score = self::isAbsent($a) || self::isAbsent($b) ? null : match ($type) {
            FieldType::Text => $this->cosine((string) $a, (string) $b),
            FieldType::List => $this->jaccard($this->terms($a), $this->terms($b)),
            FieldType::Range => $this->overlap($a, $b),
            FieldType::Count => $this->ratio($a, $b),
            FieldType::Exact => $this->canonical($a) === $this->canonical($b) ? 1.0 : 0.0,
        };

        // A value that can't be read as its type (a "range" of "adults") is
        // no better a basis for a call than no value.
        if ($score === null) {
            return ['outcome' => Outcome::Missing, 'score' => null];
        }

        $score = round(max(0.0, min(1.0, $score)), 6);

        return [
            'outcome' => match (true) {
                $score >= 1.0 => Outcome::Identical,
                $score >= $this->similar[$type->value] => Outcome::Similar,
                default => Outcome::Different,
            },
            'score' => $score,
        ];
    }

    /** Cosine similarity of word counts, ignoring case, punctuation and stop words. */
    private function cosine(string $a, string $b): ?float
    {
        $left = $this->wordCounts($a);
        $right = $this->wordCounts($b);
        $norm = sqrt(array_sum(array_map(fn ($c) => $c ** 2, $left))) * sqrt(array_sum(array_map(fn ($c) => $c ** 2, $right)));

        return $norm > 0 ? array_sum(array_map(fn ($word, $count) => $count * ($right[$word] ?? 0), array_keys($left), $left)) / $norm : null;
    }

    /** @return array<string, int> */
    private function wordCounts(string $text): array
    {
        $words = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return array_count_values(array_diff($words, TypesenseService::DEFAULT_STOPWORDS));
    }

    /** Distinct, normalised items of a list given as an array or a delimited string. */
    private function terms(mixed $value): array
    {
        $items = is_array($value) ? $value : explode(self::DELIMITER, (string) $value);
        $items = array_map(fn ($item) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $item))), $items);

        return array_values(array_unique(array_filter($items, fn ($item) => $item !== '')));
    }

    private function jaccard(array $a, array $b): ?float
    {
        $union = count(array_unique(array_merge($a, $b)));

        return $union === 0 ? null : count(array_intersect($a, $b)) / $union;
    }

    /** Overlap of two "20-55" style ranges over their combined span. */
    private function overlap(mixed $a, mixed $b): ?float
    {
        $left = $this->interval($a);
        $right = $this->interval($b);
        if ($left === null || $right === null) {
            return null;
        }

        $span = max($left[1], $right[1]) - min($left[0], $right[0]);

        // A zero span is the same single point twice ("0-0": a birth cohort).
        return $span == 0 ? 1.0 : max(0.0, min($left[1], $right[1]) - max($left[0], $right[0])) / $span;
    }

    /** @return array{0: float, 1: float}|null */
    private function interval(mixed $value): ?array
    {
        if (! is_string($value) || ! preg_match('/^\s*(\d+(?:\.\d+)?)\s*[-\x{2013}]\s*(\d+(?:\.\d+)?)\s*$/u', $value, $m)) {
            return null;
        }
        $bounds = [(float) $m[1], (float) $m[2]];
        sort($bounds);

        return $bounds;
    }

    /** Smaller over larger. */
    private function ratio(mixed $a, mixed $b): ?float
    {
        $left = $this->number($a);
        $right = $this->number($b);
        if ($left === null || $right === null) {
            return null;
        }

        return max($left, $right) == 0 ? 1.0 : min($left, $right) / max($left, $right);
    }

    private function number(mixed $value): ?float
    {
        $value = is_string($value) ? preg_replace('/[,\s]/', '', $value) : $value;

        return is_numeric($value) ? abs((float) $value) : null;
    }

    private function canonical(mixed $value): string
    {
        return mb_strtolower(trim(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value));
    }
}
