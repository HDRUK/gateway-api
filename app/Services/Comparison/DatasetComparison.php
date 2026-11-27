<?php

namespace App\Services\Comparison;

use Illuminate\Support\Arr;

/**
 * Compares datasets' GWDM `metadata` blocks field by field. The first dataset
 * is the reference: each field, keyed by dotted path, comes back with every
 * dataset's value and, for each of the others, a call against the reference,
 * so a client can render them side by side and tint each cell. Fields come in
 * the order the datasets first record them.
 */
class DatasetComparison
{
    /**
     * Not compared as fields: variables have their own comparison, `required`
     * is bookkeeping (ids, revisions) that differs for every pair, and
     * `linkage` points at other datasets.
     */
    private const SKIPPED_SECTIONS = ['required', 'structuralMetadata', 'linkage'];

    /** Free text is told from a short label by its length in words. */
    private const TEXT_MIN_WORDS = 6;

    public function __construct(private readonly FieldComparator $comparator)
    {
    }

    public function compare(array $reference, array ...$others): array
    {
        $flattened = array_map($this->flatten(...), [$reference, ...$others]);

        $fields = [];

        foreach (array_keys(array_merge(...$flattened)) as $path) {
            // Null for a dataset that doesn't record the field.
            $values = array_map(
                fn (array $fieldsOf) => FieldComparator::isAbsent($fieldsOf[$path] ?? null) ? null : $fieldsOf[$path],
                $flattened,
            );
            // No dataset says anything about this field.
            if (array_filter($values, fn ($value) => $value !== null) === []) {
                continue;
            }

            $type = $this->typeFor($values);

            $fields[$path] = [
                'type' => $type->value,
                'values' => $values,
                // The reference has no call of its own; every other dataset is
                // compared with it.
                'calls' => [null, ...array_map(function ($value) use ($type, $values) {
                    $result = $this->comparator->compare($type, $values[0], $value);

                    return ['outcome' => $result['outcome']->value, 'score' => $result['score']];
                }, array_slice($values, 1))],
            ];
        }

        return ['fields' => $fields, 'thresholds' => $this->comparator->thresholds()];
    }

    /**
     * The metadata as [dotted path => value]. List positions drop out of the
     * path, so tissuesSampleCollection[].materialType is one list field.
     */
    private function flatten(array $metadata): array
    {
        $fields = [];

        foreach (Arr::dot(Arr::except($metadata, self::SKIPPED_SECTIONS)) as $dotted => $value) {
            $path = preg_replace('/\.\d+(?=\.|$)/', '', $dotted, -1, $indexed);
            if ($indexed) {
                $fields[$path][] = $value;
            } else {
                $fields[$path] = $value;
            }
        }

        return $fields;
    }

    /**
     * The type is read from the values, not a fixed field list, so any GWDM
     * version or partner's extra fields compare too. When the datasets disagree
     * the wider type wins: "DDI" against "DDI;,;Local" is a list, not a mismatch.
     */
    private function typeFor(array $values): FieldType
    {
        $types = array_map($this->infer(...), array_values(array_filter($values, fn ($v) => $v !== null)));

        foreach ([FieldType::List, FieldType::Text] as $wider) {
            if (in_array($wider, $types, true)) {
                return $wider;
            }
        }

        return $types[0];
    }

    private function infer(mixed $value): FieldType
    {
        return match (true) {
            is_bool($value) => FieldType::Exact,
            is_int($value), is_float($value) => FieldType::Count,
            is_array($value) => FieldType::List,
            str_contains((string) $value, FieldComparator::DELIMITER) => FieldType::List,
            (bool) preg_match('/^\s*\d+(?:\.\d+)?\s*[-\x{2013}]\s*\d+(?:\.\d+)?\s*$/u', (string) $value) => FieldType::Range,
            str_word_count((string) $value) >= self::TEXT_MIN_WORDS => FieldType::Text,
            default => FieldType::Exact,
        };
    }
}
