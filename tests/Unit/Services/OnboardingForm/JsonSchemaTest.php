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
}
