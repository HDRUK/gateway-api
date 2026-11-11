<?php

namespace Tests\Unit\SearchProviders;

use App\SearchProviders\ARDC;
use Tests\TestCase;

class ARDCTest extends TestCase
{
    public function test_partner_hit_without_an_id_links_to_the_portal(): void
    {
        $portal = (new ARDC())->toPartnerPortalResult(
            ['hits' => [['title' => 'Title']], 'total' => 1],
            'asthma'
        )->toArray();

        $this->assertSame('https://researchdata.edu.au/health/view', $portal['hits'][0]['url']);
        $this->assertSame('', $portal['hits'][0]['description']);
    }

    public function test_search_url_omits_the_query_when_it_is_empty(): void
    {
        $portal = (new ARDC())->toPartnerPortalResult(['hits' => [], 'total' => 0], '')->toArray();

        $this->assertSame('https://researchdata.edu.au/health/search?filter-type=all-fields', $portal['search_url']);
    }

    public function test_search_url_encodes_the_query(): void
    {
        $portal = (new ARDC())->toPartnerPortalResult(['hits' => [], 'total' => 0], 'heart & lung')->toArray();

        $this->assertSame(
            'https://researchdata.edu.au/health/search?filter-type=all-fields&q=heart%20%26%20lung',
            $portal['search_url']
        );
    }
}
