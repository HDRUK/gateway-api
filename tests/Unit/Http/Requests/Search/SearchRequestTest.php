<?php

namespace Tests\Unit\Http\Requests\Search;

use App\Context\PartnerContext;
use App\Http\Requests\Search\Search as SearchRequest;
use App\Http\Requests\Search\SearchExport;
use Illuminate\Support\Facades\Validator;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SearchRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'scout.driver' => 'typesense',
            'typesense.variables.enabled' => true,
            'typesense.variables.partners' => 'PRUK',
        ]);
        $this->actingAsPartner('PRUK');
    }

    private function actingAsPartner(string $partner): void
    {
        $context = Mockery::mock(PartnerContext::class);
        $context->shouldReceive('getPartner')->andReturn($partner);
        $this->app->instance(PartnerContext::class, $context);
    }

    private function passes(array $data, string $request = SearchRequest::class): bool
    {
        return ! Validator::make($data, $request::create('/', 'POST', $data)->rules())->fails();
    }

    // Regression: requests without variables validate exactly as before.

    public function test_a_search_without_variables_still_passes_for_any_type(): void
    {
        foreach (['datasets', 'tools', 'dur', 'publications'] as $type) {
            $this->assertTrue($this->passes(['type' => $type, 'query' => 'asthma']), $type);
        }
    }

    public function test_a_search_without_variables_passes_for_a_partner_with_no_variable_index(): void
    {
        $this->actingAsPartner('CRUK');

        $this->assertTrue($this->passes(['type' => 'datasets', 'query' => 'asthma']));
    }

    public function test_empty_variables_are_no_constraint_and_pass_for_any_type(): void
    {
        $this->assertTrue($this->passes(['type' => 'tools', 'variables' => []]));
    }

    // The variables constraint.

    #[DataProvider('variableCases')]
    public function test_variables_validation(array $data, bool $passes): void
    {
        $this->assertSame($passes, $this->passes(['type' => 'datasets'] + $data));
    }

    public static function variableCases(): array
    {
        return [
            'with a query' => [['query' => 'pregnancy', 'variables' => ['birthweight', 'depression']], true],
            'variables alone' => [['variables' => ['bmi']], true],
            'too many terms' => [['variables' => ['a1', 'b2', 'c3', 'd4', 'e5', 'f6', 'g7']], false],
            'duplicate terms' => [['variables' => ['BMI', 'bmi']], false],
            'too short' => [['variables' => ['a']], false],
            'filter syntax' => [['variables' => ['bmi`] || x:=[`']], false],
            'not a list' => [['variables' => 'bmi'], false],
        ];
    }

    public function test_variables_are_rejected_for_types_other_than_datasets(): void
    {
        foreach (['tools', 'dur', 'publications'] as $type) {
            $this->assertFalse($this->passes(['type' => $type, 'variables' => ['bmi']]), $type);
        }
    }

    public function test_variables_are_rejected_for_a_partner_with_no_variable_index(): void
    {
        // Searching would find nothing, which reads as "no dataset matched".
        $this->actingAsPartner('CRUK');

        $this->assertFalse($this->passes(['type' => 'datasets', 'variables' => ['bmi']]));
    }

    public function test_variables_are_rejected_when_variable_search_is_disabled(): void
    {
        config(['typesense.variables.enabled' => false]);

        $this->assertFalse($this->passes(['type' => 'datasets', 'variables' => ['bmi']]));
    }

    public function test_the_export_request_validates_variables_too(): void
    {
        $this->assertTrue($this->passes(['type' => 'datasets', 'variables' => ['bmi']], SearchExport::class));
        $this->assertFalse($this->passes(['type' => 'tools', 'variables' => ['bmi']], SearchExport::class));
    }
}
