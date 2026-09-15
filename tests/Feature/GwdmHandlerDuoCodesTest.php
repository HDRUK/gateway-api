<?php

namespace Tests\Feature;

use App\Services\Gwdm\GwdmHandlerFactory;
use Tests\TestCase;

/**
 * duoCodes (GA4GH Data Use Ontology) are a GWDM 2.2 addition, introduced
 * alongside HDRUK schema 4.1.0. Only the 2.2 handler surfaces them to the
 * Typesense document; earlier versions have no such field.
 */
class GwdmHandlerDuoCodesTest extends TestCase
{
    protected bool $shouldFakeQueue = false;

    private function factory(): GwdmHandlerFactory
    {
        return new GwdmHandlerFactory();
    }

    private function envelope(mixed $duoCodes): array
    {
        return [
            'gwdmVersion' => '2.2',
            'metadata' => [
                'accessibility' => [
                    'usage' => ['duoCodes' => $duoCodes],
                ],
            ],
        ];
    }

    public function test_2_2_handler_extracts_duo_codes_from_delimited_string(): void
    {
        $fields = $this->factory()->resolve('2.2')
            ->toSearchableFields($this->envelope('DUO:0000042;,;DUO:0000021'));

        $this->assertSame(['DUO:0000042', 'DUO:0000021'], $fields['duoCodes']);
    }

    public function test_2_2_handler_extracts_duo_codes_from_array(): void
    {
        $fields = $this->factory()->resolve('2.2')
            ->toSearchableFields($this->envelope(['DUO:0000042', 'DUO:0000021']));

        $this->assertSame(['DUO:0000042', 'DUO:0000021'], $fields['duoCodes']);
    }

    public function test_2_2_handler_returns_empty_list_when_duo_codes_absent(): void
    {
        $fields = $this->factory()->resolve('2.2')->toSearchableFields([
            'gwdmVersion' => '2.2',
            'metadata' => ['accessibility' => ['usage' => []]],
        ]);

        $this->assertSame([], $fields['duoCodes']);
    }

    public function test_2_2_handler_still_returns_the_shared_2x_fields(): void
    {
        $fields = $this->factory()->resolve('2.2')->toSearchableFields([
            'gwdmVersion' => '2.2',
            'metadata' => [
                'summary' => ['title' => 'A Dataset', 'keywords' => 'cancer;,;imaging'],
                'accessibility' => ['usage' => ['duoCodes' => ['DUO:0000042']]],
            ],
        ]);

        $this->assertSame(['cancer', 'imaging'], $fields['keywords']);
        $this->assertSame(['DUO:0000042'], $fields['duoCodes']);
    }

    public function test_earlier_versions_do_not_emit_duo_codes(): void
    {
        foreach (['2.0', '2.1'] as $version) {
            $fields = $this->factory()->resolve($version)
                ->toSearchableFields($this->envelope(['DUO:0000042']));

            $this->assertArrayNotHasKey('duoCodes', $fields, "GWDM {$version} should not emit duoCodes");
        }
    }
}
