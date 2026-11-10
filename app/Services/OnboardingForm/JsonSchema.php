<?php

namespace App\Services\OnboardingForm;

final class JsonSchema
{
    public static function resolveNode(array $schema, array $node): array
    {
        if (isset($node['$ref'])) {
            $path = str_replace('/', '.', ltrim($node['$ref'], '#/'));

            return self::resolveNode($schema, data_get($schema, $path, []));
        }

        $options = array_values(array_filter(
            $node['anyOf'] ?? $node['allOf'] ?? [],
            fn (array $option) => ($option['type'] ?? null) !== 'null',
        ));

        return count($options) === 1 ? self::resolveNode($schema, $options[0]) : $node;
    }

    /** A required list of values left empty; a required list of records may be. */
    public static function hasEmptyRequiredValueList(array $schema, array $node, mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }
        $node = self::resolveNode($schema, $node);

        $required = $node['required'] ?? [];
        foreach ($node['properties'] ?? [] as $key => $property) {
            $childValue = $value[$key] ?? null;
            $child = self::optionFor($schema, self::resolveNode($schema, $property), $childValue);
            if (
                $childValue === []
                && in_array($key, $required, true)
                && self::isList($child)
                && !self::isRecord(self::resolveNode($schema, $child['items'] ?? []))
            ) {
                return true;
            }
            if (self::hasEmptyRequiredValueList($schema, $child, $childValue)) {
                return true;
            }
        }

        return false;
    }

    private static function optionFor(array $schema, array $node, mixed $value): array
    {
        if (!is_array($value) || !array_is_list($value) || self::isList($node)) {
            return $node;
        }
        foreach ($node['anyOf'] ?? $node['allOf'] ?? [] as $option) {
            $option = self::resolveNode($schema, $option);
            if (self::isList($option)) {
                return $option;
            }
        }

        return $node;
    }

    private static function isList(array $node): bool
    {
        return ($node['type'] ?? null) === 'array' || isset($node['items']);
    }

    private static function isRecord(array $node): bool
    {
        return isset($node['properties']) || ($node['type'] ?? null) === 'object';
    }
}
