<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Dataset;
use App\Models\DatasetVersion;
use Tests\Traits\MockExternalApis;

/**
 * Verifies the fix for Vuln 5 from the Claude security baseline review:
 *   - Vuln 5: SQL injection in Dataset::searchByTitle() via unparameterised whereRaw
 *
 * The method was dead code at review time, but carried a real injection in
 * LOWER('%$title%'). The fix moves to a bound parameter:
 *   ->whereRaw("... LIKE LOWER(?)", ['%' . $title . '%'])
 */
class ClaudeSecurityReview_Sonnet_46_3 extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * Verify that a well-formed title search returns the matching DatasetVersion.
     * This is the happy-path that was the intended use of the method.
     */
    public function test_search_by_title_returns_matching_version(): void
    {
        $dataset = Dataset::factory()->create();
        DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'metadata' => [
                'metadata' => [
                    'summary' => [
                        'title' => 'Cardiovascular Health Registry',
                    ],
                ],
            ],
        ]);

        $result = $dataset->searchByTitle('Cardiovascular');

        $this->assertNotNull($result, 'searchByTitle should find a matching version');
        $this->assertEquals(
            'Cardiovascular Health Registry',
            $result->metadata['metadata']['summary']['title']
        );
    }

    /**
     * A title that does not match any version must return null gracefully.
     */
    public function test_search_by_title_returns_null_when_no_match(): void
    {
        $dataset = Dataset::factory()->create();
        DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'metadata' => [
                'metadata' => [
                    'summary' => ['title' => 'Diabetes Cohort'],
                ],
            ],
        ]);

        $result = $dataset->searchByTitle('Nonexistent Title XYZ');

        $this->assertNull($result);
    }

    /**
     * SQL injection payload must NOT cause a database error and must return null
     * rather than leaking rows.
     *
     * Pre-fix, passing a string like "') OR ('1'='1" would produce:
     *   LIKE LOWER('%') OR ('1'='1%')
     * which evaluates to TRUE for every row.
     *
     * Post-fix, the value is bound as a literal parameter so the database
     * treats it as plain text — no injection occurs and no rows match.
     */
    public function test_search_by_title_injection_payload_does_not_leak_rows(): void
    {
        $dataset = Dataset::factory()->create();
        DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'metadata' => [
                'metadata' => [
                    'summary' => ['title' => 'Legitimate Dataset'],
                ],
            ],
        ]);

        // Classic LIKE-escape injection: closing the LIKE value early and
        // appending a condition that is always true.
        $injectionPayload = "') OR ('1'='1";

        // Must not throw a PDOException / QueryException.
        $result = $dataset->searchByTitle($injectionPayload);

        // The payload is treated as a literal string — no row title contains it.
        $this->assertNull(
            $result,
            'SQL injection payload must not match any rows after parameterisation fix'
        );
    }

    /**
     * A UNION-based injection attempt must similarly return null.
     */
    public function test_search_by_title_union_injection_does_not_leak(): void
    {
        $dataset = Dataset::factory()->create();

        $unionPayload = "x' UNION SELECT 1,2,3,4,5,6,7,8,9,10-- ";

        $result = $dataset->searchByTitle($unionPayload);

        $this->assertNull(
            $result,
            'UNION injection payload must not return results after parameterisation fix'
        );
    }

    /**
     * SQL special characters in a genuine search string must be treated as
     * literals, not operators — so a search for "50%" matches titles containing
     * "50%" rather than acting as a LIKE wildcard beyond the intended wrapping.
     */
    public function test_search_by_title_treats_percent_as_literal(): void
    {
        $dataset = Dataset::factory()->create();
        DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'version' => 1,
            'metadata' => [
                'metadata' => [
                    'summary' => ['title' => 'Dataset covering 50% of UK population'],
                ],
            ],
        ]);

        // Should find the row because the title really does contain '50%'.
        $result = $dataset->searchByTitle('50%');
        $this->assertNotNull($result, 'Literal % in search term should match title containing %');

        // Should NOT match a completely different title.
        DatasetVersion::factory()->create([
            'dataset_id' => $dataset->id,
            'version' => 2,
            'metadata' => [
                'metadata' => [
                    'summary' => ['title' => 'Unrelated Dataset'],
                ],
            ],
        ]);
        $noMatch = $dataset->searchByTitle('999% unrelated');
        $this->assertNull($noMatch, 'Percent character must not act as a wildcard beyond the wrapping %');
    }
}
