<?php

namespace Tests\Unit\Services\Search;

use App\Services\Search\AdvancedQueryCompiler;
use InvalidArgumentException;
use Tests\TestCase;

class AdvancedQueryCompilerTest extends TestCase
{
    private function compile(?array $tree, string $type = 'datasets'): ?string
    {
        return (new AdvancedQueryCompiler)->compile($type, $tree);
    }

    public function test_returns_null_for_no_tree_or_no_rules(): void
    {
        $this->assertNull($this->compile(null));
        $this->assertNull($this->compile([]));
        $this->assertNull($this->compile(['match' => 'any', 'rules' => []]));
    }

    public function test_joins_rules_with_and_for_match_all(): void
    {
        $this->assertSame(
            '((recruitmentStatus:=[`Recruiting`]) && (isLongitudinal:=true))',
            $this->compile([
                'match' => 'all',
                'rules' => [
                    ['field' => 'recruitmentStatus', 'op' => 'any', 'values' => ['Recruiting']],
                    ['field' => 'isLongitudinal', 'op' => 'is', 'value' => true],
                ],
            ])
        );
    }

    public function test_joins_rules_with_or_for_match_any(): void
    {
        $this->assertSame(
            '((waveType:=[`Sub-study`]) || (isCohortDiscovery:=true))',
            $this->compile([
                'match' => 'any',
                'rules' => [
                    ['field' => 'waveType', 'op' => 'any', 'values' => ['Sub-study']],
                    ['field' => 'isCohortDiscovery', 'op' => 'is', 'value' => true],
                ],
            ])
        );
    }

    public function test_defaults_to_match_all(): void
    {
        $this->assertSame(
            '((isFamilyBased:=false))',
            $this->compile(['rules' => [['field' => 'isFamilyBased', 'op' => 'is', 'value' => false]]])
        );
    }

    public function test_list_operators(): void
    {
        $rule = fn (string $op) => $this->compile([
            'rules' => [['field' => 'keywords', 'op' => $op, 'values' => ['asthma', 'COPD']]],
        ]);

        $this->assertSame('((keywords:=[`asthma`,`COPD`]))', $rule('any'));
        $this->assertSame('((keywords:=`asthma` && keywords:=`COPD`))', $rule('all'));
        $this->assertSame('((keywords:!=[`asthma`,`COPD`]))', $rule('none'));
    }

    public function test_strips_backticks_and_blank_values_and_deduplicates(): void
    {
        $this->assertSame(
            '((keywords:=[`a) || b`]))',
            $this->compile([
                'rules' => [['field' => 'keywords', 'op' => 'any', 'values' => ['`a) || b`', ' ', 'a) || b']]],
            ])
        );
    }

    public function test_number_range_with_one_or_both_bounds(): void
    {
        $range = fn (array $bounds) => $this->compile([
            'rules' => [['field' => 'populationSize', 'op' => 'between'] + $bounds],
        ]);

        $this->assertSame('((populationSize:>=1000 && populationSize:<=50000))', $range(['from' => 1000, 'to' => 50000]));
        $this->assertSame('((populationSize:>=1000))', $range(['from' => 1000]));
        $this->assertSame('((populationSize:<=50000))', $range(['to' => '50000']));
    }

    public function test_overlap_checks_the_record_range_against_the_requested_one(): void
    {
        // A study covering 20-55 overlaps a request for 50-70: its max reaches
        // the request's from, and its min is below the request's to.
        $this->assertSame(
            '((ageRangeMax:>=50 && ageRangeMin:<=70))',
            $this->compile(['rules' => [['field' => 'ageRange', 'op' => 'overlaps', 'from' => 50, 'to' => 70]]])
        );
    }

    public function test_compiles_one_level_of_nested_groups(): void
    {
        $this->assertSame(
            '((dataType:=[`Lifestyle`]) && ((waveType:=[`Contact wave`]) || (recruitmentStatus:=[`Recruiting`])))',
            $this->compile([
                'match' => 'all',
                'rules' => [
                    ['field' => 'dataType', 'op' => 'any', 'values' => ['Lifestyle']],
                    [
                        'match' => 'any',
                        'rules' => [
                            ['field' => 'waveType', 'op' => 'any', 'values' => ['Contact wave']],
                            ['field' => 'recruitmentStatus', 'op' => 'any', 'values' => ['Recruiting']],
                        ],
                    ],
                ],
            ])
        );
    }

    public function test_drops_an_empty_nested_group(): void
    {
        $this->assertSame(
            '((isLongitudinal:=true))',
            $this->compile([
                'rules' => [
                    ['field' => 'isLongitudinal', 'op' => 'is', 'value' => true],
                    ['match' => 'any', 'rules' => []],
                ],
            ])
        );
    }

    /**
     * @dataProvider invalidTrees
     */
    public function test_rejects_invalid_trees(array $tree): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->compile($tree);
    }

    public static function invalidTrees(): array
    {
        $rule = ['field' => 'isLongitudinal', 'op' => 'is', 'value' => true];

        return [
            'unknown field' => [['rules' => [['field' => 'partnerContext', 'op' => 'any', 'values' => ['HDRUK']]]]],
            'raw filter syntax as a field' => [['rules' => [['field' => 'title:*', 'op' => 'any', 'values' => ['x']]]]],
            'operator from another kind' => [['rules' => [['field' => 'keywords', 'op' => 'between', 'from' => 1]]]],
            'unknown match' => [['match' => 'xor', 'rules' => [$rule]]],
            'rules not a list' => [['rules' => ['a' => $rule]]],
            'list with no values' => [['rules' => [['field' => 'keywords', 'op' => 'any', 'values' => []]]]],
            'boolean as a string' => [['rules' => [['field' => 'isLongitudinal', 'op' => 'is', 'value' => 'true']]]],
            'range with no bounds' => [['rules' => [['field' => 'populationSize', 'op' => 'between']]]],
            'range bound not numeric' => [['rules' => [['field' => 'populationSize', 'op' => 'between', 'from' => '1 || x']]]],
            'range reversed' => [['rules' => [['field' => 'ageRange', 'op' => 'overlaps', 'from' => 70, 'to' => 50]]]],
            'nested too deep' => [['rules' => [['rules' => [['rules' => [$rule]]]]]]],
            'too many rules' => [['rules' => array_fill(0, AdvancedQueryCompiler::MAX_RULES + 1, $rule)]],
        ];
    }

    public function test_rejects_fields_for_a_type_without_an_advanced_map(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->compile(['rules' => [['field' => 'license', 'op' => 'any', 'values' => ['MIT']]]], 'tools');
    }
}
