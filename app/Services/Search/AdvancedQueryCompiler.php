<?php

namespace App\Services\Search;

use InvalidArgumentException;

/**
 * Compiles an advanced search rule tree into a Typesense filter_by expression.
 *
 * The tree is a group of rules, optionally holding one level of nested groups:
 *
 *   {
 *     "match": "all" | "any",
 *     "rules": [
 *       { "field": "keywords", "op": "all", "values": ["asthma", "COPD"] },
 *       { "field": "isCohortDiscovery", "op": "is", "value": true },
 *       { "field": "ageRange", "op": "overlaps", "from": 60 },
 *       { "match": "any", "rules": [ ... ] }
 *     ]
 *   }
 *
 * Only fields listed in config('typesense.advanced_map.{type}') are accepted,
 * and each field only accepts the operators of its kind, so request input
 * never reaches filter_by as raw syntax. Any other shape is rejected with an
 * InvalidArgumentException rather than silently dropped, so a caller is never
 * shown results for a query other than the one they built.
 */
class AdvancedQueryCompiler
{
    public const MAX_DEPTH = 2;

    public const MAX_RULES = 12;

    public const MAX_VALUES = 25;

    private const MATCH_OPERATORS = ['all' => ' && ', 'any' => ' || '];

    private const KIND_OPERATORS = [
        'list' => ['any', 'all', 'none'],
        'boolean' => ['is'],
        'number' => ['between'],
        'overlap' => ['overlaps'],
    ];

    /**
     * @return string|null The bracketed expression, or null when the tree
     *                     holds no rules at all.
     */
    public function compile(string $type, ?array $tree): ?string
    {
        if ($tree === null || $tree === []) {
            return null;
        }

        $expression = $this->compileGroup($this->fieldsFor($type), $tree, 1);

        return $expression === null ? null : "({$expression})";
    }

    private function fieldsFor(string $type): array
    {
        return config("typesense.advanced_map.{$type}", []);
    }

    private function compileGroup(array $fields, mixed $group, int $depth): ?string
    {
        if (! is_array($group)) {
            throw new InvalidArgumentException('Each group must be an object.');
        }

        $match = $group['match'] ?? 'all';
        if (! is_string($match) || ! array_key_exists($match, self::MATCH_OPERATORS)) {
            throw new InvalidArgumentException("'match' must be 'all' or 'any'.");
        }

        $rules = $group['rules'] ?? [];
        if (! is_array($rules) || ! array_is_list($rules)) {
            throw new InvalidArgumentException("'rules' must be a list.");
        }
        if (count($rules) > self::MAX_RULES) {
            throw new InvalidArgumentException('A group may hold at most '.self::MAX_RULES.' rules.');
        }

        $parts = [];
        foreach ($rules as $rule) {
            if (is_array($rule) && array_key_exists('rules', $rule)) {
                if ($depth >= self::MAX_DEPTH) {
                    throw new InvalidArgumentException('Groups may be nested only '.(self::MAX_DEPTH - 1).' level deep.');
                }
                $nested = $this->compileGroup($fields, $rule, $depth + 1);
            } else {
                $nested = $this->compileRule($fields, $rule);
            }

            if ($nested !== null) {
                $parts[] = "({$nested})";
            }
        }

        return $parts === [] ? null : implode(self::MATCH_OPERATORS[$match], $parts);
    }

    private function compileRule(array $fields, mixed $rule): string
    {
        if (! is_array($rule)) {
            throw new InvalidArgumentException('Each rule must be an object.');
        }

        $field = $rule['field'] ?? null;
        if (! is_string($field) || ! array_key_exists($field, $fields)) {
            throw new InvalidArgumentException('Unknown advanced search field.');
        }

        $config = $fields[$field];
        $op = $rule['op'] ?? null;
        if (! is_string($op) || ! in_array($op, self::KIND_OPERATORS[$config['kind']], true)) {
            throw new InvalidArgumentException("Operator not supported for '{$field}'.");
        }

        return match ($config['kind']) {
            'list' => $this->compileList($field, $op, $rule['values'] ?? null),
            'boolean' => $this->compileBoolean($field, $rule['value'] ?? null),
            'number' => $this->compileRange($field, $field, $field, $rule),
            'overlap' => $this->compileRange($field, $config['max'], $config['min'], $rule),
        };
    }

    private function compileList(string $field, string $op, mixed $values): string
    {
        if (! is_array($values) || ! array_is_list($values)) {
            throw new InvalidArgumentException("'{$field}' needs a list of values.");
        }

        $quoted = collect($values)
            ->filter(fn ($value) => is_string($value) || is_int($value) || is_float($value))
            ->map(fn ($value) => trim(str_replace('`', '', (string) $value)))
            ->filter(fn (string $value) => $value !== '')
            ->unique()
            ->map(fn (string $value) => "`{$value}`")
            ->values();

        if ($quoted->isEmpty()) {
            throw new InvalidArgumentException("'{$field}' needs at least one value.");
        }
        if ($quoted->count() > self::MAX_VALUES) {
            throw new InvalidArgumentException("'{$field}' accepts at most ".self::MAX_VALUES.' values.');
        }

        return match ($op) {
            'any' => "{$field}:=[{$quoted->implode(',')}]",
            'none' => "{$field}:!=[{$quoted->implode(',')}]",
            // A list field holds every value only when each matches on its own.
            'all' => $quoted->map(fn (string $value) => "{$field}:={$value}")->implode(' && '),
        };
    }

    private function compileBoolean(string $field, mixed $value): string
    {
        if (! is_bool($value)) {
            throw new InvalidArgumentException("'{$field}' needs a true or false value.");
        }

        return $field.':='.($value ? 'true' : 'false');
    }

    /**
     * A bounded range: `from` is checked against $fromField and `to` against
     * $toField. For a plain number both are the field itself; for an
     * overlap the record's own range [min, max] must reach into [from, to],
     * so max is checked against `from` and min against `to`.
     */
    private function compileRange(string $field, string $fromField, string $toField, array $rule): string
    {
        $from = $this->bound($field, $rule['from'] ?? null);
        $to = $this->bound($field, $rule['to'] ?? null);

        if ($from === null && $to === null) {
            throw new InvalidArgumentException("'{$field}' needs a 'from' or 'to' value.");
        }
        if ($from !== null && $to !== null && $from > $to) {
            throw new InvalidArgumentException("'{$field}' has 'from' after 'to'.");
        }

        $parts = [];
        if ($from !== null) {
            $parts[] = "{$fromField}:>={$from}";
        }
        if ($to !== null) {
            $parts[] = "{$toField}:<={$to}";
        }

        return implode(' && ', $parts);
    }

    private function bound(string $field, mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (! is_numeric($value) || (int) $value != $value) {
            throw new InvalidArgumentException("'{$field}' bounds must be whole numbers.");
        }

        return (int) $value;
    }
}
