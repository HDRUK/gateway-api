<?php

namespace Tests\Unit\Gwdm;

use App\Models\Team;
use App\Services\Gwdm\Gwdm1xHandler;
use App\Services\Gwdm\Gwdm2xHandler;
use Tests\TestCase;
use Tests\Traits\MockExternalApis;

/**
 * Covers the raw + normalise + team-fallback behaviour of resolvePublisher()
 * on the GWDM handlers.
 *
 *   - RAW: an incoming publisher naming a *different* organisation is honoured
 *     (enables publish-on-behalf), not overwritten with the requesting team.
 *   - NORMALISE: the gateway identifier is stored as a pid, whether the payload
 *     supplied a primary key or a pid.
 *   - FALLBACK: an absent or unresolvable publisher falls back to the requesting
 *     team (by pid).
 */
class PublisherResolutionTest extends TestCase
{
    use MockExternalApis {
        setUp as commonSetUp;
    }

    protected function setUp(): void
    {
        $this->commonSetUp();
    }

    private function handler2x(): Gwdm2xHandler
    {
        return new Gwdm2xHandler('2.0');
    }

    private function handler1x(): Gwdm1xHandler
    {
        return new Gwdm1xHandler('1.0');
    }

    public function test_2x_preserves_incoming_pid_and_extra_keys(): void
    {
        $team = Team::factory()->create(['pid' => 'pid-'.uniqid()]);

        $result = $this->handler2x()->resolvePublisher(
            ['gatewayId' => $team->pid, 'name' => 'Custom Publisher', 'rorId' => '012345678'],
            $team,
        );

        $this->assertSame($team->pid, $result['gatewayId']);
        $this->assertSame('Custom Publisher', $result['name']);
        $this->assertSame('012345678', $result['rorId']);
    }

    public function test_2x_normalises_numeric_id_to_pid(): void
    {
        $team = Team::factory()->create(['pid' => 'pid-'.uniqid()]);

        $result = $this->handler2x()->resolvePublisher(
            ['gatewayId' => (string) $team->id, 'name' => 'Org'],
            $team,
        );

        $this->assertSame($team->pid, $result['gatewayId']);
        $this->assertSame('Org', $result['name']);
    }

    public function test_2x_honours_a_different_publishing_team_normalised_to_pid(): void
    {
        // Requesting team A submits metadata whose publisher is team B.
        $teamA = Team::factory()->create(['pid' => 'pid-'.uniqid()]);
        $teamB = Team::factory()->create(['pid' => 'pid-'.uniqid()]);

        $result = $this->handler2x()->resolvePublisher(
            ['gatewayId' => (string) $teamB->id],
            $teamA,
        );

        // Raw data honoured: publisher is team B, normalised to team B's pid.
        $this->assertSame($teamB->pid, $result['gatewayId']);
        $this->assertSame($teamB->name, $result['name']);
    }

    public function test_2x_falls_back_to_requesting_team_when_publisher_absent(): void
    {
        $team = Team::factory()->create(['pid' => 'pid-'.uniqid()]);

        $result = $this->handler2x()->resolvePublisher([], $team);

        $this->assertSame(['gatewayId' => $team->pid, 'name' => $team->name], $result);
    }

    public function test_2x_falls_back_when_gateway_id_unresolvable(): void
    {
        $team = Team::factory()->create(['pid' => 'pid-'.uniqid()]);

        $result = $this->handler2x()->resolvePublisher(
            ['gatewayId' => '99999999', 'name' => 'Ghost Org'],
            $team,
        );

        $this->assertSame(['gatewayId' => $team->pid, 'name' => $team->name], $result);
    }

    public function test_1x_normalises_publisher_id_to_pid(): void
    {
        $team = Team::factory()->create(['pid' => 'pid-'.uniqid()]);

        $result = $this->handler1x()->resolvePublisher(
            ['publisherId' => (string) $team->id],
            $team,
        );

        $this->assertSame($team->pid, $result['publisherId']);
        $this->assertSame($team->name, $result['publisherName']);
    }

    public function test_1x_falls_back_to_requesting_team_when_absent(): void
    {
        $team = Team::factory()->create(['pid' => 'pid-'.uniqid()]);

        $result = $this->handler1x()->resolvePublisher([], $team);

        $this->assertSame(['publisherId' => $team->pid, 'publisherName' => $team->name], $result);
    }
}
