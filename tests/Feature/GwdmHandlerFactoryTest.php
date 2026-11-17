<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Services\Gwdm\Gwdm1xHandler;
use App\Services\Gwdm\Gwdm20Handler;
use App\Services\Gwdm\Gwdm21Handler;
use App\Services\Gwdm\Gwdm22Handler;
use App\Services\Gwdm\Gwdm23Handler;
use App\Services\Gwdm\Gwdm2xHandler;
use App\Services\Gwdm\GwdmHandlerFactory;
use Tests\TestCase;

/**
 * Coverage for the single point of GWDM version branching. This factory is
 * the ONLY place allowed to switch on the schema version; all scattered
 * version_compare() calls in DatasetService were replaced by resolve() here.
 */
class GwdmHandlerFactoryTest extends TestCase
{
    protected bool $shouldFakeQueue = false;

    private function factory(): GwdmHandlerFactory
    {
        return new GwdmHandlerFactory();
    }

    public function test_resolves_legacy_versions_to_1x_handler(): void
    {
        $this->assertInstanceOf(Gwdm1xHandler::class, $this->factory()->resolve('1.0'));
    }

    public function test_resolves_2_0_and_2_1_to_dedicated_handlers(): void
    {
        $this->assertInstanceOf(Gwdm20Handler::class, $this->factory()->resolve('2.0'));
        $this->assertInstanceOf(Gwdm21Handler::class, $this->factory()->resolve('2.1'));
        $this->assertInstanceOf(Gwdm22Handler::class, $this->factory()->resolve('2.2'));
        $this->assertInstanceOf(Gwdm23Handler::class, $this->factory()->resolve('2.3'));
    }

    public function test_resolves_unknown_1_1_plus_version_to_2x_catch_all(): void
    {
        $this->assertInstanceOf(Gwdm2xHandler::class, $this->factory()->resolve('1.5'));
    }

    public function test_supported_versions_are_2_x_only(): void
    {
        $this->assertSame(['2.0', '2.1', '2.2', '2.3'], GwdmHandlerFactory::supportedVersions());
        $this->assertNotContains('3.0', GwdmHandlerFactory::supportedVersions());
    }

    public function test_only_gwdm_2_3_records_have_record_types(): void
    {
        $caseStudy = ['metadata' => ['required' => ['recordType' => 'caseStudy']]];

        $this->assertFalse($this->factory()->resolve('2.2')->hasRecordTypes());
        $this->assertTrue($this->factory()->resolve('2.2')->isDatasetRecord($caseStudy));

        $this->assertTrue($this->factory()->resolve('2.3')->hasRecordTypes());
    }

    public function test_gwdm_2_3_treats_only_case_studies_as_non_datasets(): void
    {
        $handler = $this->factory()->resolve('2.3');

        $this->assertFalse($handler->isDatasetRecord(['metadata' => ['required' => ['recordType' => 'caseStudy']]]));
        $this->assertTrue($handler->isDatasetRecord(['metadata' => ['required' => ['recordType' => 'dataset']]]));
        // recordType defaults to dataset when absent.
        $this->assertTrue($handler->isDatasetRecord(['metadata' => ['required' => []]]));
    }

    public function test_a_new_dataset_is_attributed_to_the_team(): void
    {
        $team = Team::factory()->create([
            'name' => 'Example Team',
            'contact_point' => 'team@example.com',
        ]);

        $this->assertSame([
            'summary' => [
                'contactPoint' => 'team@example.com',
                'publisher' => [
                    'gatewayId' => (string) $team->id,
                    'name' => 'Example Team',
                ],
            ],
            'accessibility' => [
                'access' => [
                    'dataController' => 'Example Team',
                    'dataProcessor' => 'Example Team',
                    'jurisdiction' => 'UK',
                ],
                'formatAndStandards' => [
                    'languages' => 'en',
                ],
            ],
        ], $this->factory()->resolve('2.2')->teamDefaults($team));
    }
}
