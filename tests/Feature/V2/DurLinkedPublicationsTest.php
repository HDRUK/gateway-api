<?php

namespace Tests\Feature\V2;

use Config;
use Tests\TestCase;
use App\Models\Dur;
use App\Models\Publication;
use Tests\Traits\Authorization;
use Tests\Traits\Helpers;
use Tests\Traits\MockExternalApis;

class DurLinkedPublicationsTest extends TestCase
{
    use Authorization;
    use Helpers;
    use MockExternalApis {
        setUp as commonSetUp;
    }

    public function setUp(): void
    {
        $this->commonSetUp();

        Dur::flushEventListeners();
    }

    public function test_getting_a_data_use_returns_its_linked_active_publications(): void
    {
        $activePublication = Publication::factory()->create(['status' => Publication::STATUS_ACTIVE]);
        $draftPublication = Publication::factory()->create(['status' => Publication::STATUS_DRAFT]);
        $archivedPublication = Publication::factory()->create(['status' => Publication::STATUS_ARCHIVED]);

        $teamId = $this->createTeam([], [$this->createNotification()]);

        $durId = $this->json(
            'POST',
            'api/v2/teams/' . $teamId . '/dur',
            [
                'project_title' => 'Data use with linked publications',
                'publications' => [
                    ['id' => $activePublication->id],
                    ['id' => $draftPublication->id],
                    ['id' => $archivedPublication->id],
                ],
                'organisation_sector' => 'academia',
                'status' => 'ACTIVE',
            ],
            $this->header,
        )->assertStatus(Config::get('statuscodes.STATUS_CREATED.code'))
            ->json('data');

        $response = $this->json('GET', '/api/v2/dur/' . $durId, [], $this->header);

        $response->assertStatus(Config::get('statuscodes.STATUS_OK.code'));
        $this->assertEqualsCanonicalizing(
            [$activePublication->id],
            array_column($response->json('data.publications'), 'id')
        );
    }
}
