<?php

namespace Tests\Unit\Services\Comparison;

use App\Services\Comparison\DatasetComparison;
use App\Services\Comparison\FieldComparator;
use App\Services\Comparison\FieldType;
use Database\Seeders\Demo\PrukDemoGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Compares two GWDM `metadata` blocks field by field. Pure: the envelopes
 * come from the demo generator or are written inline.
 */
class DatasetComparisonTest extends TestCase
{
    private function compare(array ...$metadata): array
    {
        // Fixed here, not read from config: tuning the boundaries mustn't break these.
        $thresholds = array_fill_keys(array_column(FieldType::cases(), 'value'), 0.5);

        return (new DatasetComparison(new FieldComparator($thresholds)))->compare(...$metadata);
    }

    /** The second dataset's call against the first (the reference). */
    private function call(array $field): array
    {
        return $field['calls'][1];
    }

    /** Metadata of the nth generated demo dataset. */
    private function demo(int $n): array
    {
        return (new PrukDemoGenerator())->generate($n + 1)[$n]['envelope']['metadata'];
    }

    public function test_fields_are_keyed_by_dotted_path_with_both_values(): void
    {
        $result = $this->compare(
            ['summary' => ['title' => 'A', 'publisher' => ['name' => 'Custodian X']]],
            ['summary' => ['title' => 'B', 'publisher' => ['name' => 'Custodian X']]],
        );

        $this->assertSame(['summary.title', 'summary.publisher.name'], array_keys($result['fields']));
        $this->assertSame('identical', $this->call($result['fields']['summary.publisher.name'])['outcome']);
        $this->assertSame(['Custodian X', 'Custodian X'], $result['fields']['summary.publisher.name']['values']);
    }

    public function test_variables_bookkeeping_and_links_are_not_compared_as_fields(): void
    {
        // Variables have their own comparison; revisions and ids differ for
        // every pair of datasets and say nothing about the study.
        $metadata = [
            'required' => ['gatewayId' => 'x'],
            'structuralMetadata' => [['name' => 't', 'columns' => []]],
            'linkage' => ['datasetLinkage' => ['waveOf' => [['pid' => 'p']]]],
            'summary' => ['title' => 'T'],
        ];

        $this->assertSame(['summary.title'], array_keys($this->compare($metadata, $metadata)['fields']));
    }

    public function test_a_list_of_objects_becomes_one_list_field_per_key(): void
    {
        $field = $this->compare(
            ['tissuesSampleCollection' => [['materialType' => 'Serum'], ['materialType' => 'DNA']]],
            ['tissuesSampleCollection' => [['materialType' => 'dna']]],
        )['fields']['tissuesSampleCollection.materialType'];

        $this->assertSame('list', $field['type']);
        $this->assertSame(['Serum', 'DNA'], $field['values'][0]);
        $this->assertEqualsWithDelta(0.5, $this->call($field)['score'], 1e-6);
    }

    public function test_a_field_on_one_dataset_only_is_missing_with_null_on_the_other_side(): void
    {
        $field = $this->compare(
            ['summary' => ['title' => 'A', 'abstract' => 'Only here']],
            ['summary' => ['title' => 'A']],
        )['fields']['summary.abstract'];

        $this->assertSame(['outcome' => 'missing', 'score' => null], $this->call($field));
        $this->assertSame(['Only here', null], $field['values']);
    }

    public function test_a_field_neither_dataset_records_is_not_listed(): void
    {
        $result = $this->compare(['summary' => ['abstract' => '']], ['summary' => ['abstract' => null]]);

        $this->assertSame([], $result['fields']);
    }

    public function test_fields_follow_the_first_datasets_order_then_those_only_on_the_second(): void
    {
        $result = $this->compare(
            ['summary' => ['title' => 'A', 'contact' => 'x']],
            ['coverage' => ['spatial' => 'England'], 'summary' => ['title' => 'B', 'contact' => 'x']],
        );

        $this->assertSame(['summary.title', 'summary.contact', 'coverage.spatial'], array_keys($result['fields']));
    }

    public function test_a_fields_type_is_inferred_from_its_values(): void
    {
        $type = fn ($a, $b) => $this->compare(['f' => $a], ['f' => $b])['fields']['f']['type'];

        $this->assertSame('exact', $type(true, false));
        $this->assertSame('exact', $type('Annual', 'Biennial'));
        $this->assertSame('count', $type(4800, 8500));
        $this->assertSame('range', $type('12-25', '20-55'));
        $this->assertSame('list', $type(['a', 'b'], ['b']));
        $this->assertSame('text', $type(
            'A longitudinal cohort following children from birth to age eleven in three regions.',
            'A household panel following members of private households every year since 1991.',
        ));
    }

    public function test_a_single_value_is_compared_as_a_list_when_the_other_dataset_holds_several(): void
    {
        // "DDI" against "DDI;,;Local" must share an item, not be an exact mismatch.
        $field = $this->compare(['std' => 'DDI'], ['std' => 'DDI;,;Local'])['fields']['std'];

        $this->assertSame('list', $field['type']);
        $this->assertEqualsWithDelta(0.5, $this->call($field)['score'], 1e-6);
    }

    public function test_a_real_dataset_compared_with_itself_is_identical_everywhere(): void
    {
        // Every field of a full GWDM record is typed and read without error.
        $metadata = $this->demo(0);

        $result = $this->compare($metadata, $metadata);

        $this->assertNotEmpty($result['fields']);
        foreach ($result['fields'] as $path => $field) {
            $this->assertSame('identical', $this->call($field)['outcome'], $path);
        }
    }


    public function test_each_other_dataset_gets_its_own_call_against_the_first(): void
    {
        $field = $this->compare(['access' => 'Open'], ['access' => 'open'], ['access' => 'TRE'], ['access' => null])['fields']['access'];

        $this->assertSame(['Open', 'open', 'TRE', null], $field['values']);
        // The first dataset is the reference, so has no call of its own.
        $this->assertNull($field['calls'][0]);
        $this->assertSame(['identical', 'different', 'missing'], array_column(array_slice($field['calls'], 1), 'outcome'));
    }

    public function test_a_field_only_a_later_dataset_records_is_missing_against_the_reference(): void
    {
        $field = $this->compare(['a' => 'x'], ['a' => 'x'], ['a' => 'x', 'extra' => 'y'])['fields']['extra'];

        $this->assertSame([null, null, 'y'], $field['values']);
        $this->assertSame(['missing', 'missing'], array_column(array_slice($field['calls'], 1), 'outcome'));
    }

    public function test_the_type_comes_from_all_the_datasets_values(): void
    {
        // Only the third holds a list, but all three are compared as lists.
        $field = $this->compare(['std' => 'DDI'], ['std' => 'Local'], ['std' => 'DDI;,;Local'])['fields']['std'];

        $this->assertSame('list', $field['type']);
        $this->assertEqualsWithDelta(0.5, $field['calls'][2]['score'], 1e-6);
    }

    public function test_the_boundaries_are_returned_with_the_result(): void
    {
        $this->assertSame(0.5, $this->compare(['a' => 'x'], ['a' => 'y'])['thresholds']['text']);
    }
}
