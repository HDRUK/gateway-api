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
}
