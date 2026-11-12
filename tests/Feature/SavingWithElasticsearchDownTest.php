<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Dataset;
use App\Models\Dur;
use App\Models\Publication;
use App\Models\Team;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

class SavingWithElasticsearchDownTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

        config(['database.connections.elasticsearch.host' => 'http://elasticsearch-down.test']);
        Http::fake([
            'elasticsearch-down.test/*' => Http::response(['ok' => false, 'message' => 'Unknown resource.'], 404),
        ]);
    }

    public function test_an_active_collection_can_be_created(): void
    {
        $collection = Collection::factory()->create(['status' => Collection::STATUS_ACTIVE]);

        $this->assertDatabaseHas('collections', ['id' => $collection->id, 'status' => Collection::STATUS_ACTIVE]);
    }

    public function test_an_active_data_use_can_be_created(): void
    {
        $team = Team::factory()->create();
        $dur = Dur::create([
            'user_id' => $this->currentUser['id'],
            'team_id' => $team->id,
            'project_title' => 'Active data use',
            'status' => Dur::STATUS_ACTIVE,
        ]);

        $this->assertDatabaseHas('dur', ['id' => $dur->id, 'status' => Dur::STATUS_ACTIVE]);
    }

    public function test_an_active_publication_can_be_created(): void
    {
        $publication = Publication::factory()->create(['status' => Publication::STATUS_ACTIVE]);

        $this->assertDatabaseHas('publications', ['id' => $publication->id, 'status' => Publication::STATUS_ACTIVE]);
    }

    public function test_a_team_with_an_active_dataset_can_be_renamed(): void
    {
        $team = Team::factory()->create();
        Dataset::withoutEvents(fn () => Dataset::factory()->create([
            'team_id' => $team->id,
            'status' => Dataset::STATUS_ACTIVE,
        ]));

        $team->update(['name' => 'Renamed Team']);

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Renamed Team']);
    }
}
