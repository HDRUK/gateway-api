<?php

namespace Tests\Unit\Services\Comparison;

use App\Services\Comparison\FieldComparator;
use App\Services\Comparison\FieldType;
use App\Services\Comparison\Outcome;
use PHPUnit\Framework\TestCase;

/**
 * One similarity method per field type: free text by cosine similarity, lists
 * by Jaccard, ranges by interval overlap, counts by ratio, the rest by exact
 * match. Pure: no database, no Laravel.
 */
class FieldComparatorTest extends TestCase
{
    private const DELIMITER = ';,;';

    /**
     * Fixed here, not read from config/comparison.php: the boundaries are the
     * spike's tuning knob, and tuning them mustn't break these tests.
     */
    private const SIMILAR_FROM = 0.5;

    /** @return array{0: Outcome, 1: ?float} */
    private function compare(FieldType $type, mixed $a, mixed $b): array
    {
        $thresholds = array_fill_keys(array_column(FieldType::cases(), 'value'), self::SIMILAR_FROM);
        $result = (new FieldComparator($thresholds))->compare($type, $a, $b);

        return [$result['outcome'], $result['score']];
    }

    public function test_a_value_missing_on_either_side_is_missing_not_different(): void
    {
        // "Not recorded" says nothing about whether two datasets differ.
        foreach ([null, '', '   ', [], self::DELIMITER] as $absent) {
            foreach (FieldType::cases() as $type) {
                $this->assertSame([Outcome::Missing, null], $this->compare($type, $absent, 'x'), $type->value);
                $this->assertSame([Outcome::Missing, null], $this->compare($type, 'x', $absent), $type->value);
            }
        }
    }

    public function test_zero_and_false_are_values_not_missing(): void
    {
        $this->assertSame([Outcome::Identical, 1.0], $this->compare(FieldType::Count, 0, 0));
        $this->assertSame(Outcome::Different, $this->compare(FieldType::Exact, false, true)[0]);
    }

    public function test_text_ignores_case_punctuation_and_stop_words(): void
    {
        $this->assertSame([Outcome::Identical, 1.0], $this->compare(
            FieldType::Text,
            'A longitudinal cohort of children, followed from birth.',
            'longitudinal COHORT children followed from birth',
        ));
    }

    public function test_text_on_the_same_theme_scores_between_unrelated_and_identical_text(): void
    {
        $a = 'A longitudinal cohort following children from birth to investigate early-life determinants of health and family wellbeing.';
        $related = $this->compare(FieldType::Text, $a, 'A longitudinal cohort following young people to investigate determinants of mental health.')[1];
        $unrelated = $this->compare(FieldType::Text, $a, 'Hospital admissions for fractures among older adults.')[1];

        $this->assertSame(0.0, $unrelated);
        $this->assertGreaterThan($unrelated, $related);
        $this->assertLessThan(1.0, $related);
    }

    public function test_lists_ignore_order_case_and_spacing_and_accept_the_gateway_delimiter_or_arrays(): void
    {
        $this->assertSame([Outcome::Identical, 1.0], $this->compare(
            FieldType::List,
            'Serum'.self::DELIMITER.' whole blood ',
            ['WHOLE BLOOD', 'serum'],
        ));
    }

    public function test_list_score_is_shared_items_over_all_distinct_items_with_no_fuzziness(): void
    {
        $this->assertSame(1.0, $this->compare(FieldType::List, ['a', 'a', 'b'], ['a', 'b'])[1]);

        // E11.9 is a child of E11 in ICD-10, but they are different codes here.
        [$outcome, $score] = $this->compare(FieldType::List, 'E11.9;,;I10', 'E11;,;I10');
        $this->assertEqualsWithDelta(1 / 3, $score, 1e-6);
        $this->assertSame(Outcome::Different, $outcome);
    }

    public function test_range_score_is_overlap_over_combined_span(): void
    {
        // 20-55 and 30-60: overlap 25, combined span 40.
        [$outcome, $score] = $this->compare(FieldType::Range, '20-55', '30-60');
        $this->assertEqualsWithDelta(0.625, $score, 1e-6);
        $this->assertSame(Outcome::Similar, $outcome);

        $this->assertSame(1.0, $this->compare(FieldType::Range, '20–55', '55 - 20')[1]);
        $this->assertSame(0.0, $this->compare(FieldType::Range, '10-20', '30-40')[1]);
    }

    public function test_a_single_point_range_equals_itself_and_one_that_cannot_be_read_is_missing(): void
    {
        // Birth cohorts are "0-0": a single age, not an empty interval.
        $this->assertSame([Outcome::Identical, 1.0], $this->compare(FieldType::Range, '0-0', '0-0'));
        $this->assertSame([Outcome::Different, 0.0], $this->compare(FieldType::Range, '0-0', '20-55'));
        $this->assertSame([Outcome::Missing, null], $this->compare(FieldType::Range, 'adults', '20-55'));
    }

    public function test_count_score_is_smaller_over_larger_whichever_way_round(): void
    {
        $a = $this->compare(FieldType::Count, 4800, 8500)[1];

        $this->assertEqualsWithDelta(4800 / 8500, $a, 1e-6);
        $this->assertSame($a, $this->compare(FieldType::Count, 8500, 4800)[1]);
        $this->assertSame(1.0, $this->compare(FieldType::Count, '8,500', 8500)[1]);
        $this->assertSame(0.0, $this->compare(FieldType::Count, 0, 500)[1]);
    }

    public function test_exact_match_ignores_case_and_padding(): void
    {
        $this->assertSame([Outcome::Identical, 1.0], $this->compare(FieldType::Exact, ' Free ', 'free'));
        $this->assertSame([Outcome::Different, 0.0], $this->compare(FieldType::Exact, 'Free', 'Low cost'));
    }

    public function test_a_score_exactly_on_the_similar_boundary_counts_as_similar(): void
    {
        // {a,b,c} and {b,c,d} score exactly SIMILAR_FROM.
        $this->assertSame([Outcome::Similar, self::SIMILAR_FROM], $this->compare(FieldType::List, ['a', 'b', 'c'], ['b', 'c', 'd']));
    }
}
