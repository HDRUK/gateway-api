<?php

namespace Tests\Unit;

use Auditor;

use App\Models\User;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Facade;

class AuditorTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_it_can_audit(): void
    {
        $user = User::all()->random();
        $description = 'testing auditor description';
        $function = 'auditor_facade_test';

        Auditor::log($user, $description, $function);

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'description' => $description,
            'function' => $function,
        ]);
    }

}