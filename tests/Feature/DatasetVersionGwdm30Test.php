<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\ApplicationHasPermission;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use App\Models\DatasetVersionHasDatasetVersion;
use App\Models\Gwdm30\Accessibility;
use App\Models\Gwdm30\Coverage;
use App\Models\Gwdm30\Distribution;
use App\Models\Gwdm30\Observation;
use App\Models\Gwdm30\Provenance;
use App\Models\Gwdm30\QualityAnnotation;
use App\Models\Gwdm30\Summary;
use App\Models\Permission;
use App\Models\Team;
use App\Models\User;
use App\Services\Gwdm\Gwdm2xHandler;
use App\Services\Gwdm\GwdmHandlerFactory;
use Tests\TestCase;
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

    protected function setUp(): void
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
                'permission_id' => $perm->id,
            ]);
        }

        $this->header = [
            'Accept' => 'application/json',
            'x-application-id' => $this->integration->app_id,
            'x-client-id' => $this->integration->client_id,
        ];

        $this->header30 = array_merge($this->header, ['x-gwdm-version' => '3.0']);
    }

    // ─── Write path ───────────────────────────────────────────────────────────

    public function test_persist_populates_all_sql_tables(): void
    {
        $gwdm = $this->getGwdm30Metadata()['metadata'];
        $dv = $this->makeDatasetVersion30([]);

        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dv->dataset, $dv, $gwdm);

        $this->assertEquals('3.0', $dv->gwdm_version);

        // Accessibility
        $acc = Accessibility::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($acc, 'gwdm30_accessibility row should be created');
        // accessRights / jurisdiction / vocabularyEncodingSchemes / conformsTo /
        // languages are normalised to arrays by Gwdm30Handler::ensureArray() on
        // write and cast back to arrays on read, so a scalar source value round-
        // trips as a single-element array. access_service / access_request_cost
        // are not array-normalised and stay scalar.
        $this->assertEquals(['https://example.com/licence'], $acc->access_rights);
        $this->assertEquals('https://example.com/access', $acc->access_service);
        $this->assertEquals('Free at point of access', $acc->access_request_cost);
        $this->assertEquals(['GENERAL RESEARCH USE'], $acc->data_use_limitation);
        $this->assertEquals(['RETURN TO DATABASE OR RESOURCE', 'USER SPECIFIC RESTRICTION'], $acc->data_use_requirements);
        $this->assertEquals(['CSV', 'JSON'], $acc->formats);
        $this->assertEquals(['GB-ENG'], $acc->jurisdiction);
        $this->assertEquals(['OTHER'], $acc->vocabulary_encoding_schemes);
        $this->assertEquals(['LOCAL'], $acc->conforms_to);
        $this->assertEquals(['en'], $acc->languages);

        // Summary
        $sum = Summary::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($sum, 'gwdm30_summary row should be created');
        $this->assertEquals('Test dataset for GWDM 3.0 feature tests', $sum->abstract);
        $this->assertEquals('test@example.com', $sum->contact_point);
        $this->assertEquals(['Test,GWDM30'], $sum->keywords);
        $this->assertEquals(['test'], $sum->dataset_type);
        $this->assertEquals('Test Publisher', $sum->publisher_name);
        $this->assertEquals(0, $sum->population_size);

        // Coverage
        $cov = Coverage::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($cov, 'gwdm30_coverage row should be created');
        $this->assertEquals('NOT APPLICABLE', $cov->pathway);
        $this->assertEquals('UNKNOWN', $cov->followup);
        $this->assertEquals(0, $cov->min_typical_age);
        $this->assertEquals(150, $cov->max_typical_age);

        // Provenance
        $prov = Provenance::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($prov, 'gwdm30_provenance row should be created');
        $this->assertEquals(['OTHER'], $prov->origin_purpose);
        $this->assertEquals(['MACHINE GENERATED'], $prov->origin_source);
        $this->assertEquals('2020-01-01', $prov->temporal_start_date->toDateString());
        $this->assertEquals('2024-12-31', $prov->temporal_end_date->toDateString());
        $this->assertEquals('Annual', $prov->temporal_accrual_periodicity);

        // Observations
        $obs = Observation::where('dataset_version_id', $dv->id)->get();
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
            'summary' => ['shortTitle' => 'Test', 'title' => 'Test'],
            'coverage' => [],
            'provenance' => [],
            'observations' => [],
            'accessibility' => [
                'access' => [
                    'accessRights' => 'https://example.com/rights',
                    'accessService' => null,
                    'accessRequestCost' => null,
                    'deliveryLeadTime' => null,
                ],
                'usage' => [
                    'dataUseLimitation' => ['GENERAL RESEARCH USE'],
                    'dataUseRequirements' => ['RETURN TO DATABASE OR RESOURCE'],
                ],
                'formatAndStandards' => ['formats' => null],
            ],
        ];

        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dv->dataset, $dv, $gwdm);

        $row = Accessibility::where('dataset_version_id', $dv->id)->first();
        $this->assertNotNull($row);
        $this->assertEquals(['GENERAL RESEARCH USE'], $row->data_use_limitation);
        $this->assertEquals(['RETURN TO DATABASE OR RESOURCE'], $row->data_use_requirements);
    }

    public function test_persist_replaces_rows_on_rewrite(): void
    {
        $gwdm = $this->getGwdm30Metadata()['metadata'];
        $dv = $this->makeDatasetVersion30([]);
        $handler = app(GwdmHandlerFactory::class)->resolve('3.0');

        $handler->afterStore($dv->dataset, $dv, $gwdm);
        $this->assertCount(1, Observation::where('dataset_version_id', $dv->id)->get());

        // Re-persist with two observations — old rows must be replaced
        $gwdm['observations'] = [
            ['observedNode' => 'Persons', 'measuredValue' => 50, 'observationDate' => '2024-01-01', 'measuredProperty' => 'Count', 'disambiguatingDescription' => null],
            ['observedNode' => 'Events',  'measuredValue' => 200, 'observationDate' => '2024-06-01', 'measuredProperty' => 'Count', 'disambiguatingDescription' => null],
        ];
        $handler->afterStore($dv->dataset, $dv, $gwdm);

        $this->assertCount(2, Observation::where('dataset_version_id', $dv->id)->get());
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

        // GWDM 3.0 writes linkage junctions synchronously in afterStore() from the
        // input metadata; extractLinkages() (the async job path) is a no-op for 3.0
        // because these arrays are not recoverable from storage on reconstruction.
        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dv->dataset, $dv, $dv->metadata['metadata']);

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
                            'url' => config('gateway.gateway_url').'/en/dataset/'.$targetDv->dataset_id,
                            'pid' => null,
                            'title' => null,
                        ],
                    ],
                ],
                'publicationAboutDataset' => null,
                'publicationUsingDataset' => null,
            ],
        ]);

        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($sourceDv->dataset, $sourceDv, $sourceDv->metadata['metadata']);

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
        $handler->afterStore($dv->dataset, $dv, $dv->metadata['metadata']);

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
        $handler->afterStore($dv->dataset, $dv, $dv->metadata['metadata']);

        $this->assertEquals(2, $countRows());
    }

    // ─── Read path ────────────────────────────────────────────────────────────

    public function test_read_reconstructs_nested_accessibility_shape(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        Accessibility::create([
            'dataset_version_id' => $dv->id,
            'access_rights' => 'https://example.com/rights',
            'access_service' => 'https://example.com/service',
            'access_request_cost' => 'Free',
            'delivery_lead_time' => '1-2 WEEKS',
            'jurisdiction' => 'GB-ENG',
            'data_use_limitation' => ['GENERAL RESEARCH USE'],
            'data_use_requirements' => ['RETURN TO DATABASE OR RESOURCE'],
            'formats' => ['CSV'],
        ]);

        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);

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

        Summary::create([
            'dataset_version_id' => $dv->id,
            'abstract' => 'A test abstract',
            'contact_point' => 'contact@example.com',
            'keywords' => 'health,data',
            'publisher_name' => 'NHS England',
            'population_size' => 5000,
        ]);

        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);

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

        Coverage::create([
            'dataset_version_id' => $dv->id,
            'spatial' => 'https://www.geonames.org/countries/GB/united-kingdom.html',
            'min_typical_age' => 18,
            'max_typical_age' => 65,
            'pathway' => 'PRIMARY CARE',
            'followup' => '5 YEARS',
        ]);

        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);

        $this->assertArrayHasKey('coverage', $result);
        $this->assertEquals(18, $result['coverage']['minTypicalAge']);
        $this->assertEquals(65, $result['coverage']['maxTypicalAge']);
        $this->assertEquals('PRIMARY CARE', $result['coverage']['pathway']);
        $this->assertEquals('5 YEARS', $result['coverage']['followUp']);
    }

    public function test_read_reconstructs_provenance(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        Provenance::create([
            'dataset_version_id' => $dv->id,
            'origin_purpose' => 'ADMINISTRATIVE',
            'origin_source' => 'EPR',
            'origin_collection_situation' => 'PRIMARY CARE',
            'temporal_start_date' => '2010-01-01',
            'temporal_end_date' => '2023-12-31',
            'temporal_time_lag' => '1-2 MONTHS',
            'temporal_accrual_periodicity' => 'Monthly',
        ]);

        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);

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

        Observation::create([
            'dataset_version_id' => $dv->id,
            'observed_node' => 'Persons',
            'measured_value' => 12500,
            'observation_date' => '2023-01-01',
            'measured_property' => 'Count',
            'disambiguating_description' => 'Total unique patients',
        ]);
        Observation::create([
            'dataset_version_id' => $dv->id,
            'observed_node' => 'Events',
            'measured_value' => 45000,
            'observation_date' => '2023-01-01',
            'measured_property' => 'Count',
            'disambiguating_description' => null,
        ]);

        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);

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
            'linkage_type' => 'isDerivedFrom',
            'direct_linkage' => 1,
            'description' => Gwdm2xHandler::LINKAGE_DESCRIPTION,
            'raw_url' => 'https://external.example.com/dataset/1',
            'raw_pid' => null,
            'raw_title' => 'External Dataset',
        ]);

        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);

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

        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);

        $this->assertSame([], $result);
    }

    // ─── Rollback safety ─────────────────────────────────────────────────────

    public function test_rollback_2x_version_has_no_gwdm30_sql_rows(): void
    {
        $team = Team::first();
        $user = User::first();

        $dataset20 = Dataset::create([
            'user_id' => $user->id,
            'team_id' => $team->id,
            'pid' => 'rollback-test-2x-'.fake()->uuid(),
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);
        $version20 = DatasetVersion::create([
            'dataset_id' => $dataset20->id,
            'version' => 1,
            'gwdm_version' => '2.0',
            'short_title' => 'Rollback Test 2.0',
            'metadata' => [
                'gwdmVersion' => '2.0',
                'metadata' => $this->getMetadataV2p0()['metadata'],
                'original_metadata' => [],
            ],
        ]);

        $this->assertEquals('2.0', $version20->gwdm_version);

        // No GWDM 3.0 SQL rows for a 2.0 version
        $this->assertNull(Accessibility::where('dataset_version_id', $version20->id)->first());
        $this->assertNull(Summary::where('dataset_version_id', $version20->id)->first());
        $this->assertNull(Coverage::where('dataset_version_id', $version20->id)->first());
        $this->assertNull(Provenance::where('dataset_version_id', $version20->id)->first());
        $this->assertCount(0, Observation::where('dataset_version_id', $version20->id)->get());

        // Create and persist a 3.0 version
        $gwdm = $this->getGwdm30Metadata()['metadata'];
        $version30 = $this->makeDatasetVersion30([]);
        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($version30->dataset, $version30, $gwdm);

        $this->assertEquals('3.0', $version30->gwdm_version);
        $this->assertNotNull(Accessibility::where('dataset_version_id', $version30->id)->first());
        $this->assertNotNull(Summary::where('dataset_version_id', $version30->id)->first());

        // 2.0 version is still untouched
        $this->assertNull(Accessibility::where('dataset_version_id', $version20->id)->first());
        $this->assertNull(Summary::where('dataset_version_id', $version20->id)->first());
    }

    // ─── DCAT / HealthDCAT-AP alignment ──────────────────────────────────────

    public function test_persist_stores_dcat_summary_fields(): void
    {
        $dv = $this->makeDatasetVersion30([]);
        $gwdm = array_merge($this->getGwdm30Metadata()['metadata'], [
            'summary' => array_merge($this->getGwdm30Metadata()['metadata']['summary'], [
                'licenseUrl' => 'https://creativecommons.org/licenses/by/4.0/',
                'landingPage' => 'https://gateway.hdruk.ac.uk/datasets/abc123',
                'creator' => [
                    'name' => 'NHS England',
                    'rorId' => 'https://ror.org/052gg0110',
                    'orcidId' => null,
                    'gatewayId' => '42',
                ],
                'theme' => ['https://health-ri.eu/themes/EHR', 'https://health-ri.eu/themes/Registry'],
            ]),
        ]);

        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dv->dataset, $dv, $gwdm);

        $sum = Summary::where('dataset_version_id', $dv->id)->first();
        $this->assertEquals('https://creativecommons.org/licenses/by/4.0/', $sum->license_url);
        $this->assertEquals('https://gateway.hdruk.ac.uk/datasets/abc123', $sum->landing_page);
        $this->assertEquals('NHS England', $sum->creator_name);
        $this->assertEquals('https://ror.org/052gg0110', $sum->creator_ror_id);
        $this->assertEquals('42', $sum->creator_gateway_id);
        $this->assertEquals(['https://health-ri.eu/themes/EHR', 'https://health-ri.eu/themes/Registry'], $sum->theme);
    }

    public function test_persist_stores_healthdcatap_coverage_fields(): void
    {
        $dv = $this->makeDatasetVersion30([]);
        $gwdm = array_merge($this->getGwdm30Metadata()['metadata'], [
            'coverage' => [
                'spatial' => 'GB',
                'minTypicalAge' => 18,
                'maxTypicalAge' => 65,
                'populationCoverage' => 'Adults registered with a GP in England',
                'numberOfUniqueIndividuals' => 58000000,
                'numberOfRecords' => 250000000,
                'pathway' => 'PRIMARY CARE',
                'followUp' => '10 YEARS',
            ],
        ]);

        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dv->dataset, $dv, $gwdm);

        $cov = Coverage::where('dataset_version_id', $dv->id)->first();
        $this->assertEquals(18, $cov->min_typical_age);
        $this->assertEquals(65, $cov->max_typical_age);
        $this->assertEquals('Adults registered with a GP in England', $cov->population_coverage);
        $this->assertEquals(58000000, $cov->number_of_unique_individuals);
        $this->assertEquals(250000000, $cov->number_of_records);
    }

    public function test_persist_stores_retention_period(): void
    {
        $dv = $this->makeDatasetVersion30([]);
        $gwdm = array_merge($this->getGwdm30Metadata()['metadata'], [
            'provenance' => array_merge($this->getGwdm30Metadata()['metadata']['provenance'], [
                'retentionPeriod' => [
                    'startDate' => '2019-04-01',
                    'endDate' => '2030-03-31',
                ],
            ]),
        ]);

        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dv->dataset, $dv, $gwdm);

        $prov = Provenance::where('dataset_version_id', $dv->id)->first();
        $this->assertEquals('2019-04-01', $prov->retention_period_start->toDateString());
        $this->assertEquals('2030-03-31', $prov->retention_period_end->toDateString());
    }

    public function test_persist_stores_gdpr_accessibility_fields(): void
    {
        $dv = $this->makeDatasetVersion30([]);
        $gwdm = $this->getGwdm30Metadata()['metadata'];
        $gwdm['accessibility']['access']['legalBasis'] = 'GDPR Article 6(1)(e) — public task';
        $gwdm['accessibility']['access']['personalData'] = 'pseudonymised';
        $gwdm['accessibility']['access']['applicableLegislation'] = 'Data Protection Act 2018';

        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dv->dataset, $dv, $gwdm);

        $acc = Accessibility::where('dataset_version_id', $dv->id)->first();
        $this->assertEquals('GDPR Article 6(1)(e) — public task', $acc->legal_basis);
        $this->assertEquals('pseudonymised', $acc->personal_data);
        $this->assertEquals('Data Protection Act 2018', $acc->applicable_legislation);
    }

    public function test_persist_and_read_distributions(): void
    {
        $dv = $this->makeDatasetVersion30([]);
        $gwdm = array_merge($this->getGwdm30Metadata()['metadata'], [
            'distributions' => [
                [
                    'title' => 'CSV export',
                    'accessUrl' => 'https://example.com/access',
                    'downloadUrl' => 'https://example.com/download.csv',
                    'mediaType' => 'text/csv',
                    'format' => 'CSV',
                    'byteSize' => 1048576,
                    'licenseUrl' => 'https://creativecommons.org/licenses/by/4.0/',
                    'accessService' => 'https://example.com/api',
                ],
                [
                    'title' => 'FHIR API',
                    'accessUrl' => 'https://example.com/fhir',
                    'mediaType' => 'application/fhir+json',
                ],
            ],
        ]);

        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dv->dataset, $dv, $gwdm);

        $rows = Distribution::where('dataset_version_id', $dv->id)->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertEquals('CSV export', $rows[0]->title);
        $this->assertEquals('https://example.com/access', $rows[0]->access_url);
        $this->assertEquals('https://example.com/download.csv', $rows[0]->download_url);
        $this->assertEquals('text/csv', $rows[0]->media_type);
        $this->assertEquals(1048576, $rows[0]->byte_size);
        $this->assertEquals('https://creativecommons.org/licenses/by/4.0/', $rows[0]->license_url);
        $this->assertEquals('FHIR API', $rows[1]->title);
        $this->assertEquals('https://example.com/fhir', $rows[1]->access_url);

        // Read path round-trip
        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);
        $this->assertArrayHasKey('distributions', $result);
        $this->assertCount(2, $result['distributions']);
        $this->assertEquals('CSV export', $result['distributions'][0]['title']);
        $this->assertEquals('text/csv', $result['distributions'][0]['mediaType']);
        $this->assertEquals('https://example.com/fhir', $result['distributions'][1]['accessUrl']);
    }

    public function test_persist_and_read_quality_annotations(): void
    {
        $dv = $this->makeDatasetVersion30([]);
        $gwdm = array_merge($this->getGwdm30Metadata()['metadata'], [
            'qualityAnnotations' => [
                [
                    'annotationType' => 'duf_score',
                    'qualityDimension' => 'completeness',
                    'qualityValue' => '4',
                    'qualityDescription' => 'Data completeness rated 4/5 by HDR UK DUF review',
                    'annotationDate' => '2024-03-01',
                ],
                [
                    'annotationType' => 'certification',
                    'certificationUrl' => 'https://example.com/iso27001-cert.pdf',
                    'qualityDescription' => 'ISO 27001 certified data custodian',
                    'annotationDate' => '2023-09-15',
                ],
            ],
        ]);

        app(GwdmHandlerFactory::class)->resolve('3.0')->afterStore($dv->dataset, $dv, $gwdm);

        $rows = QualityAnnotation::where('dataset_version_id', $dv->id)->orderBy('id')->get();
        $this->assertCount(2, $rows);
        $this->assertEquals('duf_score', $rows[0]->annotation_type);
        $this->assertEquals('completeness', $rows[0]->quality_dimension);
        $this->assertEquals('4', $rows[0]->quality_value);
        $this->assertEquals('2024-03-01', $rows[0]->annotation_date->toDateString());
        $this->assertEquals('certification', $rows[1]->annotation_type);
        $this->assertEquals('https://example.com/iso27001-cert.pdf', $rows[1]->certification_url);

        // Read path round-trip
        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);
        $this->assertArrayHasKey('qualityAnnotations', $result);
        $this->assertCount(2, $result['qualityAnnotations']);
        $this->assertEquals('duf_score', $result['qualityAnnotations'][0]['annotationType']);
        $this->assertEquals('completeness', $result['qualityAnnotations'][0]['qualityDimension']);
        $this->assertEquals('certification', $result['qualityAnnotations'][1]['annotationType']);
        $this->assertEquals('https://example.com/iso27001-cert.pdf', $result['qualityAnnotations'][1]['certificationUrl']);
    }

    public function test_read_returns_dcat_summary_fields(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        Summary::create([
            'dataset_version_id' => $dv->id,
            'abstract' => 'Test abstract',
            'license_url' => 'https://creativecommons.org/licenses/by/4.0/',
            'landing_page' => 'https://gateway.hdruk.ac.uk/datasets/abc',
            'creator_name' => 'NHS England',
            'creator_ror_id' => 'https://ror.org/052gg0110',
            'theme' => ['https://health-ri.eu/themes/EHR'],
            'publisher_name' => 'NHS England',
        ]);

        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);

        $this->assertEquals('https://creativecommons.org/licenses/by/4.0/', $result['summary']['licenseUrl']);
        $this->assertEquals('https://gateway.hdruk.ac.uk/datasets/abc', $result['summary']['landingPage']);
        $this->assertEquals('NHS England', $result['summary']['creator']['name']);
        $this->assertEquals('https://ror.org/052gg0110', $result['summary']['creator']['rorId']);
        $this->assertEquals(['https://health-ri.eu/themes/EHR'], $result['summary']['theme']);
    }

    public function test_read_returns_gdpr_accessibility_fields(): void
    {
        $dv = $this->makeDatasetVersion30([]);

        Accessibility::create([
            'dataset_version_id' => $dv->id,
            'access_rights' => 'https://example.com/rights',
            'legal_basis' => 'GDPR Article 6(1)(e)',
            'personal_data' => 'pseudonymised',
            'applicable_legislation' => 'Data Protection Act 2018',
        ]);

        $result = app(GwdmHandlerFactory::class)->resolve('3.0')->afterRead($dv);

        $this->assertEquals('GDPR Article 6(1)(e)', $result['accessibility']['access']['legalBasis']);
        $this->assertEquals('pseudonymised', $result['accessibility']['access']['personalData']);
        $this->assertEquals('Data Protection Act 2018', $result['accessibility']['access']['applicableLegislation']);
    }

    public function test_distributions_replaced_on_rewrite(): void
    {
        $dv = $this->makeDatasetVersion30([]);
        $handler = app(GwdmHandlerFactory::class)->resolve('3.0');
        $gwdm = $this->getGwdm30Metadata()['metadata'];

        $gwdm['distributions'] = [['accessUrl' => 'https://example.com/v1']];
        $handler->afterStore($dv->dataset, $dv, $gwdm);
        $this->assertCount(1, Distribution::where('dataset_version_id', $dv->id)->get());

        $gwdm['distributions'] = [
            ['accessUrl' => 'https://example.com/v2a'],
            ['accessUrl' => 'https://example.com/v2b'],
        ];
        $handler->afterStore($dv->dataset, $dv, $gwdm);
        $this->assertCount(2, Distribution::where('dataset_version_id', $dv->id)->get());
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function getGwdm30Metadata(): array
    {
        $json = file_get_contents(getcwd().'/tests/Unit/test_files/gwdm_v3p0_dataset_min.json');

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
            'user_id' => $user->id,
            'team_id' => $team->id,
            'pid' => 'test-'.fake()->uuid(),
            'create_origin' => Dataset::ORIGIN_MANUAL,
            'status' => Dataset::STATUS_ACTIVE,
        ]);

        $baseMetadata = $this->getGwdm30Metadata()['metadata'];
        $mergedMetadata = array_replace_recursive($baseMetadata, $metadataOverrides);

        return DatasetVersion::create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'gwdm_version' => '3.0',
            'short_title' => $mergedMetadata['summary']['shortTitle'] ?? 'GWDM 3.0 Test',
            'metadata' => [
                'gwdmVersion' => '3.0',
                'metadata' => $mergedMetadata,
                'original_metadata' => [],
            ],
        ]);
    }

    // ─── M2: batched section preload (avoids N+1 on multi-version reads) ────────

    public function test_preload_sections_for_versions_batches_queries(): void
    {
        $handler = app(GwdmHandlerFactory::class)->resolve('3.0');
        $gwdm = $this->getGwdm30Metadata()['metadata'];

        // Three separate 3.0 versions, each with SQL sections written.
        $versions = collect();
        for ($i = 0; $i < 3; $i++) {
            $dv = $this->makeDatasetVersion30([]);
            $handler->afterStore($dv->dataset, $dv, $gwdm);
            $versions->push(DatasetVersion::find($dv->id)); // fresh, no relations loaded
        }

        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $handler->preloadSectionsForVersions($versions);
        $preloadQueries = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        // One query per section type regardless of the version count — bounded well
        // below the per-row cost (3 versions x ~16 sections = ~48 queries).
        $this->assertLessThanOrEqual(20, $preloadQueries, "expected a batched preload, got {$preloadQueries} queries");
        $this->assertTrue($versions->first()->relationLoaded('gwdm30Summary'));
        $this->assertTrue($versions->last()->relationLoaded('gwdm30Observations'));

        // afterRead() on a preloaded version reads the 16 SQL sections from the
        // hydrated relations (no re-query); the only queries it may still issue are
        // for linkage reconstruction from the junction tables, which are not part
        // of the section preload. So the count stays small and constant, not ~16.
        \Illuminate\Support\Facades\DB::flushQueryLog();
        \Illuminate\Support\Facades\DB::enableQueryLog();
        $handler->afterRead($versions->first());
        $afterReadQueries = count(\Illuminate\Support\Facades\DB::getQueryLog());
        \Illuminate\Support\Facades\DB::disableQueryLog();

        $this->assertLessThanOrEqual(4, $afterReadQueries, 'afterRead should serve the 16 SQL sections from preloaded relations');
    }
}
