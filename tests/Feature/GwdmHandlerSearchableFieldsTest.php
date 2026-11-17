<?php

namespace Tests\Feature;

use App\Http\Requests\Search\Search;
use App\Services\Gwdm\GwdmHandlerFactory;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * The Typesense fields advanced search filters on: the age range bounds and
 * access timescale every GWDM version has, and the GWDM 2.3 population
 * fields. Uses the fictional PRUK sample, which has all of them.
 */
class GwdmHandlerSearchableFieldsTest extends TestCase
{
    private function prukEnvelope(): array
    {
        $sample = json_decode(
            file_get_contents(base_path('tests/Unit/test_files/pruk_dummy_data/dataset_01.json')),
            true
        );

        return ['gwdmVersion' => '2.3', 'metadata' => $sample['metadata']];
    }

    public function test_indexes_the_population_and_access_fields_of_a_gwdm_2_3_record(): void
    {
        $fields = (new GwdmHandlerFactory())->resolve('2.3')->toSearchableFields($this->prukEnvelope());

        $this->assertSame(20, $fields['ageRangeMin']);
        $this->assertSame(55, $fields['ageRangeMax']);
        $this->assertSame('2-6 months', $fields['deliveryLeadTime']);
        $this->assertSame('Contact wave', $fields['waveType']);
        $this->assertSame('Closed', $fields['recruitmentStatus']);
        $this->assertSame('Every 3-5 years', $fields['followUpFrequency']);
        $this->assertTrue($fields['isLongitudinal']);
        $this->assertFalse($fields['isFamilyBased']);
    }

    public function test_leaves_missing_or_unparseable_values_null(): void
    {
        $envelope = $this->prukEnvelope();
        data_set($envelope, 'metadata.coverage.typicalAgeRange', '18+');
        data_set($envelope, 'metadata.accessibility.access.deliveryLeadTime', ' ');
        data_forget($envelope, 'metadata.population.studyDesign');

        $fields = (new GwdmHandlerFactory())->resolve('2.3')->toSearchableFields($envelope);

        $this->assertNull($fields['ageRangeMin']);
        $this->assertNull($fields['ageRangeMax']);
        $this->assertNull($fields['deliveryLeadTime']);
        $this->assertNull($fields['isLongitudinal']);
        $this->assertNull($fields['isFamilyBased']);
    }

    public function test_earlier_versions_index_the_age_range_but_no_population_fields(): void
    {
        $envelope = $this->prukEnvelope();
        $envelope['gwdmVersion'] = '2.0';

        $fields = (new GwdmHandlerFactory())->resolve('2.0')->toSearchableFields($envelope);

        $this->assertSame(20, $fields['ageRangeMin']);
        $this->assertArrayNotHasKey('waveType', $fields);
    }

    public function test_search_request_rejects_an_invalid_advanced_tree(): void
    {
        $data = [
            'type' => 'datasets',
            'advanced' => ['rules' => [['field' => 'partnerContext', 'op' => 'any', 'values' => ['HDRUK']]]],
        ];

        $validator = Validator::make($data, Search::create('/', 'POST', $data)->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('advanced', $validator->errors()->toArray());
    }

    public function test_search_request_accepts_a_valid_advanced_tree(): void
    {
        $data = [
            'type' => 'datasets',
            'advanced' => [
                'match' => 'any',
                'rules' => [['field' => 'ageRange', 'op' => 'overlaps', 'from' => 60]],
            ],
        ];

        $validator = Validator::make($data, Search::create('/', 'POST', $data)->rules());

        $this->assertFalse($validator->fails());
    }
}
