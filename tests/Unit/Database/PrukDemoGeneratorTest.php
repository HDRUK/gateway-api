<?php

namespace Tests\Unit\Database;

use Database\Seeders\Demo\PrukDemoGenerator;
use PHPUnit\Framework\TestCase;

/**
 * The PRUK demo data has to exercise the combined search: keywords, variables
 * and "Refine further" conditions. These check it can, without a database.
 */
class PrukDemoGeneratorTest extends TestCase
{
    // The values the search UI offers for these fields (pruk-client
    // config/advancedSearch.ts); demo data outside them could never match.
    private const WAVE_TYPES = ['Contact wave', 'Sub-study'];

    private const RECRUITMENT_STATUSES = ['Not yet recruiting', 'Recruiting', 'Closed', 'Unknown'];

    private const FOLLOW_UP_FREQUENCIES = [
        'No scheduled follow-up', 'Irregular', 'Annual', 'Biennial',
        'Every 3-5 years', 'Every 5+ years', 'Continuous', 'Other',
    ];

    private const ACCESS_SERVICES = ['TRE/SDE', 'Direct access', 'Open access', 'Varies based on project'];

    private const DELIVERY_LEAD_TIMES = [
        'Less than 1 week', '1-2 weeks', '2-4 weeks', '1-2 months',
        '2-6 months', 'More than 6 months', 'Variable', 'Not applicable', 'Other',
    ];

    private static array $sample;

    /** @return array<int, array{custodian: string, envelope: array}> */
    private function sample(): array
    {
        return self::$sample ??= (new PrukDemoGenerator())->generate(300);
    }

    private function metadata(array $item): array
    {
        return $item['envelope']['metadata'];
    }

    public function test_it_generates_exactly_the_number_asked_for(): void
    {
        $this->assertCount(300, $this->sample());
        $this->assertCount(7, (new PrukDemoGenerator())->generate(7));
    }

    public function test_the_same_seed_gives_the_same_data_and_a_smaller_run_is_a_prefix(): void
    {
        // The seeder is idempotent on pid, so reruns (at any size) must
        // regenerate the same datasets rather than new ones.
        $this->assertSame((new PrukDemoGenerator())->generate(40), array_slice($this->sample(), 0, 40));
        $this->assertNotSame(
            (new PrukDemoGenerator(seed: 1))->generate(5),
            (new PrukDemoGenerator(seed: 2))->generate(5),
        );
    }

    public function test_generating_twice_with_one_generator_gives_the_same_data(): void
    {
        $generator = new PrukDemoGenerator();

        $this->assertSame($generator->generate(10), $generator->generate(10));
    }

    public function test_every_dataset_has_a_unique_pid_used_as_its_gateway_id(): void
    {
        $pids = array_map(fn ($item) => $this->metadata($item)['required']['gatewayId'], $this->sample());

        $this->assertCount(count($pids), array_unique($pids));
        foreach ($pids as $pid) {
            $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-5[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $pid);
        }
    }

    public function test_it_is_gwdm_2_3_dataset_metadata(): void
    {
        foreach ($this->sample() as $item) {
            $this->assertSame('2.3', $item['envelope']['gwdmVersion']);
            $this->assertSame('dataset', $this->metadata($item)['required']['recordType']);
            $this->assertNotEmpty($this->metadata($item)['summary']['title']);
            $this->assertNotEmpty($this->metadata($item)['summary']['abstract']);
            $this->assertSame($item['custodian'], $this->metadata($item)['summary']['publisher']['name']);
        }
    }

    public function test_waves_link_to_a_parent_study_generated_before_them(): void
    {
        $seen = [];
        $waves = 0;

        foreach ($this->sample() as $item) {
            $metadata = $this->metadata($item);
            $waveOf = $metadata['linkage']['datasetLinkage']['waveOf'] ?? [];

            if (isset($metadata['population']['wave'])) {
                $waves++;
                $this->assertCount(1, $waveOf);
                $this->assertArrayHasKey($waveOf[0]['pid'], $seen, 'A wave must follow its parent, so linkage can resolve it.');
            } else {
                $this->assertSame([], $waveOf, 'A parent study is not a wave of anything.');
            }

            $seen[$metadata['required']['gatewayId']] = true;
        }

        $this->assertGreaterThan(150, $waves);
    }

    public function test_option_fields_only_use_values_the_search_offers(): void
    {
        foreach ($this->sample() as $item) {
            $metadata = $this->metadata($item);

            if (isset($metadata['population']['wave'])) {
                $this->assertContains($metadata['population']['wave']['waveType'], self::WAVE_TYPES);
            }
            $this->assertContains($metadata['population']['recruitment']['status'], self::RECRUITMENT_STATUSES);
            $this->assertContains($metadata['population']['followUpSchedule']['frequency'], self::FOLLOW_UP_FREQUENCIES);
            $this->assertContains($metadata['accessibility']['access']['accessServiceCategory'], self::ACCESS_SERVICES);
            $this->assertContains($metadata['accessibility']['access']['deliveryLeadTime'], self::DELIVERY_LEAD_TIMES);
        }
    }

    public function test_age_ranges_index_as_bounds(): void
    {
        foreach ($this->sample() as $item) {
            $range = $this->metadata($item)['coverage']['typicalAgeRange'];

            $this->assertMatchesRegularExpression('/^\d+-\d+$/', $range);
            [$min, $max] = array_map('intval', explode('-', $range));
            $this->assertLessThanOrEqual($max, $min);
            $this->assertLessThanOrEqual(120, $max);
        }
    }

    public function test_lists_use_the_gwdm_delimiter(): void
    {
        foreach ($this->sample() as $item) {
            $summary = $this->metadata($item)['summary'];

            foreach (['keywords', 'datasetType'] as $field) {
                $this->assertIsString($summary[$field]);
                $this->assertStringNotContainsString(',', str_replace(';,;', '', $summary[$field]));
            }
        }
    }

    public function test_every_dataset_has_named_and_described_variables(): void
    {
        foreach ($this->sample() as $item) {
            $tables = $this->metadata($item)['structuralMetadata'];
            $this->assertNotEmpty($tables);

            $columns = 0;
            foreach ($tables as $table) {
                $names = array_column($table['columns'], 'name');
                $this->assertCount(count($names), array_unique($names), 'Column names repeat within a table.');

                foreach ($table['columns'] as $column) {
                    $this->assertNotSame('', trim($column['name']));
                    $this->assertNotSame('', trim($column['description']));
                    $columns++;
                }
            }
            $this->assertGreaterThanOrEqual(8, $columns);
        }
    }

    public function test_every_filter_has_something_to_find(): void
    {
        $values = fn (callable $pick) => array_unique(array_filter(array_map(
            fn ($item) => $pick($this->metadata($item)),
            $this->sample(),
        )));

        $this->assertEqualsCanonicalizing(
            self::ACCESS_SERVICES,
            $values(fn ($m) => $m['accessibility']['access']['accessServiceCategory']),
        );
        $this->assertEqualsCanonicalizing(
            self::WAVE_TYPES,
            $values(fn ($m) => $m['population']['wave']['waveType'] ?? null),
        );
        $this->assertGreaterThanOrEqual(3, count($values(fn ($m) => $m['population']['recruitment']['status'])));
        $this->assertGreaterThanOrEqual(5, count($values(fn ($m) => $m['population']['followUpSchedule']['frequency'])));

        $spatial = implode(' ', $values(fn ($m) => $m['coverage']['spatial']));
        foreach (['England', 'Scotland', 'Wales', 'Northern Ireland', 'United Kingdom'] as $place) {
            $this->assertStringContainsString($place, $spatial);
        }

        $withSamples = array_filter($this->sample(), fn ($item) => ! empty($this->metadata($item)['tissuesSampleCollection']));
        $this->assertNotEmpty($withSamples);
        $this->assertLessThan(count($this->sample()), count($withSamples));

        $longitudinal = $values(fn ($m) => var_export($m['population']['studyDesign']['isFamilyBased'], true));
        $this->assertEqualsCanonicalizing(['true', 'false'], $longitudinal);
    }

    public function test_the_example_variable_search_has_datasets_to_find(): void
    {
        // The search panel's example is "pregnancy outcomes" AND "depression
        // measures"; some datasets must hold variables for both.
        $both = array_filter($this->sample(), function ($item) {
            $names = implode(' ', array_merge(...array_map(
                fn ($table) => array_column($table['columns'], 'name'),
                $this->metadata($item)['structuralMetadata'],
            )));

            return str_contains($names, 'birthweight') && preg_match('/epds|phq9/', $names);
        });

        $this->assertGreaterThanOrEqual(5, count($both));
    }
}
