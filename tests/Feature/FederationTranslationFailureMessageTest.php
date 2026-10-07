<?php

namespace Tests\Feature;

use App\Models\Federation;
use App\Models\Team;
use App\Models\TeamHasFederation;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class FederationTranslationFailureMessageTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    private const HDRUK_212 = 'https://raw.githubusercontent.com/HDRUK/schemata-2/master/hdr_schemata/models/HDRUK/2.1.2/schema.json';

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    private function makeFederation(): array
    {
        $team = Team::factory()->create();
        $federation = Federation::factory()->create(['enabled' => true, 'tested' => true, 'is_running' => false]);
        TeamHasFederation::create(['team_id' => $team->id, 'federation_id' => $federation->id]);

        return [$team, $federation];
    }

    private function noMatchResponse(): array
    {
        return [
            'metadata' => null,
            'statusCode' => 400,
            'wasTranslated' => false,
            'traser_message' => [
                'details' => ['available_schemas' => ['GWDM' => ['2.0'], 'HDRUK' => ['2.1.2']]],
                'message' => 'Input metadata object matched no known schemas',
            ],
        ];
    }

    // Stands in for traser's /validate: errors for one named schema.
    private function traserValidate(): \Closure
    {
        return fn (string $name, string $version): array => match ("{$name}:{$version}") {
            'HDRUK:2.1.2' => [
                ['instancePath' => '', 'message' => "must have required property 'url'"],
                ['instancePath' => '', 'message' => "must have required property 'accessibility'"],
            ],
            'HDRUK:2.2.0' => [],
            default => [['message' => "Schema {$name}:{$version} is not known"]],
        };
    }

    /**
     * Records the stored failure for one dataset and returns the message run history shows for it.
     */
    private function shownMessage(array $traserResponse, array $catalogueItem, \Closure $validateAgainst): string
    {
        [$team, $federation] = $this->makeFederation();

        $ingestion = new class () {
            use \App\Traits\GatewayMetadataIngestionTrait;
        };
        $stored = $ingestion->translationFailureForHistory($traserResponse, $catalogueItem, $validateAgainst);
        $ingestion->sendToHistory($team->id, $federation->id, 'pid-1', 'uuid-1', $stored, 0, 1);

        $content = $this->get("api/v1/teams/{$team->id}/federations/{$federation->id}/history", $this->header)
            ->decodeResponseJson();

        return $content['data'][0]['message'];
    }

    public function test_a_gwdm_validation_failure_shows_only_the_gwdm_errors(): void
    {
        $response = [
            'metadata' => null,
            'statusCode' => 400,
            'wasTranslated' => false,
            'traser_message' => [
                'message' => 'Output metadata validation failed',
                'details' => [
                    ['instancePath' => '/summary', 'message' => "must have required property 'title'"],
                ],
            ],
        ];

        $message = $this->shownMessage($response, ['@schema' => self::HDRUK_212], $this->traserValidate());

        $gwdm = Config::get('metadata.GWDM.name') . '/' . Config::get('metadata.GWDM.version');
        $this->assertSame("{$gwdm}: must have required property 'title'", $message);
    }

    public function test_no_matching_schema_shows_only_the_errors_for_the_declared_schema(): void
    {
        $message = $this->shownMessage($this->noMatchResponse(), ['@schema' => self::HDRUK_212], $this->traserValidate());

        $this->assertSame(
            "HDRUK/2.1.2: must have required property 'url'; HDRUK/2.1.2: must have required property 'accessibility'",
            $message
        );
    }

    public function test_no_matching_schema_without_a_declared_schema_says_so(): void
    {
        $message = $this->shownMessage($this->noMatchResponse(), ['persistentId' => 'pid-1'], $this->traserValidate());

        $this->assertSame("Doesn't match any supported metadata schema", $message);
    }

    public function test_no_matching_schema_with_an_unreadable_declared_schema_says_so(): void
    {
        $message = $this->shownMessage($this->noMatchResponse(), ['@schema' => 'https://example.com/my-schema'], $this->traserValidate());

        $this->assertSame("Doesn't match any supported metadata schema", $message);
    }

    public function test_no_matching_schema_with_a_declared_schema_traser_does_not_know_shows_traser_message(): void
    {
        $unknown = 'https://raw.githubusercontent.com/HDRUK/schemata-2/master/hdr_schemata/models/HDRUK/9.9.9/schema.json';

        $message = $this->shownMessage($this->noMatchResponse(), ['@schema' => $unknown], $this->traserValidate());

        $this->assertSame('HDRUK/9.9.9: Schema HDRUK:9.9.9 is not known', $message);
    }

    public function test_no_matching_schema_when_the_declared_schema_validates_says_so(): void
    {
        $valid = 'https://raw.githubusercontent.com/HDRUK/schemata-2/master/hdr_schemata/models/HDRUK/2.2.0/schema.json';

        $message = $this->shownMessage($this->noMatchResponse(), ['@schema' => $valid], $this->traserValidate());

        $this->assertSame("Doesn't match any supported metadata schema", $message);
    }

    public function test_no_declared_schema_makes_no_validate_call(): void
    {
        $message = $this->shownMessage(
            $this->noMatchResponse(),
            ['persistentId' => 'pid-1'],
            fn () => $this->fail('validate should not be called without a declared schema')
        );

        $this->assertSame("Doesn't match any supported metadata schema", $message);
    }

    public function test_a_failed_translation_step_shows_traser_message(): void
    {
        $response = [
            'metadata' => null,
            'statusCode' => 500,
            'wasTranslated' => false,
            'traser_message' => ['message' => 'Failed to execute translation between HDRUK:2.1.2 and GWDM:2.2', 'details' => []],
        ];

        $message = $this->shownMessage($response, ['@schema' => self::HDRUK_212], $this->traserValidate());

        $this->assertSame('Failed to execute translation between HDRUK:2.1.2 and GWDM:2.2', $message);
    }
}
