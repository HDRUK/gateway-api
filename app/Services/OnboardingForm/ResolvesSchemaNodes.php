<?php

namespace App\Services\OnboardingForm;

trait ResolvesSchemaNodes
{
    private function resolveNode(array $schema, array $node): array
    {
        if (isset($node['$ref'])) {
            $path = str_replace('/', '.', ltrim($node['$ref'], '#/'));

            return $this->resolveNode($schema, data_get($schema, $path, []));
        }

        $options = array_values(array_filter(
            $node['anyOf'] ?? $node['allOf'] ?? [],
            fn (array $option) => ($option['type'] ?? null) !== 'null',
        ));

        return count($options) === 1 ? $this->resolveNode($schema, $options[0]) : $node;
    }
}
