<?php

namespace Tests\Unit\Modules\Closer;

use App\Modules\Closer\CloserClient;
use App\Modules\Closer\Harvest\CloserItemType;
use App\Modules\Closer\Harvest\CloserWaveHarvester;
use App\Modules\Closer\Models\CloserQuestion;
use App\Modules\Closer\Models\CloserVariable;
use App\Modules\Closer\Models\CloserWave;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class CloserWaveHarvesterTest extends TestCase
{
    private const AGENCY = 'uk.example';

    private const WAVE = 'wave-1';

    private int $waveVersion = 3;

    private bool $failGetList = false;

    /** Fail this many _getList calls before answering normally. */
    private int $transientFailures = 0;

    /** @var array<string, array{type: string, xml: string}> */
    private array $items = [];

    /** Datasets the wave lists, in order. */
    private array $waveDatasets = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.closer.base_url' => 'https://closer.example.test',
            'services.closer.username' => 'example-user',
            'services.closer.password' => 'example-pass',
            'services.closer.batch_size' => 200,
            'services.closer.retry_sleep_ms' => 0,
        ]);
        Cache::forget(CloserClient::TOKEN_CACHE_KEY);
        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => $this->respond($request));

        // One dataset with statistics for two variables; v-3 exists in CLOSER
        // but no dataset has statistics for it.
        $this->dataset('pi-1', 'Example Questionnaire Dataset', 'example_file', 100, ['st-1', 'st-2']);
        $this->variable('v-1', 'a001', 'First question', 'q-1');
        $this->variable('v-2', 'a002', 'Second question', 'q-1');
        $this->variable('v-3', 'a003', 'Not in any dataset statistics');
        $this->statistics('st-1', 'v-1', [1 => 60, 2 => 40]);
        $this->statistics('st-2', 'v-2', [1 => 3, 2 => 97]);
        $this->question('q-1', 'QuestionItem', 'A1', 'How often do you exercise?');
    }

    // --- Fake CLOSER ---------------------------------------------------------

    private function respond(Request $request)
    {
        $path = parse_url($request->url(), PHP_URL_PATH);

        return match (true) {
            $path === '/token/createtoken' => Http::response(['access_token' => 'tok', 'expires' => 3600]),
            $path === '/api/v1/item/'.self::AGENCY.'/'.self::WAVE => Http::response([
                'Version' => $this->waveVersion,
                'Item' => $this->fragment(
                    'StudyUnit',
                    'ddi:studyunit:3_3',
                    self::WAVE,
                    $this->waveVersion,
                    '<r:Citation><r:Title><r:String>Example Wave</r:String></r:Title></r:Citation>'
                    .implode('', array_map(fn ($pi) => $this->reference('PhysicalInstanceReference', $pi), $this->waveDatasets))
                ),
            ]),
            $path === '/api/v1/item/_getList' && $this->transientFailures-- > 0 => Http::response('', 503),
            $path === '/api/v1/item/_getList' => $this->failGetList
                ? Http::response('', 500)
                : Http::response(array_map(
                    fn ($triple) => ['Identifier' => $triple['Identifier'], 'Item' => $this->items[$triple['Identifier']]['xml']],
                    $request['Identifiers'],
                )),
            default => Http::response('', 404),
        };
    }

    private function fragment(string $element, string $namespace, string $id, int $version, string $inner): string
    {
        return '<Fragment xmlns:r="ddi:reusable:3_3" xmlns="ddi:instance:3_3">'
            ."<{$element} xmlns=\"{$namespace}\"><r:Agency>".self::AGENCY."</r:Agency><r:ID>{$id}</r:ID><r:Version>{$version}</r:Version>"
            .'<r:VersionResponsibility>user@example.com</r:VersionResponsibility>'
            ."{$inner}</{$element}></Fragment>";
    }

    private function reference(string $element, string $id): string
    {
        return "<{$element}><r:Agency>".self::AGENCY."</r:Agency><r:ID>{$id}</r:ID><r:Version>1</r:Version></{$element}>";
    }

    private function dataset(string $id, string $title, string $file, int $cases, array $statistics): void
    {
        if (! in_array($id, $this->waveDatasets, true)) {
            $this->waveDatasets[] = $id;
        }
        $this->items[$id] = ['type' => CloserItemType::PHYSICAL_INSTANCE, 'xml' => $this->fragment(
            'PhysicalInstance',
            'ddi:physicalinstance:3_3',
            $id,
            1,
            "<r:UserID typeOfUserID=\"closer:sourceFileName\">{$file}</r:UserID>"
            ."<r:Citation><r:Title><r:String>{$title}</r:String></r:Title></r:Citation>"
            ."<GrossFileStructure><CaseQuantity>{$cases}</CaseQuantity></GrossFileStructure>"
            .'<StatisticalSummary>'.implode('', array_map(fn ($s) => $this->reference('VariableStatisticsReference', $s), $statistics)).'</StatisticalSummary>'
        )];
    }

    private function variable(string $id, string $name, string $label, ?string $questionId = null): void
    {
        $this->items[$id] = ['type' => CloserItemType::VARIABLE, 'xml' => $this->fragment(
            'Variable',
            'ddi:logicalproduct:3_3',
            $id,
            1,
            "<VariableName><r:String>{$name}</r:String></VariableName><r:Label><r:Content>{$label}</r:Content></r:Label>"
            .($questionId ? $this->reference('r:QuestionReference', $questionId) : '')
        )];
    }

    private function question(string $id, string $element, string $label, string $text): void
    {
        $this->items[$id] = ['type' => CloserItemType::QUESTION_ITEM, 'xml' => $this->fragment(
            $element,
            'ddi:datacollection:3_3',
            $id,
            1,
            "<r:Label><r:Content>{$label}</r:Content></r:Label>"
            ."<QuestionText><LiteralText><Text>{$text}</Text></LiteralText></QuestionText>"
        )];
    }

    private function statistics(string $id, string $variableId, array $frequencies): void
    {
        $categories = implode('', array_map(
            fn ($value, $count) => "<VariableCategory><CategoryValue><r:Value>{$value}</r:Value></CategoryValue>"
                ."<CategoryStatistic><TypeOfCategoryStatistic>Frequency</TypeOfCategoryStatistic><StatisticDouble>{$count}</StatisticDouble></CategoryStatistic></VariableCategory>",
            array_keys($frequencies),
            $frequencies,
        ));

        $this->items[$id] = ['type' => CloserItemType::VARIABLE_STATISTICS, 'xml' => $this->fragment(
            'VariableStatistics',
            'ddi:physicalinstance:3_3',
            $id,
            1,
            $this->reference('r:VariableReference', $variableId)
            .'<SummaryStatistic><TypeOfSummaryStatistic>Mean</TypeOfSummaryStatistic><StatisticDouble>1.4</StatisticDouble></SummaryStatistic>'
            ."<UnfilteredCategoryStatistics>{$categories}</UnfilteredCategoryStatistics>"
        )];
    }

    private function harvest(bool $force = false): CloserWave
    {
        return app(CloserWaveHarvester::class)->harvest(self::AGENCY, self::WAVE, $force);
    }

    /** @return string[][] identifiers asked for in each _getList call */
    private function getListCalls(): array
    {
        return Http::recorded()
            ->filter(fn ($pair) => str_ends_with($pair[0]->url(), '/item/_getList'))
            ->map(fn ($pair) => array_column($pair[0]['Identifiers'], 'Identifier'))
            ->values()
            ->all();
    }

    // --- Tests -------------------------------------------------------------------

    public function test_a_wave_is_harvested_into_datasets_and_variables_with_their_statistics(): void
    {
        $wave = $this->harvest();

        $this->assertSame(CloserWave::STATUS_COMPLETE, $wave->status);
        $this->assertSame(3, $wave->version);
        $this->assertSame('Example Wave', $wave->title);
        $this->assertSame(1, $wave->dataset_count);
        $this->assertSame(2, $wave->variable_count);
        $this->assertNotNull($wave->harvested_at);

        $dataset = $wave->datasets()->sole();
        $this->assertSame(['Example Questionnaire Dataset', 'example_file', 100], [$dataset->title, $dataset->file_name, $dataset->case_quantity]);

        $first = CloserVariable::where('identifier', 'v-1')->sole();
        $this->assertSame($dataset->id, $first->closer_dataset_id);
        $this->assertSame(['a001', 'First question'], [$first->name, $first->label]);
        $this->assertSame(['Mean' => 1.4], $first->summary_statistics);
        $this->assertSame([['value' => '1', 'count' => 60], ['value' => '2', 'count' => 40]], $first->frequencies);

        $question = CloserQuestion::sole();
        $this->assertSame(['item', 'A1', 'How often do you exercise?'], [$question->type, $question->label, $question->text]);
        $this->assertSame($question->id, $first->closer_question_id);
        $this->assertSame($question->id, CloserVariable::where('identifier', 'v-2')->value('closer_question_id'));

        // Reached through dataset -> statistics -> variable, so a variable no
        // dataset has statistics for is not harvested.
        $this->assertFalse(CloserVariable::where('identifier', 'v-3')->exists());
    }

    public function test_the_wave_is_walked_through_its_references_without_the_large_typed_set(): void
    {
        $this->harvest();

        $paths = Http::recorded()->map(fn ($pair) => parse_url($pair[0]->url(), PHP_URL_PATH));
        $this->assertFalse($paths->contains(fn ($path) => str_contains($path, '/set/')));
    }

    public function test_items_are_fetched_in_batches_of_the_configured_size(): void
    {
        config(['services.closer.batch_size' => 1]);

        $this->harvest();

        $calls = $this->getListCalls();
        $this->assertTrue(collect($calls)->every(fn ($ids) => count($ids) === 1));
        // q-1 is shared by both variables, so it is fetched once.
        $this->assertSame(['pi-1', 'st-1', 'st-2', 'v-1', 'v-2', 'q-1'], collect($calls)->flatten()->all());
    }

    public function test_an_unchanged_complete_wave_is_not_downloaded_again(): void
    {
        $this->harvest();
        $before = Http::recorded()->count();

        $this->harvest();

        $after = Http::recorded()->slice($before)->map(fn ($pair) => parse_url($pair[0]->url(), PHP_URL_PATH))->values()->all();
        $this->assertSame(['/api/v1/item/'.self::AGENCY.'/'.self::WAVE], $after);
    }

    public function test_force_harvests_an_unchanged_wave_again(): void
    {
        $this->harvest();
        $before = count($this->getListCalls());

        $this->harvest(force: true);

        $this->assertGreaterThan($before, count($this->getListCalls()));
    }

    public function test_a_variable_without_a_question_has_none(): void
    {
        $this->variable('v-2', 'a002', 'Derived variable');

        $this->harvest();

        $this->assertNull(CloserVariable::where('identifier', 'v-2')->value('closer_question_id'));
    }

    public function test_a_grid_question_is_stored_as_a_grid(): void
    {
        $this->question('q-1', 'QuestionGrid', 'B1', 'Please give all measurements taken.');

        $this->harvest();

        $this->assertSame('grid', CloserQuestion::sole()->type);
    }

    public function test_a_new_version_replaces_the_previous_rows(): void
    {
        $this->harvest();

        $this->waveVersion = 4;
        $this->variable('v-4', 'a004', 'Added in the new version');
        $this->statistics('st-4', 'v-4', [1 => 50, 2 => 50]);
        $this->dataset('pi-1', 'Example Questionnaire Dataset', 'example_file', 100, ['st-2', 'st-4']);
        $wave = $this->harvest();

        $this->assertSame(4, $wave->version);
        $this->assertSame(['v-2', 'v-4'], CloserVariable::where('closer_wave_id', $wave->id)->orderBy('identifier')->pluck('identifier')->all());
        $this->assertSame(1, $wave->datasets()->count());
    }

    public function test_a_failed_harvest_keeps_the_previous_data_and_records_the_error(): void
    {
        $this->harvest();
        $this->waveVersion = 4;
        $this->failGetList = true;

        try {
            $this->harvest();
            $this->fail('Expected the harvest to fail');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('500', $e->getMessage());
        }

        $wave = CloserWave::sole();
        $this->assertSame(CloserWave::STATUS_FAILED, $wave->status);
        $this->assertSame(3, $wave->version);
        $this->assertStringContainsString('500', $wave->error);
        $this->assertSame(2, CloserVariable::where('closer_wave_id', $wave->id)->count());
    }

    public function test_a_batch_that_fails_briefly_is_retried_rather_than_failing_the_wave(): void
    {
        $this->transientFailures = 2;

        $wave = $this->harvest();

        $this->assertSame(CloserWave::STATUS_COMPLETE, $wave->status);
        $this->assertSame(2, $wave->variable_count);
    }

    public function test_a_failed_wave_is_retried_even_at_the_same_version(): void
    {
        $this->failGetList = true;
        try {
            $this->harvest();
        } catch (RuntimeException) {
        }

        $this->failGetList = false;
        $wave = $this->harvest();

        $this->assertSame(CloserWave::STATUS_COMPLETE, $wave->status);
    }

    public function test_no_personal_data_is_stored(): void
    {
        $wave = $this->harvest();

        $stored = json_encode([$wave->toArray(), $wave->datasets()->get()->toArray(), CloserVariable::all()->toArray(), CloserQuestion::all()->toArray()]);
        $this->assertStringNotContainsString('@', $stored);
    }
}
