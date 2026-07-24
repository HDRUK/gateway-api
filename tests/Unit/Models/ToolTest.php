<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Tool;

class ToolTest extends TestCase
{
    public function test_scopeIndexEligible_matches_shouldBeSearchable(): void
    {
        $active = Tool::factory()->create(['status' => Tool::STATUS_ACTIVE]);
        $archived = Tool::factory()->create(['status' => Tool::STATUS_ARCHIVED]);

        $eligibleIds = Tool::indexEligible()->pluck('id');

        $this->assertTrue($eligibleIds->contains($active->id));
        $this->assertFalse($eligibleIds->contains($archived->id));
    }
}
