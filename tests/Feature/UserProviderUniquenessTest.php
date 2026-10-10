<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\QueryException;
use Tests\TestCase;

class UserProviderUniquenessTest extends TestCase
{
    public function test_duplicate_provider_providerid_pair_is_rejected_at_the_database_level(): void
    {
        User::factory()->create(['provider' => 'azure', 'providerid' => 'shared-id']);

        $this->expectException(QueryException::class);

        User::factory()->create(['provider' => 'azure', 'providerid' => 'shared-id']);
    }

    public function test_two_different_providers_may_share_the_same_providerid(): void
    {
        User::factory()->create(['provider' => 'azure', 'providerid' => 'shared-id']);
        $other = User::factory()->create(['provider' => 'google', 'providerid' => 'shared-id']);

        $this->assertSame('google', $other->fresh()->provider);
    }

    public function test_multiple_null_providerid_rows_are_not_treated_as_duplicates(): void
    {
        User::factory()->create(['provider' => null, 'providerid' => null]);
        $second = User::factory()->create(['provider' => null, 'providerid' => null]);

        $this->assertNull($second->fresh()->providerid);
    }
}
