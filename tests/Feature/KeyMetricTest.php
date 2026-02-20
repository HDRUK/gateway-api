<?php

namespace Tests\Feature;

use Config;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class KeyMetricTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected $header = [];

    public function setUp(): void
    {
        $this->commonSetUp();
    }

    /**
     * List all Aliases
     *
     * @return void
     */
    public function test_the_application_can_list_key_metrics()
    {
        $response = $this->get('api/v2/metrics', $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'))
            ->assertJsonStructure([
                'message',
                'data',
            ]);
    }
}
