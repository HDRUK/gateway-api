<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Dur;
use App\Models\DurOutput;
use App\Models\Team;

class DurOutputTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Dur::flushEventListeners();
    }

    public function test_outputs_relation_returns_related_dur_outputs(): void
    {
        $team = Team::factory()->create();
        $dur = Dur::create([
            'project_title' => 'Project with outputs',
            'team_id'       => $team->id,
            'status'        => Dur::STATUS_DRAFT,
        ]);

        $output = DurOutput::create([
            'dur_id' => $dur->id,
            'type'   => 'Paper',
            'title'  => 'A research output',
            'status' => 'Published',
            'detail' => 'Some detail about the output',
            'url'    => 'https://example.com/output',
        ]);

        $outputs = $dur->fresh()->outputs;

        $this->assertCount(1, $outputs);
        $this->assertTrue($outputs->first()->is($output));
        $this->assertEquals('A research output', $outputs->first()->title);
        $this->assertEquals('https://example.com/output', $outputs->first()->url);
    }

    public function test_outputs_relation_is_empty_when_dur_has_no_outputs(): void
    {
        $team = Team::factory()->create();
        $dur = Dur::create([
            'project_title' => 'Project without outputs',
            'team_id'       => $team->id,
            'status'        => Dur::STATUS_DRAFT,
        ]);

        $this->assertCount(0, $dur->fresh()->outputs);
    }

    public function test_soft_deleted_output_is_excluded_from_the_relation_but_not_the_database(): void
    {
        $team = Team::factory()->create();
        $dur = Dur::create([
            'project_title' => 'Project with a removed output',
            'team_id'       => $team->id,
            'status'        => Dur::STATUS_DRAFT,
        ]);

        $output = DurOutput::create([
            'dur_id' => $dur->id,
            'url'    => 'https://example.com/output',
        ]);

        $output->delete();

        $this->assertCount(0, $dur->fresh()->outputs);
        $this->assertSoftDeleted('dur_outputs', ['id' => $output->id]);
    }
}
