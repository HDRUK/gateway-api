<?php

namespace Tests\Unit\Services\Search;

use App\Context\PartnerContext;
use App\Http\Requests\Search\MetadataSummarySearch as MetadataSummarySearchRequest;
use App\Models\DatasetVersion;
use App\Services\Search\MetadataSummaryIndex;
use App\Services\Search\MetadataSummarySearch;
use App\Services\TypesenseService;
use Illuminate\Support\Facades\Validator;
use Laravel\Pennant\Feature;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MetadataSummarySearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Pin the limits, so a config change doesn't silently change what these tests prove.
        config([
            'typesense.summary_embedding_config.distance_threshold' => 0.70,
            'typesense.summary_embedding_config.per_dataset' => 5,
            'typesense.summary_embedding_config.max_datasets' => 50,
        ]);
    }

    /** One Typesense hit: a summary field document of a dataset. */
    private function hit(int $datasetId, string $field, float $distance, int $tokensMatched = 0, string $text = ''): array
    {
        return [
            'document' => [
                'id' => "{$datasetId}-{$field}",
                'dataset_id' => (string) $datasetId,
                'datasetTitle' => "Study {$datasetId}",
                'publisherName' => 'Example Custodian',
                'partnerContext' => 'HDRUK',
                'field' => $field,
                'text' => $text !== '' ? $text : "Some {$field} text.",
            ],
            'text_match_info' => ['tokens_matched' => $tokensMatched],
            'vector_distance' => $distance,
        ];
    }

    private function group(int $datasetId, array $hits): array
    {
        return ['group_key' => [(string) $datasetId], 'hits' => $hits];
    }

    /**
     * The service with a fake Typesense. $searches receives the queries the
     * service sent, so a test can check them.
     */
    private function service(array $results, ?array &$searches = null, array $datasetHits = [], string $partner = 'HDRUK'): MetadataSummarySearch
    {
        $typesense = Mockery::mock(TypesenseService::class);
        $typesense->shouldReceive('multiSearch')
            ->once()
            ->andReturnUsing(function (array $sent) use ($results, &$searches) {
                $searches = $sent;

                return ['results' => $results];
            });
        $typesense->shouldReceive('rawSearch')->andReturn(['hits' => $datasetHits]);

        $partnerContext = Mockery::mock(PartnerContext::class);
        $partnerContext->shouldReceive('getPartner')->andReturn($partner);

        return new MetadataSummarySearch($typesense, $partnerContext);
    }

    // --- Ranking and sifting ---------------------------------------------

    public function test_match_all_keeps_only_datasets_matching_every_term_best_first(): void
    {
        $service = $this->service([
            ['grouped_hits' => [
                $this->group(1, [$this->hit(1, 'abstract', 0.6)]),
                $this->group(2, [$this->hit(2, 'keywords', 0.4)]),
                $this->group(3, [$this->hit(3, 'title', 0.5)]),
            ]],
            ['grouped_hits' => [
                $this->group(2, [$this->hit(2, 'description', 0.2)]),
                $this->group(1, [$this->hit(1, 'description', 0.3)]),
            ]],
        ]);

        $result = $service->search(['childhood asthma', 'air pollution']);

        // 3 matches only the first term; 2 (0.6 in total) is closer than 1 (0.9).
        $this->assertSame([2, 1], array_column($result['datasets'], 'id'));
        $this->assertSame('all', $result['match']);
        $this->assertSame('abstract', $result['datasets'][1]['matches'][0][0]['field']);
        $this->assertSame('description', $result['datasets'][1]['matches'][1][0]['field']);
    }

    public function test_match_any_ranks_datasets_matching_more_terms_first(): void
    {
        $service = $this->service([
            ['grouped_hits' => [
                $this->group(3, [$this->hit(3, 'title', 0.1)]),
                $this->group(1, [$this->hit(1, 'abstract', 0.6)]),
            ]],
            ['grouped_hits' => [
                $this->group(1, [$this->hit(1, 'keywords', 0.6)]),
            ]],
        ]);

        $result = $service->search(['childhood asthma', 'air pollution'], MetadataSummarySearch::MATCH_ANY);

        $this->assertSame([1, 3], array_column($result['datasets'], 'id'));
        $this->assertSame([], $result['datasets'][1]['matches'][1]);
    }

    public function test_word_matches_come_before_meaning_matches(): void
    {
        $service = $this->service([
            ['grouped_hits' => [$this->group(1, [
                $this->hit(1, 'description', 0.3),
                $this->hit(1, 'keywords', 0.5, tokensMatched: 2),
            ])]],
        ]);

        $matches = $service->search(['childhood asthma'])['datasets'][0]['matches'][0];

        $this->assertSame(['keywords', 'description'], array_column($matches, 'field'));
        $this->assertSame(['words', 'meaning'], array_column($matches, 'matchedBy'));
    }

    public function test_hits_sharing_only_some_words_need_to_be_close_in_meaning(): void
    {
        $service = $this->service([
            ['grouped_hits' => [$this->group(1, [
                $this->hit(1, 'description', 0.9, tokensMatched: 1),
                $this->hit(1, 'abstract', 0.5, tokensMatched: 1),
            ])]],
        ]);

        $matches = $service->search(['childhood asthma'])['datasets'][0]['matches'][0];

        $this->assertSame(['abstract'], array_column($matches, 'field'));
        $this->assertSame('meaning', $matches[0]['matchedBy']);
    }

    public function test_a_match_carries_the_field_and_its_text(): void
    {
        $service = $this->service([
            ['grouped_hits' => [$this->group(7, [
                $this->hit(7, 'keywords', 0.31234, tokensMatched: 1, text: 'Asthma, COPD'),
            ])]],
        ]);

        $dataset = $service->search(['asthma'])['datasets'][0];

        $this->assertSame('Study 7', $dataset['title']);
        $this->assertSame('Example Custodian', $dataset['publisherName']);
        // matches[0] is the list for the first (and only) term.
        $this->assertSame([[
            'id' => '7-keywords',
            'field' => 'keywords',
            'text' => 'Asthma, COPD',
            'matchedBy' => 'words',
            'distance' => 0.312,
        ]], $dataset['matches'][0]);
    }

    // --- The query sent to Typesense --------------------------------------

    public function test_each_term_queries_the_summary_collection_by_text_and_meaning(): void
    {
        $searches = null;
        $service = $this->service([['grouped_hits' => []], ['grouped_hits' => []]], $searches);

        $service->search(['childhood asthma', 'air pollution']);

        $this->assertCount(2, $searches);
        $this->assertSame(['childhood asthma', 'air pollution'], array_column($searches, 'q'));
        $this->assertSame(MetadataSummaryIndex::collectionName(), $searches[0]['collection']);
        $this->assertSame('text,embedding', $searches[0]['query_by']);
        $this->assertSame('embedding', $searches[0]['exclude_fields']);
        $this->assertStringContainsString('distance_threshold: 0.7', $searches[0]['vector_query']);
        $this->assertSame('dataset_id', $searches[0]['group_by']);
        $this->assertSame(5, $searches[0]['group_limit']);
    }

    // --- Partner visibility -----------------------------------------------

    public function test_a_partner_caller_only_searches_its_own_partner(): void
    {
        $searches = null;
        $service = $this->service([['grouped_hits' => []]], $searches, partner: 'PRUK');

        $service->search(['asthma']);

        $this->assertSame('partnerContext:=[`PRUK`]', $searches[0]['filter_by']);
    }

    public function test_an_hdruk_caller_only_sees_partners_whose_flag_is_on(): void
    {
        Feature::flushCache();
        Feature::deactivate(array_values(PartnerContext::CROSS_CONTEXT_FLAGS));
        Feature::activate('RenderSchemaPRUK');
        $searches = null;
        $service = $this->service([['grouped_hits' => []]], $searches, partner: 'HDRUK');

        $service->search(['asthma']);

        $this->assertSame('partnerContext:=[`HDRUK`,`PRUK`]', $searches[0]['filter_by']);
    }

    public function test_an_hdruk_caller_with_no_flags_on_sees_only_hdruk(): void
    {
        Feature::flushCache();
        Feature::deactivate(array_values(PartnerContext::CROSS_CONTEXT_FLAGS));
        $searches = null;
        $service = $this->service([['grouped_hits' => []]], $searches, partner: 'HDRUK');

        $service->search(['asthma']);

        $this->assertSame('partnerContext:=[`HDRUK`]', $searches[0]['filter_by']);
    }

    // --- Comparison mode (dataset_ids) -------------------------------------

    public function test_dataset_ids_keep_their_order_and_fill_in_unmatched_datasets(): void
    {
        $searches = null;
        $service = $this->service(
            [['grouped_hits' => [$this->group(5, [$this->hit(5, 'abstract', 0.4)])]]],
            $searches,
            [['document' => ['dataset_id' => '9', 'title' => 'Study 9', 'publisherName' => 'Example Custodian']]],
            partner: 'PRUK',
        );

        $result = $service->search(['asthma'], MetadataSummarySearch::MATCH_ALL, [9, 5, 404]);

        $this->assertStringContainsString('dataset_id:=[9,5,404]', $searches[0]['filter_by']);
        // 404 isn't readable by this caller (or isn't indexed), so it drops out.
        $this->assertSame([9, 5], array_column($result['datasets'], 'id'));
        $this->assertSame('Study 9', $result['datasets'][0]['title']);
        $this->assertSame([[]], $result['datasets'][0]['matches']);
    }

    public function test_dataset_ids_without_terms_return_just_the_datasets(): void
    {
        $typesense = Mockery::mock(TypesenseService::class);
        $typesense->shouldNotReceive('multiSearch');
        $typesense->shouldReceive('rawSearch')
            ->once()
            ->with((new DatasetVersion())->searchableAs(), '*', Mockery::on(
                fn (array $options) => $options['filter_by'] === 'partnerContext:=[`PRUK`] && dataset_id:=[7]'
            ))
            ->andReturn(['hits' => [
                ['document' => ['dataset_id' => '7', 'title' => 'Study 7', 'publisherName' => 'Example Custodian']],
            ]]);
        $partner = Mockery::mock(PartnerContext::class);
        $partner->shouldReceive('getPartner')->andReturn('PRUK');

        $result = (new MetadataSummarySearch($typesense, $partner))->search([], MetadataSummarySearch::MATCH_ANY, [7]);

        $this->assertSame([['id' => 7, 'title' => 'Study 7', 'publisherName' => 'Example Custodian', 'matches' => []]], $result['datasets']);
    }

    public function test_results_are_capped_at_max_datasets(): void
    {
        config(['typesense.summary_embedding_config.max_datasets' => 2]);
        $service = $this->service([
            ['grouped_hits' => [
                $this->group(1, [$this->hit(1, 'title', 0.1)]),
                $this->group(2, [$this->hit(2, 'title', 0.2)]),
                $this->group(3, [$this->hit(3, 'title', 0.3)]),
            ]],
        ]);

        $result = $service->search(['asthma']);

        $this->assertSame([1, 2], array_column($result['datasets'], 'id'));
    }

    // --- Request validation -----------------------------------------------

    #[DataProvider('requestCases')]
    public function test_request_validation(array $data, bool $passes): void
    {
        $validator = Validator::make($data, MetadataSummarySearchRequest::create('/', 'POST', $data)->rules());

        $this->assertSame($passes, ! $validator->fails());
    }

    public static function requestCases(): array
    {
        return [
            'two terms' => [['terms' => ['childhood asthma', 'air pollution']], true],
            'compare mode' => [['terms' => ['asthma'], 'match' => 'any', 'dataset_ids' => [1, 2]], true],
            'compare with no terms yet' => [['dataset_ids' => [1, 2]], true],
            'no terms' => [['terms' => []], false],
            'nothing at all' => [[], false],
            'too many terms' => [['terms' => ['a1', 'b2', 'c3', 'd4', 'e5', 'f6', 'g7']], false],
            'term too short' => [['terms' => ['a']], false],
            'term too long' => [['terms' => [str_repeat('a', 101)]], false],
            'duplicate terms' => [['terms' => ['COPD', 'copd']], false],
            'filter syntax' => [['terms' => ['copd`] || x:=[`']], false],
            'html' => [['terms' => ['<script>']], false],
            'unknown match' => [['terms' => ['asthma'], 'match' => 'most'], false],
            'bad dataset id' => [['terms' => ['asthma'], 'dataset_ids' => ['abc']], false],
            'zero dataset id' => [['terms' => ['asthma'], 'dataset_ids' => [0]], false],
            'too many dataset ids' => [['dataset_ids' => range(1, 51)], false],
        ];
    }
}
