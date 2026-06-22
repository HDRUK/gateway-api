<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Gwdm30\DatasetVersionAccessibility;
use App\Models\Gwdm30\DatasetVersionCoverage;
use App\Models\Gwdm30\DatasetVersionObservation;
use App\Models\Gwdm30\DatasetVersionProvenance;
use App\Models\Gwdm30\DatasetVersionSummary;
use App\Models\Application;
use App\Models\ApplicationHasPermission;
use App\Models\Permission;
use App\Models\Team;
use App\Models\User;
use App\Services\Gwdm\Gwdm2xHandler;
use App\Services\Gwdm\GwdmHandlerFactory;
use App\Services\Gwdm\Gwdm30PersistenceService;
use Tests\Traits\Authorization;
use Tests\Traits\MockExternalApis;

class DatasetVersionGwdm30Test extends TestCase
{
    use Authorization;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public const TEST_URL_DATASET = '/api/v1/datasets';

    private Application $integration;
    protected $header30 = [];

    public function setUp(): void
    {
        $this->commonSetUp();

        $this->integration = Application::where('id', 1)->first();

        $perms = Permission::whereIn('name', [
            'datasets.create',
            'datasets.read',
            'datasets.update',
            'datasets.delete',
        ])->get();

        foreach ($perms as $perm) {
            ApplicationHasPermission::firstOrCreate([
                'application_id' => $this->integration->id,
                'permission_id'  => $perm->id,
            ]);
        }

        $this->header = [
            'Accept'           => 'application/json',
            'x-application-id' => $this->integration->app_id,
            'x-client-id'      => $this->integration->client_id,
        ];

        $this->header30 = array_merge($this->header, ['x-gwdm-version' => '3.0']);
    }

    // ─── Write path ───────────────────────────────────────────────────────────

    public function test_persist_populates_all_sql_tables(): void
    {
        $gwdm = $this->getGwdm30Metadata()['metadata'];
        $dv   = $this->makeDatasetVersion30([]);

        app(Gwdm30PersistenceService::class)->persist($dv, $gwdm);

        $this->assertEquals('3.0', $dv->gwdm_version);

        // Accessibility
        $acc = DatasetVersionAccessibility::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($acc, 'gwdm30_accessibility row should be created');
        $this->assertEquals('https://example.com/licence', $acc->access_rights);
        $this->assertEquals('https://example.com/access', $acc->access_service);
        $this->assertEquals('Free at point of access', $acc->access_request_cost);
        $this->assertEquals(['GENERAL RESEARCH USE'], $acc->data_use_limitation);
        $this->assertEquals(['RETURN TO DATABASE OR RESOURCE', 'USER SPECIFIC RESTRICTION'], $acc->data_use_requirements);
        $this->assertEquals(['CSV', 'JSON'], $acc->formats);
        $this->assertEquals('GB-ENG', $acc->jurisdiction);
        $this->assertEquals('OTHER', $acc->vocabulary_encoding_schemes);
        $this->assertEquals('LOCAL', $acc->conforms_to);
        $this->assertEquals('en', $acc->languages);

        // Summary
        $sum = DatasetVersionSummary::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($sum, 'gwdm30_summary row should be created');
        $this->assertEquals('Test dataset for GWDM 3.0 feature tests', $sum->abstract);
        $this->assertEquals('test@example.com', $sum->contact_point);
        $this->assertEquals('Test,GWDM30', $sum->keywords);
        $this->assertEquals('test', $sum->dataset_type);
        $this->assertEquals('Test Publisher', $sum->publisher_name);
        $this->assertEquals(0, $sum->population_size);

        // Coverage
        $cov = DatasetVersionCoverage::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($cov, 'gwdm30_coverage row should be created');
        $this->assertEquals('NOT APPLICABLE', $cov->pathway);
        $this->assertEquals('UNKNOWN', $cov->followup);
        $this->assertEquals('0-150', $cov->typical_age_range);

        // Provenance
        $prov = DatasetVersionProvenance::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($prov, 'gwdm30_provenance row should be created');
        $this->assertEquals('OTHER', $prov->origin_purpose);
        $this->assertEquals('MACHINE GENERATED', $prov->origin_source);
        $this->assertEquals('2020-01-01', $prov->temporal_start_date->toDateString());
        $this->assertEquals('2024-12-31', $prov->temporal_end_date->toDateString());
        $this->assertEquals('Annual', $prov->temporal_accrual_periodicity);

        // Observations
        $obs = DatasetVersionObservation::where('dataset_version_id', $dv->id)->get();
        $this->assertCount(1, $obs);
        $this->assertEquals('Findings', $obs[0]->observed_node);
        $this->assertEquals(100, $obs[0]->measured_value);
        $this->assertEquals('2024-01-01', $obs[0]->observation_date->toDateString());
        $this->assertEquals('Count', $obs[0]->measured_property);
        $this->assertEquals('Test observation', $obs[0]->disambiguating_description);
    }

    public function test_persist_reads_data_use_fields_from_usage_not_access(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        $gwdm = [
            'summary'     => ['shortTitle' => 'Test', 'title' => 'Test'],
            'coverage'    => [],
            'provenance'  => [],
            'observations' => [],
            'accessibility' => [
                'access' => [
                    'accessRights'      => 'https://example.com/rights',
                    'accessService'     => null,
                    'accessRequestCost' => null,
                    'deliveryLeadTime'  => null,
                ],
                'usage' => [
                    'dataUseLimitation'   => ['GENERAL RESEARCH USE'],
                    'dataUseRequirements' => ['RETURN TO DATABASE OR RESOURCE'],
                ],
                'formatAndStandards' => ['formats' => null],
            ],
        ];

        app(Gwdm30PersistenceService::class)->persist($dv, $gwdm);

        $row = DatasetVersionAccessibility::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($row);
        $this->assertEquals(['GENERAL RESEARCH USE'], $row->data_use_limitation);
        $this->assertEquals(['RETURN TO DATABASE OR RESOURCE'], $row->data_use_requirements);
    }

    public function test_persist_replaces_rows_on_rewrite(): void
    {
        $gwdm = $this->getGwdm30Metadata()['metadata'];
        $dv   = $this->makeDatasetVersion30([]);
        $service = app(Gwdm30PersistenceService::class);

        $service->persist($dv, $gwdm);
        $this->assertCount(1, DatasetVersionObservation::where('dataset_version_id', $dv->id)->get());

        // Re-persist with two observations — old rows must be replaced
        $gwdm['observations'] = [
            ['observedNode' => 'Persons', 'measuredValue' => 50, 'observationDate' => '2024-01-01', 'measuredProperty' => 'Count', 'disambiguatingDescription' => null],
            ['observedNode' => 'Events',  'measuredValue' => 200, 'observationDate' => '2024-06-01', 'measuredProperty' => 'Count', 'disambiguatingDescription' => null],
        ];
        $service->persist($dv, $gwdm);

        $this->assertCount(2, DatasetVersionObservation::where('dataset_version_id', $dv->id)->get());
    }

    // ─── LinkageExtraction ────────────────────────────────────────────────────

    public function test_extract_linkages_creates_external_rows_for_unresolved_entries(): void
    {
        $dv = $this->makeDatasetVersion30([
            'linkage' => [
                'datasetLinkage' => [
                    'isDerivedFrom' => [
                        ['url' => 'https://external-site.example.com/datasets/99999', 'pid' => null, 'title' => 'External Dataset'],
                    ],
                ],
                'publicationAboutDataset' => null,
                'publicationUsingDataset' => null,
            ],
        ]);

        app(GwdmHandlerFactory::class)->resolve('3.0')->extractLinkages($dv);

        $rows = DatasetVersionHasDatasetVersion::where('dataset_version_source_id', $dv->id)
            ->where('direct_linkage', 1)
            ->where('description', Gwdm2xHandler::LINKAGE_DESCRIPTION)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertEquals('isDerivedFrom', $rows[0]->linkage_type);
        $this->assertNull($rows[0]->dataset_version_target_id, 'Unresolved entry should have null target');
        $this->assertEquals('External Dataset', $rows[0]->raw_title);
        $this->assertEquals('https://external-site.example.com/datasets/99999', $rows[0]->raw_url);
    }

    public function test_extract_linkages_creates_internal_row_for_resolved_gateway_dataset(): void
    {
        $targetDv = $this->makeDatasetVersion30([]);

        $sourceDv = $this->makeDatasetVersion30([
            'linkage' => [
                'datasetLinkage' => [
                    'linkedDatasets' => [
                        [
                            'url'   => config('gateway.gateway_url') . '/en/dataset/' . $targetDv->dataset_id,
                            'pid'   => null,
                            'title' => null,
                        ],
                    ],
                ],
                'publicationAboutDataset' => null,
                'publicationUsingDataset' => null,
            ],
        ]);

        app(GwdmHandlerFactory::class)->resolve('3.0')->extractLinkages($sourceDv);

        $rows = DatasetVersionHasDatasetVersion::where('dataset_version_source_id', $sourceDv->id)
            ->where('direct_linkage', 1)
            ->where('description', Gwdm2xHandler::LINKAGE_DESCRIPTION)
            ->get();

        $this->assertCount(1, $rows);
        $this->assertEquals('linkedDatasets', $rows[0]->linkage_type);
        $this->assertEquals($targetDv->id, $rows[0]->dataset_version_target_id, 'Resolved entry should have target version ID set');
    }

    public function test_extract_linkages_replaces_existing_rows_on_rerun(): void
    {
        $dv = $this->makeDatasetVersion30([
            'linkage' => [
                'datasetLinkage' => [
                    'isDerivedFrom' => [
                        ['url' => 'https://external.example.com/a', 'pid' => null, 'title' => 'Dataset A'],
                    ],
                ],
                'publicationAboutDataset' => null,
                'publicationUsingDataset' => null,
            ],
        ]);

        $handler = app(GwdmHandlerFactory::class)->resolve('3.0');
        $handler->extractLinkages($dv);

        $countRows = fn () => DatasetVersionHasDatasetVersion::where('dataset_version_source_id', $dv->id)
            ->where('direct_linkage', 1)
            ->where('description', Gwdm2xHandler::LINKAGE_DESCRIPTION)
            ->count();

        $this->assertEquals(1, $countRows());

        // Re-run with different entries — old rows must be replaced, not appended
        $dv->metadata = array_replace_recursive($dv->metadata, [
            'metadata' => [
                'linkage' => [
                    'datasetLinkage' => [
                        'isDerivedFrom' => [
                            ['url' => 'https://external.example.com/b', 'pid' => null, 'title' => 'Dataset B'],
                            ['url' => 'https://external.example.com/c', 'pid' => null, 'title' => 'Dataset C'],
                        ],
                    ],
                ],
            ],
        ]);
        $handler->extractLinkages($dv);

        $this->assertEquals(2, $countRows());
    }

    // ─── Read path ────────────────────────────────────────────────────────────

    public function test_read_reconstructs_nested_accessibility_shape(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        DatasetVersionAccessibility::create([
            'dataset_version_id'  => $dv->id,
            'access_rights'       => 'https://example.com/rights',
            'access_service'      => 'https://example.com/service',
            'access_request_cost' => 'Free',
            'delivery_lead_time'  => '1-2 WEEKS',
            'jurisdiction'        => 'GB-ENG',
            'data_use_limitation'   => ['GENERAL RESEARCH USE'],
            'data_use_requirements' => ['RETURN TO DATABASE OR RESOURCE'],
            'formats'               => ['CSV'],
        ]);

        $result = app(Gwdm30PersistenceService::class)->read($dv);

        $this->assertArrayHasKey('accessibility', $result);
        $this->assertArrayHasKey('access', $result['accessibility']);
        $this->assertArrayHasKey('usage', $result['accessibility']);
        $this->assertArrayHasKey('formatAndStandards', $result['accessibility']);
        $this->assertEquals('https://example.com/rights', $result['accessibility']['access']['accessRights']);
        $this->assertEquals('GB-ENG', $result['accessibility']['access']['jurisdiction']);
        $this->assertEquals(['GENERAL RESEARCH USE'], $result['accessibility']['usage']['dataUseLimitation']);
        $this->assertEquals(['CSV'], $result['accessibility']['formatAndStandards']['formats']);
    }

    public function test_read_reconstructs_summary(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        DatasetVersionSummary::create([
            'dataset_version_id' => $dv->id,
            'abstract'           => 'A test abstract',
            'contact_point'      => 'contact@example.com',
            'keywords'           => 'health,data',
            'publisher_name'     => 'NHS England',
            'population_size'    => 5000,
        ]);

        $result = app(Gwdm30PersistenceService::class)->read($dv);

        $this->assertArrayHasKey('summary', $result);
        $this->assertEquals('A test abstract', $result['summary']['abstract']);
        $this->assertEquals('contact@example.com', $result['summary']['contactPoint']);
        $this->assertEquals('NHS England', $result['summary']['publisher']['name']);
        $this->assertEquals(5000, $result['summary']['populationSize']);
        // title/shortTitle should be carried forward from the JSON blob
        $this->assertArrayHasKey('title', $result['summary']);
    }

    public function test_read_reconstructs_coverage(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        DatasetVersionCoverage::create([
            'dataset_version_id' => $dv->id,
            'spatial'            => 'https://www.geonames.org/countries/GB/united-kingdom.html',
            'typical_age_range'  => '18-65',
            'pathway'            => 'PRIMARY CARE',
            'followup'           => '5 YEARS',
        ]);

        $result = app(Gwdm30PersistenceService::class)->read($dv);

        $this->assertArrayHasKey('coverage', $result);
        $this->assertEquals('18-65', $result['coverage']['typicalAgeRange']);
        $this->assertEquals('PRIMARY CARE', $result['coverage']['pathway']);
        $this->assertEquals('5 YEARS', $result['coverage']['followup']);
    }

    public function test_read_reconstructs_provenance(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        DatasetVersionProvenance::create([
            'dataset_version_id'           => $dv->id,
            'origin_purpose'               => 'ADMINISTRATIVE',
            'origin_source'                => 'EPR',
            'origin_collection_situation'  => 'PRIMARY CARE',
            'temporal_start_date'          => '2010-01-01',
            'temporal_end_date'            => '2023-12-31',
            'temporal_time_lag'            => '1-2 MONTHS',
            'temporal_accrual_periodicity' => 'Monthly',
        ]);

        $result = app(Gwdm30PersistenceService::class)->read($dv);

        $this->assertArrayHasKey('provenance', $result);
        $this->assertEquals('ADMINISTRATIVE', $result['provenance']['origin']['purpose']);
        $this->assertEquals('EPR', $result['provenance']['origin']['source']);
        $this->assertEquals('2010-01-01', $result['provenance']['temporal']['startDate']);
        $this->assertEquals('2023-12-31', $result['provenance']['temporal']['endDate']);
        $this->assertEquals('Monthly', $result['provenance']['temporal']['accrualPeriodicity']);
    }

    public function test_read_reconstructs_observations(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        DatasetVersionObservation::create([
            'dataset_version_id'          => $dv->id,
            'observed_node'               => 'Persons',
            'measured_value'              => 12500,
            'observation_date'            => '2023-01-01',
            'measured_property'           => 'Count',
            'disambiguating_description'  => 'Total unique patients',
        ]);
        DatasetVersionObservation::create([
            'dataset_version_id'          => $dv->id,
            'observed_node'               => 'Events',
            'measured_value'              => 45000,
            'observation_date'            => '2023-01-01',
            'measured_property'           => 'Count',
            'disambiguating_description'  => null,
        ]);

        $result = app(Gwdm30PersistenceService::class)->read($dv);

        $this->assertArrayHasKey('observations', $result);
        $this->assertCount(2, $result['observations']);
        $this->assertEquals('Persons', $result['observations'][0]['observedNode']);
        $this->assertEquals(12500, $result['observations'][0]['measuredValue']);
        $this->assertEquals('2023-01-01', $result['observations'][0]['observationDate']);
        $this->assertEquals('Events', $result['observations'][1]['observedNode']);
    }

    public function test_read_reconstructs_external_dataset_linkages(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        DatasetVersionHasDatasetVersion::create([
            'dataset_version_source_id' => $dv->id,
            'dataset_version_target_id' => null,
            'linkage_type'              => 'isDerivedFrom',
            'direct_linkage'            => 1,
            'description'               => Gwdm2xHandler::LINKAGE_DESCRIPTION,
            'raw_url'                   => 'https://external.example.com/dataset/1',
            'raw_pid'                   => null,
            'raw_title'                 => 'External Dataset',
        ]);

        $result = app(Gwdm30PersistenceService::class)->read($dv);

        $this->assertArrayHasKey('linkage', $result);
        $this->assertArrayHasKey('isDerivedFrom', $result['linkage']['datasetLinkage']);
        $entry = $result['linkage']['datasetLinkage']['isDerivedFrom'][0];
        $this->assertEquals('https://external.example.com/dataset/1', $entry['url']);
        $this->assertEquals('External Dataset', $entry['title']);
        $this->assertNull($entry['pid']);
    }

    public function test_read_returns_empty_when_no_sql_rows(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        $result = app(Gwdm30PersistenceService::class)->read($dv);

        $this->assertSame([], $result);
    }

    // ─── Rollback safety ─────────────────────────────────────────────────────

    public function test_rollback_2x_version_has_no_gwdm30_sql_rows(): void
    {
        $team = Team::first();
        $user = User::first();

        $dataset20 = Dataset::create([
            'user_id'       => $user->id,
            'team_id'       => $team->id,
            'pid'           => 'rollback-test-2x-' . fake()->uuid(),
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status'        => Dataset::STATUS_ACTIVE,
        ]);
        $version20 = DatasetVersion::create([
            'dataset_id'   => $dataset20->id,
            'version'      => 1,
            'gwdm_version' => '2.0',
            'short_title'  => 'Rollback Test 2.0',
            'metadata'     => [
                'gwdmVersion'       => '2.0',
                'metadata'          => $this->getMetadataV2p0()['metadata'],
                'original_metadata' => [],
            ],
        ]);

        $this->assertEquals('2.0', $version20->gwdm_version);

        // No GWDM 3.0 SQL rows for a 2.0 version
        $this->assertNull(DatasetVersionAccessibility::where('dataset_version_id', $version20->id)->first());
        $this->assertNull(DatasetVersionSummary::where('dataset_version_id', $version20->id)->first());
        $this->assertNull(DatasetVersionCoverage::where('dataset_version_id', $version20->id)->first());
        $this->assertNull(DatasetVersionProvenance::where('dataset_version_id', $version20->id)->first());
        $this->assertCount(0, DatasetVersionObservation::where('dataset_version_id', $version20->id)->get());

        // Create and persist a 3.0 version
        $gwdm      = $this->getGwdm30Metadata()['metadata'];
        $version30 = $this->makeDatasetVersion30([]);
        app(Gwdm30PersistenceService::class)->persist($version30, $gwdm);

        $this->assertEquals('3.0', $version30->gwdm_version);
        $this->assertNotNull(DatasetVersionAccessibility::where('dataset_version_id', $version30->id)->first());
        $this->assertNotNull(DatasetVersionSummary::where('dataset_version_id', $version30->id)->first());

        // 2.0 version is still untouched
        $this->assertNull(DatasetVersionAccessibility::where('dataset_version_id', $version20->id)->first());
        $this->assertNull(DatasetVersionSummary::where('dataset_version_id', $version20->id)->first());
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function getGwdm30Metadata(): array
    {
        $json = file_get_contents(getcwd() . '/tests/Unit/test_files/gwdm_v3p0_dataset_min.json');
        return json_decode($json, true);
    }

    /**
     * Create a Dataset + DatasetVersion directly with GWDM 3.0 metadata,
     * merging $metadataOverrides into the `metadata` section of the envelope.
     */
    private function makeDatasetVersion30(array $metadataOverrides): DatasetVersion
    {
        $team = Team::first();
        $user = User::first();

        $dataset = Dataset::create([
            'user_id'       => $user->id,
            'team_id'       => $team->id,
            'pid'           => 'test-' . fake()->uuid(),
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status'        => Dataset::STATUS_ACTIVE,
        ]);

        $baseMetadata   = $this->getGwdm30Metadata()['metadata'];
        $mergedMetadata = array_replace_recursive($baseMetadata, $metadataOverrides);

        return DatasetVersion::create([
            'dataset_id'   => $dataset->id,
            'version'      => 1,
            'gwdm_version' => '3.0',
            'short_title'  => $mergedMetadata['summary']['shortTitle'] ?? 'GWDM 3.0 Test',
            'metadata'     => [
                'gwdmVersion'       => '3.0',
                'metadata'          => $mergedMetadata,
                'original_metadata' => [],
            ],
        ]);
    }
}
