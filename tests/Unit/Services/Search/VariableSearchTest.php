<?php

namespace Tests\Unit\Services\Search;

use App\Context\PartnerContext;
use App\Http\Requests\Search\VariableSearch as VariableSearchRequest;
use App\Services\Search\VariableIndex;
use App\Services\Search\VariableSearch;
use App\Services\TypesenseService;
use Illuminate\Support\Facades\Validator;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VariableSearchTest extends TestCase
{
    private function hit(int $datasetId, string $name, float $distance, int $textMatch = 0): array
    {
        return [
            'document' => [
                'id' => "{$datasetId}-0-{$name}",
                'dataset_id' => (string) $datasetId,
                'datasetTitle' => "Study {$datasetId}",
                'publisherName' => 'Example Custodian',
                'tableName' => 'Questionnaire',
                'name' => $name,
                'description' => "About {$name}.",
                'dataType' => 'integer',
            ],
            'text_match' => $textMatch,
            'text_match_info' => ['tokens_matched' => $textMatch > 0 ? 2 : 0],
            'vector_distance' => $distance,
        ];
    }

    private function group(int $datasetId, array $hits): array
    {
        return ['group_key' => [(string) $datasetId], 'hits' => $hits];
    }

    private function service(array $results, ?array &$searches = null, array $datasetHits = []): VariableSearch
    {
        $typesense = Mockery::mock(TypesenseService::class);
        $typesense->shouldReceive('multiSearch')
            ->once()
            ->andReturnUsing(function (array $sent) use ($results, &$searches) {
                $searches = $sent;

                return ['results' => $results];
            });
        $typesense->shouldReceive('rawSearch')->andReturn(['hits' => $datasetHits]);

        $partner = Mockery::mock(PartnerContext::class);
        $partner->shouldReceive('getPartner')->andReturn('PRUK');

        return new VariableSearch($typesense, $partner);
    }

    public function test_match_all_keeps_only_datasets_matching_every_term_best_first(): void
    {
        $service = $this->service([
            ['grouped_hits' => [
                $this->group(1, [$this->hit(1, 'birthweight_g', 0.6)]),
                $this->group(2, [$this->hit(2, 'gestation_weeks', 0.4)]),
                $this->group(3, [$this->hit(3, 'preeclampsia', 0.5)]),
            ]],
            ['grouped_hits' => [
                $this->group(2, [$this->hit(2, 'epds_total', 0.2)]),
                $this->group(1, [$this->hit(1, 'phq9_total', 0.3)]),
            ]],
        ]);

        $result = $service->search(['pregnancy outcomes', 'depression measures']);

        // 3 matches only the first term; 2 (0.6 in total) is closer than 1 (0.9).
        $this->assertSame([2, 1], array_column($result['datasets'], 'id'));
        $this->assertSame('all', $result['match']);
        $this->assertSame('birthweight_g', $result['datasets'][1]['matches'][0][0]['name']);
        $this->assertSame('phq9_total', $result['datasets'][1]['matches'][1][0]['name']);
    }

    public function test_match_any_ranks_datasets_matching_more_terms_first(): void
    {
        $service = $this->service([
            ['grouped_hits' => [
                $this->group(3, [$this->hit(3, 'preeclampsia', 0.1)]),
                $this->group(1, [$this->hit(1, 'birthweight_g', 0.6)]),
            ]],
            ['grouped_hits' => [
                $this->group(1, [$this->hit(1, 'phq9_total', 0.6)]),
            ]],
        ]);

        $result = $service->search(['pregnancy outcomes', 'depression measures'], VariableSearch::MATCH_ANY);

        $this->assertSame([1, 3], array_column($result['datasets'], 'id'));
        $this->assertSame([], $result['datasets'][1]['matches'][1]);
    }

    public function test_word_matches_come_before_meaning_matches(): void
    {
        $service = $this->service([
            ['grouped_hits' => [$this->group(1, [
                $this->hit(1, 'gdm_diagnosis', 0.3),
                $this->hit(1, 'pregnancy_outcome', 0.5, 12345),
            ])]],
        ]);

        $variables = $service->search(['pregnancy outcome'])['datasets'][0]['matches'][0];

        $this->assertSame(['pregnancy_outcome', 'gdm_diagnosis'], array_column($variables, 'name'));
        $this->assertSame(['words', 'meaning'], array_column($variables, 'matchedBy'));
    }

    public function test_hits_sharing_only_some_words_need_to_be_close_in_meaning(): void
    {
        $partial = $this->hit(1, 'clinic_measured_at', 0.9, 999);
        $partial['text_match_info']['tokens_matched'] = 1;
        $closePartial = $this->hit(1, 'phq9_total', 0.5, 999);
        $closePartial['text_match_info']['tokens_matched'] = 1;

        $service = $this->service([
            ['grouped_hits' => [$this->group(1, [$partial, $closePartial])]],
        ]);

        $variables = $service->search(['depression measures'])['datasets'][0]['matches'][0];

        $this->assertSame(['phq9_total'], array_column($variables, 'name'));
        $this->assertSame('meaning', $variables[0]['matchedBy']);
    }

    public function test_searches_are_scoped_to_the_partner_and_threshold(): void
    {
        $searches = null;
        $service = $this->service([['grouped_hits' => []]], $searches);

        $service->search(['blood pressure']);

        $this->assertSame(VariableIndex::collectionName(), $searches[0]['collection']);
        $this->assertSame('partnerContext:=[`PRUK`]', $searches[0]['filter_by']);
        $this->assertStringContainsString('distance_threshold: 0.7', $searches[0]['vector_query']);
        $this->assertSame('dataset_id', $searches[0]['group_by']);
    }

    public function test_dataset_ids_keep_their_order_and_fill_in_unmatched_datasets(): void
    {
        $searches = null;
        $service = $this->service(
            [['grouped_hits' => [$this->group(5, [$this->hit(5, 'sbp', 0.4)])]]],
            $searches,
            [['document' => ['dataset_id' => '9', 'title' => 'Study 9', 'publisherName' => 'Example Custodian']]],
        );

        $result = $service->search(['blood pressure'], VariableSearch::MATCH_ALL, [9, 5, 404]);

        $this->assertStringContainsString('dataset_id:=[9,5,404]', $searches[0]['filter_by']);
        // 404 isn't in the partner's index, so it drops out.
        $this->assertSame([9, 5], array_column($result['datasets'], 'id'));
        $this->assertSame('Study 9', $result['datasets'][0]['title']);
        $this->assertSame([[]], $result['datasets'][0]['matches']);
    }

    public function test_dataset_ids_without_terms_return_just_the_datasets(): void
    {
        $typesense = Mockery::mock(TypesenseService::class);
        $typesense->shouldNotReceive('multiSearch');
        $typesense->shouldReceive('rawSearch')->andReturn(['hits' => [
            ['document' => ['dataset_id' => '7', 'title' => 'Study 7', 'publisherName' => 'Example Custodian']],
        ]]);
        $partner = Mockery::mock(PartnerContext::class);
        $partner->shouldReceive('getPartner')->andReturn('PRUK');

        $result = (new VariableSearch($typesense, $partner))->search([], VariableSearch::MATCH_ANY, [7]);

        $this->assertSame([['id' => 7, 'title' => 'Study 7', 'publisherName' => 'Example Custodian', 'matches' => []]], $result['datasets']);
    }

    public function test_label_reads_a_column_name_as_words(): void
    {
        $this->assertSame('ghq12 score', VariableIndex::labelFor('ghq12_score'));
        $this->assertSame('Birth weight g', VariableIndex::labelFor('Birth.weight-g'));
    }

    #[DataProvider('requestCases')]
    public function test_request_validation(array $data, bool $passes): void
    {
        $validator = Validator::make($data, VariableSearchRequest::create('/', 'POST', $data)->rules());

        $this->assertSame($passes, ! $validator->fails());
    }

    public static function requestCases(): array
    {
        return [
            'two terms' => [['terms' => ['pregnancy outcomes', 'depression']], true],
            'compare mode' => [['terms' => ['bmi'], 'match' => 'any', 'dataset_ids' => [1, 2]], true],
            'no terms' => [['terms' => []], false],
            'compare with no terms yet' => [['dataset_ids' => [1, 2]], true],
            'too many terms' => [['terms' => ['a1', 'b2', 'c3', 'd4', 'e5', 'f6', 'g7']], false],
            'duplicate terms' => [['terms' => ['BMI', 'bmi']], false],
            'filter syntax' => [['terms' => ['bmi`] || x:=[`']], false],
            'unknown match' => [['terms' => ['bmi'], 'match' => 'most'], false],
            'bad dataset id' => [['terms' => ['bmi'], 'dataset_ids' => ['abc']], false],
        ];
    }
}
