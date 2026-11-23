<?php

namespace Tests\Unit\Services\OnboardingForm;

use App\Services\OnboardingForm\JsonSchema;
use PHPUnit\Framework\TestCase;

class JsonSchemaTest extends TestCase
{
    public function test_follows_a_ref(): void
    {
        $schema = ['$defs' => ['Summary' => ['type' => 'object']]];

        $this->assertSame(
            ['type' => 'object'],
            JsonSchema::resolveNode($schema, ['$ref' => '#/$defs/Summary'])
        );
    }

    public function test_unwraps_a_nullable_option(): void
    {
        $schema = ['$defs' => ['Summary' => ['type' => 'object']]];

        $this->assertSame(
            ['type' => 'object'],
            JsonSchema::resolveNode($schema, ['anyOf' => [['$ref' => '#/$defs/Summary'], ['type' => 'null']]])
        );
    }

    public function test_keeps_a_node_with_several_options(): void
    {
        $node = ['anyOf' => [['type' => 'string'], ['type' => 'integer']]];

        $this->assertSame($node, JsonSchema::resolveNode([], $node));
    }

    public function test_finds_an_empty_required_list_of_values(): void
    {
        $schema = [
            'properties' => ['standards' => ['$ref' => '#/$defs/Standards']],
            '$defs' => [
                'Standards' => [
                    'type' => 'object',
                    'required' => ['language'],
                    'properties' => [
                        'language' => [
                            'anyOf' => [['type' => 'array', 'items' => ['enum' => ['en']]], ['type' => 'null']],
                        ],
                    ],
                ],
            ],
        ];

        $this->assertTrue(
            JsonSchema::hasEmptyRequiredValueList($schema, $schema, ['standards' => ['language' => []]])
        );
    }

    public function test_finds_an_empty_required_list_of_values_among_several_options(): void
    {
        $schema = [
            'required' => ['spatial'],
            'properties' => [
                'spatial' => [
                    'anyOf' => [['type' => 'string'], ['type' => 'array', 'items' => ['type' => 'string']]],
                ],
            ],
        ];

        $this->assertTrue(JsonSchema::hasEmptyRequiredValueList($schema, $schema, ['spatial' => []]));
    }

    public function test_allows_an_empty_required_list_of_records(): void
    {
        $schema = [
            'required' => ['observations'],
            'properties' => [
                'observations' => [
                    'type' => 'array',
                    'items' => ['type' => 'object', 'properties' => ['name' => ['type' => 'string']]],
                ],
            ],
        ];

        $this->assertFalse(JsonSchema::hasEmptyRequiredValueList($schema, $schema, ['observations' => []]));
    }
}
