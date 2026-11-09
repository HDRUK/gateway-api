<?php

namespace Tests\Unit\Console\Commands;

use App\Exceptions\TypesenseReindexException;
use App\Jobs\ReindexTypesenseEntity;
use App\Models\Tool;
use App\Services\Search\CollectionReindexer;
use Auditor;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class TypesenseReindexTest extends TestCase
{
    private MockInterface $reindexer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reindexer = Mockery::mock(CollectionReindexer::class);
        $this->app->instance(CollectionReindexer::class, $this->reindexer);
    }

    public function test_it_rebuilds_behind_the_alias_by_default(): void
    {
        $this->reindexer->shouldReceive('rebuild')->once()
            ->with(Tool::class, Mockery::type('callable'))
            ->andReturn(['collection' => 'tools_1', 'documents' => 3]);
        $this->reindexer->shouldNotReceive('refresh');

        $this->artisan('typesense:reindex', ['entity' => 'tools'])
            ->expectsOutputToContain("'tools' now serves 'tools_1' (3 documents)")
            ->assertSuccessful();
    }

    public function test_upsert_refreshes_the_live_collection_in_place(): void
    {
        $this->reindexer->shouldReceive('refresh')->once()
            ->with(Tool::class, Mockery::type('callable'))
            ->andReturn(['collection' => 'tools', 'documents' => 3, 'pruned' => 1]);
        $this->reindexer->shouldNotReceive('rebuild');

        $this->artisan('typesense:reindex', ['entity' => 'tools', '--upsert' => true])
            ->expectsOutputToContain('3 documents upserted, 1 stale removed')
            ->assertSuccessful();
    }

    public function test_a_failed_reindex_exits_non_zero_with_the_reason(): void
    {
        $this->reindexer->shouldReceive('rebuild')->andThrow(new TypesenseReindexException('bad field'));

        $this->artisan('typesense:reindex', ['entity' => 'tools'])
            ->expectsOutputToContain('bad field')
            ->assertFailed();
    }

    public function test_upsert_does_not_apply_to_variables(): void
    {
        $this->artisan('typesense:reindex', ['entity' => 'variables', '--upsert' => true])
            ->expectsOutputToContain('--upsert does not apply to variables')
            ->assertFailed();
    }

    public function test_the_queued_job_uses_the_requested_mode(): void
    {
        $this->reindexer->shouldReceive('rebuild')->once()->with(Tool::class)->andReturn([]);
        (new ReindexTypesenseEntity('tools'))->handle($this->reindexer);

        $this->reindexer->shouldReceive('refresh')->once()->with(Tool::class)->andReturn([]);
        (new ReindexTypesenseEntity('tools', upsert: true))->handle($this->reindexer);
    }

    public function test_the_job_fails_without_retrying_when_the_reindex_is_refused(): void
    {
        // Rejected documents or 'already running': a retry can't succeed.
        Auditor::shouldReceive('log')->once();
        $refused = new TypesenseReindexException("A reindex of 'tools' is already running.");
        $this->reindexer->shouldReceive('rebuild')->andThrow($refused);

        $job = (new ReindexTypesenseEntity('tools'))->withFakeQueueInteractions();
        $job->handle($this->reindexer);

        $job->assertFailedWith($refused);
    }

    public function test_the_job_still_retries_unexpected_errors(): void
    {
        Auditor::shouldReceive('log')->once();
        $this->reindexer->shouldReceive('rebuild')->andThrow(new \RuntimeException('Typesense unreachable'));

        $this->expectException(\RuntimeException::class);

        (new ReindexTypesenseEntity('tools'))->withFakeQueueInteractions()->handle($this->reindexer);
    }
}
