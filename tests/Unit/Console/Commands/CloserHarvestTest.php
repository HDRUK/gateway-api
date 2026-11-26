<?php

namespace Tests\Unit\Console\Commands;

use App\Modules\Closer\Harvest\CloserWaveHarvester;
use App\Modules\Closer\Jobs\HarvestCloserWave;
use App\Modules\Closer\Models\CloserWave;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class CloserHarvestTest extends TestCase
{
    public function test_it_harvests_the_wave_straight_away_and_reports_the_counts(): void
    {
        $wave = new CloserWave([
            'agency' => 'uk.example', 'identifier' => 'abc-123', 'version' => 57,
            'status' => CloserWave::STATUS_COMPLETE, 'dataset_count' => 18, 'variable_count' => 8329,
        ]);
        $harvester = Mockery::mock(CloserWaveHarvester::class);
        $harvester->shouldReceive('harvest')->once()->with('uk.example', 'abc-123', true)->andReturn($wave);
        $this->app->instance(CloserWaveHarvester::class, $harvester);

        $this->artisan('closer:harvest', ['identifier' => 'urn:ddi:uk.example:abc-123:57', '--force' => true])
            ->expectsOutputToContain('complete: version 57, 18 datasets, 8329 variables')
            ->assertSuccessful();
    }

    public function test_queue_option_dispatches_the_job_instead(): void
    {
        $this->artisan('closer:harvest', ['identifier' => 'uk.example/abc-123', '--queue' => true])->assertSuccessful();

        Queue::assertPushed(HarvestCloserWave::class, fn ($job) => $job->agency === 'uk.example' && $job->identifier === 'abc-123');
    }
}
