<?php

namespace Tests\Unit\Services\Experiments;

use App\Models\Experiment;
use App\Services\Experiments\ExperimentTargeting;
use PHPUnit\Framework\TestCase;

class ExperimentTargetingTest extends TestCase
{
    private function experiment(array $targeting): Experiment
    {
        $experiment = new Experiment(['targeting' => array_merge(['paths' => ['/en/search']], $targeting)]);
        $experiment->id = 7;

        return $experiment;
    }

    private function visitor(?int $userId = null, ?string $subject = null): array
    {
        return ['userId' => $userId, 'subject' => $subject ?? ($userId ? 'user:' . $userId : null)];
    }

    public function test_all_audience_includes_anonymous_visitors(): void
    {
        $this->assertTrue(ExperimentTargeting::isShownTo($this->experiment(['audience' => 'all']), $this->visitor()));
    }

    public function test_logged_in_audience_excludes_anonymous_visitors(): void
    {
        $experiment = $this->experiment(['audience' => 'logged_in']);

        $this->assertFalse(ExperimentTargeting::isShownTo($experiment, $this->visitor()));
        $this->assertTrue(ExperimentTargeting::isShownTo($experiment, $this->visitor(1)));
    }

    public function test_logged_out_audience_excludes_signed_in_users(): void
    {
        $experiment = $this->experiment(['audience' => 'logged_out']);

        $this->assertTrue(ExperimentTargeting::isShownTo($experiment, $this->visitor()));
        $this->assertFalse(ExperimentTargeting::isShownTo($experiment, $this->visitor(1)));
    }

    public function test_roles_is_no_longer_an_audience(): void
    {
        $experiment = $this->experiment(['audience' => 'roles', 'roles' => ['custodian.team.admin']]);

        $this->assertFalse(ExperimentTargeting::isShownTo($experiment, $this->visitor(1)));
    }

    public function test_user_ids_audience_needs_a_listed_user(): void
    {
        $experiment = $this->experiment(['audience' => 'user_ids', 'user_ids' => [2]]);

        $this->assertFalse(ExperimentTargeting::isShownTo($experiment, $this->visitor(1)));
        $this->assertTrue(ExperimentTargeting::isShownTo($experiment, $this->visitor(2)));
    }

    public function test_an_unknown_audience_is_shown_to_nobody(): void
    {
        $this->assertFalse(ExperimentTargeting::isShownTo($this->experiment(['audience' => 'everyone']), $this->visitor(1)));
    }

    public function test_a_partial_rollout_needs_a_subject(): void
    {
        $experiment = $this->experiment(['audience' => 'all', 'percentage' => 99]);

        $this->assertFalse(ExperimentTargeting::isShownTo($experiment, $this->visitor()));
    }

    public function test_a_partial_rollout_buckets_the_subject(): void
    {
        $experiment = $this->experiment(['audience' => 'all', 'percentage' => 50]);
        $subject = 'web: session-1';

        $this->assertSame(
            crc32('7:' . $subject) % 100 < 50,
            ExperimentTargeting::isShownTo($experiment, $this->visitor(null, $subject))
        );
    }
}
