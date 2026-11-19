<?php

namespace Tests\Unit\Services\Gwdm;

use App\Services\Gwdm\SnapshotRule;
use PHPUnit\Framework\TestCase;

class SnapshotRuleTest extends TestCase
{
    public function test_the_first_version_is_a_full_copy(): void
    {
        $this->assertTrue(SnapshotRule::shouldSnapshot(1, null, '2.0'));
    }

    public function test_every_tenth_version_is_a_full_copy(): void
    {
        $this->assertTrue(SnapshotRule::shouldSnapshot(10, '2.0', '2.0'));
        $this->assertTrue(SnapshotRule::shouldSnapshot(20, '2.0', '2.0'));
    }

    public function test_a_gwdm_version_change_is_a_full_copy(): void
    {
        $this->assertTrue(SnapshotRule::shouldSnapshot(4, '2.0', '2.1'));
    }

    public function test_an_ordinary_version_is_a_diff(): void
    {
        $this->assertFalse(SnapshotRule::shouldSnapshot(2, '2.0', '2.0'));
        $this->assertFalse(SnapshotRule::shouldSnapshot(11, '2.0', '2.0'));
    }
}
